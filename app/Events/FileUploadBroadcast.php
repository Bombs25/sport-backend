<?php

namespace App\Events;

use App\Models\User;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

/**
 * Progression du pipeline d'upload / traitement d'images pour l'utilisateur connecté.
 * Canal privé : {@code file.upload.progress.{userId}} — événement {@code file.upload.progress}.
 *
 * Diffusion synchrone ({@see ShouldBroadcastNow}) pour que la WebView reçoive la progression sans
 * attendre la queue {@code post_notifications}.
 */
class FileUploadBroadcast implements ShouldBroadcastNow
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    /**
     * @param  array{
     *     batch_id?: string,
     *     percent: int,
     *     processed_jobs?: int,
     *     total_jobs?: int,
     *     pending_jobs?: int,
     *     failed_jobs?: int,
     *     progress_bar?: string
     * }  $payload
     */
    public function __construct(
        public User $user,
        public array $payload,
        public string $status = 'progress',
    ) {}

    /**
     * @return array<int, PrivateChannel>
     */
    public function broadcastOn(): array
    {
        return [
            new PrivateChannel("file.upload.progress.{$this->user->id}"),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function broadcastWith(): array
    {
        return [
            'user_id' => $this->user->id,
            'status' => $this->status,
            'percent' => $this->payload['percent'],
            'processed_jobs' => $this->payload['processed_jobs'] ?? null,
            'total_jobs' => $this->payload['total_jobs'] ?? null,
            'pending_jobs' => $this->payload['pending_jobs'] ?? null,
            'failed_jobs' => $this->payload['failed_jobs'] ?? null,
            'progress_bar' => $this->payload['progress_bar'] ?? null,
            'batch_id' => $this->payload['batch_id'] ?? null,
        ];
    }

    public function broadcastAs(): string
    {
        return 'file.upload.progress';
    }
}
