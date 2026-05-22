<?php

namespace App\Notifications;

use App\Support\NotificationType;
use Illuminate\Notifications\Notification;

class TeamMemberDestroyNotification extends Notification
{
    public function __construct(
        private readonly string $kind,
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
            'notif_type' => NotificationType::TEAM,
            'kind' => $this->kind,
            'actor_user_id' => $this->actorUserId,
            'team_id' => $this->teamId,
            'message' => $this->message,
        ];
    }
}
