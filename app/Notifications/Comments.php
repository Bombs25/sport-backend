<?php

namespace App\Notifications;

use Illuminate\Notifications\Notification;

class Comments extends Notification
{
    /**
     * Create a new notification instance.
     */
    public function __construct(
        private readonly int $publicationId,
        private readonly int $userId,
        private readonly string $publicationType,
        private readonly string $content,
        private readonly ?string $senderName = null,
        private readonly bool $isResponse = false,
    ) {}

    /**
     * Determine which connections should be used for each notification channel.
     *
     * @return array<string, string>
     */
    public function viaConnections(): array
    {
        return [
            'database' => 'database',
        ];
    }

    /**
     * Determine which queues should be used for each notification channel.
     *
     * @return array<string, string>
     */
    public function viaQueues(): array
    {
        return [
            'database' => 'database',
        ];
    }

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
     * Get the array representation of the notification.
     *
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        return [
            'publication_id' => $this->publicationId,
            'publication_type' => $this->publicationType,
            'content' => $this->content,
            'user_id' => $this->userId,
            'is_response' => $this->isResponse,
            'message' => ($this->senderName ?? 'Un utilisateur').($this->isResponse
                ? ' a repondu a un commentaire'
                : ' a ajoute un nouveau commentaire'),
        ];
    }

    public function shouldSend(object $notifiable, string $channel): bool
    {
        return true;
    }

    /**
     * Handle the notification after it has been sent.
     */
    public function afterSending(object $notifiable, string $channel, mixed $response): void
    {
        // Log::info('Notification sent to '.$channel);
    }

    //  /**
    //  * Get the mail representation of the notification.
    //  */
    // public function toDatabase(object $notifiable): array
    // {
    //     return [
    //         'message' => 'Vous avez un nouveau commentaire',
    //     ];
    // }
}
