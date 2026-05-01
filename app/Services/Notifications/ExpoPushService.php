<?php

namespace App\Services\Notifications;

use Illuminate\Support\Facades\Http;
use RuntimeException;

class ExpoPushService
{
    /**
     * @param  array<int, string>  $tokens
     */
    public function send(array $tokens, string $title, string $body, ?string $imageUrl = null, ?string $data = null): void
    {
        $expoTokens = array_values(array_filter(
            $tokens,
            static fn (string $token): bool => str_starts_with($token, 'ExponentPushToken['),
        ));

        if ($expoTokens === []) {
            return;
        }

        $messages = array_map(
            static function (string $token) use ($title, $body, $imageUrl, $data): array {
                $message = [
                    'to' => $token,
                    'title' => $title,
                    'body' => $body,
                    'sound' => 'default',
                    'data' => $data,
                ];

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
    }
}
