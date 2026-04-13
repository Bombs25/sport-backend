<?php

namespace Tests\Feature\Api\V1\Auth;

use App\Models\User;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Tests\TestCase;

class LoginTest extends TestCase
{
    use LazilyRefreshDatabase;

    private function validPassword(): string
    {
        return 'Str0ng!Pass';
    }

    public function test_login_returns_bearer_token_and_user_payload(): void
    {
        $password = $this->validPassword();

        User::factory()->create([
            'email' => 'login@example.com',
            'password' => $password,
        ]);

        $this->postJson('/api/v1/auth/login', [
            'email' => 'login@example.com',
            'password' => $password,
        ])->assertOk()
            ->assertJsonStructure(['message', 'token', 'token_type', 'user'])
            ->assertJsonPath('message', __('Connexion réussie.'))
            ->assertJsonPath('user.email', 'login@example.com');
    }

    public function test_login_matches_email_case_insensitively(): void
    {
        $password = $this->validPassword();

        User::factory()->create([
            'email' => 'MixedCase@Example.com',
            'password' => $password,
        ]);

        $this->postJson('/api/v1/auth/login', [
            'email' => 'mixedcase@example.com',
            'password' => $password,
        ])->assertOk()
            ->assertJsonPath('user.email', 'MixedCase@Example.com');
    }

    public function test_login_accepts_accept_terms_when_sent(): void
    {
        $password = $this->validPassword();

        User::factory()->create([
            'email' => 'terms@example.com',
            'password' => $password,
        ]);

        $this->postJson('/api/v1/auth/login', [
            'email' => 'terms@example.com',
            'password' => $password,
            'accept_terms' => true,
        ])->assertOk();
    }

    public function test_login_rejects_when_accept_terms_is_false(): void
    {
        $password = $this->validPassword();

        User::factory()->create([
            'email' => 'noterms@example.com',
            'password' => $password,
        ]);

        $this->postJson('/api/v1/auth/login', [
            'email' => 'noterms@example.com',
            'password' => $password,
            'accept_terms' => false,
        ])->assertUnprocessable()
            ->assertJsonValidationErrors(['accept_terms']);
    }

    public function test_login_fails_with_invalid_password(): void
    {
        User::factory()->create([
            'email' => 'u@example.com',
            'password' => $this->validPassword(),
        ]);

        $this->postJson('/api/v1/auth/login', [
            'email' => 'u@example.com',
            'password' => 'wrong-Str0ng!1',
        ])->assertUnprocessable()
            ->assertJsonPath('errors.email.0', __('Identifiants incorrects. Vérifiez votre adresse e-mail et votre mot de passe.'));
    }

    public function test_login_fails_when_email_not_verified(): void
    {
        $password = $this->validPassword();

        User::factory()->unverified()->create([
            'email' => 'unverified@example.com',
            'password' => $password,
        ]);

        $this->postJson('/api/v1/auth/login', [
            'email' => 'unverified@example.com',
            'password' => $password,
        ])->assertUnprocessable()
            ->assertJsonPath('errors.email.0', __('Veuillez vérifier votre adresse e-mail avant de vous connecter.'));
    }
}
