<?php

namespace Tests\Feature\Api\V1\Teams;

use App\Jobs\TeamMemberDestroyNotificationJob;
use App\Models\User;
use App\Notifications\TeamMemberDestroyNotification;
use App\Services\Notifications\ExpoPushService;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;

class TeamMemberDestroyNotificationTest extends TestCase
{
    use LazilyRefreshDatabase;

    /**
     * @return array{
     *     captain: User,
     *     member: User,
     *     leaver: User,
     *     teamId: int,
     * }
     */
    private function seedTeamFixture(): array
    {
        $captain = User::factory()->create([
            'fcm_token' => 'ExponentPushToken[test-captain]',
        ]);
        $member = User::factory()->create([
            'fcm_token' => 'ExponentPushToken[test-member]',
        ]);
        $leaver = User::factory()->create([
            'fcm_token' => 'ExponentPushToken[test-leaver]',
        ]);

        $now = now();
        $teamId = DB::table('teams')->insertGetId([
            'creator_id' => $captain->id,
            'sport_id' => 1,
            'name' => 'Equipe Notif Destroy '.uniqid('', true),
            'slug' => 'equipe-notif-destroy-'.uniqid('', true),
            'competition_type' => 'leisure',
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
            [
                'team_id' => $teamId,
                'user_id' => $leaver->id,
                'role' => 'member',
                'status' => 'active',
                'created_at' => $now,
                'updated_at' => $now,
            ],
        ]);

        return [
            'captain' => $captain,
            'member' => $member,
            'leaver' => $leaver,
            'teamId' => $teamId,
        ];
    }

    public function test_captain_remove_dispatches_notification_job(): void
    {
        Queue::fake();

        $fixture = $this->seedTeamFixture();
        $token = $fixture['captain']->createToken('auth')->plainTextToken;

        $this->deleteJson(
            '/api/v1/auth/teams/'.$fixture['teamId'].'/members/'.$fixture['member']->id,
            [],
            ['Authorization' => 'Bearer '.$token],
        )->assertOk();

        Queue::assertPushed(TeamMemberDestroyNotificationJob::class, function (TeamMemberDestroyNotificationJob $job) use ($fixture): bool {
            return $job->teamId === $fixture['teamId']
                && $job->memberUserId === $fixture['member']->id
                && $job->actorUserId === $fixture['captain']->id;
        });
    }

    public function test_remove_job_notifies_removed_member_only(): void
    {
        Notification::fake();

        $fixture = $this->seedTeamFixture();
        $fixture['member']->update(['fcm_token' => 'ExponentPushToken[test-removed]']);

        DB::table('team_members')
            ->where('team_id', $fixture['teamId'])
            ->where('user_id', $fixture['member']->id)
            ->update(['status' => 'left', 'updated_at' => now()]);

        $this->mock(ExpoPushService::class, function ($mock): void {
            $mock->shouldReceive('send')->once();
        });

        $job = new TeamMemberDestroyNotificationJob(
            $fixture['teamId'],
            $fixture['member']->id,
            $fixture['captain']->id,
        );
        $job->handle(app(ExpoPushService::class));

        Notification::assertSentTo(
            $fixture['member'],
            TeamMemberDestroyNotification::class,
            function (TeamMemberDestroyNotification $notification) use ($fixture): bool {
                $data = $notification->toArray($fixture['member']);

                return $data['kind'] === 'team_member_removed'
                    && $data['actor_user_id'] === $fixture['captain']->id
                    && $data['team_id'] === $fixture['teamId']
                    && str_contains((string) $data['message'], 'vous a retiré');
            },
        );

        Notification::assertNotSentTo($fixture['captain'], TeamMemberDestroyNotification::class);
    }

    public function test_self_leave_dispatches_notification_job(): void
    {
        Queue::fake();

        $fixture = $this->seedTeamFixture();
        $token = $fixture['leaver']->createToken('auth')->plainTextToken;

        $this->deleteJson(
            '/api/v1/auth/teams/'.$fixture['teamId'].'/members/'.$fixture['leaver']->id,
            [],
            ['Authorization' => 'Bearer '.$token],
        )->assertOk();

        Queue::assertPushed(TeamMemberDestroyNotificationJob::class, function (TeamMemberDestroyNotificationJob $job) use ($fixture): bool {
            return $job->teamId === $fixture['teamId']
                && $job->memberUserId === $fixture['leaver']->id
                && $job->actorUserId === $fixture['leaver']->id;
        });
    }

    public function test_self_leave_job_notifies_remaining_members_not_leaver(): void
    {
        Notification::fake();

        $fixture = $this->seedTeamFixture();

        DB::table('team_members')
            ->where('team_id', $fixture['teamId'])
            ->where('user_id', $fixture['leaver']->id)
            ->update(['status' => 'left', 'updated_at' => now()]);

        $this->mock(ExpoPushService::class, function ($mock): void {
            $mock->shouldReceive('send')->twice();
        });

        $job = new TeamMemberDestroyNotificationJob(
            $fixture['teamId'],
            $fixture['leaver']->id,
            $fixture['leaver']->id,
        );
        $job->handle(app(ExpoPushService::class));

        Notification::assertSentTo(
            $fixture['captain'],
            TeamMemberDestroyNotification::class,
            function (TeamMemberDestroyNotification $notification) use ($fixture): bool {
                $data = $notification->toArray($fixture['captain']);

                return $data['kind'] === 'team_member_left'
                    && $data['actor_user_id'] === $fixture['leaver']->id
                    && str_contains((string) $data['message'], 'a quitté');
            },
        );
        Notification::assertSentTo($fixture['member'], TeamMemberDestroyNotification::class);
        Notification::assertNotSentTo($fixture['leaver'], TeamMemberDestroyNotification::class);
    }

    public function test_job_skips_when_membership_not_left(): void
    {
        Notification::fake();

        $fixture = $this->seedTeamFixture();

        $this->mock(ExpoPushService::class, function ($mock): void {
            $mock->shouldNotReceive('send');
        });

        $job = new TeamMemberDestroyNotificationJob(
            $fixture['teamId'],
            $fixture['member']->id,
            $fixture['captain']->id,
        );
        $job->handle(app(ExpoPushService::class));

        Notification::assertNothingSent();
    }
}
