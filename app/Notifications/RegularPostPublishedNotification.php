<?php

namespace App\Notifications;

use App\Support\NotificationType;
use Illuminate\Notifications\Notification;

class RegularPostPublishedNotification extends Notification
{
    public function __construct(
        private readonly int $actorUserId,
        private readonly int $postId,
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
            'notif_type' => NotificationType::POST_PUBLISHED,
            'kind' => 'regular_post_published',
            'actor_user_id' => $this->actorUserId,
            'post_id' => $this->postId,
            'message' => $this->message,
        ];
    }
}
