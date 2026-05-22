<?php

namespace Tests\Feature\Api\V1\Posts;

use App\Models\User;
use Database\Seeders\SportsSeeder;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class MatchResultShowTest extends TestCase
{
    use LazilyRefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(SportsSeeder::class);
    }

    /**
     * Crée un match (deux équipes + événement + résultat) et renvoie l'id du
     * `match_results`.
     *
     * @param  array<string, mixed>  $resultOverrides
     */
    private function createMatchResult(int $authorId, array $resultOverrides = []): int
    {
        $footballId = (int) DB::table('sports')->where('slug', 'football')->value('id');

        $homeTeamId = DB::table('teams')->insertGetId([
            'creator_id' => $authorId,
            'sport_id' => $footballId,
            'name' => 'Home '.uniqid(),
            'slug' => 'home-'.uniqid(),
            'competition_type' => 'leisure',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $awayTeamId = DB::table('teams')->insertGetId([
            'creator_id' => $authorId,
            'sport_id' => $footballId,
            'name' => 'Away '.uniqid(),
            'slug' => 'away-'.uniqid(),
            'competition_type' => 'leisure',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $matchEventId = DB::table('match_events')->insertGetId([
            'home_team_id' => $homeTeamId,
            'away_team_id' => $awayTeamId,
            'scheduled_at' => now()->copy()->addDay(),
            'venue' => 'Stade Test',
            'status' => 'finished',
            'notes' => null,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        return DB::table('match_results')->insertGetId(array_merge([
            'match_event_id' => $matchEventId,
            'home_score' => 3,
            'away_score' => 1,
            'total_comments' => 0,
            'total_likes' => 0,
            'status' => 'validated',
            'submitted_by_user_id' => $authorId,
            'submitted_at' => now()->copy()->subDay(),
            'responded_by_user_id' => null,
            'responded_at' => null,
            'validated_at' => now()->copy()->subHours(10),
            'refusal_reason' => null,
            'created_at' => now(),
            'updated_at' => now(),
        ], $resultOverrides));
    }

    public function test_viewer_can_fetch_a_validated_match_result(): void
    {
        $viewer = User::factory()->create();
        $author = User::factory()->create();
        $token = $viewer->createToken('auth')->plainTextToken;

        $matchResultId = $this->createMatchResult($author->id);

        $this->getJson('/api/v1/auth/posts/feed/'.$matchResultId, [
            'Authorization' => 'Bearer '.$token,
        ])->assertOk()
            ->assertJsonPath('data.id', $matchResultId)
            ->assertJsonPath('data.home_score', 3)
            ->assertJsonPath('data.away_score', 1)
            ->assertJsonPath('data.viewer_has_liked', false)
            ->assertJsonStructure([
                'data' => [
                    'id',
                    'match_event_id',
                    'status',
                    'total_comments',
                    'total_likes',
                    'home_team_name',
                    'away_team_name',
                ],
            ]);
    }

    public function test_viewer_has_liked_is_true_when_like_row_exists(): void
    {
        $viewer = User::factory()->create();
        $author = User::factory()->create();
        $token = $viewer->createToken('auth')->plainTextToken;

        $matchResultId = $this->createMatchResult($author->id);

        DB::table('post_likes')->insert([
            'users_id' => $viewer->id,
            'publication_id' => $matchResultId,
            'publication_type' => 'automatic',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $this->getJson('/api/v1/auth/posts/feed/'.$matchResultId, [
            'Authorization' => 'Bearer '.$token,
        ])->assertOk()
            ->assertJsonPath('data.viewer_has_liked', true);
    }

    public function test_unknown_match_result_returns_404(): void
    {
        $viewer = User::factory()->create();
        $token = $viewer->createToken('auth')->plainTextToken;

        $this->getJson('/api/v1/auth/posts/feed/999999', [
            'Authorization' => 'Bearer '.$token,
        ])->assertNotFound();
    }

    public function test_non_validated_match_result_returns_404(): void
    {
        $viewer = User::factory()->create();
        $author = User::factory()->create();
        $token = $viewer->createToken('auth')->plainTextToken;

        $matchResultId = $this->createMatchResult($author->id, [
            'status' => 'pending_validation',
            'validated_at' => null,
        ]);

        $this->getJson('/api/v1/auth/posts/feed/'.$matchResultId, [
            'Authorization' => 'Bearer '.$token,
        ])->assertNotFound();
    }

    public function test_unauthenticated_request_is_rejected(): void
    {
        $author = User::factory()->create();
        $matchResultId = $this->createMatchResult($author->id);

        $this->getJson('/api/v1/auth/posts/feed/'.$matchResultId)
            ->assertUnauthorized();
    }
}
