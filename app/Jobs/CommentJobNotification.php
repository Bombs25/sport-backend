<?php

namespace App\Jobs;

use App\Models\User;
use App\Notifications\Comments;
use App\Services\Notifications\ExpoPushService;
use Illuminate\Contracts\Queue\ShouldBeEncrypted;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Notification;

class CommentJobNotification implements ShouldBeEncrypted, ShouldQueue
{
    use Queueable;

    /**
     * The number of times the queued notification may be attempted.
     */
    public int $tries = 5;

    /**
     * The maximum number of unhandled exceptions to allow before failing.
     */
    public int $maxExceptions = 3;

    /**
     * The number of seconds the job can run before timing out.
     */
    public int $timeout = 60;

    /**
     * Calculate the number of seconds to wait before retrying the notification.
     */
    public function backoff(): array
    {
        return [1, 5, 10];
    }

    /**
     * Create a new job instance.
     */
    public function __construct(
        private readonly int $publicationId,
        private readonly int $userId,
        private readonly string $publicationType,
        private readonly string $content,
        private readonly bool $isResponse = false,
    ) {}

    public function handle(ExpoPushService $expoPushService): void
    {
        $senderName = User::query()
            ->whereKey($this->userId)
            ->value('name');

        // Resolve the two teams involved in the match result.
        $teamIds = DB::table('match_results')
            ->join('match_events', 'match_events.id', '=', 'match_results.match_event_id')
            ->where('match_results.id', $this->publicationId)
            ->select(['match_events.home_team_id', 'match_events.away_team_id'])
            ->first();

        // Stop if the match result has no linked match event.
        if ($teamIds === null) {
            return;
        }

        // Collect distinct active team members from both sides, excluding sender.
        $userIds = DB::table('team_members')
            ->whereIn('team_id', [$teamIds->home_team_id, $teamIds->away_team_id])
            ->where('status', 'active')
            ->where('user_id', '!=', $this->userId)
            ->distinct()
            ->pluck('user_id');

        // Nothing to notify if no eligible recipients were found.
        if ($userIds->isEmpty()) {
            return;
        }

        // Load notifiable user models from resolved recipient ids.
        $users = User::query()
            ->whereIn('id', $userIds)
            ->whereNotNull('fcm_token')
            ->where('fcm_token', '!=', '')
            ->get();

        // Dispatch the database notification payload to all recipients.
        $message = $this->isResponse
            ? ($senderName.' a repondu a un commentaire')
            : ($senderName.' a ajoute un nouveau commentaire');

        Notification::send($users, new Comments(
            $this->publicationId,
            $this->userId,
            $this->publicationType,
            $this->content,
            $senderName,
            $this->isResponse,
        ));

        $expoTokens = $users
            ->map(static fn (User $user): string|array => $user->routeNotificationForFcm())
            ->flatten()
            ->filter(static fn ($token): bool => is_string($token) && $token !== '')
            ->values()
            ->all();

        $data = [
            'publication_id' => $this->publicationId,
            'publication_type' => $this->publicationType,
            'content' => $this->content,
            'user_id' => $this->userId,
            'is_response' => $this->isResponse,
            'message' => $message,
        ];
        $expoPushService->send(
            $expoTokens,
            $message,
            $this->content,
            null,
            // "https://www.abcdrduson.com/wp-content/uploads/2014/07/Lil-Wayne-Tha-Carter-IV.jpg",
            json_encode($data) ?? null,
        );
    }
}
