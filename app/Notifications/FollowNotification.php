<?php

namespace App\Notifications;

use App\Support\NotificationType;
use Illuminate\Notifications\Notification;

class FollowNotification extends Notification
{
    public function __construct(
        private readonly string $kind,
        private readonly int $actorUserId,
        private readonly ?int $followId,
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
            'notif_type' => NotificationType::FOLLOW,
            'kind' => $this->kind,
            'actor_user_id' => $this->actorUserId,
            'follow_id' => $this->followId,
            'message' => $this->message,
        ];
    }
}
