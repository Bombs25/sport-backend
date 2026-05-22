<?php

namespace Tests\Unit\Services\Sendbird;

use App\Models\User;
use App\Services\Sendbird\SendbirdService;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class SendbirdServiceTest extends TestCase
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

    private function service(): SendbirdService
    {
        return $this->app->make(SendbirdService::class);
    }

    public function test_ensure_user_provisions_sendbird_user_and_persists_account(): void
    {
        Http::fake([
            'api-test.sendbird.com/v3/users*' => Http::response([
                'user_id' => 'osport_1',
                'nickname' => 'Test',
            ], 200),
        ]);

        $user = User::factory()->create();

        $sendbirdUserId = $this->service()->ensureUser($user);

        $this->assertSame('osport_'.$user->id, $sendbirdUserId);
        $this->assertDatabaseHas('sendbird_accounts', [
            'user_id' => $user->id,
            'sendbird_user_id' => 'osport_'.$user->id,
        ]);
        Http::assertSentCount(1);
    }

    public function test_ensure_user_is_idempotent(): void
    {
        Http::fake([
            'api-test.sendbird.com/v3/users*' => Http::response(['user_id' => 'osport_1'], 200),
        ]);

        $user = User::factory()->create();

        $this->service()->ensureUser($user);
        $this->service()->ensureUser($user);

        // Une seule ligne `sendbird_accounts` malgré deux appels.
        $this->assertSame(1, DB::table('sendbird_accounts')->where('user_id', $user->id)->count());
    }

    public function test_ensure_user_falls_back_to_creation_when_not_found(): void
    {
        // PUT (mise à jour) échoue en 400201 « User not found » -> bascule sur POST.
        // Le glob `v3/users*` couvre `/v3/users` (POST) et `/v3/users/{id}` (PUT).
        Http::fake([
            'api-test.sendbird.com/v3/users*' => function ($request) {
                if ($request->method() === 'PUT') {
                    return Http::response(['error' => true, 'code' => 400201], 400);
                }

                return Http::response(['user_id' => 'osport_created'], 200);
            },
        ]);

        $user = User::factory()->create();

        $sendbirdUserId = $this->service()->ensureUser($user);

        $this->assertSame('osport_'.$user->id, $sendbirdUserId);
        $this->assertDatabaseHas('sendbird_accounts', ['user_id' => $user->id]);
        // Un PUT (échec) + un POST (création).
        Http::assertSentCount(2);
    }

    public function test_ensure_user_with_session_token_returns_inline_token(): void
    {
        // Sendbird émet le session token inline dans la réponse `POST/PUT /v3/users`
        // quand `issue_session_token` est vrai.
        Http::fake([
            'api-test.sendbird.com/v3/users*' => Http::response([
                'user_id' => 'osport_1',
                'session_tokens' => [
                    ['session_token' => 'sess-token-abc', 'expires_at' => 1893456000000],
                ],
            ], 200),
        ]);

        $user = User::factory()->create();

        $result = $this->service()->ensureUserWithSessionToken($user);

        $this->assertSame('sess-token-abc', $result['token']);
        $this->assertSame(1893456000000, $result['expires_at']);
        $this->assertDatabaseHas('sendbird_accounts', ['user_id' => $user->id]);
    }
}
