<?php

namespace Tests\Feature\Api\V1\Posts;

use App\Jobs\MatchResultLikeNotificationJob;
use App\Jobs\ToggleMatchResultLike;
use App\Models\User;
use App\Notifications\MatchResultLikeNotification;
use App\Services\Post\MatchResultLikeService;
use Database\Seeders\SportsSeeder;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Queue\SyncQueue;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class MatchResultLikeApiTest extends TestCase
{
    use LazilyRefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(SportsSeeder::class);
    }

    private function sportIdBySlug(string $slug): int
    {
        $id = DB::table('sports')->where('slug', $slug)->value('id');
        $this->assertNotNull($id);

        return (int) $id;
    }

    /**
     * @return array{home_team_id: int, away_team_id: int, match_event_id: int, home_captain: User, away_captain: User}
     */
    private function createScheduledMatch(): array
    {
        $homeCaptain = User::factory()->create();
        $awayCaptain = User::factory()->create();
        $sportId = $this->sportIdBySlug('football');

        $homeTeamId = DB::table('teams')->insertGetId([
            'creator_id' => $homeCaptain->id,
            'sport_id' => $sportId,
            'name' => 'MRL Home',
            'slug' => 'mrl-home-'.uniqid(),
            'competition_type' => 'leisure',
            'skill_level' => null,
            'description' => null,
            'hq_city' => null,
            'hq_latitude' => null,
            'hq_longitude' => null,
            'cover_image_url' => null,
            'logo_url' => null,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $awayTeamId = DB::table('teams')->insertGetId([
            'creator_id' => $awayCaptain->id,
            'sport_id' => $sportId,
            'name' => 'MRL Away',
            'slug' => 'mrl-away-'.uniqid(),
            'competition_type' => 'leisure',
            'skill_level' => null,
            'description' => null,
            'hq_city' => null,
            'hq_latitude' => null,
            'hq_longitude' => null,
            'cover_image_url' => null,
            'logo_url' => null,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::table('team_members')->insert([
            ['team_id' => $homeTeamId, 'user_id' => $homeCaptain->id, 'role' => 'captain', 'status' => 'active', 'created_at' => now(), 'updated_at' => now()],
            ['team_id' => $awayTeamId, 'user_id' => $awayCaptain->id, 'role' => 'captain', 'status' => 'active', 'created_at' => now(), 'updated_at' => now()],
        ]);

        $matchEventId = DB::table('match_events')->insertGetId([
            'home_team_id' => $homeTeamId,
            'away_team_id' => $awayTeamId,
            'scheduled_at' => now()->addDay()->toDateTimeString(),
            'venue' => null,
            'status' => 'scheduled',
            'notes' => null,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        return [
            'home_team_id' => $homeTeamId,
            'away_team_id' => $awayTeamId,
            'match_event_id' => $matchEventId,
            'home_captain' => $homeCaptain,
            'away_captain' => $awayCaptain,
        ];
    }

    private function insertMatchResult(int $matchEventId, int $submittedByUserId): int
    {
        $now = now();

        return (int) DB::table('match_results')->insertGetId([
            'match_event_id' => $matchEventId,
            'home_score' => 2,
            'away_score' => 1,
            'total_comments' => 0,
            'total_likes' => 0,
            'status' => 'score_pending_validation',
            'submitted_by_user_id' => $submittedByUserId,
            'submitted_at' => $now,
            'responded_by_user_id' => null,
            'responded_at' => null,
            'validated_at' => null,
            'refusal_reason' => null,
            'created_at' => $now,
            'updated_at' => $now,
        ]);
    }

    public function test_bus_dispatch_toggle_on_post_notifications_connection_persists_like(): void
    {
        $this->assertInstanceOf(
            SyncQueue::class,
            app('queue')->connection('post_notifications'),
        );

        $ctx = $this->createScheduledMatch();
        $resultId = $this->insertMatchResult($ctx['match_event_id'], $ctx['home_captain']->id);

        $toggle = new ToggleMatchResultLike($resultId, $ctx['away_captain']->id, 'regular', 'like');
        $toggle->onConnection('post_notifications');
        Bus::dispatch($toggle);

        $this->assertDatabaseCount('post_likes', 1);
    }

    public function test_bus_chain_toggle_then_notification_persists_like(): void
    {
        $ctx = $this->createScheduledMatch();
        $resultId = $this->insertMatchResult($ctx['match_event_id'], $ctx['home_captain']->id);

        Bus::chain([
            new ToggleMatchResultLike($resultId, $ctx['away_captain']->id, 'regular', 'like'),
            new MatchResultLikeNotificationJob(
                $resultId,
                $ctx['away_captain']->id,
                'regular',
                'like',
            ),
        ])->onConnection('post_notifications')->dispatch();

        $this->assertDatabaseCount('post_likes', 1);
    }

    public function test_dispatch_sync_toggle_match_result_like_job_persists_row(): void
    {
        $ctx = $this->createScheduledMatch();
        $resultId = $this->insertMatchResult($ctx['match_event_id'], $ctx['home_captain']->id);

        dispatch_sync(new ToggleMatchResultLike(
            $resultId,
            $ctx['away_captain']->id,
            'regular',
            'like',
        ));

        $this->assertDatabaseCount('post_likes', 1);
    }

    public function test_match_result_like_service_persists_row(): void
    {
        $ctx = $this->createScheduledMatch();
        $resultId = $this->insertMatchResult($ctx['match_event_id'], $ctx['home_captain']->id);

        app(MatchResultLikeService::class)->toggleLike(
            $resultId,
            $ctx['away_captain']->id,
            'regular',
            'like',
        );

        $this->assertDatabaseCount('post_likes', 1);
    }

    public function test_like_match_result_persists_like_and_notifies_other_team_members(): void
    {
        $ctx = $this->createScheduledMatch();
        $resultId = $this->insertMatchResult($ctx['match_event_id'], $ctx['home_captain']->id);

        $this->actingAs($ctx['away_captain'], 'sanctum')
            ->postJson('/api/v1/auth/posts/'.$resultId.'/likes', [
                'action' => 'like',
                'post_type' => 'regular',
            ])
            ->assertAccepted();

        $this->assertDatabaseHas('post_likes', [
            'users_id' => $ctx['away_captain']->id,
            'publication_id' => $resultId,
            'publication_type' => 'regular',
        ]);
        $this->assertDatabaseHas('match_results', [
            'id' => $resultId,
            'total_likes' => 1,
        ]);

        $this->assertDatabaseHas('notifications', [
            'notifiable_type' => User::class,
            'notifiable_id' => (string) $ctx['home_captain']->id,
            'type' => MatchResultLikeNotification::class,
        ]);
    }

    public function test_dislike_removes_like_and_counter(): void
    {
        $ctx = $this->createScheduledMatch();
        $resultId = $this->insertMatchResult($ctx['match_event_id'], $ctx['home_captain']->id);

        $liker = $ctx['away_captain'];

        $this->actingAs($liker, 'sanctum')
            ->postJson('/api/v1/auth/posts/'.$resultId.'/likes', [
                'action' => 'like',
                'post_type' => 'regular',
            ])
            ->assertAccepted();

        $this->actingAs($liker, 'sanctum')
            ->postJson('/api/v1/auth/posts/'.$resultId.'/likes', [
                'action' => 'dislike',
                'post_type' => 'regular',
            ])
            ->assertAccepted();

        $this->assertDatabaseMissing('post_likes', [
            'users_id' => $liker->id,
            'publication_id' => $resultId,
            'publication_type' => 'regular',
        ]);
        $this->assertDatabaseHas('match_results', [
            'id' => $resultId,
            'total_likes' => 0,
        ]);
    }

    public function test_submitter_liking_own_result_notifies_other_team_members(): void
    {
        $ctx = $this->createScheduledMatch();
        $resultId = $this->insertMatchResult($ctx['match_event_id'], $ctx['home_captain']->id);

        $this->actingAs($ctx['home_captain'], 'sanctum')
            ->postJson('/api/v1/auth/posts/'.$resultId.'/likes', [
                'action' => 'like',
                'post_type' => 'regular',
            ])
            ->assertAccepted();

        $this->assertDatabaseHas('notifications', [
            'notifiable_type' => User::class,
            'notifiable_id' => (string) $ctx['away_captain']->id,
            'type' => MatchResultLikeNotification::class,
        ]);
        $this->assertDatabaseCount('notifications', 1);
    }
}
