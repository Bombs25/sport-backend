<?php

namespace App\Jobs;

use App\Models\User;
use App\Notifications\TeamIntegrationRequestNotification;
use App\Services\Notifications\ExpoPushService;
use App\Support\NotificationType;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Notification;

class TeamIntegrationNotificationJob implements ShouldQueue
{
    use Dispatchable;
    use Queueable;

    public int $tries = 5;

    public int $maxExceptions = 3;

    public int $timeout = 60;

    public function __construct(
        public readonly int $teamId,
        public readonly int $askerUserId,
    ) {}

    /**
     * @return array<int, int>
     */
    public function backoff(): array
    {
        return [1, 5, 10];
    }

    public function handle(ExpoPushService $expoPushService): void
    {
        $pendingMembership = DB::table('team_members')
            ->where('team_id', $this->teamId)
            ->where('user_id', $this->askerUserId)
            ->where('status', 'pending')
            ->exists();

        if (! $pendingMembership) {
            return;
        }

        $team = DB::table('teams')
            ->where('id', $this->teamId)
            ->select('id', 'name', 'creator_id')
            ->first();

        if ($team === null) {
            return;
        }

        $askerName = User::query()->whereKey($this->askerUserId)->value('name');
        $teamName = (string) $team->name;
        $message = ($askerName ?? 'Un utilisateur').' souhaite rejoindre l\'équipe '.$teamName;
        $title = $askerName ?? 'Un utilisateur';

        $recipientIds = DB::table('team_members')
            ->where('team_id', $this->teamId)
            ->where('status', 'active')
            ->where('user_id', '!=', $this->askerUserId)
            ->pluck('user_id')
            ->map(static fn ($id): int => (int) $id)
            ->all();

        $creatorId = (int) $team->creator_id;
        if ($creatorId !== $this->askerUserId && ! in_array($creatorId, $recipientIds, true)) {
            $recipientIds[] = $creatorId;
        }

        $recipientIds = array_values(array_unique($recipientIds));

        if ($recipientIds === []) {
            return;
        }

        $recipients = User::query()->whereIn('id', $recipientIds)->get();

        foreach ($recipients as $recipient) {
            Notification::send($recipient, new TeamIntegrationRequestNotification(
                $this->askerUserId,
                $this->teamId,
                $message,
            ));

            $rawToken = $recipient->fcm_token;
            if (! is_string($rawToken) || $rawToken === '') {
                logger()->warning('TeamIntegrationNotificationJob: destinataire sans fcm_token.', [
                    'recipient_id' => $recipient->id,
                    'team_id' => $this->teamId,
                ]);

                continue;
            }

            if (! str_starts_with($rawToken, 'ExponentPushToken[')) {
                logger()->warning('TeamIntegrationNotificationJob: fcm_token n\'est pas un jeton Expo (probable FCM natif en base).', [
                    'recipient_id' => $recipient->id,
                    'team_id' => $this->teamId,
                ]);

                continue;
            }

            $data = [
                'notif_type' => NotificationType::TEAM,
                'kind' => 'team_join_request',
                'actor_user_id' => $this->askerUserId,
                'team_id' => $this->teamId,
                'message' => $message,
            ];

            $expoPushService->send(
                [$rawToken],
                $title,
                $message,
                null,
                $data,
            );
        }
    }
}
