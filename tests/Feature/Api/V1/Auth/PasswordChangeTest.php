<?php

namespace Tests\Feature\Api\V1\Auth;

use App\Models\User;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Tests\TestCase;

class PasswordChangeTest extends TestCase
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

    public function test_password_change_updates_password_and_allows_login_with_new_password(): void
    {
        $user = User::factory()->create([
            'email' => 'connected-change@example.com',
            'password' => $this->oldPassword(),
        ]);
        $token = $user->createToken('auth')->plainTextToken;

        $this->postJson('/api/v1/auth/password/change', [
            'current_password' => $this->oldPassword(),
            'password' => $this->newPassword(),
            'password_confirmation' => $this->newPassword(),
        ], [
            'Authorization' => 'Bearer '.$token,
        ])->assertOk()
            ->assertJsonPath('message', __('Votre mot de passe a été mis à jour. Veuillez vous reconnecter.'));

        $this->postJson('/api/v1/auth/login', [
            'email' => 'connected-change@example.com',
            'password' => $this->newPassword(),
        ])->assertOk();
    }

    public function test_password_change_rejects_wrong_current_password(): void
    {
        $user = User::factory()->create([
            'password' => $this->oldPassword(),
        ]);
        $token = $user->createToken('auth')->plainTextToken;

        $this->postJson('/api/v1/auth/password/change', [
            'current_password' => 'WrongPass!1',
            'password' => $this->newPassword(),
            'password_confirmation' => $this->newPassword(),
        ], [
            'Authorization' => 'Bearer '.$token,
        ])->assertUnprocessable()
            ->assertJsonValidationErrors(['current_password']);
    }

    public function test_password_change_rejects_same_password_as_current(): void
    {
        $password = $this->oldPassword();

        $user = User::factory()->create([
            'password' => $password,
        ]);
        $token = $user->createToken('auth')->plainTextToken;

        $this->postJson('/api/v1/auth/password/change', [
            'current_password' => $password,
            'password' => $password,
            'password_confirmation' => $password,
        ], [
            'Authorization' => 'Bearer '.$token,
        ])->assertUnprocessable()
            ->assertJsonValidationErrors(['password']);
    }
}
