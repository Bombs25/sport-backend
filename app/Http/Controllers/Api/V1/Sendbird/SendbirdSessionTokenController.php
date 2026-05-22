<?php

namespace App\Http\Controllers\Api\V1\Sendbird;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\Sendbird\SendbirdSessionTokenRequest;
use App\Services\Sendbird\SendbirdService;
use Illuminate\Http\JsonResponse;

/**
 * Provisionne l'utilisateur Sendbird (au premier accès) et délivre un token de
 * session court pour que l'app mobile se connecte au SDK Sendbird.
 *
 * Le token API (Master) ne quitte jamais le serveur ; le client ne reçoit que
 * l'App ID public, son `sendbird_user_id` et un `session_token` à durée limitée.
 */
class SendbirdSessionTokenController extends Controller
{
    public function __invoke(SendbirdSessionTokenRequest $request, SendbirdService $sendbird): JsonResponse
    {
        $user = $request->user();

        // Idempotent : crée l'utilisateur Sendbird + la ligne `sendbird_accounts` si
        // absente (sinon resynchronise) et récupère un token de session frais émis
        // inline par Sendbird.
        $session = $sendbird->ensureUserWithSessionToken($user);

        return response()->json([
            'app_id' => $sendbird->appId(),
            'sendbird_user_id' => $sendbird->sendbirdUserIdFor((int) $user->id),
            'session_token' => $session['token'],
            'expires_at' => $session['expires_at'],
        ]);
    }
}
