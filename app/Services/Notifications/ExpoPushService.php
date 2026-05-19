<?php

namespace App\Services\Notifications;

use Illuminate\Support\Facades\Http;
use RuntimeException;

class ExpoPushService
{
    /**
     * Envoie une notification push via Expo à la liste de tokens fournie.
     *
     * @param  array<int, string>  $tokens  Liste des tokens Expo (ExponentPushToken) des destinataires
     * @param  string  $title  Titre de la notification
     * @param  string  $body  Corps de la notification
     * @param  string|null  $imageUrl  URL de l'image à afficher dans la notification
     * @param  string|null  $data  Données JSON à inclure dans la notification
     */
    /**
     * @param  array<string, mixed>|string|null  $data
     */
    public function send(array $tokens, string $title, string $body, ?string $imageUrl = null, array|string|null $data = null): void
    {
        $expoTokens = array_values(array_filter(
            $tokens,
            static fn (string $token): bool => str_starts_with($token, 'ExponentPushToken['),
        ));

        if ($expoTokens === []) {
            $nativeFcm = array_values(array_filter(
                $tokens,
                static fn (string $token): bool => str_contains($token, ':APA91') || str_starts_with($token, 'APA91'),
            ));
            logger()->warning('Expo push skipped: jeton invalide (attendu ExponentPushToken[...], pas FCM natif).', [
                'token_count' => count($tokens),
                'looks_like_native_fcm' => $nativeFcm !== [],
            ]);

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

        foreach ($tickets as $ticket) {
            if (! is_array($ticket)) {
                continue;
            }
            $status = $ticket['status'] ?? null;
            if ($status === 'error') {
                $message = $ticket['message'] ?? 'unknown';
                $details = $ticket['details'] ?? [];
                logger()->error('Expo push ticket error', [
                    'message' => $message,
                    'details' => $details,
                ]);
                throw new RuntimeException('Expo push ticket error: '.$message);
            }
        }

        logger()->info('Expo push sent', [
            'recipients' => count($expoTokens),
            'tickets' => $tickets,
        ]);
    }
}
