<?php

namespace Tests\Unit\Support;

use App\Models\User;
use App\Support\PostPublicationNotificationRecipients;
use Database\Seeders\SportsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class PostPublicationNotificationRecipientsTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(SportsSeeder::class);
    }

    public function test_regular_returns_post_author_excluding_actor(): void
    {
        $author = User::factory()->create();
        $actor = User::factory()->create();
        $postId = $this->insertRegularPost($author->id);

        $ids = PostPublicationNotificationRecipients::userIdsFor($postId, 'regular', $actor->id);

        $this->assertSame([$author->id], $ids);
    }

    public function test_regular_returns_empty_when_actor_is_author(): void
    {
        $author = User::factory()->create();
        $postId = $this->insertRegularPost($author->id);

        $ids = PostPublicationNotificationRecipients::userIdsFor($postId, 'regular', $author->id);

        $this->assertSame([], $ids);
    }

    public function test_automatic_returns_active_team_members_excluding_actor(): void
    {
        $homeCaptain = User::factory()->create();
        $awayCaptain = User::factory()->create();
        $matchResultId = $this->insertMatchResultWithTeams($homeCaptain, $awayCaptain);

        $ids = PostPublicationNotificationRecipients::userIdsFor(
            $matchResultId,
            'automatic',
            $awayCaptain->id,
        );

        $this->assertSame([$homeCaptain->id], $ids);
    }

    private function insertRegularPost(int $authorId): int
    {
        $now = now();

        return (int) DB::table('posts')->insertGetId([
            'user_id' => $authorId,
            'body' => 'Test post',
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

    private function insertMatchResultWithTeams(User $homeCaptain, User $awayCaptain): int
    {
        $sportId = (int) DB::table('sports')->where('slug', 'football')->value('id');
        $now = now();

        $homeTeamId = DB::table('teams')->insertGetId([
            'creator_id' => $homeCaptain->id,
            'sport_id' => $sportId,
            'name' => 'Home',
            'slug' => 'home-'.uniqid(),
            'competition_type' => 'leisure',
            'created_at' => $now,
            'updated_at' => $now,
        ]);

        $awayTeamId = DB::table('teams')->insertGetId([
            'creator_id' => $awayCaptain->id,
            'sport_id' => $sportId,
            'name' => 'Away',
            'slug' => 'away-'.uniqid(),
            'competition_type' => 'leisure',
            'created_at' => $now,
            'updated_at' => $now,
        ]);

        DB::table('team_members')->insert([
            ['team_id' => $homeTeamId, 'user_id' => $homeCaptain->id, 'role' => 'captain', 'status' => 'active', 'created_at' => $now, 'updated_at' => $now],
            ['team_id' => $awayTeamId, 'user_id' => $awayCaptain->id, 'role' => 'captain', 'status' => 'active', 'created_at' => $now, 'updated_at' => $now],
        ]);

        $matchEventId = DB::table('match_events')->insertGetId([
            'home_team_id' => $homeTeamId,
            'away_team_id' => $awayTeamId,
            'scheduled_at' => $now->copy()->addDay(),
            'status' => 'scheduled',
            'created_at' => $now,
            'updated_at' => $now,
        ]);

        return (int) DB::table('match_results')->insertGetId([
            'match_event_id' => $matchEventId,
            'home_score' => 1,
            'away_score' => 0,
            'total_comments' => 0,
            'total_likes' => 0,
            'status' => 'score_pending_validation',
            'submitted_by_user_id' => $homeCaptain->id,
            'submitted_at' => $now,
            'created_at' => $now,
            'updated_at' => $now,
        ]);
    }
}
