<?php

namespace Tests\Feature\Api\V1\Profile;

use App\Models\User;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class UserPublicProfileTest extends TestCase
{
    use LazilyRefreshDatabase;

    public function test_viewer_can_fetch_public_profile_without_email_field(): void
    {
        $viewer = User::factory()->create();
        $target = User::factory()->create(['name' => 'Cible Publique']);
        $token = $viewer->createToken('auth')->plainTextToken;

        DB::table('user_profiles')->insert([
            'user_id' => $target->id,
            'display_name' => 'Cible Publique',
            'handle' => 'cible_pub',
            'bio' => 'Bio test',
            'avatar_url' => null,
            'is_private' => false,
            'latitude' => null,
            'longitude' => null,
            'city' => 'Lyon',
            'address_line' => null,
            'birth_date' => null,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $this->getJson('/api/v1/auth/users/'.$target->id.'/profile', [
            'Authorization' => 'Bearer '.$token,
        ])->assertOk()
            ->assertJsonPath('user.id', $target->id)
            ->assertJsonPath('user.am_i_following', false)
            ->assertJsonMissingPath('user.email');
    }

    public function test_private_profile_forbidden_without_accepted_follow(): void
    {
        $viewer = User::factory()->create();
        $target = User::factory()->create();
        $token = $viewer->createToken('auth')->plainTextToken;

        DB::table('user_profiles')->insert([
            'user_id' => $target->id,
            'display_name' => 'Privé',
            'handle' => 'prive_1',
            'bio' => null,
            'avatar_url' => null,
            'is_private' => true,
            'latitude' => null,
            'longitude' => null,
            'city' => null,
            'address_line' => null,
            'birth_date' => null,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $this->getJson('/api/v1/auth/users/'.$target->id.'/profile', [
            'Authorization' => 'Bearer '.$token,
        ])->assertForbidden();
    }

    public function test_private_profile_allowed_when_accepted_follow_exists(): void
    {
        $viewer = User::factory()->create();
        $target = User::factory()->create();
        $token = $viewer->createToken('auth')->plainTextToken;

        DB::table('user_profiles')->insert([
            'user_id' => $target->id,
            'display_name' => 'Privé',
            'handle' => 'prive_2',
            'bio' => null,
            'avatar_url' => null,
            'is_private' => true,
            'latitude' => null,
            'longitude' => null,
            'city' => null,
            'address_line' => null,
            'birth_date' => null,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::table('follows')->insert([
            'follower_id' => $viewer->id,
            'following_id' => $target->id,
            'status' => 'accepted',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $this->getJson('/api/v1/auth/users/'.$target->id.'/profile', [
            'Authorization' => 'Bearer '.$token,
        ])->assertOk()
            ->assertJsonPath('user.id', $target->id)
            ->assertJsonPath('user.am_i_following', true)
            ->assertJsonMissingPath('user.email');
    }
}
