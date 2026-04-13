<?php

namespace Tests\Feature\Api\V1\Auth;

use App\Models\User;
use App\Notifications\PasswordResetCodeNotification;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class ForgotPasswordFlowTest extends TestCase
{
    use LazilyRefreshDatabase;

    private function oldPassword(): string
    {
        return 'Str0ng!Old';
    }

    private function newPassword(): string
    {
        return 'Str0ng!New1';
    }

    public function test_forgot_password_sends_mail_when_user_exists(): void
    {
        Notification::fake();

        $user = User::factory()->create([
            'email' => 'reset-flow@example.com',
            'password' => $this->oldPassword(),
        ]);

        $this->postJson('/api/v1/auth/forgot-password', [
            'email' => 'reset-flow@example.com',
        ])->assertOk()
            ->assertJsonPath('message', __('Si un compte est associé à cette adresse, un code de réinitialisation vient d’y être envoyé.'));

        Notification::assertSentTo($user, PasswordResetCodeNotification::class, function (PasswordResetCodeNotification $n): bool {
            return strlen($n->code) === 6 && ctype_digit($n->code);
        });
    }

    public function test_forgot_password_same_message_when_user_missing(): void
    {
        Notification::fake();

        $this->postJson('/api/v1/auth/forgot-password', [
            'email' => 'nobody-here@example.com',
        ])->assertOk()
            ->assertJsonPath('message', __('Si un compte est associé à cette adresse, un code de réinitialisation vient d’y être envoyé.'));

        Notification::assertNothingSent();
    }

    public function test_reset_password_updates_password_revokes_tokens_and_allows_login(): void
    {
        $oldPassword = $this->oldPassword();
        $newPassword = $this->newPassword();

        $user = User::factory()->create([
            'email' => 'token-revoke@example.com',
            'password' => $oldPassword,
        ]);
        $oldToken = $user->createToken('auth')->plainTextToken;

        Notification::fake();
        $this->postJson('/api/v1/auth/forgot-password', [
            'email' => 'token-revoke@example.com',
        ])->assertOk();

        $code = null;
        Notification::assertSentTo($user, PasswordResetCodeNotification::class, function (PasswordResetCodeNotification $n) use (&$code): bool {
            $code = $n->code;

            return true;
        });
        $this->assertNotNull($code);

        $this->postJson('/api/v1/auth/forgot-password/reset', [
            'email' => 'token-revoke@example.com',
            'code' => $code,
            'password' => $newPassword,
            'password_confirmation' => $newPassword,
        ])->assertOk()
            ->assertJsonPath('message', __('Votre mot de passe a été mis à jour. Vous pouvez vous connecter.'));

        $this->getJson('/api/v1/auth/user', [
            'Authorization' => 'Bearer '.$oldToken,
        ])->assertUnauthorized();

        $this->postJson('/api/v1/auth/login', [
            'email' => 'token-revoke@example.com',
            'password' => $newPassword,
        ])->assertOk();
    }

    public function test_reset_password_update_alias_accepts_passwordconfirmation_key(): void
    {
        $oldPassword = $this->oldPassword();
        $newPassword = $this->newPassword();

        $user = User::factory()->create([
            'email' => 'alias-update@example.com',
            'password' => $oldPassword,
        ]);

        Notification::fake();
        $this->postJson('/api/v1/auth/forgot-password', [
            'email' => 'alias-update@example.com',
        ])->assertOk();

        $code = null;
        Notification::assertSentTo($user, PasswordResetCodeNotification::class, function (PasswordResetCodeNotification $n) use (&$code): bool {
            $code = $n->code;

            return true;
        });

        $this->postJson('/api/v1/auth/forgot-password/update', [
            'email' => 'alias-update@example.com',
            'code' => $code,
            'password' => $newPassword,
            'passwordconfirmation' => $newPassword,
        ])->assertOk()
            ->assertJsonPath('message', __('Votre mot de passe a été mis à jour. Vous pouvez vous connecter.'));
    }

    public function test_reset_password_rejects_invalid_code(): void
    {
        $user = User::factory()->create([
            'email' => 'bad-code@example.com',
            'password' => $this->oldPassword(),
        ]);

        Notification::fake();
        $this->postJson('/api/v1/auth/forgot-password', [
            'email' => 'bad-code@example.com',
        ])->assertOk();

        Notification::assertSentTo($user, PasswordResetCodeNotification::class);

        $this->postJson('/api/v1/auth/forgot-password/reset', [
            'email' => 'bad-code@example.com',
            'code' => '000000',
            'password' => $this->newPassword(),
            'password_confirmation' => $this->newPassword(),
        ])->assertUnprocessable()
            ->assertJsonValidationErrors(['code']);
    }

    public function test_reset_password_rejects_same_as_old_password(): void
    {
        $password = $this->oldPassword();

        $user = User::factory()->create([
            'email' => 'same-pass@example.com',
            'password' => $password,
        ]);

        Notification::fake();
        $this->postJson('/api/v1/auth/forgot-password', [
            'email' => 'same-pass@example.com',
        ])->assertOk();

        $code = null;
        Notification::assertSentTo($user, PasswordResetCodeNotification::class, function (PasswordResetCodeNotification $n) use (&$code): bool {
            $code = $n->code;

            return true;
        });

        $this->postJson('/api/v1/auth/forgot-password/reset', [
            'email' => 'same-pass@example.com',
            'code' => $code,
            'password' => $password,
            'password_confirmation' => $password,
        ])->assertUnprocessable()
            ->assertJsonValidationErrors(['password']);
    }
}
