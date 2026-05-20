<?php

namespace App\Jobs;

use App\Jobs\Concerns\SendsMatchTeamNotifications;
use App\Services\Notifications\ExpoPushService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\DB;

class TeamMatchDisputeResolvedNotificationJob implements ShouldQueue
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
        public readonly int $matchResultDisputeId,
        public readonly string $resolution,
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
        $dispute = DB::table('match_result_disputes')
            ->where('id', $this->matchResultDisputeId)
            ->whereNotIn('status', ['pending', 'under_review'])
            ->first();

        if ($dispute === null) {
            return;
        }

        $result = DB::table('match_results')
            ->where('id', $dispute->match_result_id)
            ->select(['match_event_id'])
            ->first();

        if ($result === null || (int) $result->match_event_id !== $this->matchEventId) {
            return;
        }

        $match = DB::table('match_events')
            ->where('id', $this->matchEventId)
            ->select(['id', 'home_team_id', 'away_team_id'])
            ->first();

        if ($match === null) {
            return;
        }

        $message = match ($this->resolution) {
            'resolved_away' => 'Le litige sur le résultat a été tranché : score validé',
            'resolved_home' => 'Le litige est clos : vous pouvez re-soumettre le score',
            'dismissed' => 'Le litige est clos sans modification du score',
            default => 'Le litige sur le résultat a été mis à jour',
        };

        $this->notifyTeamRoster(
            $expoPushService,
            (int) $match->home_team_id,
            $this->actorUserId,
            'score_dispute_resolved',
            $message,
            null,
            $this->matchEventId,
            (int) $match->home_team_id,
            (int) $match->away_team_id,
        );

        $this->notifyTeamRoster(
            $expoPushService,
            (int) $match->away_team_id,
            $this->actorUserId,
            'score_dispute_resolved',
            $message,
            null,
            $this->matchEventId,
            (int) $match->home_team_id,
            (int) $match->away_team_id,
        );
    }
}
