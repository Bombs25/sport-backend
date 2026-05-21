<?php

namespace Tests\Unit\Services\Notifications;

use App\Models\User;
use App\Services\Notifications\ExpoPushService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class ExpoPushServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_ticket_error_does_not_throw_and_clears_unregistered_token(): void
    {
        $invalidToken = 'ExponentPushToken[invalidOcc2LaRKqNn45v8I]';

        User::factory()->create([
            'fcm_token' => $invalidToken,
        ]);

        Http::fake([
            'exp.host/*' => Http::response([
                'data' => [
                    [
                        'status' => 'error',
                        'message' => 'is not a valid Expo push token',
                        'details' => [
                            'error' => 'DeviceNotRegistered',
                            'expoPushToken' => $invalidToken,
                        ],
                    ],
                ],
            ], 200),
        ]);

        $service = app(ExpoPushService::class);

        $service->send([$invalidToken], 'Titre', 'Corps');

        Http::assertSentCount(1);
        $this->assertDatabaseHas('users', [
            'fcm_token' => null,
        ]);
    }

    public function test_demo_token_skipped_in_local_without_http_call(): void
    {
        $this->app->detectEnvironment(fn () => 'local');

        Http::fake();

        $user = User::factory()->create([
            'fcm_token' => 'ExponentPushToken[demoOcc2LaRKqNn45v8I]',
        ]);

        $service = app(ExpoPushService::class);
        $service->send(User::expoPushTokensFrom([$user]), 'Titre', 'Corps');

        Http::assertNothingSent();
    }
}
