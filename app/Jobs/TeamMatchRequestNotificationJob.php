<?php

namespace App\Jobs;

use App\Jobs\Concerns\SendsMatchTeamNotifications;
use App\Models\User;
use App\Services\Notifications\ExpoPushService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\DB;

class TeamMatchRequestNotificationJob implements ShouldQueue
{
    use Dispatchable;
    use Queueable;
    use SendsMatchTeamNotifications;

    public int $tries = 5;

    public int $maxExceptions = 3;

    public int $timeout = 60;

    public function __construct(
        public readonly int $matchEventId,
        public readonly int $actorUserId,
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
        $match = DB::table('match_events')
            ->where('id', $this->matchEventId)
            ->where('status', 'requested')
            ->select(['id', 'home_team_id', 'away_team_id', 'scheduled_at'])
            ->first();

        if ($match === null) {
            return;
        }

        $homeTeam = DB::table('teams')->where('id', $match->home_team_id)->select('name')->first();
        if ($homeTeam === null) {
            return;
        }

        $actorName = User::query()->whereKey($this->actorUserId)->value('name');
        $homeName = (string) $homeTeam->name;
        $scheduledAt = $match->scheduled_at;
        $dateLabel = $scheduledAt !== null
            ? (new \DateTimeImmutable((string) $scheduledAt))->format('d/m/Y H:i')
            : '';

        $message = $dateLabel !== ''
            ? sprintf('%s souhaite un match le %s', $actorName ?? $homeName, $dateLabel)
            : sprintf('%s souhaite un match', $homeName);

        $this->notifyTeamRoster(
            $expoPushService,
            (int) $match->away_team_id,
            $this->actorUserId,
            'match_invite',
            $message,
            $homeName,
            $this->matchEventId,
            (int) $match->home_team_id,
            (int) $match->away_team_id,
        );
    }
}
