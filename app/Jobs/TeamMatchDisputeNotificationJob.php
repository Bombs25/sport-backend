<?php

namespace App\Jobs;

use App\Jobs\Concerns\SendsMatchTeamNotifications;
use App\Services\Notifications\ExpoPushService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\DB;

class TeamMatchDisputeNotificationJob implements ShouldQueue
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
            ->where('status', 'pending')
            ->select(['match_result_id'])
            ->first();

        if ($dispute === null) {
            return;
        }

        $result = DB::table('match_results')
            ->where('id', $dispute->match_result_id)
            ->where('status', 'refused')
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

        $awayTeam = DB::table('teams')->where('id', $match->away_team_id)->select('name')->first();
        if ($awayTeam === null) {
            return;
        }

        $awayName = (string) $awayTeam->name;
        $message = sprintf('%s a ouvert un litige sur le résultat du match', $awayName);

        $this->notifyTeamRoster(
            $expoPushService,
            (int) $match->home_team_id,
            $this->actorUserId,
            'score_dispute',
            $message,
            $awayName,
            $this->matchEventId,
            (int) $match->home_team_id,
            (int) $match->away_team_id,
        );
    }
}
