<?php

namespace Tests\Feature\Api\V1\Follow;

use App\Models\User;
use App\Support\UserProfileLocation;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class UserFollowCountsTest extends TestCase
{
    use LazilyRefreshDatabase;

    public function test_viewer_can_fetch_follow_counts_for_public_profile(): void
    {
        $viewer = User::factory()->create();
        $target = User::factory()->create();
        $token = $viewer->createToken('auth')->plainTextToken;

        DB::table('user_profiles')->insert(array_merge([
            'user_id' => $target->id,
            'display_name' => 'Cible',
            'handle' => 'cible',
            'bio' => null,
            'avatar_url' => null,
            'is_private' => false,
            'city' => null,
            'address_line' => null,
            'birth_date' => null,
            'created_at' => now(),
            'updated_at' => now(),
        ], UserProfileLocation::columnsFromLatLng(null, null)));

        DB::table('follows')->insert([
            'follower_id' => $viewer->id,
            'following_id' => $target->id,
            'status' => 'accepted',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $this->getJson('/api/v1/auth/users/'.$target->id.'/follows/counts', [
            'Authorization' => 'Bearer '.$token,
        ])->assertOk()
            ->assertJsonPath('data.followers_count', 1)
            ->assertJsonPath('data.following_count', 0)
            ->assertJsonPath('data.posts_count', 0);
    }

    public function test_private_profile_counts_forbidden_without_accepted_follow(): void
    {
        $viewer = User::factory()->create();
        $target = User::factory()->create();
        $token = $viewer->createToken('auth')->plainTextToken;

        DB::table('user_profiles')->insert(array_merge([
            'user_id' => $target->id,
            'display_name' => 'Privé',
            'handle' => 'prive_counts',
            'bio' => null,
            'avatar_url' => null,
            'is_private' => true,
            'city' => null,
            'address_line' => null,
            'birth_date' => null,
            'created_at' => now(),
            'updated_at' => now(),
        ], UserProfileLocation::columnsFromLatLng(null, null)));

        $this->getJson('/api/v1/auth/users/'.$target->id.'/follows/counts', [
            'Authorization' => 'Bearer '.$token,
        ])->assertForbidden();
    }
}
