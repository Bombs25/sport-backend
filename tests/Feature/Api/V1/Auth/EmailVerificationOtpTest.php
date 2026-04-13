<?php

namespace Tests\Feature\Api\V1\Auth;

use App\Models\User;
use App\Notifications\VerifyEmailOtpNotification;
use App\Services\Auth\EmailVerificationOtpService;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class EmailVerificationOtpTest extends TestCase
{
    use LazilyRefreshDatabase;

    private function validPassword(): string
    {
        return 'Str0ng!Pass';
    }

    public function test_email_verify_returns_json_401_when_unauthenticated_without_json_accept_header(): void
    {
        $this->call('POST', '/api/v1/auth/email/verify', [], [], [], [
            'CONTENT_TYPE' => 'application/json',
            'HTTP_ACCEPT' => '*/*',
        ], '{"code":"123456"}')
            ->assertUnauthorized()
            ->assertHeaderContains('content-type', 'application/json');
    }

    public function test_register_credentials_sends_otp_notification(): void
    {
        Notification::fake();

        $password = $this->validPassword();

        $this->postJson('/api/v1/auth/register/credentials', [
            'email' => 'otp-register@example.com',
            'password' => $password,
            'password_confirmation' => $password,
            'accept_terms' => true,
            'given_name' => 'Jean',
            'family_name' => 'Dupont',
            'city' => 'Lyon',
            'latitude' => 45.7640,
            'longitude' => 4.8357,
        ])->assertCreated();

        $user = User::query()->where('email', 'otp-register@example.com')->firstOrFail();

        Notification::assertSentTo($user, VerifyEmailOtpNotification::class, function (VerifyEmailOtpNotification $notification): bool {
            return strlen($notification->code) === 6 && ctype_digit($notification->code);
        });
    }

    public function test_verify_accepts_valid_code_and_marks_email_verified(): void
    {
        $user = User::factory()->unverified()->create([
            'email' => 'verify@example.com',
        ]);
        $token = $user->createToken('auth')->plainTextToken;

        Notification::fake();
        app(EmailVerificationOtpService::class)->sendForUser($user);

        $code = null;
        Notification::assertSentTo($user, VerifyEmailOtpNotification::class, function (VerifyEmailOtpNotification $n) use (&$code): bool {
            $code = $n->code;

            return true;
        });

        $this->assertNotNull($code);

        $this->postJson('/api/v1/auth/email/verify', [
            'code' => $code,
        ], [
            'Authorization' => 'Bearer '.$token,
        ])->assertOk()
            ->assertJsonPath('user.email', 'verify@example.com')
            ->assertJsonPath('user.email_verified_at', fn ($v) => $v !== null);

        $this->assertTrue($user->fresh()->hasVerifiedEmail());
    }

    public function test_verify_rejects_invalid_code(): void
    {
        $user = User::factory()->unverified()->create();
        $token = $user->createToken('auth')->plainTextToken;

        Notification::fake();
        app(EmailVerificationOtpService::class)->sendForUser($user);

        $this->postJson('/api/v1/auth/email/verify', [
            'code' => '000000',
        ], [
            'Authorization' => 'Bearer '.$token,
        ])->assertUnprocessable()
            ->assertJsonValidationErrors(['code']);
    }

    public function test_resend_sends_notification_when_unverified(): void
    {
        $user = User::factory()->unverified()->create();
        $token = $user->createToken('auth')->plainTextToken;

        Notification::fake();

        $this->postJson('/api/v1/auth/email/resend', [], [
            'Authorization' => 'Bearer '.$token,
        ])->assertNoContent();

        Notification::assertSentTo($user, VerifyEmailOtpNotification::class);
    }

    public function test_resend_when_already_verified_returns_message(): void
    {
        $user = User::factory()->create();
        $token = $user->createToken('auth')->plainTextToken;

        Notification::fake();

        $this->postJson('/api/v1/auth/email/resend', [], [
            'Authorization' => 'Bearer '.$token,
        ])->assertOk()
            ->assertJsonPath('message', 'Votre adresse e-mail est déjà vérifiée.');

        Notification::assertNothingSent();
    }
}
