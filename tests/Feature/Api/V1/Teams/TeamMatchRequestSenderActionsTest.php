<?php

namespace Tests\Feature\Api\V1\Teams;

use App\Jobs\TeamMatchRequestCancelledNotificationJob;
use App\Jobs\TeamMatchRequestUpdatedNotificationJob;
use App\Models\User;
use Database\Seeders\SportsSeeder;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;

class TeamMatchRequestSenderActionsTest extends TestCase
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
     *     home_member: User,
     *     away_captain: User,
     *     home_team_id: int,
     *     away_team_id: int,
     *     match_event_id: int,
     * }
     */
    private function seedPendingMatchRequest(): array
    {
        $homeCaptain = User::factory()->create();
        $homeMember = User::factory()->create();
        $awayCaptain = User::factory()->create();
        $sportId = (int) DB::table('sports')->where('slug', 'football')->value('id');
        $now = now();

        $homeTeamId = DB::table('teams')->insertGetId([
            'creator_id' => $homeCaptain->id,
            'sport_id' => $sportId,
            'name' => 'Home FC '.uniqid('', true),
            'slug' => 'home-fc-'.uniqid('', true),
            'competition_type' => 'leisure',
            'created_at' => $now,
            'updated_at' => $now,
        ]);

        $awayTeamId = DB::table('teams')->insertGetId([
            'creator_id' => $awayCaptain->id,
            'sport_id' => $sportId,
            'name' => 'Away FC '.uniqid('', true),
            'slug' => 'away-fc-'.uniqid('', true),
            'competition_type' => 'leisure',
            'created_at' => $now,
            'updated_at' => $now,
        ]);

        DB::table('team_members')->insert([
            ['team_id' => $homeTeamId, 'user_id' => $homeCaptain->id, 'role' => 'captain', 'status' => 'active', 'created_at' => $now, 'updated_at' => $now],
            ['team_id' => $homeTeamId, 'user_id' => $homeMember->id, 'role' => 'member', 'status' => 'active', 'created_at' => $now, 'updated_at' => $now],
            ['team_id' => $awayTeamId, 'user_id' => $awayCaptain->id, 'role' => 'captain', 'status' => 'active', 'created_at' => $now, 'updated_at' => $now],
        ]);

        $scheduledAt = now()->addDays(4)->format('Y-m-d H:i:s');
        $matchEventId = (int) DB::table('match_events')->insertGetId([
            'home_team_id' => $homeTeamId,
            'away_team_id' => $awayTeamId,
            'scheduled_at' => $scheduledAt,
            'venue' => 'Stade initial',
            'status' => 'requested',
            'notes' => 'Note initiale',
            'created_at' => $now,
            'updated_at' => $now,
        ]);

        return [
            'home_captain' => $homeCaptain,
            'home_member' => $homeMember,
            'away_captain' => $awayCaptain,
            'home_team_id' => $homeTeamId,
            'away_team_id' => $awayTeamId,
            'match_event_id' => $matchEventId,
        ];
    }

    public function test_home_captain_can_update_pending_match_request(): void
    {
        Queue::fake();

        $fixture = $this->seedPendingMatchRequest();
        $token = $fixture['home_captain']->createToken('auth')->plainTextToken;
        $newScheduledAt = now()->addDays(6)->format('Y-m-d H:i:s');

        $this->putJson('/api/v1/auth/teams/match-requests/'.$fixture['match_event_id'], [
            'scheduled_at' => $newScheduledAt,
            'venue' => 'Nouveau stade',
            'notes' => 'Message mis à jour',
        ], [
            'Authorization' => 'Bearer '.$token,
        ])
            ->assertOk()
            ->assertJsonPath('message', __('Demande de match mise à jour.'));

        $row = DB::table('match_events')->where('id', $fixture['match_event_id'])->first();
        $this->assertSame($newScheduledAt, $row->scheduled_at);
        $this->assertSame('Nouveau stade', $row->venue);
        $this->assertSame('Message mis à jour', $row->notes);
        $this->assertSame('requested', $row->status);

        Queue::assertPushed(TeamMatchRequestUpdatedNotificationJob::class, function (TeamMatchRequestUpdatedNotificationJob $job) use ($fixture): bool {
            return $job->matchEventId === $fixture['match_event_id']
                && $job->actorUserId === $fixture['home_captain']->id;
        });
    }

    public function test_home_captain_can_cancel_pending_match_request(): void
    {
        Queue::fake();

        $fixture = $this->seedPendingMatchRequest();
        $token = $fixture['home_captain']->createToken('auth')->plainTextToken;

        $this->deleteJson('/api/v1/auth/teams/match-requests/'.$fixture['match_event_id'], [], [
            'Authorization' => 'Bearer '.$token,
        ])
            ->assertOk()
            ->assertJsonPath('message', __('Demande de match annulée.'));

        $this->assertSame('cancelled', DB::table('match_events')->where('id', $fixture['match_event_id'])->value('status'));

        Queue::assertPushed(TeamMatchRequestCancelledNotificationJob::class, function (TeamMatchRequestCancelledNotificationJob $job) use ($fixture): bool {
            return $job->matchEventId === $fixture['match_event_id']
                && $job->actorUserId === $fixture['home_captain']->id;
        });
    }

    public function test_away_captain_cannot_update_sent_match_request(): void
    {
        $fixture = $this->seedPendingMatchRequest();
        $token = $fixture['away_captain']->createToken('auth')->plainTextToken;

        $this->putJson('/api/v1/auth/teams/match-requests/'.$fixture['match_event_id'], [
            'scheduled_at' => now()->addDays(7)->toDateTimeString(),
        ], [
            'Authorization' => 'Bearer '.$token,
        ])->assertForbidden();
    }

    public function test_cannot_update_match_request_that_is_no_longer_pending(): void
    {
        $fixture = $this->seedPendingMatchRequest();
        DB::table('match_events')->where('id', $fixture['match_event_id'])->update(['status' => 'scheduled']);
        $token = $fixture['home_captain']->createToken('auth')->plainTextToken;

        $this->putJson('/api/v1/auth/teams/match-requests/'.$fixture['match_event_id'], [
            'scheduled_at' => now()->addDays(7)->toDateTimeString(),
        ], [
            'Authorization' => 'Bearer '.$token,
        ])
            ->assertStatus(422)
            ->assertJsonValidationErrors(['match_event_id']);
    }

    public function test_list_includes_notes_field(): void
    {
        $fixture = $this->seedPendingMatchRequest();
        $token = $fixture['home_captain']->createToken('auth')->plainTextToken;

        $this->getJson('/api/v1/auth/teams/match-requests?type=sent&status=pending', [
            'Authorization' => 'Bearer '.$token,
        ])
            ->assertOk()
            ->assertJsonPath('data.items.0.notes', 'Note initiale');
    }
}
