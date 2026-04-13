<?php

namespace Tests\Feature\Api\V1\Register;

use Database\Seeders\SportsSeeder;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class RegisterFlowTest extends TestCase
{
    use LazilyRefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(SportsSeeder::class);
    }

    private function validPassword(): string
    {
        return 'Str0ng!Pass';
    }

    public function test_register_wizard_completes_with_sanctum_token(): void
    {
        $password = $this->validPassword();

        $r = $this->postJson('/api/v1/auth/register/credentials', [
            'email' => 'wizard@example.com',
            'password' => $password,
            'password_confirmation' => $password,
            'accept_terms' => true,
            'given_name' => 'Jean',
            'family_name' => 'Dupont',
            'city' => 'Villeurbanne',
            'latitude' => 45.7640,
            'longitude' => 4.8357,
        ]);

        $r->assertCreated();
        $r->assertJsonPath('user.name', 'Jean Dupont');
        $r->assertJsonPath('user.profile.display_name', 'Jean Dupont');
        $r->assertJsonPath('user.profile.city', 'Villeurbanne');
        $r->assertJsonPath('user.profile.latitude', 45.764);
        $r->assertJsonPath('user.profile.longitude', 4.8357);
        $token = $r->json('token');
        $this->assertNotEmpty($token);

        $this->getJson('/api/v1/auth/register/handle-availability?handle=my_handle', [
            'Authorization' => 'Bearer '.$token,
        ])->assertOk()->assertJsonPath('available', true);

        $this->patchJson('/api/v1/auth/register/location', [
            'city' => 'Lyon',
            'latitude' => 45.7640,
            'longitude' => 4.8357,
            'address_line' => '1 rue de la République',
        ], [
            'Authorization' => 'Bearer '.$token,
        ])->assertOk()->assertJsonPath('user.profile.city', 'Lyon');

        $this->patchJson('/api/v1/auth/register/profile', [
            'given_name' => 'Marie',
            'family_name' => 'Martin',
            'handle' => 'my_handle',
            'birth_date' => '1995-03-20',
        ], [
            'Authorization' => 'Bearer '.$token,
        ])->assertOk()->assertJsonPath('user.profile.handle', 'my_handle')
            ->assertJsonPath('user.name', 'Marie Martin')
            ->assertJsonPath('user.profile.display_name', 'Marie Martin');

        $footballId = (int) DB::table('sports')->where('slug', 'football')->value('id');

        $this->postJson('/api/v1/auth/register/sports', [
            'sport_ids' => [$footballId],
        ], [
            'Authorization' => 'Bearer '.$token,
        ])->assertOk()
            ->assertJsonPath('message', 'Vos sports ont été enregistrés.')
            ->assertJsonPath('user.sports.0.slug', 'football')
            ->assertJsonPath('user.sports.0.is_favorite', true);
    }

    public function test_sports_list_is_public_under_auth_prefix(): void
    {
        $this->getJson('/api/v1/auth/sports')->assertOk()->assertJsonStructure([
            'data' => [
                '*' => ['id', 'name', 'slug', 'practice_type', 'avatar'],
            ],
        ]);
    }

    public function test_register_credentials_rejects_duplicate_email_with_clear_message(): void
    {
        $password = $this->validPassword();
        $email = 'already@example.com';

        $this->postJson('/api/v1/auth/register/credentials', [
            'email' => $email,
            'password' => $password,
            'password_confirmation' => $password,
            'accept_terms' => true,
            'given_name' => 'A',
            'family_name' => 'B',
            'city' => 'Lyon',
            'latitude' => 45.7640,
            'longitude' => 4.8357,
        ])->assertCreated();

        $this->postJson('/api/v1/auth/register/credentials', [
            'email' => $email,
            'password' => $password,
            'password_confirmation' => $password,
            'accept_terms' => true,
            'given_name' => 'C',
            'family_name' => 'D',
            'city' => 'Paris',
            'latitude' => 48.8566,
            'longitude' => 2.3522,
        ])->assertUnprocessable()
            ->assertJsonPath('errors.email.0', 'Un compte existe déjà avec cette adresse e-mail.');
    }

    public function test_register_credentials_accepts_civil_name_from_client(): void
    {
        $password = $this->validPassword();

        $r = $this->postJson('/api/v1/auth/register/credentials', [
            'email' => 'named@example.com',
            'password' => $password,
            'password_confirmation' => $password,
            'accept_terms' => true,
            'given_name' => 'Sophie',
            'family_name' => 'Bernard',
            'city' => 'Paris',
            'latitude' => 48.8566,
            'longitude' => 2.3522,
        ]);

        $r->assertCreated();
        $r->assertJsonPath('user.name', 'Sophie Bernard');
        $r->assertJsonPath('user.profile.display_name', 'Sophie Bernard');
    }
}
