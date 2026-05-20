<?php

namespace Tests\Feature\Api\V1\Teams;

use App\Models\User;
use App\Services\Search\TypesenseTeamService;
use Carbon\CarbonImmutable;
use Database\Seeders\SportsSeeder;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class TeamRankingApiTest extends TestCase
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
            'slug' => 'rk-'.uniqid('', true),
            'competition_type' => 'leisure',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    private function insertStats(
        int $teamId,
        int $sportId,
        int $points,
        int $victories,
        int $draws,
        int $defeats,
        CarbonImmutable $createdAt,
    ): void {
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

    public function test_returns_rankings_ordered_by_points(): void
    {
        $sportId = $this->sportIdBySlug('football');
        $user = User::factory()->create();
        $myTeamId = $this->createTeam(['name' => 'My FC', 'sport_id' => $sportId]);
        $otherTeamId = $this->createTeam(['name' => 'Other FC', 'sport_id' => $sportId]);

        $nowYear = (int) CarbonImmutable::now()->year;
        $ref = CarbonImmutable::create($nowYear, 5, 1);
        $this->insertStats($otherTeamId, $sportId, 50, 15, 2, 1, $ref);
        $this->insertStats($myTeamId, $sportId, 30, 9, 3, 2, $ref);

        DB::table('team_members')->insert([
            'team_id' => $myTeamId,
            'user_id' => $user->id,
            'role' => 'captain',
            'status' => 'active',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $response = $this->actingAs($user, 'sanctum')
            ->getJson('/api/v1/auth/teams/rankings?sport_id='.$sportId.'&year='.$nowYear)
            ->assertOk();

        $response->assertJsonPath('data.sport_id', $sportId);
        $response->assertJsonPath('data.year', $nowYear);
        $rankings = $response->json('data.rankings');
        $this->assertIsArray($rankings);
        $this->assertCount(2, $rankings);
        $this->assertSame(1, $rankings[0]['rank']);
        $this->assertSame($otherTeamId, $rankings[0]['team_id']);
        $this->assertFalse($rankings[0]['is_current_user_team']);
        $this->assertSame(2, $rankings[1]['rank']);
        $this->assertSame($myTeamId, $rankings[1]['team_id']);
        $this->assertTrue($rankings[1]['is_current_user_team']);
        $this->assertSame(50, $rankings[0]['point_count']);
        $this->assertSame(15, $rankings[0]['victory_count']);
    }

    public function test_returns_available_years(): void
    {
        $sportId = $this->sportIdBySlug('football');
        $user = User::factory()->create();
        $teamId = $this->createTeam(['name' => 'Years Team', 'sport_id' => $sportId]);

        $this->insertStats($teamId, $sportId, 10, 3, 1, 1, CarbonImmutable::create(2023, 3, 1));
        $this->insertStats($teamId, $sportId, 20, 6, 1, 1, CarbonImmutable::create(2025, 3, 1));

        $this->actingAs($user, 'sanctum')
            ->getJson('/api/v1/auth/teams/rankings/years?sport_id='.$sportId)
            ->assertOk()
            ->assertJsonPath('data.sport_id', $sportId)
            ->assertJsonFragment(['years' => [2025, 2023]]);
    }

    public function test_rankings_pagination_has_more(): void
    {
        $sportId = $this->sportIdBySlug('football');
        $user = User::factory()->create();
        $year = (int) CarbonImmutable::now()->year;
        $ref = CarbonImmutable::create($year, 4, 1);

        for ($i = 0; $i < 12; $i++) {
            $teamId = $this->createTeam(['name' => 'Team '.$i, 'sport_id' => $sportId]);
            $this->insertStats($teamId, $sportId, 100 - $i, 5, 1, 1, $ref);
        }

        $page1 = $this->actingAs($user, 'sanctum')
            ->getJson('/api/v1/auth/teams/rankings?sport_id='.$sportId.'&year='.$year.'&page=1')
            ->assertOk();

        $page1->assertJsonCount(10, 'data.rankings');
        $page1->assertJsonPath('data.pagination.has_more', true);

        $page2 = $this->actingAs($user, 'sanctum')
            ->getJson('/api/v1/auth/teams/rankings?sport_id='.$sportId.'&year='.$year.'&page=2')
            ->assertOk();

        $page2->assertJsonCount(2, 'data.rankings');
        $page2->assertJsonPath('data.pagination.has_more', false);
    }

    public function test_rankings_filters_by_query_string(): void
    {
        $sportId = $this->sportIdBySlug('football');
        $user = User::factory()->create();
        $year = (int) CarbonImmutable::now()->year;
        $ref = CarbonImmutable::create($year, 4, 1);

        $championId = $this->createTeam(['name' => 'Champion FC', 'sport_id' => $sportId]);
        $otherId = $this->createTeam(['name' => 'Other United', 'sport_id' => $sportId]);
        $this->insertStats($championId, $sportId, 80, 20, 2, 1, $ref);
        $this->insertStats($otherId, $sportId, 40, 10, 2, 2, $ref);

        $this->mock(TypesenseTeamService::class, function ($mock) use ($championId, $sportId): void {
            $mock->shouldReceive('searchTeamIdsForRanking')
                ->once()
                ->with('Champion', $sportId)
                ->andReturn(['ids' => [$championId], 'found' => 1]);
        });

        $response = $this->actingAs($user, 'sanctum')
            ->getJson('/api/v1/auth/teams/rankings?sport_id='.$sportId.'&year='.$year.'&q=Champion')
            ->assertOk();

        $rankings = $response->json('data.rankings');
        $this->assertIsArray($rankings);
        $this->assertCount(1, $rankings);
        $this->assertSame($championId, $rankings[0]['team_id']);
        $this->assertStringContainsString('Champion', $rankings[0]['team_name']);
    }
}
