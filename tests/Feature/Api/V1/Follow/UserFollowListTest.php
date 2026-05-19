<?php

namespace Tests\Feature\Api\V1\Follow;

use App\Models\User;
use App\Support\UserProfileLocation;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class UserFollowListTest extends TestCase
{
    use LazilyRefreshDatabase;

    public function test_viewer_can_list_followers_of_public_profile(): void
    {
        $viewer = User::factory()->create();
        $target = User::factory()->create(['name' => 'Cible Liste']);
        $follower = User::factory()->create(['name' => 'Follower Un']);
        $token = $viewer->createToken('auth')->plainTextToken;

        foreach ([$target, $follower] as $user) {
            DB::table('user_profiles')->insert(array_merge([
                'user_id' => $user->id,
                'display_name' => $user->name,
                'handle' => 'u'.$user->id,
                'bio' => null,
                'avatar_url' => null,
                'is_private' => false,
                'city' => null,
                'address_line' => null,
                'birth_date' => null,
                'created_at' => now(),
                'updated_at' => now(),
            ], UserProfileLocation::columnsFromLatLng(null, null)));
        }

        DB::table('follows')->insert([
            'follower_id' => $follower->id,
            'following_id' => $target->id,
            'status' => 'accepted',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $this->getJson('/api/v1/auth/users/'.$target->id.'/follows?type=followers', [
            'Authorization' => 'Bearer '.$token,
        ])->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.id', $follower->id)
            ->assertJsonPath('data.0.name', 'Follower Un')
            ->assertJsonMissingPath('data.0.email');
    }

    public function test_private_profile_follow_list_forbidden_without_accepted_follow(): void
    {
        $viewer = User::factory()->create();
        $target = User::factory()->create();
        $token = $viewer->createToken('auth')->plainTextToken;

        DB::table('user_profiles')->insert(array_merge([
            'user_id' => $target->id,
            'display_name' => 'Privé',
            'handle' => 'prive_list',
            'bio' => null,
            'avatar_url' => null,
            'is_private' => true,
            'city' => null,
            'address_line' => null,
            'birth_date' => null,
            'created_at' => now(),
            'updated_at' => now(),
        ], UserProfileLocation::columnsFromLatLng(null, null)));

        $this->getJson('/api/v1/auth/users/'.$target->id.'/follows?type=followers', [
            'Authorization' => 'Bearer '.$token,
        ])->assertForbidden();
    }
}
