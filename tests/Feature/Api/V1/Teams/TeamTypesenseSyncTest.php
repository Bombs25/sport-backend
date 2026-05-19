<?php

namespace Tests\Feature\Api\V1\Teams;

use App\Models\User;
use App\Services\Search\TypesenseTeamService;
use Database\Seeders\SportsSeeder;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Tests\TestCase;

class TeamTypesenseSyncTest extends TestCase
{
    use LazilyRefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(SportsSeeder::class);
    }

    private function sportIdBySlug(string $slug): int
    {
        $id = DB::table('sports')->where('slug', $slug)->value('id');
        $this->assertNotNull($id);

        return (int) $id;
    }

    private function grantActiveSubscription(User $user): void
    {
        $type = (string) config('billing.subscription_type');

        DB::table('subscriptions')->updateOrInsert(
            ['user_id' => $user->id, 'type' => $type],
            [
                'stripe_id' => 'sub_test_'.Str::random(12),
                'stripe_status' => 'active',
                'stripe_price' => 'price_test',
                'quantity' => 1,
                'trial_ends_at' => null,
                'ends_at' => null,
                'created_at' => now(),
                'updated_at' => now(),
            ]
        );
    }

    public function test_delete_team_calls_typesense_delete(): void
    {
        $user = User::factory()->create();
        $this->grantActiveSubscription($user);
        $token = $user->createToken('auth')->plainTextToken;
        $sportId = $this->sportIdBySlug('football');

        $teamId = (int) DB::table('teams')->insertGetId([
            'creator_id' => $user->id,
            'sport_id' => $sportId,
            'name' => 'Typesense Delete FC',
            'slug' => 'typesense-delete-fc',
            'competition_type' => 'leisure',
            'skill_level' => null,
            'description' => null,
            'hq_city' => 'Paris',
            'hq_latitude' => 48.8566,
            'hq_longitude' => 2.3522,
            'cover_image_url' => null,
            'logo_url' => null,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::table('team_members')->insert([
            'team_id' => $teamId,
            'user_id' => $user->id,
            'role' => 'captain',
            'status' => 'active',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $this->mock(TypesenseTeamService::class, function ($mock) use ($teamId): void {
            $mock->shouldReceive('deleteTeamFromIndex')
                ->once()
                ->with($teamId);
        });

        $this->deleteJson('/api/v1/auth/teams/'.$teamId, [], [
            'Authorization' => 'Bearer '.$token,
        ])->assertOk();

        $this->assertDatabaseMissing('teams', ['id' => $teamId]);
    }
}
