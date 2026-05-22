<?php

namespace App\Jobs;

use App\Models\User;
use App\Notifications\TeamMemberDestroyNotification;
use App\Services\Notifications\ExpoPushService;
use App\Support\NotificationType;
use App\Support\TeamNotificationRecipients;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Notification;

class TeamMemberDestroyNotificationJob implements ShouldQueue
{
    use Dispatchable;
    use Queueable;

    public int $tries = 5;

    public int $maxExceptions = 3;

    public int $timeout = 60;

    public function __construct(
        public readonly int $teamId,
        public readonly int $memberUserId,
        public readonly int $actorUserId,
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
        $membershipValid = DB::table('team_members')
            ->where('team_id', $this->teamId)
            ->where('user_id', $this->memberUserId)
            ->where('status', 'left')
            ->exists();

        if (! $membershipValid) {
            return;
        }

        $team = DB::table('teams')
            ->where('id', $this->teamId)
            ->select('id', 'name')
            ->first();

        if ($team === null) {
            return;
        }

        $teamName = (string) $team->name;
        $isSelfLeave = $this->actorUserId === $this->memberUserId;

        if ($isSelfLeave) {
            $memberName = User::query()->whereKey($this->memberUserId)->value('name') ?? 'Un membre';
            $kind = 'team_member_left';
            $message = $memberName.' a quitté l\'équipe '.$teamName;
            $title = $teamName;
            $recipientIds = TeamNotificationRecipients::activeMembersAndCreator(
                $this->teamId,
                $this->memberUserId,
            );
        } else {
            $actorName = User::query()->whereKey($this->actorUserId)->value('name') ?? 'Le capitaine';
            $kind = 'team_member_removed';
            $message = $actorName.' vous a retiré de l\'équipe '.$teamName;
            $title = $teamName;
            $recipientIds = [$this->memberUserId];
        }

        if ($recipientIds === []) {
            return;
        }

        $recipients = User::query()->whereIn('id', $recipientIds)->get();

        foreach ($recipients as $recipient) {
            Notification::send($recipient, new TeamMemberDestroyNotification(
                $kind,
                $this->actorUserId,
                $this->teamId,
                $message,
            ));

            $rawToken = $recipient->fcm_token;
            if (! is_string($rawToken) || $rawToken === '') {
                logger()->warning('TeamMemberDestroyNotificationJob: destinataire sans fcm_token.', [
                    'recipient_id' => $recipient->id,
                    'team_id' => $this->teamId,
                ]);

                continue;
            }

            if (! str_starts_with($rawToken, 'ExponentPushToken[')) {
                logger()->warning('TeamMemberDestroyNotificationJob: fcm_token n\'est pas un jeton Expo (probable FCM natif en base).', [
                    'recipient_id' => $recipient->id,
                    'team_id' => $this->teamId,
                ]);

                continue;
            }

            $data = [
                'notif_type' => NotificationType::TEAM,
                'kind' => $kind,
                'actor_user_id' => $this->actorUserId,
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
