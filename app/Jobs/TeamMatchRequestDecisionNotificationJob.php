<?php

namespace App\Jobs;

use App\Jobs\Concerns\SendsMatchTeamNotifications;
use App\Services\Notifications\ExpoPushService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\DB;

class TeamMatchRequestDecisionNotificationJob implements ShouldQueue
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
        public readonly string $decision,
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
        if (! in_array($this->decision, ['accept', 'refuse'], true)) {
            return;
        }

        $expectedStatus = $this->decision === 'accept' ? 'scheduled' : 'cancelled';
        $kind = $this->decision === 'accept' ? 'team_match_accepted' : 'team_match_refused';

        $match = DB::table('match_events')
            ->where('id', $this->matchEventId)
            ->where('status', $expectedStatus)
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
        $message = $this->decision === 'accept'
            ? sprintf('%s a accepté votre demande de match', $awayName)
            : sprintf('%s a refusé votre demande de match', $awayName);

        $this->notifyTeamRoster(
            $expoPushService,
            (int) $match->home_team_id,
            $this->actorUserId,
            $kind,
            $message,
            $awayName,
            $this->matchEventId,
            (int) $match->home_team_id,
            (int) $match->away_team_id,
        );
    }
}
