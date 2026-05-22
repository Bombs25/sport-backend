<?php

namespace Tests\Feature\Api\V1\Notifications;

use App\Models\User;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Tests\TestCase;

class NotificationMarkReadTest extends TestCase
{
    use LazilyRefreshDatabase;

    /**
     * Insère une notification « database » brute pour le destinataire donné.
     */
    private function insertNotification(int $userId, ?string $readAt = null): string
    {
        $id = (string) Str::uuid();

        DB::table('notifications')->insert([
            'id' => $id,
            'type' => 'App\\Notifications\\FollowNotification',
            'notifiable_type' => 'App\\Models\\User',
            'notifiable_id' => $userId,
            'data' => json_encode(['kind' => 'new_follower', 'message' => 'Test']),
            'read_at' => $readAt,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        return $id;
    }

    public function test_marks_own_unread_notification_as_read(): void
    {
        $user = User::factory()->create();
        $token = $user->createToken('auth')->plainTextToken;
        $notificationId = $this->insertNotification($user->id);

        $this->patchJson("/api/v1/auth/notifications/{$notificationId}/read", [], [
            'Authorization' => 'Bearer '.$token,
        ])->assertOk()
            ->assertJsonPath('id', $notificationId);

        $this->assertNotNull(
            DB::table('notifications')->where('id', $notificationId)->value('read_at'),
        );
    }

    public function test_marking_another_users_notification_returns_404(): void
    {
        $user = User::factory()->create();
        $other = User::factory()->create();
        $token = $user->createToken('auth')->plainTextToken;
        $notificationId = $this->insertNotification($other->id);

        $this->patchJson("/api/v1/auth/notifications/{$notificationId}/read", [], [
            'Authorization' => 'Bearer '.$token,
        ])->assertNotFound();

        $this->assertNull(
            DB::table('notifications')->where('id', $notificationId)->value('read_at'),
        );
    }

    public function test_marking_an_already_read_notification_is_idempotent(): void
    {
        $user = User::factory()->create();
        $token = $user->createToken('auth')->plainTextToken;
        $readAt = now()->subDay();
        $notificationId = $this->insertNotification($user->id, $readAt->toDateTimeString());

        $this->patchJson("/api/v1/auth/notifications/{$notificationId}/read", [], [
            'Authorization' => 'Bearer '.$token,
        ])->assertOk();

        // read_at conservé (pas réécrit) — la notification reste lue.
        $this->assertNotNull(
            DB::table('notifications')->where('id', $notificationId)->value('read_at'),
        );
    }

    public function test_requires_authentication(): void
    {
        $user = User::factory()->create();
        $notificationId = $this->insertNotification($user->id);

        $this->patchJson("/api/v1/auth/notifications/{$notificationId}/read")
            ->assertUnauthorized();
    }
}
