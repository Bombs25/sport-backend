<?php

namespace App\Jobs;

use App\Models\User;
use App\Notifications\RegularPostPublishedNotification;
use App\Services\Notifications\ExpoPushService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Notification;

class RegularPostPublishedNotificationJob implements ShouldQueue
{
    use Dispatchable;
    use Queueable;

    public int $tries = 5;

    public int $maxExceptions = 3;

    public int $timeout = 60;

    public function __construct(
        public readonly int $postId,
        public readonly int $authorUserId,
    ) {}

    /**
     * @return array<int, int>
     */
    public function backoff(): array
    {
        return [1, 5, 10];
    }

    public function handle(ExpoPushService $expoPushService): void
    {
        $post = DB::table('posts')
            ->where('id', $this->postId)
            ->where('user_id', $this->authorUserId)
            ->where('status', 'published')
            ->first();

        if ($post === null) {
            return;
        }

        $followerIds = DB::table('follows')
            ->where('following_id', $this->authorUserId)
            ->where('status', 'accepted')
            ->where('follower_id', '<>', $this->authorUserId)
            ->pluck('follower_id')
            ->map(fn (mixed $id): int => (int) $id)
            ->unique()
            ->values()
            ->all();

        if ($followerIds === []) {
            return;
        }

        $recipients = User::query()->whereIn('id', $followerIds)->get();

        if ($recipients->isEmpty()) {
            return;
        }

        $actorName = User::query()->whereKey($this->authorUserId)->value('name');
        $title = $actorName ?? 'Un utilisateur';
        $message = ($actorName ?? 'Un utilisateur').' a publié un post';

        Notification::send($recipients, new RegularPostPublishedNotification(
            $this->authorUserId,
            $this->postId,
            $message,
        ));

        $expoTokens = $this->resolveExpoTokens($recipients);

        if ($expoTokens === []) {
            return;
        }

        $data = [
            'kind' => 'regular_post_published',
            'actor_user_id' => $this->authorUserId,
            'post_id' => $this->postId,
            'message' => $message,
        ];

        $expoPushService->send(
            $expoTokens,
            $title,
            $message,
            null,
            json_encode($data) ?: null,
        );
    }

    /**
     * @param  Collection<int, User>  $users
     * @return list<string>
     */
    private function resolveExpoTokens(Collection $users): array
    {
        return $users
            ->map(static fn (User $user) => $user->routeNotificationForFcm())
            ->flatten()
            ->filter(static function (mixed $token): bool {
                return is_string($token)
                    && $token !== ''
                    && str_starts_with($token, 'ExponentPushToken[');
            })
            ->values()
            ->all();
    }
}
