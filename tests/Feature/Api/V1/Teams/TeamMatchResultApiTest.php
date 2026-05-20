<?php

namespace Tests\Feature\Api\V1\Teams;

use App\Models\User;
use Database\Seeders\SportsSeeder;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class TeamMatchResultApiTest extends TestCase
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
     * @return array{home_team_id: int, away_team_id: int, match_event_id: int, home_captain: User, away_captain: User}
     */
    private function createScheduledMatch(): array
    {
        $homeCaptain = User::factory()->create();
        $awayCaptain = User::factory()->create();
        $sportId = $this->sportIdBySlug('football');

        $homeTeamId = DB::table('teams')->insertGetId([
            'creator_id' => $homeCaptain->id,
            'sport_id' => $sportId,
            'name' => 'MR Home',
            'slug' => 'mr-home-'.uniqid(),
            'competition_type' => 'leisure',
            'skill_level' => null,
            'description' => null,
            'hq_city' => null,
            'hq_latitude' => null,
            'hq_longitude' => null,
            'cover_image_url' => null,
            'logo_url' => null,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $awayTeamId = DB::table('teams')->insertGetId([
            'creator_id' => $awayCaptain->id,
            'sport_id' => $sportId,
            'name' => 'MR Away',
            'slug' => 'mr-away-'.uniqid(),
            'competition_type' => 'leisure',
            'skill_level' => null,
            'description' => null,
            'hq_city' => null,
            'hq_latitude' => null,
            'hq_longitude' => null,
            'cover_image_url' => null,
            'logo_url' => null,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::table('team_members')->insert([
            ['team_id' => $homeTeamId, 'user_id' => $homeCaptain->id, 'role' => 'captain', 'status' => 'active', 'created_at' => now(), 'updated_at' => now()],
            ['team_id' => $awayTeamId, 'user_id' => $awayCaptain->id, 'role' => 'captain', 'status' => 'active', 'created_at' => now(), 'updated_at' => now()],
        ]);

        $matchEventId = DB::table('match_events')->insertGetId([
            'home_team_id' => $homeTeamId,
            'away_team_id' => $awayTeamId,
            'scheduled_at' => now()->addDay()->toDateTimeString(),
            'venue' => null,
            'status' => 'scheduled',
            'notes' => null,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        return [
            'home_team_id' => $homeTeamId,
            'away_team_id' => $awayTeamId,
            'match_event_id' => $matchEventId,
            'home_captain' => $homeCaptain,
            'away_captain' => $awayCaptain,
        ];
    }

    public function test_home_captain_can_submit_score_and_first_evaluation(): void
    {
        $ctx = $this->createScheduledMatch();

        $this->actingAs($ctx['home_captain'], 'sanctum')
            ->postJson('/api/v1/auth/teams/'.$ctx['home_team_id'].'/match-events/'.$ctx['match_event_id'].'/result', [
                'home_score' => 2,
                'away_score' => 1,
                'fair_play_rating' => 4,
                'punctuality_rating' => 5,
                'remarks' => 'Bon match.',
            ])
            ->assertCreated()
            ->assertJsonPath('message', 'Score et évaluation enregistrés.');

        $resultId = (int) DB::table('match_results')->where('match_event_id', $ctx['match_event_id'])->value('id');
        $this->assertDatabaseHas('match_results', [
            'id' => $resultId,
            'home_score' => 2,
            'away_score' => 1,
            'status' => 'score_pending_validation',
            'submitted_by_user_id' => $ctx['home_captain']->id,
        ]);

        $this->assertDatabaseHas('match_opponent_evaluations', [
            'match_result_id' => $resultId,
            'evaluator_team_id' => $ctx['home_team_id'],
            'evaluated_team_id' => $ctx['away_team_id'],
            'fair_play_rating' => 4,
            'punctuality_rating' => 5,
        ]);
    }

    public function test_away_captain_can_validate_and_add_second_evaluation(): void
    {
        $ctx = $this->createScheduledMatch();

        $this->actingAs($ctx['home_captain'], 'sanctum')
            ->postJson('/api/v1/auth/teams/'.$ctx['home_team_id'].'/match-events/'.$ctx['match_event_id'].'/result', [
                'home_score' => 1,
                'away_score' => 0,
                'fair_play_rating' => 5,
                'punctuality_rating' => 4,
            ])
            ->assertCreated();

        $this->actingAs($ctx['away_captain'], 'sanctum')
            ->patchJson('/api/v1/auth/teams/match-events/'.$ctx['match_event_id'].'/result', [
                'decision' => 'validate',
                'fair_play_rating' => 3,
                'punctuality_rating' => 3,
            ])
            ->assertOk();

        $this->assertDatabaseHas('match_events', [
            'id' => $ctx['match_event_id'],
            'status' => 'finished',
        ]);

        $resultId = (int) DB::table('match_results')->where('match_event_id', $ctx['match_event_id'])->value('id');
        $this->assertDatabaseHas('match_results', [
            'id' => $resultId,
            'status' => 'validated',
        ]);

        $this->assertDatabaseHas('match_opponent_evaluations', [
            'match_result_id' => $resultId,
            'evaluator_team_id' => $ctx['away_team_id'],
            'evaluated_team_id' => $ctx['home_team_id'],
        ]);

        $this->assertDatabaseHas('stats', [
            'team_id' => $ctx['home_team_id'],
            'victory_count' => 1,
            'defeat_count' => 0,
        ]);
        $this->assertDatabaseHas('stats', [
            'team_id' => $ctx['away_team_id'],
            'victory_count' => 0,
            'defeat_count' => 1,
        ]);
    }

    public function test_away_captain_can_refuse_and_home_can_resubmit(): void
    {
        $ctx = $this->createScheduledMatch();

        $this->actingAs($ctx['home_captain'], 'sanctum')
            ->postJson('/api/v1/auth/teams/'.$ctx['home_team_id'].'/match-events/'.$ctx['match_event_id'].'/result', [
                'home_score' => 3,
                'away_score' => 0,
                'fair_play_rating' => 2,
                'punctuality_rating' => 2,
            ])
            ->assertCreated();

        $this->actingAs($ctx['away_captain'], 'sanctum')
            ->patchJson('/api/v1/auth/teams/match-events/'.$ctx['match_event_id'].'/result', [
                'decision' => 'refuse',
                'refusal_reason' => 'Score incorrect.',
            ])
            ->assertOk();

        $resultId = (int) DB::table('match_results')->where('match_event_id', $ctx['match_event_id'])->value('id');
        $this->assertDatabaseHas('match_results', [
            'id' => $resultId,
            'status' => 'refused',
        ]);

        $this->actingAs($ctx['home_captain'], 'sanctum')
            ->postJson('/api/v1/auth/teams/'.$ctx['home_team_id'].'/match-events/'.$ctx['match_event_id'].'/result', [
                'home_score' => 2,
                'away_score' => 1,
                'fair_play_rating' => 4,
                'punctuality_rating' => 4,
            ])
            ->assertOk()
            ->assertJsonPath('message', 'Score et évaluation mis à jour.');

        $this->assertDatabaseHas('match_results', [
            'id' => $resultId,
            'status' => 'score_pending_validation',
            'home_score' => 2,
            'away_score' => 1,
        ]);
    }

    public function test_responder_can_open_dispute_after_refusal(): void
    {
        Storage::fake('local');
        $ctx = $this->createScheduledMatch();

        $this->actingAs($ctx['home_captain'], 'sanctum')
            ->postJson('/api/v1/auth/teams/'.$ctx['home_team_id'].'/match-events/'.$ctx['match_event_id'].'/result', [
                'home_score' => 1,
                'away_score' => 0,
                'fair_play_rating' => 5,
                'punctuality_rating' => 5,
            ])
            ->assertCreated();

        $this->actingAs($ctx['away_captain'], 'sanctum')
            ->patchJson('/api/v1/auth/teams/match-events/'.$ctx['match_event_id'].'/result', [
                'decision' => 'refuse',
                'refusal_reason' => 'Non conforme.',
            ])
            ->assertOk();

        $file = UploadedFile::fake()->image('proof.jpg', 600, 400);

        $this->actingAs($ctx['away_captain'], 'sanctum')
            ->post('/api/v1/auth/teams/match-events/'.$ctx['match_event_id'].'/result/dispute', [
                'dispute_reason_score_incorrect' => true,
                'dispute_reason_fair_play' => false,
                'dispute_reason_behavior' => false,
                'details' => 'Veuillez vérifier la feuille de match.',
                'evidence' => $file,
            ])
            ->assertCreated()
            ->assertJsonPath('message', 'Litige envoyé.');

        $resultId = (int) DB::table('match_results')->where('match_event_id', $ctx['match_event_id'])->value('id');
        $this->assertDatabaseHas('match_result_disputes', [
            'match_result_id' => $resultId,
            'opened_by_user_id' => $ctx['away_captain']->id,
            'status' => 'pending',
        ]);
    }

    public function test_away_team_cannot_submit_first_score(): void
    {
        $ctx = $this->createScheduledMatch();

        $this->actingAs($ctx['away_captain'], 'sanctum')
            ->postJson('/api/v1/auth/teams/'.$ctx['away_team_id'].'/match-events/'.$ctx['match_event_id'].'/result', [
                'home_score' => 1,
                'away_score' => 0,
                'fair_play_rating' => 3,
                'punctuality_rating' => 3,
            ])
            ->assertForbidden();
    }

    public function test_home_team_cannot_validate_own_score(): void
    {
        $ctx = $this->createScheduledMatch();

        $this->actingAs($ctx['home_captain'], 'sanctum')
            ->postJson('/api/v1/auth/teams/'.$ctx['home_team_id'].'/match-events/'.$ctx['match_event_id'].'/result', [
                'home_score' => 1,
                'away_score' => 0,
                'fair_play_rating' => 5,
                'punctuality_rating' => 5,
            ])
            ->assertCreated();

        $this->actingAs($ctx['home_captain'], 'sanctum')
            ->patchJson('/api/v1/auth/teams/match-events/'.$ctx['match_event_id'].'/result', [
                'decision' => 'validate',
                'fair_play_rating' => 3,
                'punctuality_rating' => 3,
            ])
            ->assertForbidden();
    }

    public function test_member_cannot_submit_match_result(): void
    {
        $ctx = $this->createScheduledMatch();
        $member = User::factory()->create();
        DB::table('team_members')->insert([
            'team_id' => $ctx['home_team_id'],
            'user_id' => $member->id,
            'role' => 'member',
            'status' => 'active',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $this->actingAs($member, 'sanctum')
            ->postJson('/api/v1/auth/teams/'.$ctx['home_team_id'].'/match-events/'.$ctx['match_event_id'].'/result', [
                'home_score' => 1,
                'away_score' => 1,
                'fair_play_rating' => 3,
                'punctuality_rating' => 3,
            ])
            ->assertForbidden();
    }
}
