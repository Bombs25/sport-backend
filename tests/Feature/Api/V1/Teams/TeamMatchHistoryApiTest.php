<?php

namespace Tests\Feature\Api\V1\Teams;

use App\Models\User;
use Carbon\CarbonImmutable;
use Database\Seeders\SportsSeeder;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class TeamMatchHistoryApiTest extends TestCase
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

    private function createTeam(array $attributes): int
    {
        return (int) DB::table('teams')->insertGetId([
            'creator_id' => $attributes['creator_id'] ?? User::factory()->create()->id,
            'sport_id' => $attributes['sport_id'],
            'name' => $attributes['name'],
            'slug' => 'mh-'.uniqid(),
            'competition_type' => 'leisure',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    private function insertValidatedMatch(
        int $homeTeamId,
        int $awayTeamId,
        int $homeScore,
        int $awayScore,
        CarbonImmutable $validatedAt,
    ): int {
        $matchEventId = (int) DB::table('match_events')->insertGetId([
            'home_team_id' => $homeTeamId,
            'away_team_id' => $awayTeamId,
            'scheduled_at' => $validatedAt->toDateTimeString(),
            'venue' => 'Stade test',
            'status' => 'finished',
            'created_at' => $validatedAt->toDateTimeString(),
            'updated_at' => $validatedAt->toDateTimeString(),
        ]);

        $now = $validatedAt->toDateTimeString();
        $submitter = User::factory()->create();

        DB::table('match_results')->insert([
            'match_event_id' => $matchEventId,
            'home_score' => $homeScore,
            'away_score' => $awayScore,
            'status' => 'validated',
            'submitted_by_user_id' => $submitter->id,
            'submitted_at' => $now,
            'validated_at' => $now,
            'created_at' => $now,
            'updated_at' => $now,
        ]);

        return $matchEventId;
    }

    public function test_returns_validated_matches_newest_first(): void
    {
        $sportId = $this->sportIdBySlug('football');
        $user = User::factory()->create();
        $home = $this->createTeam(['name' => 'Hist Home', 'sport_id' => $sportId]);
        $away = $this->createTeam(['name' => 'Hist Away', 'sport_id' => $sportId]);

        $older = $this->insertValidatedMatch($home, $away, 1, 0, CarbonImmutable::create(2026, 1, 5, 10, 0));
        $newer = $this->insertValidatedMatch($home, $away, 2, 1, CarbonImmutable::create(2026, 3, 12, 20, 0));

        $response = $this->actingAs($user, 'sanctum')
            ->getJson('/api/v1/auth/teams/'.$home.'/match-history')
            ->assertOk();

        $matches = $response->json('data.matches');
        $this->assertIsArray($matches);
        $this->assertCount(2, $matches);
        $this->assertSame($newer, $matches[0]['match_event_id']);
        $this->assertSame($older, $matches[1]['match_event_id']);
        $this->assertSame('Hist Away', $matches[0]['away']['name']);
        $this->assertSame(2, $matches[0]['home']['score']);
    }
}
