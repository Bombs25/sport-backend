<?php

namespace Tests\Feature\Api\V1\Search;

use App\Models\User;
use App\Services\Search\TypesenseTeamService;
use App\Support\UserProfileLocation;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class TeamNearbySearchTest extends TestCase
{
    use LazilyRefreshDatabase;

    public function test_returns_422_when_viewer_has_no_location(): void
    {
        $user = User::factory()->create();
        $token = $user->createToken('auth')->plainTextToken;

        DB::table('user_profiles')->insert(array_merge([
            'user_id' => $user->id,
            'display_name' => 'Sans GPS',
            'handle' => 'sans_gps',
            'bio' => null,
            'avatar_url' => null,
            'is_private' => false,
            'city' => null,
            'address_line' => null,
            'birth_date' => null,
            'created_at' => now(),
            'updated_at' => now(),
        ], UserProfileLocation::columnsFromLatLng(null, null)));

        $this->getJson('/api/v1/auth/teams/search', [
            'Authorization' => 'Bearer '.$token,
        ])->assertStatus(422);
    }

    public function test_forwards_filters_and_pagination_to_search_service(): void
    {
        $user = User::factory()->create();
        $token = $user->createToken('auth')->plainTextToken;

        DB::table('user_profiles')->insert(array_merge([
            'user_id' => $user->id,
            'display_name' => 'Jean',
            'handle' => 'jean_marseille',
            'bio' => null,
            'avatar_url' => null,
            'is_private' => false,
            'city' => 'Marseille',
            'address_line' => null,
            'birth_date' => null,
            'created_at' => now(),
            'updated_at' => now(),
        ], UserProfileLocation::columnsFromLatLng(43.2965, 5.3698)));

        $this->mock(TypesenseTeamService::class, function ($mock) use ($user): void {
            $mock->shouldReceive('searchPublicTeamsAround')
                ->once()
                ->withArgs(function (
                    float $latitude,
                    float $longitude,
                    string $query,
                    ?int $sportId,
                    ?string $competitionType,
                    ?string $skillLevel,
                    float $radiusKm,
                    int $page,
                    int $perPage,
                    ?int $excludeCreatorId,
                    array $excludeTeamIds,
                ) use ($user): bool {
                    return abs($latitude - 43.2965) < 0.0001
                        && abs($longitude - 5.3698) < 0.0001
                        && $query === 'thunder'
                        && $sportId === 1
                        && $competitionType === 'competitive'
                        && $skillLevel === null
                        && abs($radiusKm - 25.0) < 0.0001
                        && $page === 2
                        && $perPage === 5
                        && $excludeCreatorId === (int) $user->id
                        && $excludeTeamIds === [];
                })
                ->andReturn([
                    'data' => [
                        ['id' => 42, 'name' => 'Thunder FC'],
                    ],
                    'meta' => [
                        'found' => 8,
                        'out_of' => 8,
                        'page' => 2,
                        'next_page' => null,
                        'per_page' => 5,
                        'search_time_ms' => 1,
                        'center' => [
                            'latitude' => 43.2965,
                            'longitude' => 5.3698,
                            'radius_km' => 25,
                        ],
                    ],
                ]);
        });

        $this->getJson('/api/v1/auth/teams/search?q=thunder&sport_id=1&competition_type=competitive&radius_km=25&page=2&per_page=5', [
            'Authorization' => 'Bearer '.$token,
        ])->assertOk()
            ->assertJsonPath('meta.page', 2)
            ->assertJsonPath('meta.next_page', null)
            ->assertJsonCount(1, 'data');
    }

    public function test_forwards_active_membership_team_ids_to_exclude_from_search(): void
    {
        $creator = User::factory()->create();
        $member = User::factory()->create();
        $token = $member->createToken('auth')->plainTextToken;

        DB::table('user_profiles')->insert(array_merge([
            'user_id' => $member->id,
            'display_name' => 'Membre',
            'handle' => 'membre_search',
            'bio' => null,
            'avatar_url' => null,
            'is_private' => false,
            'city' => 'Lyon',
            'address_line' => null,
            'birth_date' => null,
            'created_at' => now(),
            'updated_at' => now(),
        ], UserProfileLocation::columnsFromLatLng(45.764, 4.8357)));

        $sportId = (int) DB::table('sports')->value('id');

        $joinedTeamId = (int) DB::table('teams')->insertGetId([
            'creator_id' => $creator->id,
            'sport_id' => $sportId,
            'name' => 'Joined Only',
            'slug' => 'joined-only',
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
            'team_id' => $joinedTeamId,
            'user_id' => $member->id,
            'role' => 'player',
            'status' => 'active',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $this->mock(TypesenseTeamService::class, function ($mock) use ($member, $joinedTeamId): void {
            $mock->shouldReceive('searchPublicTeamsAround')
                ->once()
                ->withArgs(function (
                    float $latitude,
                    float $longitude,
                    string $query,
                    ?int $sportId,
                    ?string $competitionType,
                    ?string $skillLevel,
                    float $radiusKm,
                    int $page,
                    int $perPage,
                    ?int $excludeCreatorId,
                    array $excludeTeamIds,
                ) use ($member, $joinedTeamId): bool {
                    return $excludeCreatorId === (int) $member->id
                        && in_array($joinedTeamId, $excludeTeamIds, true);
                })
                ->andReturn([
                    'data' => [],
                    'meta' => [
                        'found' => 0,
                        'out_of' => 0,
                        'page' => 1,
                        'next_page' => null,
                        'per_page' => 10,
                        'search_time_ms' => 0,
                        'center' => [
                            'latitude' => 45.764,
                            'longitude' => 4.8357,
                            'radius_km' => 100,
                        ],
                    ],
                ]);
        });

        $this->getJson('/api/v1/auth/teams/search', [
            'Authorization' => 'Bearer '.$token,
        ])->assertOk();
    }
}
