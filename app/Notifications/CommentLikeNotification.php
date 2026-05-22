<?php

namespace App\Notifications;

use App\Support\NotificationType;
use Illuminate\Notifications\Notification;

class CommentLikeNotification extends Notification
{
    public function __construct(
        private readonly int $publicationId,
        private readonly int $commentId,
        private readonly int $actorUserId,
        private readonly string $publicationType,
        private readonly ?string $actorName = null,
    ) {}

    /**
     * Get the notification's delivery channels.
     *
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
            'notif_type' => NotificationType::LIKE_COMMENT,
            'publication_id' => $this->publicationId,
            'comment_id' => $this->commentId,
            'publication_type' => $this->publicationType,
            'user_id' => $this->actorUserId,
            'message' => ($this->actorName ?? 'Un utilisateur').' a aime votre commentaire',
        ];
    }
}
