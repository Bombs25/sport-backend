<?php

namespace Tests\Feature\Api\V1\Posts;

use App\Models\User;
use App\Services\Post\FetchPostService;
use App\Support\UserProfileLocation;
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
     * Les ids JSON peuvent arriver en string ; on normalise pour des assertions stables.
     *
     * @param  list<array<string, mixed>>|null  $data
     * @return list<int>
     */
    private function feedResultIds(?array $data): array
    {
        if ($data === null || $data === []) {
            return [];
        }

        return collect($data)
            ->pluck('id')
            ->map(static fn (mixed $id): int => (int) $id)
            ->all();
    }

    private function newViewer(): User
    {
        $user = User::factory()->createOne();
        $this->assertInstanceOf(User::class, $user);

        return $user;
    }

    private function insertUserProfileWithLocation(User $user, float $lat, float $lon): void
    {
        $now = now();
        DB::table('user_profiles')->insert(array_merge([
            'user_id' => $user->id,
            'display_name' => 'DN '.$user->id,
            'handle' => 'h'.bin2hex(random_bytes(8)),
            'bio' => null,
            'avatar_url' => null,
            'is_private' => false,
            'city' => null,
            'created_at' => $now,
            'updated_at' => $now,
        ], UserProfileLocation::columnsFromLatLng($lat, $lon)));
    }

    private function insertUserSport(User $user, int $sportId): void
    {
        $now = now();
        DB::table('user_sports')->insert([
            'user_id' => $user->id,
            'sport_id' => $sportId,
            'is_favorite' => true,
            'skill_level' => null,
            'created_at' => $now,
            'updated_at' => $now,
        ]);
    }

    /**
     * @return array{match_event_id: int, match_result_id: int, author: User}
     */
    private function createMatchResultSubmittedBy(
        User $author,
        ?string $validatedAt = null,
        ?string $submittedAt = null,
        string $status = 'validated',
    ): array {
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
        $validatedAtValue = $status === 'validated'
            ? ($validatedAt ?? $now->toDateTimeString())
            : null;
        $matchResultId = (int) DB::table('match_results')->insertGetId([
            'match_event_id' => $matchEventId,
            'home_score' => 1,
            'away_score' => 0,
            'total_comments' => 0,
            'total_likes' => 0,
            'status' => $status,
            'submitted_by_user_id' => $author->id,
            'submitted_at' => $submitted,
            'responded_by_user_id' => null,
            'responded_at' => null,
            'validated_at' => $validatedAtValue,
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
        $viewer = $this->newViewer();
        $followed = User::factory()->createOne();
        $stranger = User::factory()->createOne();

        DB::table('follows')->insert([
            'follower_id' => $viewer->id,
            'following_id' => $followed->id,
            'status' => 'accepted',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $fromFollowed = $this->createMatchResultSubmittedBy($followed);
        $fromStranger = $this->createMatchResultSubmittedBy($stranger);

        $response = $this->actingAs($viewer, 'sanctum')
            ->getJson('/api/v1/auth/posts/feed')
            ->assertOk()
            ->assertJsonPath('count', 1);

        $ids = $this->feedResultIds($response->json('data'));

        $this->assertContains((int) $fromFollowed['match_result_id'], $ids);
        $this->assertNotContains((int) $fromStranger['match_result_id'], $ids);
    }

    public function test_feed_excludes_match_results_that_are_not_validated(): void
    {
        Cache::flush();
        $viewer = $this->newViewer();
        $followed = User::factory()->createOne();

        DB::table('follows')->insert([
            'follower_id' => $viewer->id,
            'following_id' => $followed->id,
            'status' => 'accepted',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $validated = $this->createMatchResultSubmittedBy($followed);
        $pending = $this->createMatchResultSubmittedBy($followed, null, null, 'score_pending_validation');

        $response = $this->actingAs($viewer, 'sanctum')
            ->getJson('/api/v1/auth/posts/feed')
            ->assertOk()
            ->assertJsonPath('count', 1);

        $ids = $this->feedResultIds($response->json('data'));

        $this->assertContains((int) $validated['match_result_id'], $ids);
        $this->assertNotContains((int) $pending['match_result_id'], $ids);
    }

    public function test_count_is_zero_when_viewer_has_no_accepted_follows(): void
    {
        Cache::flush();
        $viewer = $this->newViewer();

        $this->actingAs($viewer, 'sanctum')
            ->getJson('/api/v1/auth/posts/feed')
            ->assertOk()
            ->assertJsonPath('count', 0)
            ->assertJsonPath('data', []);
    }

    public function test_includes_viewer_has_liked_from_post_likes(): void
    {
        Cache::flush();
        $viewer = $this->newViewer();
        $followed = User::factory()->createOne();

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
            ->assertJsonPath('count', 2)
            ->json('data');

        $byId = collect($data)->keyBy(fn (array $row): int => (int) $row['id']);
        $likedId = (int) $liked['match_result_id'];
        $notLikedId = (int) $notLiked['match_result_id'];
        $this->assertTrue((bool) $byId[$likedId]['viewer_has_liked']);
        $this->assertFalse((bool) $byId[$notLikedId]['viewer_has_liked']);
    }

    public function test_excludes_client_viewed_ids(): void
    {
        Cache::flush();
        $viewer = $this->newViewer();
        $followed = User::factory()->createOne();
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
            ->assertOk()
            ->assertJsonPath('count', 1);

        $ids = $this->feedResultIds($response->json('data'));
        $this->assertNotContains((int) $a['match_result_id'], $ids);
        $this->assertContains((int) $b['match_result_id'], $ids);
    }

    public function test_rejects_viewed_post_ids_when_sent_as_query_array_brackets(): void
    {
        Cache::flush();
        $viewer = $this->newViewer();
        $followed = User::factory()->createOne();
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
        $viewer = $this->newViewer();

        $this->actingAs($viewer, 'sanctum')
            ->getJson('/api/v1/auth/posts/feed?viewed_post_ids='.rawurlencode('not-json'))
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['viewed_post_ids']);
    }

    public function test_falls_back_to_user_post_views_when_client_sends_no_viewed_list(): void
    {
        Cache::flush();
        $viewer = $this->newViewer();
        $followed = User::factory()->createOne();
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

        $response = $this->actingAs($viewer, 'sanctum')
            ->getJson('/api/v1/auth/posts/feed')
            ->assertOk()
            ->assertJsonPath('count', 1);

        $ids = $this->feedResultIds($response->json('data'));

        $this->assertNotContains((int) $a['match_result_id'], $ids);
        $this->assertContains((int) $b['match_result_id'], $ids);
    }

    public function test_orders_by_publication_date_newest_first(): void
    {
        Cache::flush();
        $viewer = $this->newViewer();
        $followed = User::factory()->createOne();
        DB::table('follows')->insert([
            'follower_id' => $viewer->id,
            'following_id' => $followed->id,
            'status' => 'accepted',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $older = $this->createMatchResultSubmittedBy($followed, '2020-01-01 12:00:00', '2020-01-01 10:00:00');
        $newer = $this->createMatchResultSubmittedBy($followed, '2025-06-01 12:00:00', '2025-06-01 10:00:00');

        $response = $this->actingAs($viewer, 'sanctum')
            ->getJson('/api/v1/auth/posts/feed')
            ->assertOk()
            ->assertJsonPath('count', 2);

        $ids = $this->feedResultIds($response->json('data'));

        $this->assertSame(
            [(int) $newer['match_result_id'], (int) $older['match_result_id']],
            array_slice($ids, 0, 2)
        );
    }

    public function test_centre_interet_feed_returns_nearby_non_followed_with_shared_sport(): void
    {
        Cache::flush();
        $viewer = $this->newViewer();
        $stranger = User::factory()->createOne();
        $footballId = $this->sportIdBySlug('football');

        $this->insertUserProfileWithLocation($viewer, 48.8566, 2.3522);
        $this->insertUserProfileWithLocation($stranger, 48.8570, 2.3525);
        $this->insertUserSport($viewer, $footballId);
        $this->insertUserSport($stranger, $footballId);

        $nearby = $this->createMatchResultSubmittedBy($stranger);

        $httpData = $this->actingAs($viewer, 'sanctum')
            ->getJson('/api/v1/auth/posts/feed')
            ->assertOk()
            ->assertJsonPath('count', 1)
            ->json('data');

        $this->assertSame(FetchPostService::CENTRE_INTERET_TAG, $httpData[0]['tag']);
        $this->assertSame((int) $nearby['match_result_id'], (int) $httpData[0]['id']);

        $feed = app(FetchPostService::class)->fetchMatchResultCentreInteretFeed((int) $viewer->id, [], 20);
        $this->assertSame(1, $feed['count']);
        $row = $feed['items']->first();
        $this->assertNotNull($row);
        $this->assertSame(FetchPostService::CENTRE_INTERET_TAG, $row->tag);
        $this->assertSame((int) $nearby['match_result_id'], (int) $row->id);
        $this->assertNotNull($row->distance_km);
        $this->assertGreaterThan(0.0, (float) $row->distance_km);
        $this->assertLessThanOrEqual(
            FetchPostService::CENTRE_INTERET_RADIUS_METERS / 1000.0,
            (float) $row->distance_km,
        );
    }

    public function test_centre_interet_feed_orders_by_distance_km_asc(): void
    {
        Cache::flush();
        $viewer = $this->newViewer();
        $closerAuthor = User::factory()->createOne();
        $fartherAuthor = User::factory()->createOne();
        $footballId = $this->sportIdBySlug('football');

        $this->insertUserProfileWithLocation($viewer, 48.8566, 2.3522);
        $this->insertUserProfileWithLocation($closerAuthor, 48.8568, 2.3523);
        $this->insertUserProfileWithLocation($fartherAuthor, 48.8700, 2.3522);
        foreach ([$viewer, $closerAuthor, $fartherAuthor] as $u) {
            $this->insertUserSport($u, $footballId);
        }

        $farResult = $this->createMatchResultSubmittedBy($fartherAuthor, '2025-01-01 12:00:00');
        $nearResult = $this->createMatchResultSubmittedBy($closerAuthor, '2025-06-01 12:00:00');

        $feed = app(FetchPostService::class)->fetchMatchResultCentreInteretFeed((int) $viewer->id, [], 20);
        $this->assertSame(2, $feed['count']);
        $rows = $feed['items'];
        $d0 = (float) (data_get($rows[0], 'distance_km') ?? -1);
        $d1 = (float) (data_get($rows[1], 'distance_km') ?? -1);
        $this->assertTrue($d0 <= $d1, 'distance du 1er post <= distance du 2e (km)');
        $this->assertSame((int) $closerAuthor->id, (int) $rows[0]->submitted_by_user_id);
        $this->assertSame((int) $fartherAuthor->id, (int) $rows[1]->submitted_by_user_id);
        $this->assertSame((int) $nearResult['match_result_id'], (int) $rows[0]->id);
        $this->assertSame((int) $farResult['match_result_id'], (int) $rows[1]->id);
    }

    public function test_http_feed_unions_amis_then_centre_interet_when_under_cap(): void
    {
        Cache::flush();
        $viewer = $this->newViewer();
        $followed = User::factory()->createOne();
        $stranger = User::factory()->createOne();
        $footballId = $this->sportIdBySlug('football');

        DB::table('follows')->insert([
            'follower_id' => $viewer->id,
            'following_id' => $followed->id,
            'status' => 'accepted',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $this->insertUserProfileWithLocation($viewer, 48.8566, 2.3522);
        $this->insertUserProfileWithLocation($stranger, 48.8570, 2.3525);
        $this->insertUserSport($viewer, $footballId);
        $this->insertUserSport($stranger, $footballId);

        $fromFollowed = $this->createMatchResultSubmittedBy($followed);
        $fromStranger = $this->createMatchResultSubmittedBy($stranger);

        $httpData = $this->actingAs($viewer, 'sanctum')
            ->getJson('/api/v1/auth/posts/feed')
            ->assertOk()
            ->assertJsonPath('count', 2)
            ->json('data');

        $this->assertSame('amis', $httpData[0]['tag']);
        $this->assertSame((int) $fromFollowed['match_result_id'], (int) $httpData[0]['id']);
        $this->assertSame(FetchPostService::CENTRE_INTERET_TAG, $httpData[1]['tag']);
        $this->assertSame((int) $fromStranger['match_result_id'], (int) $httpData[1]['id']);

        $centre = app(FetchPostService::class)->fetchMatchResultCentreInteretFeed((int) $viewer->id, [], 20);
        $this->assertSame(1, $centre['count']);
        $centreRow = $centre['items']->first();
        $this->assertNotNull($centreRow);
        $this->assertSame(FetchPostService::CENTRE_INTERET_TAG, $centreRow->tag);
        $this->assertSame((int) $fromStranger['match_result_id'], (int) $centreRow->id);
    }

    public function test_centre_interet_requires_shared_sport(): void
    {
        Cache::flush();
        $viewer = $this->newViewer();
        $followed = User::factory()->createOne();
        $stranger = User::factory()->createOne();

        DB::table('follows')->insert([
            'follower_id' => $viewer->id,
            'following_id' => $followed->id,
            'status' => 'accepted',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $this->insertUserProfileWithLocation($viewer, 48.8566, 2.3522);
        $this->insertUserProfileWithLocation($stranger, 48.8570, 2.3525);
        $this->insertUserSport($viewer, $this->sportIdBySlug('football'));
        $this->insertUserSport($stranger, $this->sportIdBySlug('tennis'));

        $this->createMatchResultSubmittedBy($followed);
        $this->createMatchResultSubmittedBy($stranger);

        $this->actingAs($viewer, 'sanctum')
            ->getJson('/api/v1/auth/posts/feed')
            ->assertOk()
            ->assertJsonPath('count', 1);

        $centre = app(FetchPostService::class)->fetchMatchResultCentreInteretFeed((int) $viewer->id, [], 20);
        $this->assertSame(0, $centre['count']);
    }

    public function test_centre_interet_excludes_followed_users_as_authors(): void
    {
        Cache::flush();
        $viewer = $this->newViewer();
        $followed = User::factory()->createOne();
        $footballId = $this->sportIdBySlug('football');

        DB::table('follows')->insert([
            'follower_id' => $viewer->id,
            'following_id' => $followed->id,
            'status' => 'accepted',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $this->insertUserProfileWithLocation($viewer, 48.8566, 2.3522);
        $this->insertUserProfileWithLocation($followed, 48.8570, 2.3525);
        $this->insertUserSport($viewer, $footballId);
        $this->insertUserSport($followed, $footballId);

        $only = $this->createMatchResultSubmittedBy($followed);

        $httpData = $this->actingAs($viewer, 'sanctum')
            ->getJson('/api/v1/auth/posts/feed')
            ->assertOk()
            ->assertJsonPath('count', 1)
            ->json('data');

        $this->assertSame('amis', $httpData[0]['tag']);
        $this->assertSame((int) $only['match_result_id'], (int) $httpData[0]['id']);

        $centre = app(FetchPostService::class)->fetchMatchResultCentreInteretFeed((int) $viewer->id, [], 20);
        $this->assertSame(0, $centre['count']);
    }
}
