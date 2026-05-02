<?php

namespace App\Jobs;

use App\Models\User;
use App\Notifications\MatchResultLikeNotification;
use App\Services\Notifications\ExpoPushService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Notification;

class MatchResultLikeNotificationJob implements ShouldQueue
{
    use Dispatchable;
    use Queueable;

    public function __construct(
        private readonly int $matchResultId,
        private readonly int $actorUserId,
        private readonly string $publicationType,
        private readonly string $action,
    ) {}

    public int $tries = 5;

    public int $maxExceptions = 3;

    public int $timeout = 60;

    public function backoff(): array
    {
        return [1, 5, 10];
    }

    public function handle(ExpoPushService $expoPushService): void
    {
        if ($this->action !== 'like') {
            return;
        }

        $likeExists = DB::table('post_likes')
            ->where('users_id', $this->actorUserId)
            ->where('publication_id', $this->matchResultId)
            ->where('publication_type', $this->publicationType)
            ->exists();

        if (! $likeExists) {
            return;
        }

        $teamIds = DB::table('match_results')
            ->join('match_events', 'match_events.id', '=', 'match_results.match_event_id')
            ->where('match_results.id', $this->matchResultId)
            ->select(['match_events.home_team_id', 'match_events.away_team_id'])
            ->first();

        if ($teamIds === null) {
            return;
        }

        $userIds = DB::table('team_members')
            ->whereIn('team_id', [$teamIds->home_team_id, $teamIds->away_team_id])
            ->where('status', 'active')
            ->where('user_id', '!=', $this->actorUserId)
            ->distinct()
            ->pluck('user_id');

        if ($userIds->isEmpty()) {
            return;
        }

        $users = User::query()->whereIn('id', $userIds)->get();

        if ($users->isEmpty()) {
            return;
        }

        $actorName = User::query()->whereKey($this->actorUserId)->value('name');
        $message = ($actorName ?? 'Un utilisateur').' a aime le resultat de ce match';
        $title = $actorName ?? 'Un utilisateur';

        Notification::send($users, new MatchResultLikeNotification(
            $this->matchResultId,
            $this->actorUserId,
            $this->publicationType,
            $actorName,
        ));

        $expoTokens = $users
            ->map(static fn (User $user) => $user->routeNotificationForFcm())
            ->flatten()
            ->filter(static fn ($token): bool => is_string($token) && $token !== '')
            ->values()
            ->all();

        $data = [
            'publication_id' => $this->matchResultId,
            'publication_type' => $this->publicationType,
            'user_id' => $this->actorUserId,
            'message' => $message,
        ];

        $expoPushService->send(
            $expoTokens,
            $title,
            $message,
            null,
            json_encode($data) ?? null,
        );
    }
}
