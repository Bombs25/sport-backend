<?php

namespace App\Notifications;

use App\Support\NotificationType;
use Illuminate\Notifications\Notification;

class TeamTopRankChangeNotification extends Notification
{
    public function __construct(
        private readonly int $teamId,
        private readonly string $teamName,
        private readonly ?int $beforeRank,
        private readonly ?int $afterRank,
        private readonly string $changeType,
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
        $newPosition = $this->afterRank !== null ? (string) $this->afterRank : __('non classe');
        $message = $this->changeType === 'entered_top_3'
            ? "Bravo ! {$this->teamName} entre dans le top 3."
            : "{$this->teamName} sort du top 3 et passe a la position {$newPosition}.";

        return [
            'notif_type' => NotificationType::TEAM_RANK,
            'type' => 'team_top_rank_change',
            'team_id' => $this->teamId,
            'team_name' => $this->teamName,
            'before_rank' => $this->beforeRank,
            'after_rank' => $this->afterRank,
            'change_type' => $this->changeType,
            'message' => $message,
        ];
    }
}
