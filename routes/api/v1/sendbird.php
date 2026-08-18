<?php

/*
| Messagerie Sendbird : O'Sport ne gère pas le temps réel. Ces endpoints provisionnent
| l'utilisateur Sendbird, délivrent un token de session, créent des canaux, et
| reçoivent les webhooks Sendbird (push de messages).
| Le token API (Master) reste serveur ; voir App\Services\Sendbird\SendbirdService.
*/

use App\Http\Controllers\Api\V1\Sendbird\SendbirdChannelStoreController;
use App\Http\Controllers\Api\V1\Sendbird\SendbirdSessionTokenController;
use App\Http\Controllers\Api\V1\Sendbird\SendbirdWebhookController;
use Illuminate\Support\Facades\Route;

Route::prefix('v1/auth')->middleware('auth:sanctum')->group(function (): void {
    Route::post('sendbird/session-token', SendbirdSessionTokenController::class)
        ->middleware('throttle:auth-sendbird')
        ->name('api.v1.auth.sendbird.session-token');
    Route::post('sendbird/channels', SendbirdChannelStoreController::class)
        ->middleware('throttle:auth-sendbird')
        ->name('api.v1.auth.sendbird.channels.store');
});

// Webhook Sendbird : route PUBLIQUE (pas de session Sanctum) — authentifiée par
// signature HMAC dans le contrôleur. Sendbird appelle cette URL à chaque message.
Route::post('v1/sendbird/webhook', SendbirdWebhookController::class)
    ->name('api.v1.sendbird.webhook');
