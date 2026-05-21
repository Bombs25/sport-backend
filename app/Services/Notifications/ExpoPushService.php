<?php

namespace App\Services\Notifications;

use App\Models\User;
use Illuminate\Support\Facades\Http;
use RuntimeException;

class ExpoPushService
{
    /**
     * Envoie une notification push via Expo à la liste de tokens fournie.
     *
     * Les tokens doivent provenir de {@see User::routeNotificationForFcm()} / {@see User::expoPushTokensFrom()}.
     * Les erreurs par destinataire (jeton invalide, app désinstallée, etc.) sont journalisées
     * sans faire échouer le job : la notification in-app a déjà été persistée.
     *
     * @param  array<int, string>  $tokens
     * @param  array<string, mixed>|string|null  $data
     */
    public function send(array $tokens, string $title, string $body, ?string $imageUrl = null, array|string|null $data = null): void
    {
        $expoTokens = $this->filterExpoTokens($tokens);

        if ($expoTokens === []) {
            if ($tokens !== []) {
                logger()->debug('Expo push skipped: aucun jeton Expo exploitable (voir User::routeNotificationForFcm).', [
                    'received' => count($tokens),
                ]);
            }

            return;
        }

        $payloadData = match (true) {
            is_array($data) => $data,
            is_string($data) && $data !== '' => json_decode($data, true) ?? ['raw' => $data],
            default => null,
        };

        $messages = array_map(
            static function (string $token) use ($title, $body, $imageUrl, $payloadData): array {
                $message = [
                    'to' => $token,
                    'title' => $title,
                    'body' => $body,
                    'sound' => 'default',
                ];

                if ($payloadData !== null) {
                    $message['data'] = $payloadData;
                }

                if (is_string($imageUrl) && $imageUrl !== '') {
                    $message['richContent'] = [
                        'image' => $imageUrl,
                    ];
                    $message['data'] = [
                        'image' => $imageUrl,
                    ];
                }

                return $message;
            },
            $expoTokens,
        );

        $response = Http::timeout(10)
            ->acceptJson()
            ->post('https://exp.host/--/api/v2/push/send', $messages);

        if (! $response->successful()) {
            throw new RuntimeException('Expo push failed: '.$response->body());
        }

        $payload = $response->json();
        $tickets = is_array($payload) ? ($payload['data'] ?? []) : [];

        $okCount = 0;
        $errorCount = 0;

        foreach ($tickets as $ticket) {
            if (! is_array($ticket)) {
                continue;
            }

            $status = $ticket['status'] ?? null;
            if ($status === 'ok') {
                $okCount++;

                continue;
            }

            if ($status === 'error') {
                $errorCount++;
                $message = $ticket['message'] ?? 'unknown';
                $details = is_array($ticket['details'] ?? null) ? $ticket['details'] : [];

                logger()->warning('Expo push ticket error (non bloquant)', [
                    'message' => $message,
                    'details' => $details,
                ]);

                $this->handleInvalidToken($details);

                continue;
            }
        }

        logger()->info('Expo push sent', [
            'recipients' => count($expoTokens),
            'ok' => $okCount,
            'errors' => $errorCount,
        ]);
    }

    /**
     * @param  array<int, string>  $tokens
     * @return array<int, string>
     */
    private function filterExpoTokens(array $tokens): array
    {
        return array_values(array_filter(
            $tokens,
            static fn (string $token): bool => User::isExpoPushTokenFormat($token)
                && ! User::isNativeFcmToken($token)
                && ! User::shouldSkipExpoTokenInCurrentEnvironment($token),
        ));
    }

    /**
     * @param  array<string, mixed>  $details
     */
    private function handleInvalidToken(array $details): void
    {
        $error = $details['error'] ?? null;
        $expoPushToken = $details['expoPushToken'] ?? null;

        if (! is_string($expoPushToken) || $expoPushToken === '' || $error !== 'DeviceNotRegistered') {
            return;
        }

        $cleared = User::clearExpoPushTokenValue($expoPushToken);

        if ($cleared > 0) {
            logger()->info('fcm_token supprimé (DeviceNotRegistered).', [
                'token' => $expoPushToken,
                'users_updated' => $cleared,
            ]);
        }
    }
}
