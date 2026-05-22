<?php

namespace App\Notifications;

use App\Support\NotificationType;
use Illuminate\Notifications\Notification;

class ResponseCommentLikeNotification extends Notification
{
    public function __construct(
        private readonly int $publicationId,
        private readonly int $commentId,
        private readonly int $responseId,
        private readonly int $actorUserId,
        private readonly string $publicationType,
        private readonly ?string $actorName = null,
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
            'notif_type' => NotificationType::LIKE_COMMENT_RESPONSE,
            'publication_id' => $this->publicationId,
            'comment_id' => $this->commentId,
            'response_id' => $this->responseId,
            'publication_type' => $this->publicationType,
            'user_id' => $this->actorUserId,
            'message' => ($this->actorName ?? 'Un utilisateur').' a aime votre reponse',
        ];
    }
}
