<?php

namespace Tests\Feature\Api\V1\Teams;

use App\Jobs\TeamMatchRequestCancelledNotificationJob;
use App\Jobs\TeamMatchRequestDecisionNotificationJob;
use App\Jobs\TeamMatchRequestNotificationJob;
use App\Jobs\TeamMatchRequestUpdatedNotificationJob;
use App\Jobs\TeamMatchScoreProposalNotificationJob;
use App\Jobs\TeamMatchScoreRefusedNotificationJob;
use App\Jobs\TeamMatchScoreValidatedNotificationJob;
use App\Models\User;
use App\Notifications\TeamMatchNotification;
use App\Services\Notifications\ExpoPushService;
use Database\Seeders\SportsSeeder;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;

class TeamMatchNotificationTest extends TestCase
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
     *     away_member: User,
     *     home_team_id: int,
     *     away_team_id: int,
     * }
     */
    private function seedMatchTeams(): array
    {
        $homeCaptain = User::factory()->create([
            'name' => 'Capitaine Home',
            'fcm_token' => 'ExponentPushToken[home-captain]',
        ]);
        $homeMember = User::factory()->create([
            'name' => 'Membre Home',
            'fcm_token' => 'ExponentPushToken[home-member]',
        ]);
        $awayCaptain = User::factory()->create([
            'name' => 'Capitaine Away',
            'fcm_token' => 'ExponentPushToken[away-captain]',
        ]);
        $awayMember = User::factory()->create([
            'name' => 'Membre Away',
            'fcm_token' => 'ExponentPushToken[away-member]',
        ]);

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
            ['team_id' => $homeTeamId, 'user_id' => $homeMember->id, 'role' => 'member', 'status' => 'active', 'created_at' => $now, 'updated_at' => $now],
            ['team_id' => $awayTeamId, 'user_id' => $awayCaptain->id, 'role' => 'captain', 'status' => 'active', 'created_at' => $now, 'updated_at' => $now],
            ['team_id' => $awayTeamId, 'user_id' => $awayMember->id, 'role' => 'member', 'status' => 'active', 'created_at' => $now, 'updated_at' => $now],
        ]);

        return [
            'home_captain' => $homeCaptain,
            'home_member' => $homeMember,
            'away_captain' => $awayCaptain,
            'away_member' => $awayMember,
            'home_team_id' => $homeTeamId,
            'away_team_id' => $awayTeamId,
        ];
    }

    public function test_match_request_dispatches_notification_job(): void
    {
        Queue::fake();

        $fixture = $this->seedMatchTeams();
        $token = $fixture['home_captain']->createToken('auth')->plainTextToken;
        $scheduledAt = now()->addDays(3)->toIso8601String();

        $response = $this->postJson('/api/v1/auth/teams/'.$fixture['home_team_id'].'/match-requests', [
            'away_team_id' => $fixture['away_team_id'],
            'scheduled_at' => $scheduledAt,
        ], [
            'Authorization' => 'Bearer '.$token,
        ])->assertCreated();

        $matchEventId = (int) $response->json('match_event_id');

        Queue::assertPushed(TeamMatchRequestNotificationJob::class, function (TeamMatchRequestNotificationJob $job) use ($fixture, $matchEventId): bool {
            return $job->matchEventId === $matchEventId
                && $job->actorUserId === $fixture['home_captain']->id;
        });
    }

    public function test_match_invite_notifies_away_roster_not_home_actor(): void
    {
        Notification::fake();

        $fixture = $this->seedMatchTeams();
        $scheduledAt = now()->addDays(3)->toDateTimeString();

        $matchEventId = (int) DB::table('match_events')->insertGetId([
            'home_team_id' => $fixture['home_team_id'],
            'away_team_id' => $fixture['away_team_id'],
            'scheduled_at' => $scheduledAt,
            'venue' => null,
            'status' => 'requested',
            'notes' => null,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $this->mock(ExpoPushService::class, function ($mock): void {
            $mock->shouldReceive('send')->twice();
        });

        $job = new TeamMatchRequestNotificationJob($matchEventId, $fixture['home_captain']->id);
        $job->handle(app(ExpoPushService::class));

        Notification::assertSentTo(
            $fixture['away_captain'],
            TeamMatchNotification::class,
            function (TeamMatchNotification $notification) use ($fixture, $matchEventId): bool {
                $data = $notification->toArray($fixture['away_captain']);

                return $data['kind'] === 'match_invite'
                    && $data['match_event_id'] === $matchEventId
                    && $data['home_team_id'] === $fixture['home_team_id']
                    && $data['away_team_id'] === $fixture['away_team_id']
                    && str_contains((string) $data['message'], 'souhaite un match');
            },
        );

        Notification::assertSentTo($fixture['away_member'], TeamMatchNotification::class);
        Notification::assertNotSentTo($fixture['home_captain'], TeamMatchNotification::class);
        Notification::assertNotSentTo($fixture['home_member'], TeamMatchNotification::class);
    }

    public function test_update_dispatches_updated_notification_job(): void
    {
        Queue::fake();

        $fixture = $this->seedMatchTeams();
        $matchEventId = (int) DB::table('match_events')->insertGetId([
            'home_team_id' => $fixture['home_team_id'],
            'away_team_id' => $fixture['away_team_id'],
            'scheduled_at' => now()->addDays(3)->toDateTimeString(),
            'venue' => null,
            'status' => 'requested',
            'notes' => null,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $token = $fixture['home_captain']->createToken('auth')->plainTextToken;

        $this->putJson('/api/v1/auth/teams/match-requests/'.$matchEventId, [
            'scheduled_at' => now()->addDays(5)->toDateTimeString(),
            'venue' => 'Lisieux',
        ], [
            'Authorization' => 'Bearer '.$token,
        ])->assertOk();

        Queue::assertPushed(TeamMatchRequestUpdatedNotificationJob::class, function (TeamMatchRequestUpdatedNotificationJob $job) use ($fixture, $matchEventId): bool {
            return $job->matchEventId === $matchEventId
                && $job->actorUserId === $fixture['home_captain']->id;
        });
    }

    public function test_update_job_notifies_away_roster(): void
    {
        Notification::fake();

        $fixture = $this->seedMatchTeams();
        $scheduledAt = now()->addDays(5)->toDateTimeString();
        $matchEventId = (int) DB::table('match_events')->insertGetId([
            'home_team_id' => $fixture['home_team_id'],
            'away_team_id' => $fixture['away_team_id'],
            'scheduled_at' => $scheduledAt,
            'venue' => 'Lisieux',
            'status' => 'requested',
            'notes' => null,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $this->mock(ExpoPushService::class, function ($mock): void {
            $mock->shouldReceive('send')->twice();
        });

        $job = new TeamMatchRequestUpdatedNotificationJob($matchEventId, $fixture['home_captain']->id);
        $job->handle(app(ExpoPushService::class));

        Notification::assertSentTo(
            $fixture['away_captain'],
            TeamMatchNotification::class,
            function (TeamMatchNotification $notification) use ($fixture, $matchEventId): bool {
                $data = $notification->toArray($fixture['away_captain']);

                return $data['kind'] === 'match_invite_updated'
                    && $data['match_event_id'] === $matchEventId
                    && str_contains((string) $data['message'], 'a modifié la demande de match');
            },
        );

        Notification::assertSentTo($fixture['away_member'], TeamMatchNotification::class);
        Notification::assertNotSentTo($fixture['home_captain'], TeamMatchNotification::class);
        Notification::assertNotSentTo($fixture['home_member'], TeamMatchNotification::class);
    }

    public function test_cancel_dispatches_cancelled_notification_job(): void
    {
        Queue::fake();

        $fixture = $this->seedMatchTeams();
        $matchEventId = (int) DB::table('match_events')->insertGetId([
            'home_team_id' => $fixture['home_team_id'],
            'away_team_id' => $fixture['away_team_id'],
            'scheduled_at' => now()->addDays(3)->toDateTimeString(),
            'venue' => null,
            'status' => 'requested',
            'notes' => null,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $token = $fixture['home_captain']->createToken('auth')->plainTextToken;

        $this->deleteJson('/api/v1/auth/teams/match-requests/'.$matchEventId, [], [
            'Authorization' => 'Bearer '.$token,
        ])->assertOk();

        Queue::assertPushed(TeamMatchRequestCancelledNotificationJob::class, function (TeamMatchRequestCancelledNotificationJob $job) use ($fixture, $matchEventId): bool {
            return $job->matchEventId === $matchEventId
                && $job->actorUserId === $fixture['home_captain']->id;
        });
    }

    public function test_cancel_job_notifies_away_roster(): void
    {
        Notification::fake();

        $fixture = $this->seedMatchTeams();
        $matchEventId = (int) DB::table('match_events')->insertGetId([
            'home_team_id' => $fixture['home_team_id'],
            'away_team_id' => $fixture['away_team_id'],
            'scheduled_at' => now()->addDays(3)->toDateTimeString(),
            'venue' => null,
            'status' => 'cancelled',
            'notes' => null,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $this->mock(ExpoPushService::class, function ($mock): void {
            $mock->shouldReceive('send')->twice();
        });

        $job = new TeamMatchRequestCancelledNotificationJob($matchEventId, $fixture['home_captain']->id);
        $job->handle(app(ExpoPushService::class));

        Notification::assertSentTo(
            $fixture['away_captain'],
            TeamMatchNotification::class,
            function (TeamMatchNotification $notification) use ($fixture): bool {
                $data = $notification->toArray($fixture['away_captain']);

                return $data['kind'] === 'team_match_cancelled'
                    && str_contains((string) $data['message'], 'a annulé la demande de match');
            },
        );

        Notification::assertSentTo($fixture['away_member'], TeamMatchNotification::class);
        Notification::assertNotSentTo($fixture['home_captain'], TeamMatchNotification::class);
        Notification::assertNotSentTo($fixture['home_member'], TeamMatchNotification::class);
    }

    public function test_accept_decision_dispatches_notification_job(): void
    {
        Queue::fake();

        $fixture = $this->seedMatchTeams();
        $matchEventId = (int) DB::table('match_events')->insertGetId([
            'home_team_id' => $fixture['home_team_id'],
            'away_team_id' => $fixture['away_team_id'],
            'scheduled_at' => now()->addDay()->toDateTimeString(),
            'venue' => null,
            'status' => 'requested',
            'notes' => null,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $token = $fixture['away_captain']->createToken('auth')->plainTextToken;

        $this->patchJson('/api/v1/auth/teams/match-requests/'.$matchEventId, [
            'decision' => 'accept',
        ], [
            'Authorization' => 'Bearer '.$token,
        ])->assertOk();

        Queue::assertPushed(TeamMatchRequestDecisionNotificationJob::class, function (TeamMatchRequestDecisionNotificationJob $job) use ($fixture, $matchEventId): bool {
            return $job->matchEventId === $matchEventId
                && $job->actorUserId === $fixture['away_captain']->id
                && $job->decision === 'accept';
        });
    }

    public function test_decision_job_notifies_home_roster_on_accept(): void
    {
        Notification::fake();

        $fixture = $this->seedMatchTeams();
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

        $this->mock(ExpoPushService::class, function ($mock): void {
            $mock->shouldReceive('send')->twice();
        });

        $job = new TeamMatchRequestDecisionNotificationJob(
            $matchEventId,
            $fixture['away_captain']->id,
            'accept',
        );
        $job->handle(app(ExpoPushService::class));

        Notification::assertSentTo(
            $fixture['home_captain'],
            TeamMatchNotification::class,
            function (TeamMatchNotification $notification) use ($fixture): bool {
                $data = $notification->toArray($fixture['home_captain']);

                return $data['kind'] === 'team_match_accepted'
                    && str_contains((string) $data['message'], 'a accepté votre demande');
            },
        );

        Notification::assertSentTo($fixture['home_member'], TeamMatchNotification::class);
        Notification::assertNotSentTo($fixture['away_captain'], TeamMatchNotification::class);
    }

    public function test_score_submit_dispatches_proposal_job(): void
    {
        Queue::fake();

        $fixture = $this->seedMatchTeams();
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

        $this->actingAs($fixture['home_captain'], 'sanctum')
            ->postJson('/api/v1/auth/teams/'.$fixture['home_team_id'].'/match-events/'.$matchEventId.'/result', [
                'home_score' => 2,
                'away_score' => 1,
                'fair_play_rating' => 4,
                'punctuality_rating' => 5,
            ])
            ->assertCreated();

        Queue::assertPushed(TeamMatchScoreProposalNotificationJob::class, function (TeamMatchScoreProposalNotificationJob $job) use ($fixture, $matchEventId): bool {
            return $job->matchEventId === $matchEventId
                && $job->actorUserId === $fixture['home_captain']->id;
        });
    }

    public function test_score_proposal_notifies_away_roster(): void
    {
        Notification::fake();

        $fixture = $this->seedMatchTeams();
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

        DB::table('match_results')->insertGetId([
            'match_event_id' => $matchEventId,
            'home_score' => 2,
            'away_score' => 1,
            'status' => 'score_pending_validation',
            'submitted_by_user_id' => $fixture['home_captain']->id,
            'submitted_at' => now(),
            'responded_by_user_id' => null,
            'responded_at' => null,
            'validated_at' => null,
            'refusal_reason' => null,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $this->mock(ExpoPushService::class, function ($mock): void {
            $mock->shouldReceive('send')->twice();
        });

        $job = new TeamMatchScoreProposalNotificationJob($matchEventId, $fixture['home_captain']->id);
        $job->handle(app(ExpoPushService::class));

        Notification::assertSentTo($fixture['away_captain'], TeamMatchNotification::class, function (TeamMatchNotification $notification) use ($fixture, $matchEventId): bool {
            $data = $notification->toArray($fixture['away_captain']);

            return $data['kind'] === 'score_proposal'
                && $data['match_event_id'] === $matchEventId
                && str_contains((string) $data['message'], '2-1');
        });

        Notification::assertSentTo($fixture['away_member'], TeamMatchNotification::class);
        Notification::assertNotSentTo($fixture['home_captain'], TeamMatchNotification::class);
    }

    public function test_validate_dispatches_validated_job(): void
    {
        Queue::fake();

        $fixture = $this->seedMatchTeams();
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

        DB::table('match_results')->insert([
            'match_event_id' => $matchEventId,
            'home_score' => 1,
            'away_score' => 0,
            'status' => 'score_pending_validation',
            'submitted_by_user_id' => $fixture['home_captain']->id,
            'submitted_at' => now(),
            'responded_by_user_id' => null,
            'responded_at' => null,
            'validated_at' => null,
            'refusal_reason' => null,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $this->actingAs($fixture['away_captain'], 'sanctum')
            ->patchJson('/api/v1/auth/teams/match-events/'.$matchEventId.'/result', [
                'decision' => 'validate',
                'fair_play_rating' => 4,
                'punctuality_rating' => 4,
            ])
            ->assertOk();

        Queue::assertPushed(TeamMatchScoreValidatedNotificationJob::class);
        Queue::assertNotPushed(TeamMatchScoreRefusedNotificationJob::class);
    }

    public function test_refuse_dispatches_refused_job(): void
    {
        Queue::fake();

        $fixture = $this->seedMatchTeams();
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

        DB::table('match_results')->insert([
            'match_event_id' => $matchEventId,
            'home_score' => 1,
            'away_score' => 0,
            'status' => 'score_pending_validation',
            'submitted_by_user_id' => $fixture['home_captain']->id,
            'submitted_at' => now(),
            'responded_by_user_id' => null,
            'responded_at' => null,
            'validated_at' => null,
            'refusal_reason' => null,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $this->actingAs($fixture['away_captain'], 'sanctum')
            ->patchJson('/api/v1/auth/teams/match-events/'.$matchEventId.'/result', [
                'decision' => 'refuse',
                'refusal_reason' => 'Score incorrect.',
            ])
            ->assertOk();

        Queue::assertPushed(TeamMatchScoreRefusedNotificationJob::class);
        Queue::assertNotPushed(TeamMatchScoreValidatedNotificationJob::class);
    }
}
