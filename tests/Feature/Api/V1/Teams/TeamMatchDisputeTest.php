<?php

namespace Tests\Feature\Api\V1\Teams;

use App\Jobs\TeamMatchDisputeNotificationJob;
use App\Jobs\TeamMatchDisputeResolvedNotificationJob;
use App\Models\User;
use Database\Seeders\SportsSeeder;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;

class TeamMatchDisputeTest extends TestCase
{
    use LazilyRefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(SportsSeeder::class);
    }

    /**
     * @return array{
     *     home_captain: User,
     *     away_captain: User,
     *     home_team_id: int,
     *     away_team_id: int,
     * }
     */
    private function seedMatchTeams(): array
    {
        $homeCaptain = User::factory()->create();
        $awayCaptain = User::factory()->create();
        $sportId = (int) DB::table('sports')->where('slug', 'football')->value('id');
        $now = now();

        $homeTeamId = DB::table('teams')->insertGetId([
            'creator_id' => $homeCaptain->id,
            'sport_id' => $sportId,
            'name' => 'Home FC '.uniqid('', true),
            'slug' => 'home-fc-'.uniqid('', true),
            'competition_type' => 'leisure',
            'skill_level' => null,
            'description' => null,
            'hq_city' => null,
            'hq_latitude' => null,
            'hq_longitude' => null,
            'cover_image_url' => null,
            'logo_url' => null,
            'logo_blurhash' => null,
            'cover_image_blurhash' => null,
            'created_at' => $now,
            'updated_at' => $now,
        ]);

        $awayTeamId = DB::table('teams')->insertGetId([
            'creator_id' => $awayCaptain->id,
            'sport_id' => $sportId,
            'name' => 'Away FC '.uniqid('', true),
            'slug' => 'away-fc-'.uniqid('', true),
            'competition_type' => 'leisure',
            'skill_level' => null,
            'description' => null,
            'hq_city' => null,
            'hq_latitude' => null,
            'hq_longitude' => null,
            'cover_image_url' => null,
            'logo_url' => null,
            'logo_blurhash' => null,
            'cover_image_blurhash' => null,
            'created_at' => $now,
            'updated_at' => $now,
        ]);

        DB::table('team_members')->insert([
            ['team_id' => $homeTeamId, 'user_id' => $homeCaptain->id, 'role' => 'captain', 'status' => 'active', 'created_at' => $now, 'updated_at' => $now],
            ['team_id' => $awayTeamId, 'user_id' => $awayCaptain->id, 'role' => 'captain', 'status' => 'active', 'created_at' => $now, 'updated_at' => $now],
        ]);

        return [
            'home_captain' => $homeCaptain,
            'away_captain' => $awayCaptain,
            'home_team_id' => $homeTeamId,
            'away_team_id' => $awayTeamId,
        ];
    }

    private function seedRefusedScore(array $fixture): array
    {
        $matchEventId = (int) DB::table('match_events')->insertGetId([
            'home_team_id' => $fixture['home_team_id'],
            'away_team_id' => $fixture['away_team_id'],
            'scheduled_at' => now()->addDay()->toDateTimeString(),
            'venue' => null,
            'status' => 'scheduled',
            'notes' => null,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $matchResultId = (int) DB::table('match_results')->insertGetId([
            'match_event_id' => $matchEventId,
            'home_score' => 2,
            'away_score' => 1,
            'status' => 'refused',
            'submitted_by_user_id' => $fixture['home_captain']->id,
            'submitted_at' => now(),
            'responded_by_user_id' => $fixture['away_captain']->id,
            'responded_at' => now(),
            'validated_at' => null,
            'refusal_reason' => 'Score incorrect.',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        return ['match_event_id' => $matchEventId, 'match_result_id' => $matchResultId];
    }

    public function test_away_can_open_dispute_after_score_refused(): void
    {
        Queue::fake();

        $fixture = $this->seedMatchTeams();
        $match = $this->seedRefusedScore($fixture);

        $this->actingAs($fixture['away_captain'], 'sanctum')
            ->postJson('/api/v1/auth/teams/match-events/'.$match['match_event_id'].'/result/dispute', [
                'dispute_reason_score_incorrect' => true,
                'dispute_reason_fair_play' => false,
                'dispute_reason_behavior' => false,
                'details' => 'Le score annoncé ne correspond pas au match.',
            ])
            ->assertCreated()
            ->assertJsonPath('match_result_dispute_id', fn ($id): bool => is_int($id) || is_numeric($id));

        $this->assertDatabaseHas('match_result_disputes', [
            'match_result_id' => $match['match_result_id'],
            'status' => 'pending',
        ]);

        Queue::assertPushed(TeamMatchDisputeNotificationJob::class);
    }

    public function test_cannot_open_second_dispute_while_one_is_open(): void
    {
        $fixture = $this->seedMatchTeams();
        $match = $this->seedRefusedScore($fixture);

        DB::table('match_result_disputes')->insert([
            'match_result_id' => $match['match_result_id'],
            'opened_by_user_id' => $fixture['away_captain']->id,
            'dispute_reason_score_incorrect' => true,
            'dispute_reason_fair_play' => false,
            'dispute_reason_behavior' => false,
            'details' => 'Premier litige',
            'evidence_path' => null,
            'evidence_disk' => null,
            'status' => 'pending',
            'moderator_user_id' => null,
            'moderator_notes' => null,
            'resolution_notes' => null,
            'resolved_at' => null,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $this->actingAs($fixture['away_captain'], 'sanctum')
            ->postJson('/api/v1/auth/teams/match-events/'.$match['match_event_id'].'/result/dispute', [
                'dispute_reason_score_incorrect' => true,
                'dispute_reason_fair_play' => false,
                'dispute_reason_behavior' => false,
                'details' => 'Deuxième litige',
            ])
            ->assertStatus(422);
    }

    public function test_home_cannot_resubmit_score_while_dispute_is_open(): void
    {
        $fixture = $this->seedMatchTeams();
        $match = $this->seedRefusedScore($fixture);

        DB::table('match_result_disputes')->insert([
            'match_result_id' => $match['match_result_id'],
            'opened_by_user_id' => $fixture['away_captain']->id,
            'dispute_reason_score_incorrect' => true,
            'dispute_reason_fair_play' => false,
            'dispute_reason_behavior' => false,
            'details' => 'Litige ouvert',
            'evidence_path' => null,
            'evidence_disk' => null,
            'status' => 'pending',
            'moderator_user_id' => null,
            'moderator_notes' => null,
            'resolution_notes' => null,
            'resolved_at' => null,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $this->actingAs($fixture['home_captain'], 'sanctum')
            ->postJson('/api/v1/auth/teams/'.$fixture['home_team_id'].'/match-events/'.$match['match_event_id'].'/result', [
                'home_score' => 3,
                'away_score' => 2,
                'fair_play_rating' => 4,
                'punctuality_rating' => 4,
            ])
            ->assertStatus(422);
    }

    public function test_list_match_requests_exposes_score_refused_and_disputed_statuses(): void
    {
        $fixture = $this->seedMatchTeams();
        $refusedOnly = $this->seedRefusedScore($fixture);

        $disputedMatch = $this->seedRefusedScore($fixture);
        DB::table('match_result_disputes')->insert([
            'match_result_id' => $disputedMatch['match_result_id'],
            'opened_by_user_id' => $fixture['away_captain']->id,
            'dispute_reason_score_incorrect' => true,
            'dispute_reason_fair_play' => false,
            'dispute_reason_behavior' => false,
            'details' => 'Litige',
            'evidence_path' => null,
            'evidence_disk' => null,
            'status' => 'pending',
            'moderator_user_id' => null,
            'moderator_notes' => null,
            'resolution_notes' => null,
            'resolved_at' => null,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $scoreRefusedResponse = $this->actingAs($fixture['away_captain'], 'sanctum')
            ->getJson('/api/v1/auth/teams/match-requests?type=received&status=score_refused')
            ->assertOk();

        $statuses = collect($scoreRefusedResponse->json('data.items'))->pluck('status')->all();
        $this->assertContains('score_refused', $statuses);
        $this->assertContains($refusedOnly['match_event_id'], collect($scoreRefusedResponse->json('data.items'))->pluck('match_event_id')->all());

        $disputedResponse = $this->actingAs($fixture['away_captain'], 'sanctum')
            ->getJson('/api/v1/auth/teams/match-requests?type=received&status=disputed')
            ->assertOk();

        $disputedItem = collect($disputedResponse->json('data.items'))
            ->firstWhere('match_event_id', $disputedMatch['match_event_id']);

        $this->assertNotNull($disputedItem);
        $this->assertSame('disputed', $disputedItem['status']);
        $this->assertTrue($disputedItem['has_open_dispute']);

        $eventIds = collect($disputedResponse->json('data.items'))->pluck('match_event_id')->all();
        $this->assertSameSize(array_unique($eventIds), $eventIds);
    }

    public function test_get_match_result_detail_includes_dispute(): void
    {
        $fixture = $this->seedMatchTeams();
        $match = $this->seedRefusedScore($fixture);

        $disputeId = (int) DB::table('match_result_disputes')->insertGetId([
            'match_result_id' => $match['match_result_id'],
            'opened_by_user_id' => $fixture['away_captain']->id,
            'dispute_reason_score_incorrect' => true,
            'dispute_reason_fair_play' => false,
            'dispute_reason_behavior' => false,
            'details' => 'Détails litige',
            'evidence_path' => null,
            'evidence_disk' => null,
            'status' => 'pending',
            'moderator_user_id' => null,
            'moderator_notes' => null,
            'resolution_notes' => null,
            'resolved_at' => null,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $this->actingAs($fixture['home_captain'], 'sanctum')
            ->getJson('/api/v1/auth/teams/match-events/'.$match['match_event_id'].'/result')
            ->assertOk()
            ->assertJsonPath('data.status', 'disputed')
            ->assertJsonPath('data.has_open_dispute', true)
            ->assertJsonPath('data.dispute.match_result_dispute_id', $disputeId)
            ->assertJsonPath('data.result.refusal_reason', 'Score incorrect.');
    }

    public function test_resolve_dispute_dismissed_allows_home_resubmit(): void
    {
        Queue::fake();

        $fixture = $this->seedMatchTeams();
        $match = $this->seedRefusedScore($fixture);

        $disputeId = (int) DB::table('match_result_disputes')->insertGetId([
            'match_result_id' => $match['match_result_id'],
            'opened_by_user_id' => $fixture['away_captain']->id,
            'dispute_reason_score_incorrect' => true,
            'dispute_reason_fair_play' => false,
            'dispute_reason_behavior' => false,
            'details' => 'Litige',
            'evidence_path' => null,
            'evidence_disk' => null,
            'status' => 'pending',
            'moderator_user_id' => null,
            'moderator_notes' => null,
            'resolution_notes' => null,
            'resolved_at' => null,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $this->actingAs($fixture['home_captain'], 'sanctum')
            ->patchJson('/api/v1/auth/teams/match-result-disputes/'.$disputeId.'/resolve', [
                'resolution' => 'dismissed',
                'resolution_notes' => 'Clôture sans modification.',
            ])
            ->assertOk();

        $this->assertDatabaseHas('match_result_disputes', [
            'id' => $disputeId,
            'status' => 'dismissed',
        ]);

        Queue::assertPushed(TeamMatchDisputeResolvedNotificationJob::class);

        $this->actingAs($fixture['home_captain'], 'sanctum')
            ->postJson('/api/v1/auth/teams/'.$fixture['home_team_id'].'/match-events/'.$match['match_event_id'].'/result', [
                'home_score' => 3,
                'away_score' => 2,
                'fair_play_rating' => 4,
                'punctuality_rating' => 4,
            ])
            ->assertOk();
    }
}
