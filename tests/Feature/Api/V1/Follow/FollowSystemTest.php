<?php

namespace Tests\Feature\Api\V1\Follow;

use App\Models\User;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class FollowSystemTest extends TestCase
{
    use LazilyRefreshDatabase;

    public function test_user_can_follow_and_list_following(): void
    {
        $user = User::factory()->create();
        $target = User::factory()->create();
        $token = $user->createToken('auth')->plainTextToken;

        $this->postJson('/api/v1/auth/follow', [
            'target_user_id' => $target->id,
        ], [
            'Authorization' => 'Bearer '.$token,
        ])->assertOk()
            ->assertJsonPath('message', __('Abonnement enregistré.'));

        $this->assertDatabaseHas('follows', [
            'follower_id' => $user->id,
            'following_id' => $target->id,
            'status' => 'accepted',
        ]);

        $this->getJson('/api/v1/auth/follows?type=following', [
            'Authorization' => 'Bearer '.$token,
        ])->assertOk()
            ->assertJsonPath('data.0.id', $target->id)
            ->assertJsonPath('data.0.am_i_following', true);
    }

    public function test_user_can_unfollow(): void
    {
        $user = User::factory()->create();
        $target = User::factory()->create();
        $token = $user->createToken('auth')->plainTextToken;

        DB::table('follows')->insert([
            'follower_id' => $user->id,
            'following_id' => $target->id,
            'status' => 'accepted',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $this->deleteJson('/api/v1/auth/follow', [
            'target_user_id' => $target->id,
        ], [
            'Authorization' => 'Bearer '.$token,
        ])->assertOk()
            ->assertJsonPath('message', __('Abonnement supprimé.'));

        $this->assertDatabaseMissing('follows', [
            'follower_id' => $user->id,
            'following_id' => $target->id,
        ]);
    }

    public function test_user_cannot_follow_himself(): void
    {
        $user = User::factory()->create();
        $token = $user->createToken('auth')->plainTextToken;

        $this->postJson('/api/v1/auth/follow', [
            'target_user_id' => $user->id,
        ], [
            'Authorization' => 'Bearer '.$token,
        ])->assertUnprocessable()
            ->assertJsonValidationErrors(['target_user_id']);
    }

    public function test_follows_list_supports_cursor_pagination(): void
    {
        $user = User::factory()->create();
        $token = $user->createToken('auth')->plainTextToken;

        $targets = User::factory()->count(3)->create();

        foreach ($targets as $target) {
            DB::table('follows')->insert([
                'follower_id' => $user->id,
                'following_id' => $target->id,
                'status' => 'accepted',
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        $first = $this->getJson('/api/v1/auth/follows?type=following&limit=2', [
            'Authorization' => 'Bearer '.$token,
        ])->assertOk();

        $first->assertJsonPath('meta.has_more', true)
            ->assertJsonCount(2, 'data');

        $cursor = $first->json('meta.next_cursor');
        $this->assertNotEmpty($cursor);

        $second = $this->getJson('/api/v1/auth/follows?type=following&limit=2&cursor='.urlencode((string) $cursor), [
            'Authorization' => 'Bearer '.$token,
        ])->assertOk();

        $second->assertJsonPath('meta.has_more', false)
            ->assertJsonCount(1, 'data');

        foreach (array_merge($first->json('data'), $second->json('data')) as $row) {
            $this->assertTrue($row['am_i_following']);
        }
    }

    public function test_followers_list_sets_am_i_following_from_reciprocal_follows(): void
    {
        $user = User::factory()->create();
        $fanFollowedBack = User::factory()->create();
        $fanNotFollowed = User::factory()->create();
        $token = $user->createToken('auth')->plainTextToken;

        foreach ([$fanFollowedBack, $fanNotFollowed] as $fan) {
            DB::table('follows')->insert([
                'follower_id' => $fan->id,
                'following_id' => $user->id,
                'status' => 'accepted',
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        DB::table('follows')->insert([
            'follower_id' => $user->id,
            'following_id' => $fanFollowedBack->id,
            'status' => 'accepted',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $response = $this->getJson('/api/v1/auth/follows?type=followers', [
            'Authorization' => 'Bearer '.$token,
        ])->assertOk()
            ->assertJsonCount(2, 'data');

        $byId = collect($response->json('data'))->keyBy('id');
        $this->assertTrue($byId[$fanFollowedBack->id]['am_i_following']);
        $this->assertFalse($byId[$fanNotFollowed->id]['am_i_following']);
    }

    public function test_follow_counts_endpoint_returns_totals(): void
    {
        $user = User::factory()->create();
        $followerA = User::factory()->create();
        $followerB = User::factory()->create();
        $follows = User::factory()->create();
        $token = $user->createToken('auth')->plainTextToken;

        foreach ([$followerA, $followerB] as $follower) {
            DB::table('follows')->insert([
                'follower_id' => $follower->id,
                'following_id' => $user->id,
                'status' => 'accepted',
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        DB::table('follows')->insert([
            'follower_id' => $user->id,
            'following_id' => $follows->id,
            'status' => 'accepted',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $this->getJson('/api/v1/auth/follows/counts', [
            'Authorization' => 'Bearer '.$token,
        ])->assertOk()
            ->assertJsonPath('data.followers_count', 2)
            ->assertJsonPath('data.following_count', 1)
            ->assertJsonPath('data.pending_requests_count', 0);
    }

    public function test_follow_private_profile_creates_pending_request(): void
    {
        $privateUser = User::factory()->create();
        $follower = User::factory()->create();
        $token = $follower->createToken('auth')->plainTextToken;

        DB::table('user_profiles')->insert([
            'user_id' => $privateUser->id,
            'handle' => 'private_user',
            'display_name' => 'Private User',
            'is_private' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $this->postJson('/api/v1/auth/follow', [
            'target_user_id' => $privateUser->id,
        ], [
            'Authorization' => 'Bearer '.$token,
        ])->assertOk()
            ->assertJsonPath('data.status', 'pending');

        $this->assertDatabaseHas('follows', [
            'follower_id' => $follower->id,
            'following_id' => $privateUser->id,
            'status' => 'pending',
        ]);
    }

    public function test_private_user_can_list_accept_and_reject_follow_requests(): void
    {
        $privateUser = User::factory()->create();
        $requester = User::factory()->create();
        $privateToken = $privateUser->createToken('auth')->plainTextToken;

        foreach ([$privateUser, $requester] as $user) {
            DB::table('user_profiles')->insert([
                'user_id' => $user->id,
                'handle' => 'user_'.$user->id,
                'display_name' => 'User '.$user->id,
                'is_private' => $user->id === $privateUser->id,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        $followRowId = DB::table('follows')->insertGetId([
            'follower_id' => $requester->id,
            'following_id' => $privateUser->id,
            'status' => 'pending',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $this->getJson('/api/v1/auth/follows/counts', [
            'Authorization' => 'Bearer '.$privateToken,
        ])->assertOk()
            ->assertJsonPath('data.pending_requests_count', 1);

        $this->getJson('/api/v1/auth/follow-requests', [
            'Authorization' => 'Bearer '.$privateToken,
        ])->assertOk()
            ->assertJsonPath('data.0.id', $followRowId)
            ->assertJsonPath('data.0.user_id', $requester->id);

        $this->postJson('/api/v1/auth/follow-requests/accept', [
            'follow_request_id' => $followRowId,
        ], [
            'Authorization' => 'Bearer '.$privateToken,
        ])->assertOk();

        $this->assertDatabaseHas('follows', [
            'id' => $followRowId,
            'status' => 'accepted',
        ]);

        $this->getJson('/api/v1/auth/follows/counts', [
            'Authorization' => 'Bearer '.$privateToken,
        ])->assertOk()
            ->assertJsonPath('data.pending_requests_count', 0)
            ->assertJsonPath('data.followers_count', 1);
    }

    public function test_private_user_can_reject_follow_request(): void
    {
        $privateUser = User::factory()->create();
        $requester = User::factory()->create();
        $privateToken = $privateUser->createToken('auth')->plainTextToken;

        DB::table('user_profiles')->insert([
            'user_id' => $privateUser->id,
            'handle' => 'private_only',
            'display_name' => 'Private Only',
            'is_private' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $followRowId = DB::table('follows')->insertGetId([
            'follower_id' => $requester->id,
            'following_id' => $privateUser->id,
            'status' => 'pending',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $this->postJson('/api/v1/auth/follow-requests/reject', [
            'follow_request_id' => $followRowId,
        ], [
            'Authorization' => 'Bearer '.$privateToken,
        ])->assertOk();

        $this->assertDatabaseMissing('follows', [
            'id' => $followRowId,
        ]);
    }
}
