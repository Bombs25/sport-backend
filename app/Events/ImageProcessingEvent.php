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
     * @param  array<int, UploadedFile>  $files  images raster validées côté listener
     * @param  string  $uniqueKey  préfixe métier unique pour staging, cache et noms sous {@code temps/} (ex. {@code team-{id}}, {@code images-{uuid}}, {@code post-{id}})
     * @param  ?int  $contextId  identifiant optionnel pour écouteurs métier (ex. {@code teams.id} après création) ; le pipeline image ne l’utilise pas directement
     */
    public function __construct(
        public User $user,
        public array $files,
        public string $uniqueKey,
        public ?int $contextId = null,
        public ImageVariantLongEdge $variant = ImageVariantLongEdge::Feed,
        public string $type = 'images',
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
