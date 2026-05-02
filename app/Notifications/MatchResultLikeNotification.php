<?php

namespace App\Notifications;

use Illuminate\Notifications\Notification;

class MatchResultLikeNotification extends Notification
{
    public function __construct(
        private readonly int $matchResultId,
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
            'publication_id' => $this->matchResultId,
            'publication_type' => $this->publicationType,
            'user_id' => $this->actorUserId,
            'message' => ($this->actorName ?? 'Un utilisateur').' a aime le resultat de ce match',
        ];
    }
}
