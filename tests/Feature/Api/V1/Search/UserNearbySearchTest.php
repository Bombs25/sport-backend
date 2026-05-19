<?php

namespace Tests\Feature\Api\V1\Search;

use App\Models\User;
use App\Services\Search\TypesenseUserService;
use App\Support\UserProfileLocation;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class UserNearbySearchTest extends TestCase
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

        $this->getJson('/api/v1/auth/users/search', [
            'Authorization' => 'Bearer '.$token,
        ])->assertStatus(422);
    }

    public function test_forwards_page_and_per_page_to_search_service(): void
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

        $this->mock(TypesenseUserService::class, function ($mock) use ($user): void {
            $mock->shouldReceive('searchPublicUsersAround')
                ->once()
                ->withArgs(function (
                    float $latitude,
                    float $longitude,
                    string $query,
                    float $radiusKm,
                    int $page,
                    int $perPage,
                    ?int $excludeUserId,
                ) use ($user): bool {
                    return abs($latitude - 43.2965) < 0.0001
                        && abs($longitude - 5.3698) < 0.0001
                        && $query === '*'
                        && $radiusKm === 100.0
                        && $page === 2
                        && $perPage === 5
                        && $excludeUserId === $user->id;
                })
                ->andReturn([
                    'data' => [
                        ['id' => 99, 'name' => 'Autre joueur'],
                    ],
                    'meta' => [
                        'found' => 10,
                        'out_of' => 10,
                        'page' => 2,
                        'next_page' => 3,
                        'per_page' => 5,
                        'search_time_ms' => 1,
                        'center' => [
                            'latitude' => 43.2965,
                            'longitude' => 5.3698,
                            'radius_km' => 100,
                        ],
                    ],
                ]);
        });

        $this->getJson('/api/v1/auth/users/search?page=2&per_page=5', [
            'Authorization' => 'Bearer '.$token,
        ])->assertOk()
            ->assertJsonPath('meta.page', 2)
            ->assertJsonPath('meta.next_page', 3)
            ->assertJsonPath('meta.per_page', 5)
            ->assertJsonCount(1, 'data');
    }
}
