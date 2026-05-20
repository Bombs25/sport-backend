<?php

namespace App\Jobs;

use App\Jobs\Concerns\SendsMatchTeamNotifications;
use App\Services\Notifications\ExpoPushService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\DB;

class TeamMatchScoreValidatedNotificationJob implements ShouldQueue
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
            ->where('status', 'finished')
            ->select(['id', 'home_team_id', 'away_team_id'])
            ->first();

        if ($match === null) {
            return;
        }

        $result = DB::table('match_results')
            ->where('match_event_id', $this->matchEventId)
            ->where('status', 'validated')
            ->select(['home_score', 'away_score'])
            ->first();

        if ($result === null) {
            return;
        }

        $awayTeam = DB::table('teams')->where('id', $match->away_team_id)->select('name')->first();
        if ($awayTeam === null) {
            return;
        }

        $awayName = (string) $awayTeam->name;
        $message = sprintf(
            '%s a validé le score (%d-%d)',
            $awayName,
            (int) $result->home_score,
            (int) $result->away_score,
        );

        $this->notifyTeamRoster(
            $expoPushService,
            (int) $match->home_team_id,
            $this->actorUserId,
            'team_match_score_validated',
            $message,
            $awayName,
            $this->matchEventId,
            (int) $match->home_team_id,
            (int) $match->away_team_id,
        );
    }
}
