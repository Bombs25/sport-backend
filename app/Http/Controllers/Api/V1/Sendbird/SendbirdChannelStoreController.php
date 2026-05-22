<?php

namespace App\Http\Controllers\Api\V1\Sendbird;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\Sendbird\SendbirdChannelStoreRequest;
use App\Models\User;
use App\Services\Sendbird\SendbirdService;
use Illuminate\Http\JsonResponse;

/**
 * Crée (ou réutilise) un canal 1-à-1 Sendbird entre l'utilisateur courant et la
 * cible. Côté serveur car les deux comptes Sendbird doivent être provisionnés —
 * le destinataire n'a pas forcément encore ouvert la messagerie.
 */
class SendbirdChannelStoreController extends Controller
{
    public function __invoke(SendbirdChannelStoreRequest $request, SendbirdService $sendbird): JsonResponse
    {
        $target = User::query()->findOrFail((int) $request->validated('target_user_id'));

        $channelUrl = $sendbird->createDistinctChannel($request->user(), $target);

        return response()->json(['channel_url' => $channelUrl]);
    }
}
