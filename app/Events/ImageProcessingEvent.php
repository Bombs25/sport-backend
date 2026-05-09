<?php

namespace App\Events;

use App\Enums\ImageVariantLongEdge;
use App\Models\User;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Http\UploadedFile;
use Illuminate\Queue\SerializesModels;

class ImageProcessingEvent
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    /**
     * @param  array<int, UploadedFile>  $files
     * @param  ?int  $teamId  si défini, le listener met à jour `teams.cover_image_url` / `logo_url` (originaux stockés)
     */
    public function __construct(
        public User $user,
        public array $files,
        public string $uniqueKey,
        public ?int $teamId = null,
        public ImageVariantLongEdge $variant = ImageVariantLongEdge::Feed,
    ) {}

    /**
     * Get the channels the event should broadcast on.
     *
     * @return array<int, Channel>
     */
    public function broadcastOn(): array
    {
        return [
            new Channel('image-processing'),
        ];
    }
}
