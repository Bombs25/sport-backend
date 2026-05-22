<?php

namespace App\Jobs;

use App\Services\Sendbird\SendbirdMessagePushService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

/**
 * Envoie le push Expo correspondant à un message Sendbird reçu (webhook).
 * Quemé pour que le webhook réponde 200 immédiatement.
 */
class SendbirdMessagePushJob implements ShouldQueue
{
    use Queueable;

    public int $tries = 3;

    public int $timeout = 30;

    public function backoff(): array
    {
        return [5, 15];
    }

    /**
     * @param  array<string, mixed>  $payload  Corps du webhook Sendbird.
     */
    public function __construct(
        private readonly array $payload,
    ) {}

    public function handle(SendbirdMessagePushService $service): void
    {
        $service->handleMessageSendWebhook($this->payload);
    }
}
