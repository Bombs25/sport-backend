<?php

namespace App\Services\Sendbird;

use App\Services\Notifications\ExpoPushService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

/**
 * Traduit un webhook Sendbird `group_channel:message_send` en notification push Expo.
 *
 * O'Sport n'utilise pas le push natif Sendbird (l'app a des jetons Expo, pas FCM) :
 * Sendbird envoie un webhook, et c'est `ExpoPushService` qui délivre le push.
 */
class SendbirdMessagePushService
{
    /** Longueur max de l'extrait de message dans la notification. */
    private const PREVIEW_LENGTH = 140;

    public function __construct(
        private readonly ExpoPushService $expoPushService,
    ) {}

    /**
     * @param  array<string, mixed>  $payload  Corps JSON du webhook Sendbird.
     */
    public function handleMessageSendWebhook(array $payload): void
    {
        $senderSendbirdId = (string) data_get($payload, 'sender.user_id', '');
        $channelUrl = (string) data_get($payload, 'channel.channel_url', '');
        $text = (string) data_get($payload, 'payload.message', '');
        $senderName = trim((string) data_get($payload, 'sender.nickname', '')) ?: 'Nouveau message';

        if ($channelUrl === '' || $senderSendbirdId === '') {
            return;
        }

        $recipientSendbirdIds = $this->resolveOfflineRecipients($payload, $senderSendbirdId);
        if ($recipientSendbirdIds === []) {
            return;
        }

        $tokens = DB::table('sendbird_accounts')
            ->join('users', 'users.id', '=', 'sendbird_accounts.user_id')
            ->whereIn('sendbird_accounts.sendbird_user_id', $recipientSendbirdIds)
            ->whereNotNull('users.fcm_token')
            ->pluck('users.fcm_token')
            ->all();

        if ($tokens === []) {
            return;
        }

        $this->expoPushService->send(
            $tokens,
            $senderName,
            $this->preview($text),
            null,
            ['type' => 'chat', 'channel_url' => $channelUrl],
        );
    }

    /**
     * Destinataires à notifier : tous les membres du canal hors ligne, sauf
     * l'expéditeur. Sendbird marque `is_online = false` quand le SDK n'est pas
     * connecté (app fermée / arrière-plan) → c'est notre filtre « absent ».
     *
     * @param  array<string, mixed>  $payload
     * @return array<int, string>
     */
    private function resolveOfflineRecipients(array $payload, string $senderSendbirdId): array
    {
        $members = data_get($payload, 'members', []);
        if (! is_array($members)) {
            return [];
        }

        $recipients = [];
        foreach ($members as $member) {
            $userId = (string) data_get($member, 'user_id', '');
            if ($userId === '' || $userId === $senderSendbirdId) {
                continue;
            }
            // `is_online` absent => on notifie par prudence.
            $isOnline = (bool) data_get($member, 'is_online', false);
            if (! $isOnline) {
                $recipients[] = $userId;
            }
        }

        return array_values(array_unique($recipients));
    }

    private function preview(string $text): string
    {
        $clean = trim($text);
        if ($clean === '') {
            return '📎 Photo';
        }

        return Str::limit($clean, self::PREVIEW_LENGTH);
    }
}
