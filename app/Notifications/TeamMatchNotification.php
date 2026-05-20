<?php

namespace App\Notifications;

use Illuminate\Notifications\Notification;

class TeamMatchNotification extends Notification
{
    public function __construct(
        private readonly string $kind,
        private readonly int $actorUserId,
        private readonly int $matchEventId,
        private readonly int $homeTeamId,
        private readonly int $awayTeamId,
        private readonly string $message,
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
        return [
            'kind' => $this->kind,
            'actor_user_id' => $this->actorUserId,
            'match_event_id' => $this->matchEventId,
            'home_team_id' => $this->homeTeamId,
            'away_team_id' => $this->awayTeamId,
            'message' => $this->message,
        ];
    }
}
