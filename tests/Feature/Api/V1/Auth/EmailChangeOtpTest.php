<?php

namespace Tests\Feature\Api\V1\Auth;

use App\Models\User;
use App\Notifications\EmailChangeOtpNotification;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class EmailChangeOtpTest extends TestCase
{
    use LazilyRefreshDatabase;

    public function test_request_email_change_sends_otp_to_new_email(): void
    {
        $user = User::factory()->create([
            'email' => 'current@example.com',
        ]);
        $token = $user->createToken('auth')->plainTextToken;

        Notification::fake();

        $this->postJson('/api/v1/auth/email/change/request', [
            'email' => 'new-address@example.com',
        ], [
            'Authorization' => 'Bearer '.$token,
        ])->assertOk()
            ->assertJsonPath('message', __('Un code de confirmation a été envoyé à votre nouvelle adresse e-mail.'));

        Notification::assertSentOnDemand(EmailChangeOtpNotification::class, function (EmailChangeOtpNotification $notification, array $channels, object $notifiable): bool {
            return in_array('mail', $channels, true)
                && isset($notifiable->routes['mail'])
                && $notifiable->routes['mail'] === 'new-address@example.com'
                && strlen($notification->code) === 6
                && ctype_digit($notification->code);
        });
    }

    public function test_verify_email_change_updates_email_and_returns_user_payload(): void
    {
        $user = User::factory()->create([
            'email' => 'before@example.com',
            'email_verified_at' => null,
        ]);
        $token = $user->createToken('auth')->plainTextToken;

        Notification::fake();

        $this->postJson('/api/v1/auth/email/change/request', [
            'email' => 'after@example.com',
        ], [
            'Authorization' => 'Bearer '.$token,
        ])->assertOk();

        $code = null;
        Notification::assertSentOnDemand(EmailChangeOtpNotification::class, function (EmailChangeOtpNotification $notification, array $channels, object $notifiable) use (&$code): bool {
            if (($notifiable->routes['mail'] ?? null) !== 'after@example.com') {
                return false;
            }

            $code = $notification->code;

            return true;
        });

        $this->assertNotNull($code);

        $this->postJson('/api/v1/auth/email/change/verify', [
            'email' => 'after@example.com',
            'code' => $code,
        ], [
            'Authorization' => 'Bearer '.$token,
        ])->assertOk()
            ->assertJsonPath('message', __('Votre adresse e-mail a été mise à jour.'))
            ->assertJsonPath('user.email', 'after@example.com')
            ->assertJsonPath('user.email_verified_at', fn ($v) => $v !== null);
    }

    public function test_verify_email_change_rejects_invalid_code(): void
    {
        $user = User::factory()->create([
            'email' => 'before-invalid@example.com',
        ]);
        $token = $user->createToken('auth')->plainTextToken;

        Notification::fake();

        $this->postJson('/api/v1/auth/email/change/request', [
            'email' => 'after-invalid@example.com',
        ], [
            'Authorization' => 'Bearer '.$token,
        ])->assertOk();

        $this->postJson('/api/v1/auth/email/change/verify', [
            'email' => 'after-invalid@example.com',
            'code' => '000000',
        ], [
            'Authorization' => 'Bearer '.$token,
        ])->assertUnprocessable()
            ->assertJsonValidationErrors(['code']);
    }
}
