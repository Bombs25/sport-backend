<?php

namespace Tests\Feature\Api\V1\Messages;

use App\Models\User;
use App\Services\Search\TypesenseUserService;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class MessageableUsersSearchTest extends TestCase
{
    use LazilyRefreshDatabase;

    private function tokenFor(User $user): string
    {
        return $user->createToken('auth')->plainTextToken;
    }

    private function ensureProfile(User $user, string $audience = 'everyone'): void
    {
        DB::table('user_profiles')->updateOrInsert(
            ['user_id' => $user->id],
            [
                'display_name' => $user->name,
                'handle' => 'h_'.$user->id,
                'who_can_message_me' => $audience,
                'is_private' => false,
                'created_at' => now(),
                'updated_at' => now(),
            ],
        );
    }

    /** Mock du hit Typesense — on n'utilise jamais le vrai client en test. */
    private function mockTypesenseHits(array $userIds): void
    {
        $this->mock(TypesenseUserService::class, function ($mock) use ($userIds): void {
            $mock->shouldReceive('searchPublicUsersForDm')
                ->andReturn([
                    'data' => array_map(static fn (int $id): array => [
                        'id' => $id,
                        'name' => "User {$id}",
                        'display_name' => "User {$id}",
                        'handle' => "h_{$id}",
                        'is_private' => false,
                    ], $userIds),
                    'meta' => [
                        'found' => count($userIds),
                        'out_of' => count($userIds),
                        'page' => 1,
                        'next_page' => null,
                        'per_page' => 20,
                        'search_time_ms' => 0,
                    ],
                ]);
        });
    }

    public function test_user_with_audience_everyone_is_returned(): void
    {
        $viewer = User::factory()->create();
        $candidate = User::factory()->create();
        $this->ensureProfile($candidate, 'everyone');

        $this->mockTypesenseHits([$candidate->id]);

        $this->getJson('/api/v1/auth/messageable-users?q=user', [
            'Authorization' => 'Bearer '.$this->tokenFor($viewer),
        ])->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.id', $candidate->id);
    }

    public function test_user_with_audience_followers_is_returned_only_if_viewer_follows(): void
    {
        $viewer = User::factory()->create();
        $candidate = User::factory()->create();
        $this->ensureProfile($candidate, 'followers');

        // Viewer suit le candidat → autorisé.
        DB::table('follows')->insert([
            'follower_id' => $viewer->id,
            'following_id' => $candidate->id,
            'status' => 'accepted',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $this->mockTypesenseHits([$candidate->id]);

        $this->getJson('/api/v1/auth/messageable-users?q=user', [
            'Authorization' => 'Bearer '.$this->tokenFor($viewer),
        ])->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.id', $candidate->id);
    }

    public function test_user_with_audience_followers_is_excluded_when_viewer_does_not_follow(): void
    {
        $viewer = User::factory()->create();
        $candidate = User::factory()->create();
        $this->ensureProfile($candidate, 'followers');

        $this->mockTypesenseHits([$candidate->id]);

        $this->getJson('/api/v1/auth/messageable-users?q=user', [
            'Authorization' => 'Bearer '.$this->tokenFor($viewer),
        ])->assertOk()
            ->assertJsonCount(0, 'data');
    }

    public function test_user_with_audience_nobody_is_excluded(): void
    {
        $viewer = User::factory()->create();
        $candidate = User::factory()->create();
        $this->ensureProfile($candidate, 'nobody');

        // Même si le viewer le suit, `nobody` bloque.
        DB::table('follows')->insert([
            'follower_id' => $viewer->id,
            'following_id' => $candidate->id,
            'status' => 'accepted',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $this->mockTypesenseHits([$candidate->id]);

        $this->getJson('/api/v1/auth/messageable-users?q=user', [
            'Authorization' => 'Bearer '.$this->tokenFor($viewer),
        ])->assertOk()
            ->assertJsonCount(0, 'data');
    }

    public function test_requires_authentication(): void
    {
        $this->getJson('/api/v1/auth/messageable-users?q=user')
            ->assertUnauthorized();
    }
}
