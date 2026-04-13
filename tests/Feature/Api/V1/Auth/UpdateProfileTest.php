<?php

namespace Tests\Feature\Api\V1\Auth;

use App\Models\User;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class UpdateProfileTest extends TestCase
{
    use LazilyRefreshDatabase;

    public function test_update_profile_updates_partial_fields_and_returns_user_payload(): void
    {
        $user = User::factory()->create([
            'name' => 'Jean Dupont',
        ]);
        $token = $user->createToken('auth')->plainTextToken;

        DB::table('user_profiles')->insert([
            'user_id' => $user->id,
            'display_name' => 'Jean Dupont',
            'handle' => 'jean_dupont',
            'bio' => null,
            'avatar_url' => null,
            'is_private' => false,
            'latitude' => null,
            'longitude' => null,
            'city' => null,
            'address_line' => null,
            'birth_date' => null,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $this->patchJson('/api/v1/auth/profile', [
            'given_name' => 'Marie',
            'family_name' => 'Martin',
            'handle' => 'marie_m',
            'bio' => 'Joueuse de football',
            'is_private' => true,
            'avatar_url' => 'https://cdn.osport.test/avatar/marie.png',
            'latitude' => 48.8566,
            'longitude' => 2.3522,
            'city' => 'Paris',
            'address_line' => '10 avenue des Champs-Elysees',
            'birth_date' => '1998-02-14',
        ], [
            'Authorization' => 'Bearer '.$token,
        ])->assertOk()
            ->assertJsonPath('user.name', 'Marie Martin')
            ->assertJsonPath('user.profile.display_name', 'Marie Martin')
            ->assertJsonPath('user.profile.handle', 'marie_m')
            ->assertJsonPath('user.profile.bio', 'Joueuse de football')
            ->assertJsonPath('user.profile.is_private', true)
            ->assertJsonPath('user.profile.avatar_url', 'https://cdn.osport.test/avatar/marie.png')
            ->assertJsonPath('user.profile.latitude', 48.8566)
            ->assertJsonPath('user.profile.longitude', 2.3522)
            ->assertJsonPath('user.profile.city', 'Paris')
            ->assertJsonPath('user.profile.address_line', '10 avenue des Champs-Elysees')
            ->assertJsonPath('user.profile.birth_date', '1998-02-14');
    }

    public function test_update_profile_rejects_handle_already_used_by_another_user(): void
    {
        $currentUser = User::factory()->create();
        $currentToken = $currentUser->createToken('auth')->plainTextToken;

        $otherUser = User::factory()->create();

        DB::table('user_profiles')->insert([
            [
                'user_id' => $currentUser->id,
                'display_name' => 'Current User',
                'handle' => 'current_handle',
                'bio' => null,
                'avatar_url' => null,
                'is_private' => false,
                'latitude' => null,
                'longitude' => null,
                'city' => null,
                'address_line' => null,
                'birth_date' => null,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'user_id' => $otherUser->id,
                'display_name' => 'Other User',
                'handle' => 'taken_handle',
                'bio' => null,
                'avatar_url' => null,
                'is_private' => false,
                'latitude' => null,
                'longitude' => null,
                'city' => null,
                'address_line' => null,
                'birth_date' => null,
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ]);

        $this->patchJson('/api/v1/auth/profile', [
            'handle' => 'taken_handle',
        ], [
            'Authorization' => 'Bearer '.$currentToken,
        ])->assertUnprocessable()
            ->assertJsonValidationErrors(['handle']);
    }
}
