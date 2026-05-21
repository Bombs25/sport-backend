<?php

namespace App\Jobs\Team;

use App\Models\User;
use App\Notifications\SportTopRankChangeNotification;
use App\Services\Notifications\ExpoPushService;
use DateTime;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Notification;

class NotifySportTopRankChangeJob implements ShouldQueue
{
    use Dispatchable;
    use Queueable;

    public int $maxExceptions = 3;

    public function __construct(
        private readonly int $sportId,
        private readonly int $teamId,
        private readonly string $teamName,
        private readonly ?int $beforeRank,
        private readonly ?int $afterRank,
        private readonly string $changeType,
    ) {
        $this->onConnection('app_main_cache');
        $this->onQueue('sport-rank-notifications');
    }

    public function handle(ExpoPushService $expoPushService): void
    {
        $recipientIds = DB::table('team_members')
            ->join('teams', 'teams.id', '=', 'team_members.team_id')
            ->where('teams.sport_id', $this->sportId)
            ->where('team_members.status', 'active')
            ->pluck('team_members.user_id')
            ->merge(
                DB::table('teams')
                    ->where('sport_id', $this->sportId)
                    ->pluck('creator_id')
            )
            ->filter(static fn ($id): bool => $id !== null)
            ->unique()
            ->values();

        if ($recipientIds->isEmpty()) {
            return;
        }

        $recipients = User::query()->whereIn('id', $recipientIds)->get();
        if ($recipients->isEmpty()) {
            return;
        }

        Notification::send($recipients, new SportTopRankChangeNotification(
            $this->sportId,
            $this->teamId,
            $this->teamName,
            $this->beforeRank,
            $this->afterRank,
            $this->changeType,
        ));

        $message = $this->changeType === 'entered_top_3'
            ? "{$this->teamName} vient d'entrer dans le top 3 du classement."
            : "{$this->teamName} vient de sortir du top 3 du classement.";
        $expoTokens = User::expoPushTokensFrom($recipients);

        if ($expoTokens === []) {
            return;
        }

        $expoPushService->send(
            $expoTokens,
            $this->teamName,
            $message,
            null,
            json_encode([
                'type' => 'sport_top_rank_change',
                'sport_id' => $this->sportId,
                'team_id' => $this->teamId,
                'team_name' => $this->teamName,
                'before_rank' => $this->beforeRank,
                'after_rank' => $this->afterRank,
                'change_type' => $this->changeType,
                'message' => $message,
            ]) ?: null,
        );
    }

    public function backoff(): array
    {
        return [10, 20, 30];
    }

    public function retryUntil(): DateTime
    {
        return (new DateTime)->add(new \DateInterval('PT24H'));
    }
}
