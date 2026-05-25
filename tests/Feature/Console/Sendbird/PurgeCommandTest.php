<?php

namespace Tests\Feature\Console\Sendbird;

use App\Models\User;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class PurgeCommandTest extends TestCase
{
    use LazilyRefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        config()->set('services.sendbird', [
            'app_id' => 'test-app-id',
            'api_token' => 'test-api-token',
            'base_url' => 'https://api-test.sendbird.com',
        ]);
    }

    public function test_refuses_to_run_in_production_without_force(): void
    {
        $this->app->detectEnvironment(fn () => 'production');

        Http::fake();

        $this->artisan('sendbird:purge')
            ->expectsOutputToContain('production')
            ->assertExitCode(1);

        Http::assertNothingSent();
    }

    public function test_purges_channels_users_and_local_accounts(): void
    {
        $user = User::factory()->create();
        DB::table('sendbird_accounts')->insert([
            'user_id' => $user->id,
            'sendbird_user_id' => 'osport_'.$user->id,
            'sendbird_synced_at' => now(),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        Http::fake([
            // Patterns spécifiques avant patterns greedy : `*` matche aussi le `/`.
            'api-test.sendbird.com/v3/group_channels/*' => Http::response([], 200),
            'api-test.sendbird.com/v3/group_channels*' => Http::response(
                ['channels' => [['channel_url' => 'chan_a'], ['channel_url' => 'chan_b']], 'next' => null]
            ),
            'api-test.sendbird.com/v3/users/*' => Http::response([], 200),
            'api-test.sendbird.com/v3/users*' => Http::response(
                ['users' => [['user_id' => 'osport_1'], ['user_id' => 'osport_2']], 'next' => null]
            ),
        ]);

        $this->artisan('sendbird:purge')
            ->expectsConfirmation('Ceci va SUPPRIMER tous les canaux et utilisateurs Sendbird. Continuer ?', 'yes')
            ->expectsOutputToContain('canaux: 2, users: 2, sendbird_accounts: 1')
            ->assertExitCode(0);

        $this->assertSame(0, DB::table('sendbird_accounts')->count());

        Http::assertSent(fn (Request $r) => $r->method() === 'DELETE' && str_ends_with($r->url(), '/v3/group_channels/chan_a'));
        Http::assertSent(fn (Request $r) => $r->method() === 'DELETE' && str_ends_with($r->url(), '/v3/group_channels/chan_b'));
        Http::assertSent(fn (Request $r) => $r->method() === 'DELETE' && str_ends_with($r->url(), '/v3/users/osport_1'));
        Http::assertSent(fn (Request $r) => $r->method() === 'DELETE' && str_ends_with($r->url(), '/v3/users/osport_2'));
    }

    public function test_user_can_cancel_at_confirmation(): void
    {
        Http::fake();

        $this->artisan('sendbird:purge')
            ->expectsConfirmation('Ceci va SUPPRIMER tous les canaux et utilisateurs Sendbird. Continuer ?', 'no')
            ->expectsOutputToContain('Annulé.')
            ->assertExitCode(0);

        Http::assertNothingSent();
    }

    public function test_idempotent_when_channel_already_deleted(): void
    {
        Http::fake([
            'api-test.sendbird.com/v3/group_channels/*' => Http::response(['code' => 400201, 'message' => 'not found'], 404),
            'api-test.sendbird.com/v3/group_channels*' => Http::response(
                ['channels' => [['channel_url' => 'gone']], 'next' => null]
            ),
            'api-test.sendbird.com/v3/users*' => Http::response(['users' => [], 'next' => null]),
        ]);

        $this->artisan('sendbird:purge')
            ->expectsConfirmation('Ceci va SUPPRIMER tous les canaux et utilisateurs Sendbird. Continuer ?', 'yes')
            ->assertExitCode(0);
    }
}
