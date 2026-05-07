<?php

namespace App\Notifications;

use Illuminate\Notifications\Notification;

class SportTopRankChangeNotification extends Notification
{
    public function __construct(
        private readonly int $sportId,
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
        $message = $this->changeType === 'entered_top_3'
            ? "{$this->teamName} vient d'entrer dans le top 3 du classement."
            : "{$this->teamName} vient de sortir du top 3 du classement.";

        return [
            'type' => 'sport_top_rank_change',
            'sport_id' => $this->sportId,
            'team_id' => $this->teamId,
            'team_name' => $this->teamName,
            'before_rank' => $this->beforeRank,
            'after_rank' => $this->afterRank,
            'change_type' => $this->changeType,
            'message' => $message,
        ];
    }
}
