<?php

use App\Models\User;
use Illuminate\Support\Facades\Broadcast;

Broadcast::channel('file.upload.progress.{userId}.{type}', function (?User $user, int|string $userId, string $type): bool {
    if ($user === null) {
        return false;
    }

    return (int) $user->id === (int) $userId;
});
