<?php

namespace App\Jobs;

use App\Models\User;
use App\Notifications\TeamIntegrationDecisionNotification;
use App\Services\Notifications\ExpoPushService;
use App\Support\NotificationType;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Notification;

class TeamIntegrationDecisionNotificationJob implements ShouldQueue
{
    use Dispatchable;
    use Queueable;

    public int $tries = 5;

    public int $maxExceptions = 3;

    public int $timeout = 60;

    public function __construct(
        public readonly int $teamId,
        public readonly int $applicantUserId,
        public readonly int $actorUserId,
        public readonly string $decision,
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
        if ($this->applicantUserId === $this->actorUserId) {
            return;
        }

        if (! in_array($this->decision, ['accept', 'refuse'], true)) {
            return;
        }

        $expectedStatus = $this->decision === 'accept' ? 'active' : 'rejected';

        $membershipValid = DB::table('team_members')
            ->where('team_id', $this->teamId)
            ->where('user_id', $this->applicantUserId)
            ->where('status', $expectedStatus)
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

        $recipient = User::query()->find($this->applicantUserId);
        if ($recipient === null) {
            return;
        }

        $teamName = (string) $team->name;
        $kind = $this->decision === 'accept' ? 'team_join_accepted' : 'team_join_refused';
        $message = $this->decision === 'accept'
            ? 'L\'équipe '.$teamName.' a accepté votre demande d\'intégration'
            : 'L\'équipe '.$teamName.' a refusé votre demande d\'intégration';
        $title = $teamName;

        Notification::send($recipient, new TeamIntegrationDecisionNotification(
            $kind,
            $this->actorUserId,
            $this->teamId,
            $message,
        ));

        $rawToken = $recipient->fcm_token;
        if (! is_string($rawToken) || $rawToken === '') {
            logger()->warning('TeamIntegrationDecisionNotificationJob: destinataire sans fcm_token.', [
                'recipient_id' => $this->applicantUserId,
                'team_id' => $this->teamId,
            ]);

            return;
        }

        if (! str_starts_with($rawToken, 'ExponentPushToken[')) {
            logger()->warning('TeamIntegrationDecisionNotificationJob: fcm_token n\'est pas un jeton Expo (probable FCM natif en base).', [
                'recipient_id' => $this->applicantUserId,
                'team_id' => $this->teamId,
            ]);

            return;
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
