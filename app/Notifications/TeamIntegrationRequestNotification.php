<?php

namespace App\Notifications;

use Illuminate\Notifications\Notification;

class TeamIntegrationRequestNotification extends Notification
{
    public function __construct(
        private readonly int $actorUserId,
        private readonly int $teamId,
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
            'kind' => 'team_join_request',
            'actor_user_id' => $this->actorUserId,
            'team_id' => $this->teamId,
            'message' => $this->message,
        ];
    }
}
