<?php

namespace Tests\Feature\Api\V1\Profile;

use App\Models\User;
use App\Support\UserProfileLocation;
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

        DB::table('user_profiles')->insert(array_merge([
            'user_id' => $user->id,
            'display_name' => 'Jean Dupont',
            'handle' => 'jean_dupont',
            'bio' => null,
            'avatar_url' => null,
            'is_private' => false,
            'city' => null,
            'address_line' => null,
            'birth_date' => null,
            'created_at' => now(),
            'updated_at' => now(),
        ], UserProfileLocation::columnsFromLatLng(null, null)));

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
            array_merge([
                'user_id' => $currentUser->id,
                'display_name' => 'Current User',
                'handle' => 'current_handle',
                'bio' => null,
                'avatar_url' => null,
                'is_private' => false,
                'city' => null,
                'address_line' => null,
                'birth_date' => null,
                'created_at' => now(),
                'updated_at' => now(),
            ], UserProfileLocation::columnsFromLatLng(null, null)),
            array_merge([
                'user_id' => $otherUser->id,
                'display_name' => 'Other User',
                'handle' => 'taken_handle',
                'bio' => null,
                'avatar_url' => null,
                'is_private' => false,
                'city' => null,
                'address_line' => null,
                'birth_date' => null,
                'created_at' => now(),
                'updated_at' => now(),
            ], UserProfileLocation::columnsFromLatLng(null, null)),
        ]);

        $this->patchJson('/api/v1/auth/profile', [
            'handle' => 'taken_handle',
        ], [
            'Authorization' => 'Bearer '.$currentToken,
        ])->assertUnprocessable()
            ->assertJsonValidationErrors(['handle']);
    }

    public function test_update_profile_accepts_phone_and_returns_it_in_payload(): void
    {
        $user = User::factory()->create();
        $token = $user->createToken('auth')->plainTextToken;

        DB::table('user_profiles')->insert(array_merge([
            'user_id' => $user->id,
            'display_name' => 'User Phone',
            'handle' => 'user_phone',
            'bio' => null,
            'phone' => null,
            'avatar_url' => null,
            'is_private' => false,
            'city' => null,
            'address_line' => null,
            'birth_date' => null,
            'created_at' => now(),
            'updated_at' => now(),
        ], UserProfileLocation::columnsFromLatLng(null, null)));

        $this->patchJson('/api/v1/auth/profile', [
            'phone' => '+33 6 12 34 56 78',
        ], [
            'Authorization' => 'Bearer '.$token,
        ])->assertOk()
            ->assertJsonPath('user.profile.phone', '+33 6 12 34 56 78');

        $this->assertSame(
            '+33 6 12 34 56 78',
            DB::table('user_profiles')->where('user_id', $user->id)->value('phone'),
        );
    }

    public function test_update_profile_rejects_invalid_phone_format(): void
    {
        $user = User::factory()->create();
        $token = $user->createToken('auth')->plainTextToken;

        DB::table('user_profiles')->insert(array_merge([
            'user_id' => $user->id,
            'display_name' => 'User Phone Invalid',
            'handle' => 'user_phone_invalid',
            'bio' => null,
            'phone' => null,
            'avatar_url' => null,
            'is_private' => false,
            'city' => null,
            'address_line' => null,
            'birth_date' => null,
            'created_at' => now(),
            'updated_at' => now(),
        ], UserProfileLocation::columnsFromLatLng(null, null)));

        $this->patchJson('/api/v1/auth/profile', [
            'phone' => 'abc;drop table',
        ], [
            'Authorization' => 'Bearer '.$token,
        ])->assertUnprocessable()
            ->assertJsonValidationErrors(['phone']);
    }
}
