<?php

namespace Tests\Feature\Api\V1\Teams;

use App\Models\User;
use Carbon\CarbonImmutable;
use Database\Seeders\PalmaresFromStatsSeeder;
use Database\Seeders\SportsSeeder;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class TeamPalmaresApiTest extends TestCase
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

    /**
     * @param  array{name: string, sport_id: int}  $attributes
     */
    private function createTeam(array $attributes): int
    {
        return (int) DB::table('teams')->insertGetId([
            'creator_id' => User::factory()->create()->id,
            'sport_id' => $attributes['sport_id'],
            'name' => $attributes['name'],
            'slug' => 'pal-'.uniqid('', true),
            'competition_type' => 'leisure',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    private function insertStats(int $teamId, int $sportId, int $points, CarbonImmutable $createdAt): void
    {
        DB::table('stats')->insert([
            'team_id' => $teamId,
            'sport_id' => $sportId,
            'victory_count' => 5,
            'draw_count' => 1,
            'defeat_count' => 1,
            'point_count' => $points,
            'created_at' => $createdAt->toDateTimeString(),
            'updated_at' => $createdAt->toDateTimeString(),
        ]);
    }

    public function test_unauthenticated_user_cannot_access_palmares(): void
    {
        $sportId = $this->sportIdBySlug('football');
        $teamId = $this->createTeam(['name' => 'No Auth Palmares', 'sport_id' => $sportId]);

        $this->getJson('/api/v1/auth/teams/'.$teamId.'/palmares')
            ->assertUnauthorized();
    }

    public function test_returns_empty_palmares_when_no_trophies(): void
    {
        $sportId = $this->sportIdBySlug('football');
        $user = User::factory()->create();
        $teamId = $this->createTeam(['name' => 'Empty Palmares', 'sport_id' => $sportId]);

        $this->actingAs($user, 'sanctum')
            ->getJson('/api/v1/auth/teams/'.$teamId.'/palmares')
            ->assertOk()
            ->assertJsonPath('data.team_id', $teamId)
            ->assertJsonPath('data.palmares', []);
    }

    public function test_returns_palmares_after_seeder(): void
    {
        $sportId = $this->sportIdBySlug('football');
        $user = User::factory()->create();
        $topTeamId = $this->createTeam(['name' => 'Champion', 'sport_id' => $sportId]);
        $secondTeamId = $this->createTeam(['name' => 'Runner Up', 'sport_id' => $sportId]);
        $thirdTeamId = $this->createTeam(['name' => 'Third', 'sport_id' => $sportId]);

        $year = 2024;
        $ref = CarbonImmutable::create($year, 6, 1);
        $this->insertStats($topTeamId, $sportId, 50, $ref);
        $this->insertStats($secondTeamId, $sportId, 40, $ref);
        $this->insertStats($thirdTeamId, $sportId, 30, $ref);

        $this->seed(PalmaresFromStatsSeeder::class);

        $response = $this->actingAs($user, 'sanctum')
            ->getJson('/api/v1/auth/teams/'.$topTeamId.'/palmares')
            ->assertOk();

        $palmares = $response->json('data.palmares');
        $this->assertIsArray($palmares);
        $this->assertCount(1, $palmares);
        $this->assertSame(1, $palmares[0]['rank']);
        $this->assertSame('gold', $palmares[0]['trophy']);
        $this->assertSame($topTeamId, $palmares[0]['team_id']);
        $this->assertIsArray($palmares[0]['season_years']);
        $this->assertSame('2024-01-01', $palmares[0]['season_years'][0]['start_date']);
    }

    public function test_validates_unknown_team_id(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user, 'sanctum')
            ->getJson('/api/v1/auth/teams/999999/palmares')
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['team_id']);
    }
}
