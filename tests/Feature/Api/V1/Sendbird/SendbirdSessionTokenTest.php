<?php

namespace Tests\Feature\Api\V1\Sendbird;

use App\Models\User;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class SendbirdSessionTokenTest extends TestCase
{
    use LazilyRefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        config()->set('services.sendbird', [
            'app_id' => 'test-app-id',
            'api_token' => 'test-api-token',
            'base_url' => 'https://api-test.sendbird.com',
        ]);
    }

    private function fakeSendbird(): void
    {
        Http::fake([
            'api-test.sendbird.com/v3/group_channels' => Http::response([
                'channel_url' => 'sendbird_group_channel_42',
            ], 200),
            // `POST/PUT /v3/users` : Sendbird émet le session token inline.
            'api-test.sendbird.com/v3/users*' => Http::response([
                'user_id' => 'osport',
                'session_tokens' => [
                    ['session_token' => 'sess-token-xyz', 'expires_at' => 1893456000000],
                ],
            ], 200),
        ]);
    }

    public function test_authenticated_user_gets_a_session_token(): void
    {
        $this->fakeSendbird();
        $user = User::factory()->create();
        $token = $user->createToken('auth')->plainTextToken;

        $this->postJson('/api/v1/auth/sendbird/session-token', [], [
            'Authorization' => 'Bearer '.$token,
        ])->assertOk()
            ->assertJsonPath('app_id', 'test-app-id')
            ->assertJsonPath('sendbird_user_id', 'osport_'.$user->id)
            ->assertJsonPath('session_token', 'sess-token-xyz')
            ->assertJsonStructure(['app_id', 'sendbird_user_id', 'session_token', 'expires_at']);
    }

    public function test_first_call_provisions_the_sendbird_account(): void
    {
        $this->fakeSendbird();
        $user = User::factory()->create();
        $token = $user->createToken('auth')->plainTextToken;

        $this->assertDatabaseMissing('sendbird_accounts', ['user_id' => $user->id]);

        $this->postJson('/api/v1/auth/sendbird/session-token', [], [
            'Authorization' => 'Bearer '.$token,
        ])->assertOk();

        $this->assertDatabaseHas('sendbird_accounts', [
            'user_id' => $user->id,
            'sendbird_user_id' => 'osport_'.$user->id,
        ]);
    }

    public function test_unauthenticated_request_is_rejected(): void
    {
        $this->fakeSendbird();

        $this->postJson('/api/v1/auth/sendbird/session-token')
            ->assertUnauthorized();
    }

    public function test_create_channel_returns_channel_url(): void
    {
        $this->fakeSendbird();
        $user = User::factory()->create();
        $target = User::factory()->create();
        $token = $user->createToken('auth')->plainTextToken;

        $this->postJson('/api/v1/auth/sendbird/channels', [
            'target_user_id' => $target->id,
        ], ['Authorization' => 'Bearer '.$token])
            ->assertOk()
            ->assertJsonPath('channel_url', 'sendbird_group_channel_42');
    }

    public function test_create_channel_rejects_self_target(): void
    {
        $this->fakeSendbird();
        $user = User::factory()->create();
        $token = $user->createToken('auth')->plainTextToken;

        $this->postJson('/api/v1/auth/sendbird/channels', [
            'target_user_id' => $user->id,
        ], ['Authorization' => 'Bearer '.$token])
            ->assertStatus(422);
    }

    public function test_create_channel_rejects_unknown_target(): void
    {
        $this->fakeSendbird();
        $user = User::factory()->create();
        $token = $user->createToken('auth')->plainTextToken;

        $this->postJson('/api/v1/auth/sendbird/channels', [
            'target_user_id' => 999999,
        ], ['Authorization' => 'Bearer '.$token])
            ->assertStatus(422);
    }
}
