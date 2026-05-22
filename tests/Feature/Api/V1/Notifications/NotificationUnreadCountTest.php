<?php

namespace Tests\Feature\Api\V1\Notifications;

use App\Models\User;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Tests\TestCase;

class NotificationUnreadCountTest extends TestCase
{
    use LazilyRefreshDatabase;

    /**
     * Insère une notification « database » brute pour le destinataire donné.
     */
    private function insertNotification(int $userId, ?string $readAt = null): void
    {
        DB::table('notifications')->insert([
            'id' => (string) Str::uuid(),
            'type' => 'App\\Notifications\\FollowNotification',
            'notifiable_type' => 'App\\Models\\User',
            'notifiable_id' => $userId,
            'data' => json_encode(['kind' => 'new_follower', 'message' => 'Test']),
            'read_at' => $readAt,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    public function test_returns_count_of_unread_notifications_only(): void
    {
        $user = User::factory()->create();
        $token = $user->createToken('auth')->plainTextToken;

        // 3 non lues, 2 lues.
        $this->insertNotification($user->id);
        $this->insertNotification($user->id);
        $this->insertNotification($user->id);
        $this->insertNotification($user->id, now()->toDateTimeString());
        $this->insertNotification($user->id, now()->toDateTimeString());

        $this->getJson('/api/v1/auth/notifications/unread-count', [
            'Authorization' => 'Bearer '.$token,
        ])->assertOk()
            ->assertJsonPath('unread_count', 3);
    }

    public function test_does_not_count_other_users_notifications(): void
    {
        $user = User::factory()->create();
        $other = User::factory()->create();
        $token = $user->createToken('auth')->plainTextToken;

        $this->insertNotification($user->id);
        $this->insertNotification($other->id);
        $this->insertNotification($other->id);

        $this->getJson('/api/v1/auth/notifications/unread-count', [
            'Authorization' => 'Bearer '.$token,
        ])->assertOk()
            ->assertJsonPath('unread_count', 1);
    }

    public function test_returns_zero_when_no_unread_notifications(): void
    {
        $user = User::factory()->create();
        $token = $user->createToken('auth')->plainTextToken;

        $this->insertNotification($user->id, now()->toDateTimeString());

        $this->getJson('/api/v1/auth/notifications/unread-count', [
            'Authorization' => 'Bearer '.$token,
        ])->assertOk()
            ->assertJsonPath('unread_count', 0);
    }

    public function test_requires_authentication(): void
    {
        $this->getJson('/api/v1/auth/notifications/unread-count')
            ->assertUnauthorized();
    }
}
