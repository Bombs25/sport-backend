<?php

namespace Tests\Feature\Api\V1\Teams;

use App\Models\User;
use Carbon\CarbonImmutable;
use Database\Seeders\SportsSeeder;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class TeamLatestMatchApiTest extends TestCase
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
     * @param  array{name: string, sport_id: int, logo_url?: ?string, creator_id?: ?int}  $attributes
     */
    private function createTeam(array $attributes): int
    {
        return (int) DB::table('teams')->insertGetId([
            'creator_id' => $attributes['creator_id'] ?? User::factory()->create()->id,
            'sport_id' => $attributes['sport_id'],
            'name' => $attributes['name'],
            'slug' => 'lm-'.uniqid(),
            'competition_type' => 'leisure',
            'skill_level' => null,
            'description' => null,
            'hq_city' => null,
            'hq_latitude' => null,
            'hq_longitude' => null,
            'cover_image_url' => null,
            'logo_url' => array_key_exists('logo_url', $attributes) ? $attributes['logo_url'] : null,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    /**
     * @return array{match_event_id: int, home_team_id: int, away_team_id: int}
     */
    private function insertValidatedFinishedMatch(
        int $homeTeamId,
        int $awayTeamId,
        int $homeScore,
        int $awayScore,
        User $submitter,
        User $responder,
        CarbonImmutable $validatedAt,
        string $pendingOrValidated = 'validated',
    ): array {
        $matchEventId = (int) DB::table('match_events')->insertGetId([
            'home_team_id' => $homeTeamId,
            'away_team_id' => $awayTeamId,
            'scheduled_at' => $validatedAt->toDateTimeString(),
            'venue' => null,
            'status' => 'finished',
            'notes' => null,
            'created_at' => $validatedAt->toDateTimeString(),
            'updated_at' => $validatedAt->toDateTimeString(),
        ]);

        $now = $validatedAt->toDateTimeString();

        DB::table('match_results')->insert([
            'match_event_id' => $matchEventId,
            'home_score' => $homeScore,
            'away_score' => $awayScore,
            'total_comments' => 0,
            'total_likes' => 0,
            'status' => $pendingOrValidated,
            'submitted_by_user_id' => $submitter->id,
            'submitted_at' => $now,
            'responded_by_user_id' => $responder->id,
            'responded_at' => $pendingOrValidated === 'validated' ? $now : null,
            'validated_at' => $pendingOrValidated === 'validated' ? $now : null,
            'refusal_reason' => null,
            'created_at' => $now,
            'updated_at' => $now,
        ]);

        return [
            'match_event_id' => $matchEventId,
            'home_team_id' => $homeTeamId,
            'away_team_id' => $awayTeamId,
        ];
    }

    public function test_unauthenticated_user_cannot_access_latest_match(): void
    {
        $sportId = $this->sportIdBySlug('football');
        $teamId = $this->createTeam(['name' => 'Lonely', 'sport_id' => $sportId]);

        $this->getJson('/api/v1/auth/teams/'.$teamId.'/latest-match')
            ->assertUnauthorized();
    }

    public function test_validates_unknown_team_id(): void
    {
        /** @var User $user */
        $user = User::factory()->create();

        $this->actingAs($user, 'sanctum')
            ->getJson('/api/v1/auth/teams/2147483647/latest-match')
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['team_id']);
    }

    public function test_returns_null_when_no_validated_match(): void
    {
        $sportId = $this->sportIdBySlug('football');
        /** @var User $user */
        $user = User::factory()->create();
        $teamId = $this->createTeam(['name' => 'No Match', 'sport_id' => $sportId]);

        $this->actingAs($user, 'sanctum')
            ->getJson('/api/v1/auth/teams/'.$teamId.'/latest-match')
            ->assertOk()
            ->assertJsonPath('data.latest_match', null);
    }

    public function test_ignores_matches_not_yet_validated(): void
    {
        $sportId = $this->sportIdBySlug('football');
        $submit = User::factory()->create();
        $respond = User::factory()->create();
        /** @var User $viewer */
        $viewer = User::factory()->create();

        $home = $this->createTeam(['name' => 'Home P', 'sport_id' => $sportId, 'creator_id' => $submit->id]);
        $away = $this->createTeam(['name' => 'Away P', 'sport_id' => $sportId, 'creator_id' => $respond->id]);
        $validatedAt = CarbonImmutable::create(2026, 2, 10, 14, 30, 0);

        $this->insertValidatedFinishedMatch($home, $away, 2, 2, $submit, $respond, $validatedAt, 'score_pending_validation');

        $this->actingAs($viewer, 'sanctum')
            ->getJson('/api/v1/auth/teams/'.$home.'/latest-match')
            ->assertOk()
            ->assertJsonPath('data.latest_match', null);
    }

    public function test_returns_most_recent_match_by_validated_at(): void
    {
        $sportId = $this->sportIdBySlug('football');
        $submit = User::factory()->create();
        $respond = User::factory()->create();
        /** @var User $viewer */
        $viewer = User::factory()->create();

        $home = $this->createTeam(['name' => 'Older', 'sport_id' => $sportId]);
        $away = $this->createTeam(['name' => 'Rival Old', 'sport_id' => $sportId]);
        $home2 = $this->createTeam(['name' => 'New Home', 'sport_id' => $sportId]);
        $away2 = $this->createTeam(['name' => 'Rival New', 'sport_id' => $sportId]);

        $older = CarbonImmutable::create(2025, 5, 1, 10, 0, 0);
        $newer = CarbonImmutable::create(2026, 1, 20, 18, 0, 0);

        $this->insertValidatedFinishedMatch($home, $away, 1, 0, $submit, $respond, $older);
        $ctx = $this->insertValidatedFinishedMatch($home2, $away2, 3, 1, $submit, $respond, $newer);

        $this->actingAs($viewer, 'sanctum')
            ->getJson('/api/v1/auth/teams/'.$home2.'/latest-match')
            ->assertOk()
            ->assertJsonPath('data.latest_match.match_event_id', $ctx['match_event_id'])
            ->assertJsonPath('data.latest_match.home.score', 3)
            ->assertJsonPath('data.latest_match.away.score', 1)
            ->assertJsonPath('data.latest_match.home.name', 'New Home')
            ->assertJsonPath('data.latest_match.away.name', 'Rival New');
    }

    public function test_outcome_win_when_viewing_team_wins_as_home(): void
    {
        $sportId = $this->sportIdBySlug('football');
        $submit = User::factory()->create();
        $respond = User::factory()->create();

        $lyon = $this->createTeam(['name' => 'Lyon Lions', 'sport_id' => $sportId, 'logo_url' => 'lyon.png']);
        $paris = $this->createTeam(['name' => 'Paris FC', 'sport_id' => $sportId]);

        $this->insertValidatedFinishedMatch($lyon, $paris, 3, 1, $submit, $respond, CarbonImmutable::create(2026, 5, 1));

        /** @var User $viewer */
        $viewer = User::factory()->create();

        $this->actingAs($viewer, 'sanctum')
            ->getJson('/api/v1/auth/teams/'.$lyon.'/latest-match')
            ->assertOk()
            ->assertJsonPath('data.latest_match.outcome_for_viewing_team', 'win')
            ->assertJsonPath('data.latest_match.home.logo_url', 'lyon.png')
            ->assertJsonPath('data.latest_match.home.score', 3)
            ->assertJsonPath('data.latest_match.away.score', 1);
    }

    public function test_outcome_win_when_viewing_team_wins_as_away(): void
    {
        $sportId = $this->sportIdBySlug('football');
        $submit = User::factory()->create();
        $respond = User::factory()->create();

        $homeId = $this->createTeam(['name' => 'Big Club', 'sport_id' => $sportId]);
        $awayId = $this->createTeam(['name' => 'Underdog', 'sport_id' => $sportId]);

        $this->insertValidatedFinishedMatch($homeId, $awayId, 0, 2, $submit, $respond, CarbonImmutable::create(2026, 3, 3));

        /** @var User $viewer */
        $viewer = User::factory()->create();

        $this->actingAs($viewer, 'sanctum')
            ->getJson('/api/v1/auth/teams/'.$awayId.'/latest-match')
            ->assertOk()
            ->assertJsonPath('data.latest_match.outcome_for_viewing_team', 'win');
    }

    public function test_outcome_loss_and_draw(): void
    {
        $sportId = $this->sportIdBySlug('football');
        $submit = User::factory()->create();
        $respond = User::factory()->create();

        $homeLost = $this->createTeam(['name' => 'Home L', 'sport_id' => $sportId]);
        $awayBeat = $this->createTeam(['name' => 'Away W', 'sport_id' => $sportId]);

        $this->insertValidatedFinishedMatch($homeLost, $awayBeat, 1, 4, $submit, $respond, CarbonImmutable::create(2026, 4, 1));

        /** @var User $viewer */
        $viewer = User::factory()->create();

        $this->actingAs($viewer, 'sanctum')
            ->getJson('/api/v1/auth/teams/'.$homeLost.'/latest-match')
            ->assertOk()
            ->assertJsonPath('data.latest_match.outcome_for_viewing_team', 'loss');

        $h = $this->createTeam(['name' => 'D1', 'sport_id' => $sportId]);
        $a = $this->createTeam(['name' => 'D2', 'sport_id' => $sportId]);
        $this->insertValidatedFinishedMatch($h, $a, 2, 2, $submit, $respond, CarbonImmutable::create(2026, 6, 1));

        $this->actingAs($viewer, 'sanctum')
            ->getJson('/api/v1/auth/teams/'.$h.'/latest-match')
            ->assertOk()
            ->assertJsonPath('data.latest_match.outcome_for_viewing_team', 'draw');
    }
}
