<?php

namespace Tests\Feature\Api\V1\Posts;

use App\Models\User;
use App\Notifications\Comments;
use Database\Seeders\SportsSeeder;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class PostCommentNotificationTest extends TestCase
{
    use LazilyRefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(SportsSeeder::class);
    }

    public function test_regular_post_comment_notifies_author_only(): void
    {
        $author = User::factory()->create();
        $commenter = User::factory()->create();
        $postId = $this->insertRegularPost($author->id);

        $this->actingAs($commenter, 'sanctum')
            ->postJson('/api/v1/auth/posts/'.$postId.'/comments', [
                'post_type' => 'regular',
                'commentaire' => 'Beau post',
            ])
            ->assertAccepted();

        $this->assertDatabaseHas('posts', [
            'id' => $postId,
            'total_comments' => 1,
        ]);
        $this->assertDatabaseHas('notifications', [
            'notifiable_type' => User::class,
            'notifiable_id' => (string) $author->id,
            'type' => Comments::class,
        ]);
        $this->assertDatabaseCount('notifications', 1);
    }

    public function test_regular_post_self_comment_does_not_notify(): void
    {
        $author = User::factory()->create();
        $postId = $this->insertRegularPost($author->id);

        $this->actingAs($author, 'sanctum')
            ->postJson('/api/v1/auth/posts/'.$postId.'/comments', [
                'post_type' => 'regular',
                'commentaire' => 'Mon propre commentaire',
            ])
            ->assertAccepted();

        $this->assertDatabaseCount('notifications', 0);
        $this->assertDatabaseHas('posts', [
            'id' => $postId,
            'total_comments' => 1,
        ]);
    }

    public function test_automatic_match_comment_notifies_other_team_member(): void
    {
        $ctx = $this->createScheduledMatch();
        $resultId = $this->insertMatchResult($ctx['match_event_id'], $ctx['home_captain']->id);

        $this->actingAs($ctx['away_captain'], 'sanctum')
            ->postJson('/api/v1/auth/posts/'.$resultId.'/comments', [
                'post_type' => 'automatic',
                'commentaire' => 'Commentaire match',
            ])
            ->assertAccepted();

        $this->assertDatabaseHas('notifications', [
            'notifiable_type' => User::class,
            'notifiable_id' => (string) $ctx['home_captain']->id,
            'type' => Comments::class,
        ]);
        $this->assertDatabaseCount('notifications', 1);
        $this->assertDatabaseHas('match_results', [
            'id' => $resultId,
            'total_comments' => 1,
        ]);
    }

    /**
     * @return array{home_team_id: int, away_team_id: int, match_event_id: int, home_captain: User, away_captain: User}
     */
    private function createScheduledMatch(): array
    {
        $homeCaptain = User::factory()->create();
        $awayCaptain = User::factory()->create();
        $sportId = (int) DB::table('sports')->where('slug', 'football')->value('id');

        $homeTeamId = DB::table('teams')->insertGetId([
            'creator_id' => $homeCaptain->id,
            'sport_id' => $sportId,
            'name' => 'PCN Home',
            'slug' => 'pcn-home-'.uniqid(),
            'competition_type' => 'leisure',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $awayTeamId = DB::table('teams')->insertGetId([
            'creator_id' => $awayCaptain->id,
            'sport_id' => $sportId,
            'name' => 'PCN Away',
            'slug' => 'pcn-away-'.uniqid(),
            'competition_type' => 'leisure',
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
            'scheduled_at' => now()->addDay(),
            'status' => 'scheduled',
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
            'created_at' => $now,
            'updated_at' => $now,
        ]);
    }

    private function insertRegularPost(int $authorId): int
    {
        $now = now();

        return (int) DB::table('posts')->insertGetId([
            'user_id' => $authorId,
            'body' => 'Publication test',
            'visibility' => 'public',
            'status' => 'published',
            'media_count' => 0,
            'total_likes' => 0,
            'total_comments' => 0,
            'total_shares' => 0,
            'published_at' => $now,
            'created_at' => $now,
            'updated_at' => $now,
        ]);
    }
}
