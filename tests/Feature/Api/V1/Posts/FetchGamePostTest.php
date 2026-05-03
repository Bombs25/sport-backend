<?php

namespace Tests\Feature\Api\V1\Posts;

use App\Models\User;
use App\Services\Post\FetchPostService;
use Database\Seeders\SportsSeeder;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class FetchGamePostTest extends TestCase
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
     * @return array{match_event_id: int, match_result_id: int, author: User}
     */
    private function createMatchResultSubmittedBy(User $author, ?string $validatedAt = null, ?string $submittedAt = null): array
    {
        $sportId = $this->sportIdBySlug('football');
        $now = now();

        $homeTeamId = DB::table('teams')->insertGetId([
            'creator_id' => $author->id,
            'sport_id' => $sportId,
            'name' => 'Home '.uniqid(),
            'slug' => 'home-'.uniqid(),
            'competition_type' => 'leisure',
            'skill_level' => null,
            'description' => null,
            'hq_city' => null,
            'hq_latitude' => null,
            'hq_longitude' => null,
            'cover_image_url' => null,
            'logo_url' => null,
            'created_at' => $now,
            'updated_at' => $now,
        ]);

        $awayTeamId = DB::table('teams')->insertGetId([
            'creator_id' => $author->id,
            'sport_id' => $sportId,
            'name' => 'Away '.uniqid(),
            'slug' => 'away-'.uniqid(),
            'competition_type' => 'leisure',
            'skill_level' => null,
            'description' => null,
            'hq_city' => null,
            'hq_latitude' => null,
            'hq_longitude' => null,
            'cover_image_url' => null,
            'logo_url' => null,
            'created_at' => $now,
            'updated_at' => $now,
        ]);

        DB::table('team_members')->insert([
            ['team_id' => $homeTeamId, 'user_id' => $author->id, 'role' => 'captain', 'status' => 'active', 'created_at' => $now, 'updated_at' => $now],
        ]);

        $matchEventId = DB::table('match_events')->insertGetId([
            'home_team_id' => $homeTeamId,
            'away_team_id' => $awayTeamId,
            'scheduled_at' => $now->copy()->addDay()->toDateTimeString(),
            'venue' => 'Stade Test',
            'status' => 'scheduled',
            'notes' => null,
            'created_at' => $now,
            'updated_at' => $now,
        ]);

        $submitted = $submittedAt ?? $now->toDateTimeString();
        $matchResultId = (int) DB::table('match_results')->insertGetId([
            'match_event_id' => $matchEventId,
            'home_score' => 1,
            'away_score' => 0,
            'total_comments' => 0,
            'total_likes' => 0,
            'status' => 'validated',
            'submitted_by_user_id' => $author->id,
            'submitted_at' => $submitted,
            'responded_by_user_id' => null,
            'responded_at' => null,
            'validated_at' => $validatedAt ?? $now->toDateTimeString(),
            'refusal_reason' => null,
            'created_at' => $now,
            'updated_at' => $now,
        ]);

        return [
            'match_event_id' => $matchEventId,
            'match_result_id' => $matchResultId,
            'author' => $author,
        ];
    }

    public function test_returns_results_from_followed_users_only(): void
    {
        Cache::flush();
        $viewer = User::factory()->create();
        $followed = User::factory()->create();
        $stranger = User::factory()->create();

        DB::table('follows')->insert([
            'follower_id' => $viewer->id,
            'following_id' => $followed->id,
            'status' => 'accepted',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $fromFollowed = $this->createMatchResultSubmittedBy($followed);
        $fromStranger = $this->createMatchResultSubmittedBy($stranger);

        $ids = collect(
            $this->actingAs($viewer, 'sanctum')
                ->getJson('/api/v1/auth/posts/feed')
                ->assertOk()
                ->json('data')
        )->pluck('id')->all();

        $this->assertContains($fromFollowed['match_result_id'], $ids);
        $this->assertNotContains($fromStranger['match_result_id'], $ids);
    }

    public function test_includes_viewer_has_liked_from_post_likes(): void
    {
        Cache::flush();
        $viewer = User::factory()->create();
        $followed = User::factory()->create();

        DB::table('follows')->insert([
            'follower_id' => $viewer->id,
            'following_id' => $followed->id,
            'status' => 'accepted',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $liked = $this->createMatchResultSubmittedBy($followed);
        $notLiked = $this->createMatchResultSubmittedBy($followed);

        DB::table('post_likes')->insert([
            'users_id' => $viewer->id,
            'publication_id' => $liked['match_result_id'],
            'publication_type' => 'regular',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $data = $this->actingAs($viewer, 'sanctum')
            ->getJson('/api/v1/auth/posts/feed')
            ->assertOk()
            ->json('data');

        $byId = collect($data)->keyBy('id');
        $this->assertTrue($byId[$liked['match_result_id']]['viewer_has_liked']);
        $this->assertFalse($byId[$notLiked['match_result_id']]['viewer_has_liked']);
    }

    public function test_excludes_client_viewed_ids(): void
    {
        Cache::flush();
        $viewer = User::factory()->create();
        $followed = User::factory()->create();
        DB::table('follows')->insert([
            'follower_id' => $viewer->id,
            'following_id' => $followed->id,
            'status' => 'accepted',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $a = $this->createMatchResultSubmittedBy($followed);
        $b = $this->createMatchResultSubmittedBy($followed);

        $payload = json_encode([$a['match_result_id']], JSON_THROW_ON_ERROR);
        $query = 'viewed_post_ids='.rawurlencode($payload);

        $response = $this->actingAs($viewer, 'sanctum')
            ->getJson('/api/v1/auth/posts/feed?'.$query)
            ->assertOk();

        $ids = collect($response->json('data'))->pluck('id')->all();
        $this->assertNotContains($a['match_result_id'], $ids);
        $this->assertContains($b['match_result_id'], $ids);
    }

    public function test_rejects_viewed_post_ids_when_sent_as_query_array_brackets(): void
    {
        Cache::flush();
        $viewer = User::factory()->create();
        $followed = User::factory()->create();
        DB::table('follows')->insert([
            'follower_id' => $viewer->id,
            'following_id' => $followed->id,
            'status' => 'accepted',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $a = $this->createMatchResultSubmittedBy($followed);
        $b = $this->createMatchResultSubmittedBy($followed);

        $url = '/api/v1/auth/posts/feed?viewed_post_ids[]='.$a['match_result_id']
            .'&viewed_post_ids[]='.$b['match_result_id'];

        $this->actingAs($viewer, 'sanctum')
            ->getJson($url)
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['viewed_post_ids']);
    }

    public function test_rejects_invalid_json_for_viewed_post_ids(): void
    {
        Cache::flush();
        $viewer = User::factory()->create();

        $this->actingAs($viewer, 'sanctum')
            ->getJson('/api/v1/auth/posts/feed?viewed_post_ids='.rawurlencode('not-json'))
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['viewed_post_ids']);
    }

    public function test_falls_back_to_user_post_views_when_client_sends_no_viewed_list(): void
    {
        Cache::flush();
        $viewer = User::factory()->create();
        $followed = User::factory()->create();
        DB::table('follows')->insert([
            'follower_id' => $viewer->id,
            'following_id' => $followed->id,
            'status' => 'accepted',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $a = $this->createMatchResultSubmittedBy($followed);
        $b = $this->createMatchResultSubmittedBy($followed);

        DB::table('user_post_views')->insert([
            'publication_id' => $a['match_result_id'],
            'publication_type' => FetchPostService::PUBLICATION_TYPE_MATCH_RESULT,
            'user_id' => $viewer->id,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $ids = collect(
            $this->actingAs($viewer, 'sanctum')
                ->getJson('/api/v1/auth/posts/feed')
                ->json('data')
        )->pluck('id')->all();

        $this->assertNotContains($a['match_result_id'], $ids);
        $this->assertContains($b['match_result_id'], $ids);
    }

    public function test_orders_by_publication_date_newest_first(): void
    {
        Cache::flush();
        $viewer = User::factory()->create();
        $followed = User::factory()->create();
        DB::table('follows')->insert([
            'follower_id' => $viewer->id,
            'following_id' => $followed->id,
            'status' => 'accepted',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $older = $this->createMatchResultSubmittedBy($followed, '2020-01-01 12:00:00', '2020-01-01 10:00:00');
        $newer = $this->createMatchResultSubmittedBy($followed, '2025-06-01 12:00:00', '2025-06-01 10:00:00');

        $ids = collect(
            $this->actingAs($viewer, 'sanctum')
                ->getJson('/api/v1/auth/posts/feed')
                ->json('data')
        )->pluck('id')->all();

        $this->assertSame([$newer['match_result_id'], $older['match_result_id']], array_slice($ids, 0, 2));
    }
}
