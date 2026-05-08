<?php

namespace Tests\Feature\Api\V1\Teams;

use App\Models\User;
use Carbon\CarbonImmutable;
use Database\Seeders\SportsSeeder;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class TeamSeasonStatsApiTest extends TestCase
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
     * @param  array{name: string, sport_id: int, logo_url?: string|null, creator_id?: int|null}  $attributes
     */
    private function createTeam(array $attributes): int
    {
        return (int) DB::table('teams')->insertGetId([
            'creator_id' => $attributes['creator_id'] ?? User::factory()->create()->id,
            'sport_id' => $attributes['sport_id'],
            'name' => $attributes['name'],
            'slug' => 'ss-'.uniqid(),
            'competition_type' => 'leisure',
            'skill_level' => null,
            'description' => null,
            'hq_city' => null,
            'hq_latitude' => null,
            'hq_longitude' => null,
            'cover_image_url' => null,
            'logo_url' => $attributes['logo_url'] ?? null,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    private function insertStats(int $teamId, int $sportId, int $points, int $victories, int $draws, int $defeats, ?CarbonImmutable $createdAt = null): void
    {
        $createdAt ??= CarbonImmutable::now();

        DB::table('stats')->insert([
            'team_id' => $teamId,
            'sport_id' => $sportId,
            'victory_count' => $victories,
            'draw_count' => $draws,
            'defeat_count' => $defeats,
            'point_count' => $points,
            'created_at' => $createdAt->toDateTimeString(),
            'updated_at' => $createdAt->toDateTimeString(),
        ]);
    }

    public function test_unauthenticated_user_cannot_access_season_stats(): void
    {
        $sportId = $this->sportIdBySlug('football');
        $teamId = $this->createTeam(['name' => 'No Auth', 'sport_id' => $sportId]);

        $this->getJson('/api/v1/auth/teams/'.$teamId.'/season-stats')
            ->assertUnauthorized();
    }

    public function test_returns_season_totals_for_current_year(): void
    {
        $sportId = $this->sportIdBySlug('football');
        /** @var User $user */
        $user = User::factory()->create();
        $teamId = $this->createTeam(['name' => 'Dashboard Team', 'sport_id' => $sportId]);

        $nowYear = (int) CarbonImmutable::now()->year;
        $ref = CarbonImmutable::create($nowYear, 6, 15);
        $this->insertStats($teamId, $sportId, 26, 8, 2, 2, $ref);

        $response = $this->actingAs($user, 'sanctum')
            ->getJson('/api/v1/auth/teams/'.$teamId.'/season-stats')
            ->assertOk();

        $response->assertJsonPath('data.team_id', $teamId);
        $response->assertJsonPath('data.sport_id', $sportId);
        $response->assertJsonPath('data.year', $nowYear);
        $response->assertJsonPath('data.season_key', (string) $nowYear);
        $response->assertJsonPath('data.played', 12);
        $response->assertJsonPath('data.won', 8);
        $response->assertJsonPath('data.draw', 2);
        $response->assertJsonPath('data.lost', 2);
        $response->assertJsonPath('data.point_count', 26);
    }

    public function test_year_query_limits_stats_to_that_season_window(): void
    {
        $sportId = $this->sportIdBySlug('football');
        /** @var User $user */
        $user = User::factory()->create();
        $teamId = $this->createTeam(['name' => 'Year Split', 'sport_id' => $sportId]);

        $this->insertStats($teamId, $sportId, 10, 3, 1, 1, CarbonImmutable::create(2024, 3, 1));
        $this->insertStats($teamId, $sportId, 99, 16, 2, 1, CarbonImmutable::create(2026, 3, 1));

        $this->actingAs($user, 'sanctum')
            ->getJson('/api/v1/auth/teams/'.$teamId.'/season-stats?year=2024')
            ->assertOk()
            ->assertJsonPath('data.year', 2024)
            ->assertJsonPath('data.played', 5)
            ->assertJsonPath('data.won', 3)
            ->assertJsonPath('data.draw', 1)
            ->assertJsonPath('data.lost', 1)
            ->assertJsonPath('data.point_count', 10);
    }

    public function test_sums_multiple_stats_rows_in_same_season(): void
    {
        $sportId = $this->sportIdBySlug('football');
        /** @var User $user */
        $user = User::factory()->create();
        $teamId = $this->createTeam(['name' => 'Multi Row', 'sport_id' => $sportId]);

        $this->insertStats($teamId, $sportId, 6, 2, 0, 0, CarbonImmutable::create(2025, 2, 1));
        $this->insertStats($teamId, $sportId, 9, 3, 1, 1, CarbonImmutable::create(2025, 8, 1));

        $this->actingAs($user, 'sanctum')
            ->getJson('/api/v1/auth/teams/'.$teamId.'/season-stats?year=2025')
            ->assertOk()
            ->assertJsonPath('data.played', 7)
            ->assertJsonPath('data.won', 5)
            ->assertJsonPath('data.draw', 1)
            ->assertJsonPath('data.lost', 1)
            ->assertJsonPath('data.point_count', 15);
    }

    public function test_returns_zeros_when_no_stats_in_season(): void
    {
        $sportId = $this->sportIdBySlug('football');
        /** @var User $user */
        $user = User::factory()->create();
        $teamId = $this->createTeam(['name' => 'Empty Stats', 'sport_id' => $sportId]);

        $this->actingAs($user, 'sanctum')
            ->getJson('/api/v1/auth/teams/'.$teamId.'/season-stats?year=2021')
            ->assertOk()
            ->assertJsonPath('data.played', 0)
            ->assertJsonPath('data.won', 0)
            ->assertJsonPath('data.draw', 0)
            ->assertJsonPath('data.lost', 0)
            ->assertJsonPath('data.point_count', 0);
    }

    public function test_validates_unknown_team_id(): void
    {
        /** @var User $user */
        $user = User::factory()->create();

        $this->actingAs($user, 'sanctum')
            ->getJson('/api/v1/auth/teams/999999/season-stats')
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['team_id']);
    }

    public function test_validates_year_range(): void
    {
        $sportId = $this->sportIdBySlug('football');
        /** @var User $user */
        $user = User::factory()->create();
        $teamId = $this->createTeam(['name' => 'Bounds', 'sport_id' => $sportId]);

        $this->actingAs($user, 'sanctum')
            ->getJson('/api/v1/auth/teams/'.$teamId.'/season-stats?year=1999')
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['year']);
    }
}
