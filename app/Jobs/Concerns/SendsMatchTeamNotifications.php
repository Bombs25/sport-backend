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

            $expoToken = $recipient->routeNotificationForFcm();

            if ($expoToken === null) {
                continue;
            }

            $expoPushService->send(
                [$expoToken],
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
