<?php

namespace Tests\Feature\Api\V1\Posts;

use App\Jobs\RegularPostPublishedNotificationJob;
use App\Models\User;
use App\Notifications\RegularPostPublishedNotification;
use App\Services\Notifications\ExpoPushService;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class RegularPostPublishedNotificationTest extends TestCase
{
    use LazilyRefreshDatabase;

    public function test_post_store_dispatches_notification_job(): void
    {
        Queue::fake();
        Storage::fake('public');

        $author = User::factory()->create(['name' => 'Auteur Test']);
        $token = $author->createToken('auth')->plainTextToken;

        $this->postJson('/api/v1/auth/posts', [
            'body' => 'Mon premier post',
            'visibility' => 'public',
            'media' => [UploadedFile::fake()->image('p.jpg')],
        ], [
            'Authorization' => 'Bearer '.$token,
        ])->assertCreated();

        Queue::assertPushed(RegularPostPublishedNotificationJob::class, function (RegularPostPublishedNotificationJob $job) use ($author): bool {
            return $job->authorUserId === $author->id && $job->postId > 0;
        });
    }

    public function test_job_notifies_accepted_followers_not_author(): void
    {
        Notification::fake();

        $author = User::factory()->create(['name' => 'Auteur Post']);
        $follower = User::factory()->create([
            'name' => 'Abonné Test',
            'fcm_token' => 'ExponentPushToken[test-follower]',
        ]);

        $now = now();
        DB::table('follows')->insert([
            'follower_id' => $follower->id,
            'following_id' => $author->id,
            'status' => 'accepted',
            'created_at' => $now,
            'updated_at' => $now,
        ]);

        $postId = (int) DB::table('posts')->insertGetId([
            'user_id' => $author->id,
            'body' => 'Hello',
            'visibility' => 'public',
            'status' => 'published',
            'media_count' => 0,
            'total_likes' => 0,
            'total_comments' => 0,
            'total_shares' => 0,
            'published_at' => $now,
            'created_at' => $now,
            'updated_at' => $now,
        ]);

        $this->mock(ExpoPushService::class, function ($mock): void {
            $mock->shouldReceive('send')->once();
        });

        $job = new RegularPostPublishedNotificationJob($postId, $author->id);
        $job->handle(app(ExpoPushService::class));

        Notification::assertSentTo(
            $follower,
            RegularPostPublishedNotification::class,
            function (RegularPostPublishedNotification $notification) use ($author, $postId, $follower): bool {
                $data = $notification->toArray($follower);

                return $data['kind'] === 'regular_post_published'
                    && $data['actor_user_id'] === $author->id
                    && $data['post_id'] === $postId
                    && str_contains((string) $data['message'], 'Auteur Post')
                    && str_contains((string) $data['message'], 'a publié un post');
            },
        );

        Notification::assertNotSentTo($author, RegularPostPublishedNotification::class);
    }

    public function test_job_sends_nothing_when_author_has_no_followers(): void
    {
        Notification::fake();

        $author = User::factory()->create(['name' => 'Solo User']);

        $now = now();
        $postId = (int) DB::table('posts')->insertGetId([
            'user_id' => $author->id,
            'body' => 'Solo',
            'visibility' => 'public',
            'status' => 'published',
            'media_count' => 0,
            'total_likes' => 0,
            'total_comments' => 0,
            'total_shares' => 0,
            'published_at' => $now,
            'created_at' => $now,
            'updated_at' => $now,
        ]);

        $this->mock(ExpoPushService::class, function ($mock): void {
            $mock->shouldNotReceive('send');
        });

        $job = new RegularPostPublishedNotificationJob($postId, $author->id);
        $job->handle(app(ExpoPushService::class));

        Notification::assertNothingSent();
    }
}
