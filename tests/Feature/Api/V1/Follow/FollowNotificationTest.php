<?php

namespace Tests\Feature\Api\V1\Follow;

use App\Jobs\FollowNotificationJob;
use App\Models\User;
use App\Notifications\FollowNotification;
use App\Services\Notifications\ExpoPushService;
use App\Support\UserProfileLocation;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;

class FollowNotificationTest extends TestCase
{
    use LazilyRefreshDatabase;

    public function test_follow_public_profile_dispatches_new_follower_notification_job(): void
    {
        Queue::fake();

        $follower = User::factory()->create(['name' => 'Alice']);
        $target = User::factory()->create();
        $token = $follower->createToken('auth')->plainTextToken;

        $this->insertPublicProfile($target->id, 'target_user');

        $this->postJson('/api/v1/auth/follow', [
            'target_user_id' => $target->id,
        ], [
            'Authorization' => 'Bearer '.$token,
        ])->assertOk()
            ->assertJsonPath('data.status', 'accepted');

        Queue::assertPushed(FollowNotificationJob::class, function (FollowNotificationJob $job) use ($follower, $target): bool {
            return $job->kind === 'new_follower'
                && $job->actorUserId === $follower->id
                && $job->recipientUserId === $target->id;
        });
    }

    public function test_follow_private_profile_dispatches_follow_request_job(): void
    {
        Queue::fake();

        $follower = User::factory()->create(['name' => 'Bob']);
        $target = User::factory()->create();
        $token = $follower->createToken('auth')->plainTextToken;

        $this->insertPublicProfile($target->id, 'private_target', isPrivate: true);

        $this->postJson('/api/v1/auth/follow', [
            'target_user_id' => $target->id,
        ], [
            'Authorization' => 'Bearer '.$token,
        ])->assertOk()
            ->assertJsonPath('data.status', 'pending');

        Queue::assertPushed(FollowNotificationJob::class, function (FollowNotificationJob $job) use ($follower, $target): bool {
            return $job->kind === 'follow_request'
                && $job->actorUserId === $follower->id
                && $job->recipientUserId === $target->id;
        });
    }

    public function test_refollow_already_accepted_does_not_dispatch_notification_job(): void
    {
        Queue::fake();

        $follower = User::factory()->create();
        $target = User::factory()->create();
        $token = $follower->createToken('auth')->plainTextToken;

        DB::table('follows')->insert([
            'follower_id' => $follower->id,
            'following_id' => $target->id,
            'status' => 'accepted',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $this->postJson('/api/v1/auth/follow', [
            'target_user_id' => $target->id,
        ], [
            'Authorization' => 'Bearer '.$token,
        ])->assertOk()
            ->assertJsonPath('data.status', 'accepted');

        Queue::assertNothingPushed();
    }

    public function test_accept_follow_request_dispatches_follow_accepted_job(): void
    {
        Queue::fake();

        $privateUser = User::factory()->create(['name' => 'Carol']);
        $requester = User::factory()->create();
        $token = $privateUser->createToken('auth')->plainTextToken;

        $this->insertPublicProfile($privateUser->id, 'private_owner', isPrivate: true);

        $followRowId = DB::table('follows')->insertGetId([
            'follower_id' => $requester->id,
            'following_id' => $privateUser->id,
            'status' => 'pending',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $this->postJson('/api/v1/auth/follow-requests/accept', [
            'follow_request_id' => $followRowId,
        ], [
            'Authorization' => 'Bearer '.$token,
        ])->assertOk();

        Queue::assertPushed(FollowNotificationJob::class, function (FollowNotificationJob $job) use ($privateUser, $requester, $followRowId): bool {
            return $job->kind === 'follow_accepted'
                && $job->actorUserId === $privateUser->id
                && $job->recipientUserId === $requester->id
                && $job->followId === $followRowId;
        });
    }

    public function test_follow_notification_job_persists_database_notification(): void
    {
        Notification::fake();

        $follower = User::factory()->create(['name' => 'Diane']);
        $target = User::factory()->create();

        $followId = DB::table('follows')->insertGetId([
            'follower_id' => $follower->id,
            'following_id' => $target->id,
            'status' => 'accepted',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $job = new FollowNotificationJob(
            'new_follower',
            $follower->id,
            $target->id,
            $followId,
        );
        $job->handle(app(ExpoPushService::class));

        Notification::assertSentTo(
            $target,
            FollowNotification::class,
            function (FollowNotification $notification) use ($follower, $followId): bool {
                $data = $notification->toArray($follower);

                return $data['kind'] === 'new_follower'
                    && $data['actor_user_id'] === $follower->id
                    && $data['follow_id'] === $followId
                    && str_contains($data['message'], 'Diane');
            },
        );
    }

    public function test_follow_notification_job_skips_when_actor_is_recipient(): void
    {
        Notification::fake();

        $user = User::factory()->create();

        $followId = DB::table('follows')->insertGetId([
            'follower_id' => $user->id,
            'following_id' => $user->id,
            'status' => 'accepted',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $job = new FollowNotificationJob(
            'new_follower',
            $user->id,
            $user->id,
            $followId,
        );
        $job->handle(app(ExpoPushService::class));

        Notification::assertNothingSent();
    }

    private function insertPublicProfile(int $userId, string $handle, bool $isPrivate = false): void
    {
        DB::table('user_profiles')->insert(array_merge([
            'user_id' => $userId,
            'display_name' => 'User '.$userId,
            'handle' => $handle,
            'bio' => null,
            'avatar_url' => null,
            'is_private' => $isPrivate,
            'city' => null,
            'address_line' => null,
            'birth_date' => null,
            'created_at' => now(),
            'updated_at' => now(),
        ], UserProfileLocation::columnsFromLatLng(null, null)));
    }
}
