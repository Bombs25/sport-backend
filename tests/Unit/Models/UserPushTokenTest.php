<?php

namespace Tests\Unit\Models;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class UserPushTokenTest extends TestCase
{
    use RefreshDatabase;

    public function test_route_notification_for_fcm_clears_demo_token_in_local(): void
    {
        $this->app->detectEnvironment(fn () => 'local');

        $user = User::factory()->create([
            'fcm_token' => 'ExponentPushToken[demoOcc2LaRKqNn45v8I]',
        ]);

        $this->assertNull($user->routeNotificationForFcm());
        $this->assertDatabaseHas('users', [
            'id' => $user->id,
            'fcm_token' => null,
        ]);
    }

    public function test_expo_push_tokens_from_ignores_invalid_fcm(): void
    {
        User::factory()->create([
            'fcm_token' => 'ExponentPushToken[valid-token]',
        ]);
        User::factory()->create([
            'fcm_token' => 'APA91b-native-fcm-token',
        ]);

        $tokens = User::expoPushTokensFrom(User::query()->get());

        $this->assertSame(['ExponentPushToken[valid-token]'], $tokens);
    }
}
