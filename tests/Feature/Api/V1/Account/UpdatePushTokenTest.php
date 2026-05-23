<?php

namespace Tests\Feature\Api\V1\Account;

use App\Models\User;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Tests\TestCase;

class UpdatePushTokenTest extends TestCase
{
    use LazilyRefreshDatabase;

    public function test_updates_fcm_token_for_authenticated_user(): void
    {
        $user = User::factory()->create(['fcm_token' => null]);
        $token = $user->createToken('auth')->plainTextToken;

        $this->patchJson('/api/v1/auth/push-token', [
            'fcm_token' => 'ExponentPushToken[abc123XYZ]',
        ], [
            'Authorization' => 'Bearer '.$token,
        ])->assertOk();

        $this->assertSame(
            'ExponentPushToken[abc123XYZ]',
            User::query()->whereKey($user->id)->value('fcm_token'),
        );
    }

    public function test_rejects_non_expo_tokens(): void
    {
        $user = User::factory()->create();
        $token = $user->createToken('auth')->plainTextToken;

        $this->patchJson('/api/v1/auth/push-token', [
            'fcm_token' => 'APA91b-native-fcm-token',
        ], [
            'Authorization' => 'Bearer '.$token,
        ])->assertUnprocessable()
            ->assertJsonValidationErrors(['fcm_token']);
    }

    public function test_requires_authentication(): void
    {
        $this->patchJson('/api/v1/auth/push-token', [
            'fcm_token' => 'ExponentPushToken[abc123XYZ]',
        ])->assertUnauthorized();
    }
}
