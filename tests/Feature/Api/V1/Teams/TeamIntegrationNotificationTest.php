<?php

namespace Tests\Feature\Api\V1\Teams;

use App\Jobs\TeamIntegrationDecisionNotificationJob;
use App\Jobs\TeamIntegrationNotificationJob;
use App\Models\User;
use App\Notifications\TeamIntegrationDecisionNotification;
use App\Notifications\TeamIntegrationRequestNotification;
use App\Services\Notifications\ExpoPushService;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;

class TeamIntegrationNotificationTest extends TestCase
{
    use LazilyRefreshDatabase;

    /**
     * @return array{
     *     captain: User,
     *     member: User,
     *     asker: User,
     *     teamId: int,
     * }
     */
    private function seedCollectiveTeamWithActiveMembers(): array
    {
        $captain = User::factory()->create([
            'name' => 'Capitaine Test',
            'fcm_token' => 'ExponentPushToken[test-captain]',
        ]);
        $member = User::factory()->create([
            'name' => 'Membre Test',
            'fcm_token' => 'ExponentPushToken[test-member]',
        ]);
        $asker = User::factory()->create(['name' => 'Demandeur Test']);

        $now = now();
        $teamId = DB::table('teams')->insertGetId([
            'creator_id' => $captain->id,
            'sport_id' => 1,
            'name' => 'Equipe Notif '.uniqid('', true),
            'slug' => 'equipe-notif-'.uniqid('', true),
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
            [
                'team_id' => $teamId,
                'user_id' => $captain->id,
                'role' => 'captain',
                'status' => 'active',
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'team_id' => $teamId,
                'user_id' => $member->id,
                'role' => 'member',
                'status' => 'active',
                'created_at' => $now,
                'updated_at' => $now,
            ],
        ]);

        return [
            'captain' => $captain,
            'member' => $member,
            'asker' => $asker,
            'teamId' => $teamId,
        ];
    }

    /**
     * @param  array{captain: User, member: User, asker: User, teamId: int}  $fixture
     */
    private function seedPendingIntegrationRequest(array $fixture): void
    {
        DB::table('team_members')->insert([
            'team_id' => $fixture['teamId'],
            'user_id' => $fixture['asker']->id,
            'role' => 'member',
            'status' => 'pending',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    public function test_integration_request_dispatches_notification_job(): void
    {
        Queue::fake();

        $fixture = $this->seedCollectiveTeamWithActiveMembers();
        $token = $fixture['asker']->createToken('auth')->plainTextToken;

        $this->postJson('/api/v1/auth/teams/'.$fixture['teamId'].'/integrations', [], [
            'Authorization' => 'Bearer '.$token,
        ])->assertCreated();

        Queue::assertPushed(TeamIntegrationNotificationJob::class, function (TeamIntegrationNotificationJob $job) use ($fixture): bool {
            return $job->teamId === $fixture['teamId']
                && $job->askerUserId === $fixture['asker']->id;
        });
    }

    public function test_job_notifies_active_captain_and_members_not_asker(): void
    {
        Notification::fake();

        $fixture = $this->seedCollectiveTeamWithActiveMembers();

        $this->mock(ExpoPushService::class, function ($mock): void {
            $mock->shouldReceive('send')->twice();
        });

        $job = new TeamIntegrationNotificationJob($fixture['teamId'], $fixture['asker']->id);

        DB::table('team_members')->insert([
            'team_id' => $fixture['teamId'],
            'user_id' => $fixture['asker']->id,
            'role' => 'member',
            'status' => 'pending',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $job->handle(app(ExpoPushService::class));

        Notification::assertSentTo(
            $fixture['captain'],
            TeamIntegrationRequestNotification::class,
            function (TeamIntegrationRequestNotification $notification) use ($fixture): bool {
                $data = $notification->toArray($fixture['captain']);

                return $data['kind'] === 'team_join_request'
                    && $data['actor_user_id'] === $fixture['asker']->id
                    && $data['team_id'] === $fixture['teamId']
                    && str_contains((string) $data['message'], 'Demandeur Test')
                    && str_contains((string) $data['message'], 'souhaite rejoindre');
            },
        );

        Notification::assertSentTo($fixture['member'], TeamIntegrationRequestNotification::class);
        Notification::assertNotSentTo($fixture['asker'], TeamIntegrationRequestNotification::class);
    }

    public function test_job_skips_when_membership_is_no_longer_pending(): void
    {
        Notification::fake();

        $fixture = $this->seedCollectiveTeamWithActiveMembers();

        $this->mock(ExpoPushService::class, function ($mock): void {
            $mock->shouldNotReceive('send');
        });

        $job = new TeamIntegrationNotificationJob($fixture['teamId'], $fixture['asker']->id);
        $job->handle(app(ExpoPushService::class));

        Notification::assertNothingSent();
    }

    public function test_post_integration_creates_database_notifications_for_recipients(): void
    {
        $fixture = $this->seedCollectiveTeamWithActiveMembers();
        $token = $fixture['asker']->createToken('auth')->plainTextToken;

        $this->mock(ExpoPushService::class, function ($mock): void {
            $mock->shouldReceive('send')->zeroOrMoreTimes();
        });

        $this->postJson('/api/v1/auth/teams/'.$fixture['teamId'].'/integrations', [], [
            'Authorization' => 'Bearer '.$token,
        ])->assertCreated();

        $this->assertDatabaseHas('notifications', [
            'notifiable_type' => User::class,
            'notifiable_id' => $fixture['captain']->id,
        ]);

        $this->assertDatabaseHas('notifications', [
            'notifiable_type' => User::class,
            'notifiable_id' => $fixture['member']->id,
        ]);

        $captainNotification = $fixture['captain']->notifications()->first();
        $this->assertNotNull($captainNotification);
        $this->assertSame('team_join_request', $captainNotification->data['kind'] ?? null);
        $this->assertSame($fixture['asker']->id, $captainNotification->data['actor_user_id'] ?? null);
        $this->assertSame($fixture['teamId'], $captainNotification->data['team_id'] ?? null);

        $this->assertDatabaseMissing('notifications', [
            'notifiable_type' => User::class,
            'notifiable_id' => $fixture['asker']->id,
        ]);
    }

    public function test_accept_decision_dispatches_notification_job(): void
    {
        Queue::fake();

        $fixture = $this->seedCollectiveTeamWithActiveMembers();
        $this->seedPendingIntegrationRequest($fixture);
        $token = $fixture['captain']->createToken('auth')->plainTextToken;

        $this->patchJson(
            '/api/v1/auth/teams/'.$fixture['teamId'].'/integrations/'.$fixture['asker']->id,
            ['decision' => 'accept'],
            ['Authorization' => 'Bearer '.$token],
        )->assertOk();

        Queue::assertPushed(TeamIntegrationDecisionNotificationJob::class, function (TeamIntegrationDecisionNotificationJob $job) use ($fixture): bool {
            return $job->teamId === $fixture['teamId']
                && $job->applicantUserId === $fixture['asker']->id
                && $job->actorUserId === $fixture['captain']->id
                && $job->decision === 'accept';
        });
    }

    public function test_decision_job_notifies_applicant_on_accept(): void
    {
        Notification::fake();

        $fixture = $this->seedCollectiveTeamWithActiveMembers();
        $fixture['asker']->update(['fcm_token' => 'ExponentPushToken[test-asker]']);
        $this->seedPendingIntegrationRequest($fixture);

        DB::table('team_members')
            ->where('team_id', $fixture['teamId'])
            ->where('user_id', $fixture['asker']->id)
            ->update(['status' => 'active', 'updated_at' => now()]);

        $this->mock(ExpoPushService::class, function ($mock): void {
            $mock->shouldReceive('send')->once();
        });

        $job = new TeamIntegrationDecisionNotificationJob(
            $fixture['teamId'],
            $fixture['asker']->id,
            $fixture['captain']->id,
            'accept',
        );
        $job->handle(app(ExpoPushService::class));

        Notification::assertSentTo(
            $fixture['asker'],
            TeamIntegrationDecisionNotification::class,
            function (TeamIntegrationDecisionNotification $notification) use ($fixture): bool {
                $data = $notification->toArray($fixture['asker']);

                return $data['kind'] === 'team_join_accepted'
                    && $data['actor_user_id'] === $fixture['captain']->id
                    && $data['team_id'] === $fixture['teamId']
                    && str_contains((string) $data['message'], 'a accepté votre demande');
            },
        );

        Notification::assertNotSentTo($fixture['captain'], TeamIntegrationDecisionNotification::class);
    }

    public function test_decision_job_notifies_applicant_on_refuse(): void
    {
        Notification::fake();

        $fixture = $this->seedCollectiveTeamWithActiveMembers();
        $fixture['asker']->update(['fcm_token' => 'ExponentPushToken[test-asker]']);
        $this->seedPendingIntegrationRequest($fixture);

        DB::table('team_members')
            ->where('team_id', $fixture['teamId'])
            ->where('user_id', $fixture['asker']->id)
            ->update(['status' => 'rejected', 'updated_at' => now()]);

        $this->mock(ExpoPushService::class, function ($mock): void {
            $mock->shouldReceive('send')->once();
        });

        $job = new TeamIntegrationDecisionNotificationJob(
            $fixture['teamId'],
            $fixture['asker']->id,
            $fixture['captain']->id,
            'refuse',
        );
        $job->handle(app(ExpoPushService::class));

        Notification::assertSentTo(
            $fixture['asker'],
            TeamIntegrationDecisionNotification::class,
            function (TeamIntegrationDecisionNotification $notification) use ($fixture): bool {
                $data = $notification->toArray($fixture['asker']);

                return $data['kind'] === 'team_join_refused'
                    && str_contains((string) $data['message'], 'a refusé votre demande');
            },
        );
    }

    public function test_decision_job_skips_when_membership_still_pending(): void
    {
        Notification::fake();

        $fixture = $this->seedCollectiveTeamWithActiveMembers();
        $this->seedPendingIntegrationRequest($fixture);

        $this->mock(ExpoPushService::class, function ($mock): void {
            $mock->shouldNotReceive('send');
        });

        $job = new TeamIntegrationDecisionNotificationJob(
            $fixture['teamId'],
            $fixture['asker']->id,
            $fixture['captain']->id,
            'accept',
        );
        $job->handle(app(ExpoPushService::class));

        Notification::assertNothingSent();
    }
}
