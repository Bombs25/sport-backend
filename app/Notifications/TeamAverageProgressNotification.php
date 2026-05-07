<?php

namespace App\Notifications;

use Illuminate\Notifications\Notification;

class TeamAverageProgressNotification extends Notification
{
    public function __construct(
        private readonly int $teamId,
        private readonly string $teamName,
        private readonly int $threshold,
        private readonly float $beforeAverage,
        private readonly float $afterAverage,
        private readonly ?int $currentRank,
    ) {}

    /**
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ['database'];
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        $rankLabel = $this->currentRank !== null ? (string) $this->currentRank : __('non classe');

        return [
            'type' => 'team_average_progress',
            'team_id' => $this->teamId,
            'team_name' => $this->teamName,
            'threshold' => $this->threshold,
            'before_average' => $this->beforeAverage,
            'after_average' => $this->afterAverage,
            'current_rank' => $this->currentRank,
            'message' => "Felicitation ! {$this->teamName} est maintenant classee #{$rankLabel} dans son sport.",
        ];
    }
}
