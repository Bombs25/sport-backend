<?php

namespace Tests\Feature\Api\V1\Teams;

use App\Models\User;
use Carbon\CarbonImmutable;
use Database\Seeders\SportsSeeder;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class TeamRankingListApiTest extends TestCase
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
            'slug' => 'rk-'.uniqid(),
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

    public function test_unauthenticated_user_cannot_access_rankings(): void
    {
        $sportId = $this->sportIdBySlug('football');

        $this->getJson('/api/v1/auth/teams/rankings?sport_id='.$sportId)
            ->assertUnauthorized();
    }

    public function test_returns_rankings_ordered_by_points_for_current_year(): void
    {
        $sportId = $this->sportIdBySlug('football');
        /** @var User $user */
        $user = User::factory()->create();

        $teamA = $this->createTeam(['name' => 'Paris FC', 'sport_id' => $sportId, 'logo_url' => 'paris.png']);
        $teamB = $this->createTeam(['name' => 'Lyon Juniors', 'sport_id' => $sportId]);
        $teamC = $this->createTeam(['name' => 'Saint-Etienne', 'sport_id' => $sportId]);

        $this->insertStats($teamA, $sportId, 48, 15, 3, 2);
        $this->insertStats($teamB, $sportId, 45, 14, 3, 3);
        $this->insertStats($teamC, $sportId, 42, 13, 3, 4);

        $response = $this->actingAs($user, 'sanctum')
            ->getJson('/api/v1/auth/teams/rankings?sport_id='.$sportId)
            ->assertOk();

        $rankings = $response->json('data.rankings');
        $this->assertCount(3, $rankings);

        $this->assertSame(1, $rankings[0]['rank']);
        $this->assertSame($teamA, $rankings[0]['team_id']);
        $this->assertSame('Paris FC', $rankings[0]['team_name']);
        $this->assertSame('paris.png', $rankings[0]['logo_url']);
        $this->assertSame(48, $rankings[0]['point_count']);
        $this->assertFalse($rankings[0]['is_current_user_team']);

        $this->assertSame(2, $rankings[1]['rank']);
        $this->assertSame($teamB, $rankings[1]['team_id']);

        $this->assertSame(3, $rankings[2]['rank']);
        $this->assertSame($teamC, $rankings[2]['team_id']);

        $response->assertJsonPath('data.sport_id', $sportId);
        $response->assertJsonPath('data.year', (int) CarbonImmutable::now()->year);
    }

    public function test_year_filter_excludes_stats_outside_window(): void
    {
        $sportId = $this->sportIdBySlug('football');
        /** @var User $user */
        $user = User::factory()->create();

        $teamA = $this->createTeam(['name' => 'Team 2024', 'sport_id' => $sportId]);
        $teamB = $this->createTeam(['name' => 'Team 2026', 'sport_id' => $sportId]);

        $this->insertStats($teamA, $sportId, 30, 10, 0, 0, CarbonImmutable::create(2024, 6, 1));
        $this->insertStats($teamB, $sportId, 50, 16, 2, 1, CarbonImmutable::create(2026, 6, 1));

        $response = $this->actingAs($user, 'sanctum')
            ->getJson('/api/v1/auth/teams/rankings?sport_id='.$sportId.'&year=2024')
            ->assertOk();

        $rankings = $response->json('data.rankings');
        $this->assertCount(1, $rankings);
        $this->assertSame($teamA, $rankings[0]['team_id']);
        $response->assertJsonPath('data.year', 2024);
        $response->assertJsonPath('data.season_key', '2024');
    }

    public function test_flags_user_team_for_highlight(): void
    {
        $sportId = $this->sportIdBySlug('football');
        /** @var User $user */
        $user = User::factory()->create();

        $myTeamId = $this->createTeam(['name' => 'My Team', 'sport_id' => $sportId, 'creator_id' => $user->id]);
        $otherTeamId = $this->createTeam(['name' => 'Other Team', 'sport_id' => $sportId]);

        DB::table('team_members')->insert([
            'team_id' => $myTeamId,
            'user_id' => $user->id,
            'role' => 'captain',
            'status' => 'active',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $this->insertStats($otherTeamId, $sportId, 50, 16, 2, 1);
        $this->insertStats($myTeamId, $sportId, 30, 10, 0, 0);

        $response = $this->actingAs($user, 'sanctum')
            ->getJson('/api/v1/auth/teams/rankings?sport_id='.$sportId)
            ->assertOk();

        $rankings = $response->json('data.rankings');
        $this->assertCount(2, $rankings);

        $byTeamId = [];
        foreach ($rankings as $row) {
            $byTeamId[(int) $row['team_id']] = $row;
        }

        $this->assertTrue($byTeamId[$myTeamId]['is_current_user_team']);
        $this->assertFalse($byTeamId[$otherTeamId]['is_current_user_team']);
    }

    public function test_validates_required_sport_id(): void
    {
        /** @var User $user */
        $user = User::factory()->create();

        $this->actingAs($user, 'sanctum')
            ->getJson('/api/v1/auth/teams/rankings')
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['sport_id']);
    }

    public function test_validates_unknown_sport_id(): void
    {
        /** @var User $user */
        $user = User::factory()->create();

        $this->actingAs($user, 'sanctum')
            ->getJson('/api/v1/auth/teams/rankings?sport_id=999999')
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['sport_id']);
    }

    public function test_excludes_teams_from_other_sports(): void
    {
        $footballId = $this->sportIdBySlug('football');
        $tennisId = $this->sportIdBySlug('tennis');
        /** @var User $user */
        $user = User::factory()->create();

        $footballTeam = $this->createTeam(['name' => 'Football Team', 'sport_id' => $footballId]);
        $tennisTeam = $this->createTeam(['name' => 'Tennis Team', 'sport_id' => $tennisId]);

        $this->insertStats($footballTeam, $footballId, 30, 10, 0, 0);
        $this->insertStats($tennisTeam, $tennisId, 50, 16, 2, 1);

        $response = $this->actingAs($user, 'sanctum')
            ->getJson('/api/v1/auth/teams/rankings?sport_id='.$footballId)
            ->assertOk();

        $rankings = $response->json('data.rankings');
        $this->assertCount(1, $rankings);
        $this->assertSame($footballTeam, $rankings[0]['team_id']);
    }

    public function test_paginates_rankings_with_ten_items_per_page(): void
    {
        $sportId = $this->sportIdBySlug('football');
        /** @var User $user */
        $user = User::factory()->create();

        for ($i = 1; $i <= 12; $i++) {
            $teamId = $this->createTeam(['name' => 'Team '.$i, 'sport_id' => $sportId]);
            $this->insertStats($teamId, $sportId, 100 - $i, 10, 0, 0, CarbonImmutable::create(2026, 6, 1)->addSeconds($i));
        }

        $pageOne = $this->actingAs($user, 'sanctum')
            ->getJson('/api/v1/auth/teams/rankings?sport_id='.$sportId.'&page=1')
            ->assertOk();

        $pageOneRankings = $pageOne->json('data.rankings');
        $this->assertCount(10, $pageOneRankings);
        $pageOne->assertJsonPath('data.pagination.current_page', 1);
        $pageOne->assertJsonPath('data.pagination.per_page', 10);
        $pageOne->assertJsonPath('data.pagination.has_more', true);

        $pageTwo = $this->actingAs($user, 'sanctum')
            ->getJson('/api/v1/auth/teams/rankings?sport_id='.$sportId.'&page=2')
            ->assertOk();

        $pageTwoRankings = $pageTwo->json('data.rankings');
        $this->assertCount(2, $pageTwoRankings);
        $pageTwo->assertJsonPath('data.pagination.current_page', 2);
        $pageTwo->assertJsonPath('data.pagination.per_page', 10);
        $pageTwo->assertJsonPath('data.pagination.has_more', false);
    }

    public function test_validates_page_minimum_value(): void
    {
        $sportId = $this->sportIdBySlug('football');
        /** @var User $user */
        $user = User::factory()->create();

        $this->actingAs($user, 'sanctum')
            ->getJson('/api/v1/auth/teams/rankings?sport_id='.$sportId.'&page=0')
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['page']);
    }

    public function test_returns_available_years_for_ranking_dropdown(): void
    {
        $sportId = $this->sportIdBySlug('football');
        /** @var User $user */
        $user = User::factory()->create();

        $teamA = $this->createTeam(['name' => 'Years Team A', 'sport_id' => $sportId]);
        $teamB = $this->createTeam(['name' => 'Years Team B', 'sport_id' => $sportId]);

        $this->insertStats($teamA, $sportId, 20, 6, 2, 3, CarbonImmutable::create(2026, 3, 1));
        $this->insertStats($teamB, $sportId, 30, 9, 3, 1, CarbonImmutable::create(2024, 3, 1));

        $this->actingAs($user, 'sanctum')
            ->getJson('/api/v1/auth/teams/rankings/years?sport_id='.$sportId)
            ->assertOk()
            ->assertJsonPath('data.sport_id', $sportId)
            ->assertJsonPath('data.years.0', 2026)
            ->assertJsonPath('data.years.1', 2024);
    }

    public function test_ranking_years_endpoint_validates_sport_id(): void
    {
        /** @var User $user */
        $user = User::factory()->create();

        $this->actingAs($user, 'sanctum')
            ->getJson('/api/v1/auth/teams/rankings/years')
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['sport_id']);
    }
}
