<?php

namespace App\Support;

use App\Services\Post\FetchPostService;

final class PostPublicationNotificationMessages
{
    public static function likeMessage(string $publicationType, ?string $actorName): string
    {
        $actor = $actorName ?? 'Un utilisateur';

        if ($publicationType === FetchPostService::PUBLICATION_TYPE_REGULAR) {
            return $actor.' a aimé votre publication';
        }

        return $actor.' a aime le resultat de ce match';
    }

    public static function commentMessage(
        string $publicationType,
        ?string $senderName,
        bool $isResponse,
    ): string {
        $sender = $senderName ?? 'Un utilisateur';

        if ($publicationType === FetchPostService::PUBLICATION_TYPE_REGULAR) {
            return $isResponse
                ? $sender.' a répondu sur votre publication'
                : $sender.' a commenté votre publication';
        }

        return $isResponse
            ? $sender.' a repondu a un commentaire'
            : $sender.' a ajoute un nouveau commentaire';
    }
}
