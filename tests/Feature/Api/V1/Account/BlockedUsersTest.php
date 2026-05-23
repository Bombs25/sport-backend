<?php

namespace Tests\Feature\Api\V1\Account;

use App\Models\User;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class BlockedUsersTest extends TestCase
{
    use LazilyRefreshDatabase;

    private function tokenFor(User $user): string
    {
        return $user->createToken('auth')->plainTextToken;
    }

    public function test_block_creates_row_and_returns_201(): void
    {
        $blocker = User::factory()->create();
        $blocked = User::factory()->create();
        $token = $this->tokenFor($blocker);

        $this->postJson('/api/v1/auth/blocked-users', [
            'user_id' => $blocked->id,
        ], [
            'Authorization' => 'Bearer '.$token,
        ])->assertStatus(201)
            ->assertJson(['blocked' => true, 'created' => true]);

        $this->assertDatabaseHas('user_blocks', [
            'blocker_id' => $blocker->id,
            'blocked_id' => $blocked->id,
        ]);
    }

    public function test_block_is_idempotent_returns_200_when_already_blocked(): void
    {
        $blocker = User::factory()->create();
        $blocked = User::factory()->create();
        $token = $this->tokenFor($blocker);

        DB::table('user_blocks')->insert([
            'blocker_id' => $blocker->id,
            'blocked_id' => $blocked->id,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $this->postJson('/api/v1/auth/blocked-users', [
            'user_id' => $blocked->id,
        ], [
            'Authorization' => 'Bearer '.$token,
        ])->assertOk()
            ->assertJson(['blocked' => true, 'created' => false]);
    }

    public function test_block_rejects_self(): void
    {
        $user = User::factory()->create();
        $token = $this->tokenFor($user);

        $this->postJson('/api/v1/auth/blocked-users', [
            'user_id' => $user->id,
        ], [
            'Authorization' => 'Bearer '.$token,
        ])->assertUnprocessable()
            ->assertJsonValidationErrors(['user_id']);
    }

    public function test_block_removes_reciprocal_follows(): void
    {
        $a = User::factory()->create();
        $b = User::factory()->create();
        $token = $this->tokenFor($a);

        DB::table('follows')->insert([
            ['follower_id' => $a->id, 'following_id' => $b->id, 'status' => 'accepted', 'created_at' => now(), 'updated_at' => now()],
            ['follower_id' => $b->id, 'following_id' => $a->id, 'status' => 'accepted', 'created_at' => now(), 'updated_at' => now()],
        ]);

        $this->postJson('/api/v1/auth/blocked-users', [
            'user_id' => $b->id,
        ], [
            'Authorization' => 'Bearer '.$token,
        ])->assertStatus(201);

        $this->assertDatabaseMissing('follows', ['follower_id' => $a->id, 'following_id' => $b->id]);
        $this->assertDatabaseMissing('follows', ['follower_id' => $b->id, 'following_id' => $a->id]);
    }

    public function test_list_returns_blocked_users_paginated(): void
    {
        $blocker = User::factory()->create();
        $token = $this->tokenFor($blocker);

        // Crée 3 users bloqués
        $blockedUsers = User::factory()->count(3)->create();
        foreach ($blockedUsers as $u) {
            DB::table('user_blocks')->insert([
                'blocker_id' => $blocker->id,
                'blocked_id' => $u->id,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        $this->getJson('/api/v1/auth/blocked-users?limit=10', [
            'Authorization' => 'Bearer '.$token,
        ])->assertOk()
            ->assertJsonCount(3, 'data')
            ->assertJsonPath('meta.has_more', false)
            ->assertJsonPath('meta.per_page', 10);
    }

    public function test_unblock_removes_row(): void
    {
        $blocker = User::factory()->create();
        $blocked = User::factory()->create();
        $token = $this->tokenFor($blocker);

        DB::table('user_blocks')->insert([
            'blocker_id' => $blocker->id,
            'blocked_id' => $blocked->id,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $this->deleteJson('/api/v1/auth/blocked-users/'.$blocked->id, [], [
            'Authorization' => 'Bearer '.$token,
        ])->assertOk()
            ->assertJson(['blocked' => false]);

        $this->assertDatabaseMissing('user_blocks', [
            'blocker_id' => $blocker->id,
            'blocked_id' => $blocked->id,
        ]);
    }

    public function test_unblock_is_idempotent_when_not_blocked(): void
    {
        $blocker = User::factory()->create();
        $someone = User::factory()->create();
        $token = $this->tokenFor($blocker);

        $this->deleteJson('/api/v1/auth/blocked-users/'.$someone->id, [], [
            'Authorization' => 'Bearer '.$token,
        ])->assertOk()
            ->assertJson(['blocked' => false]);
    }
}
