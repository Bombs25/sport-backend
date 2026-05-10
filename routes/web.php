<?php

use App\Http\Controllers\Auth\VerifyEmailFromSignedUrlController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

/*
| Ce qu’il fait : route signée utilisée par le **lien de vérification email** (notification Laravel).
| Pourquoi : les mails ouvrent un navigateur ; ce n’est pas une route API Bearer ; le nom `verification.verify`
| est attendu par `MustVerifyEmail` pour générer l’URL.
*/
Route::get('/email/verify/{id}/{hash}', VerifyEmailFromSignedUrlController::class)
    ->middleware(['signed', 'throttle:6,1'])
    ->name('verification.verify');

/*
| Retour Stripe Checkout (démo web / tests locaux). Pour React Native, remplacer par un schéma d’app dans .env.
*/
Route::get('/billing/checkout/return', function (Request $request) {
    return view('billing.checkout-return', [
        'result' => $request->query('result'),
        'sessionId' => $request->query('session_id'),
    ]);
})->name('billing.checkout.return');

/*
| Page de test multipart + Sanctum stateful (local uniquement). Doit passer par le groupe `web`
| (session + CSRF) pour que le meta token et les cookies de session soient cohérents avec l’API.
*/
if (app()->environment('local')) {
    Route::view('/dev/api-test/create-team', 'dev.api-test-create-team');
    Route::view('/dev/api-test/update-team', 'dev.api-test-update-team');
    Route::view('/dev/api-test/update-profile', 'dev.api-test-update-profile');
    Route::view('/dev/api-test/billing-checkout', 'dev.api-test-billing-checkout');
}
