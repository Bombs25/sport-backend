<?php

namespace App\Http\Controllers\Api\V1\Sendbird;

use App\Http\Controllers\Controller;
use App\Jobs\SendbirdMessagePushJob;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Reçoit les webhooks Sendbird. Route publique (Sendbird n'a pas de session
 * Sanctum) : la requête est authentifiée par signature HMAC.
 *
 * Seuls les événements `group_channel:message_send` déclenchent un push ; le
 * traitement est délégué à un job quemé pour répondre 200 immédiatement.
 */
class SendbirdWebhookController extends Controller
{
    public function __invoke(Request $request): JsonResponse
    {
        abort_unless($this->signatureIsValid($request), 401);

        $payload = $request->json()->all();

        if (($payload['category'] ?? null) === 'group_channel:message_send') {
            SendbirdMessagePushJob::dispatch($payload)->onQueue('post_notifications');
        }

        return response()->json(['received' => true]);
    }

    /**
     * Sendbird signe le corps brut en HMAC-SHA256 avec le token API, dans
     * l'en-tête `x-sendbird-signature`.
     */
    private function signatureIsValid(Request $request): bool
    {
        $apiToken = (string) config('services.sendbird.api_token');
        $signature = (string) $request->header('x-sendbird-signature', '');

        if ($apiToken === '' || $signature === '') {
            return false;
        }

        $expected = hash_hmac('sha256', $request->getContent(), $apiToken);

        return hash_equals($expected, $signature);
    }
}
