<?php

namespace App\Jobs\Concerns;

use App\Models\User;
use App\Notifications\TeamMatchNotification;
use App\Services\Notifications\ExpoPushService;
use App\Support\TeamNotificationRecipients;
use Illuminate\Support\Facades\Notification;

trait SendsMatchTeamNotifications
{
    protected function notifyTeamRoster(
        ExpoPushService $expoPushService,
        int $recipientTeamId,
        int $actorUserId,
        string $kind,
        string $message,
        string $title,
        int $matchEventId,
        int $homeTeamId,
        int $awayTeamId,
    ): void {
        $recipientIds = TeamNotificationRecipients::activeMembersAndCreator($recipientTeamId, $actorUserId);

        if ($recipientIds === []) {
            return;
        }

        $recipients = User::query()->whereIn('id', $recipientIds)->get();

        foreach ($recipients as $recipient) {
            Notification::send($recipient, new TeamMatchNotification(
                $kind,
                $actorUserId,
                $matchEventId,
                $homeTeamId,
                $awayTeamId,
                $message,
            ));

            $rawToken = $recipient->fcm_token;
            if (! is_string($rawToken) || $rawToken === '') {
                logger()->warning(static::class.': destinataire sans fcm_token.', [
                    'recipient_id' => $recipient->id,
                    'match_event_id' => $matchEventId,
                ]);

                continue;
            }

            if (! str_starts_with($rawToken, 'ExponentPushToken[')) {
                logger()->warning(static::class.': fcm_token n\'est pas un jeton Expo.', [
                    'recipient_id' => $recipient->id,
                    'match_event_id' => $matchEventId,
                ]);

                continue;
            }

            $expoPushService->send(
                [$rawToken],
                $title,
                $message,
                null,
                [
                    'kind' => $kind,
                    'actor_user_id' => $actorUserId,
                    'match_event_id' => $matchEventId,
                    'home_team_id' => $homeTeamId,
                    'away_team_id' => $awayTeamId,
                    'message' => $message,
                ],
            );
        }
    }
}
