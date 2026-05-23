<?php

namespace Tests\Feature\Api\V1\Account;

use App\Models\User;
use App\Services\Account\NotificationPreferencesService;
use App\Support\UserProfileLocation;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class NotificationPreferencesTest extends TestCase
{
    use LazilyRefreshDatabase;

    private function userWithProfile(): User
    {
        $user = User::factory()->create();
        DB::table('user_profiles')->insert(array_merge([
            'user_id' => $user->id,
            'display_name' => 'NP User',
            'handle' => 'np_user_'.$user->id,
            'created_at' => now(),
            'updated_at' => now(),
        ], UserProfileLocation::columnsFromLatLng(null, null)));

        return $user;
    }

    public function test_show_returns_defaults_when_null(): void
    {
        $user = $this->userWithProfile();
        $token = $user->createToken('auth')->plainTextToken;

        $this->getJson('/api/v1/auth/notification-preferences', [
            'Authorization' => 'Bearer '.$token,
        ])->assertOk()
            ->assertJsonPath('preferences.channels.push', true)
            ->assertJsonPath('preferences.channels.sms', false)
            ->assertJsonPath('preferences.social.mentions', true)
            ->assertJsonPath('preferences.messaging.media', false);
    }

    public function test_update_persists_partial_patch(): void
    {
        $user = $this->userWithProfile();
        $token = $user->createToken('auth')->plainTextToken;

        $this->patchJson('/api/v1/auth/notification-preferences', [
            'social' => ['follow' => false, 'likes' => false],
            'channels' => ['sms' => true],
        ], [
            'Authorization' => 'Bearer '.$token,
        ])->assertOk()
            ->assertJsonPath('preferences.social.follow', false)
            ->assertJsonPath('preferences.social.likes', false)
            ->assertJsonPath('preferences.social.mentions', true) // inchangé
            ->assertJsonPath('preferences.channels.sms', true);
    }

    public function test_should_send_respects_section_off(): void
    {
        $user = $this->userWithProfile();
        $service = app(NotificationPreferencesService::class);

        $service->update($user->id, ['social' => ['follow' => false]]);

        $this->assertFalse($service->shouldSend($user->id, 'social', 'follow', 'push'));
        $this->assertTrue($service->shouldSend($user->id, 'social', 'mentions', 'push'));
    }

    public function test_should_send_respects_channel_off(): void
    {
        $user = $this->userWithProfile();
        $service = app(NotificationPreferencesService::class);

        $service->update($user->id, ['channels' => ['push' => false]]);

        $this->assertFalse($service->shouldSend($user->id, 'social', 'follow', 'push'));
        $this->assertFalse($service->shouldSend($user->id, 'matches', 'requests', 'push'));
    }
}
