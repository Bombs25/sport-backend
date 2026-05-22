<?php

namespace App\Support;

/**
 * Valeurs stables du champ `notif_type` présent dans le `data` de chaque
 * notification (in-app ET push). Discriminant unique consommé par le routeur de
 * deep-link côté app — voir `osport-app/src/lib/notifications/notificationRoute.ts`.
 */
final class NotificationType
{
    /** Nouveau commentaire sur une publication. */
    public const COMMENT = 'comment';

    /** Réponse à un commentaire. */
    public const COMMENT_REPLY = 'comment_reply';

    /** Like sur un commentaire. */
    public const LIKE_COMMENT = 'like_comment';

    /** Like sur une réponse de commentaire. */
    public const LIKE_COMMENT_RESPONSE = 'like_comment_response';

    /** Like sur une publication (résultat de match). */
    public const LIKE_POST = 'like_post';

    /** Publication d'un nouveau post régulier par un compte suivi. */
    public const POST_PUBLISHED = 'post_published';

    /** Événement de suivi (nouveau follower, demande, acceptation). */
    public const FOLLOW = 'follow';

    /** Événement lié à un match (demande, score, litige). */
    public const MATCH = 'match';

    /** Événement lié à une équipe (adhésion, départ). */
    public const TEAM = 'team';

    /** Changement de classement d'une équipe. */
    public const TEAM_RANK = 'team_rank';

    /** Changement de classement dans un sport. */
    public const SPORT_RANK = 'sport_rank';
}
