<?php

namespace App\Http\Controllers\Api\V1\Account;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\Account\UpdatePushTokenRequest;
use Illuminate\Http\JsonResponse;

/**
 * Enregistre le jeton Expo push (`ExponentPushToken[...]`) pour l'utilisateur connecté.
 */
class UpdatePushTokenController extends Controller
{
    public function __invoke(UpdatePushTokenRequest $request): JsonResponse
    {
        $user = $request->user();
        $user->forceFill([
            'fcm_token' => $request->validated('fcm_token'),
        ])->save();

        return response()->json([
            'message' => __('Jeton push enregistré.'),
        ]);
    }
}
