<?php

/*
| Ce qu’il fait : point d’entrée des routes API — charge chaque domaine sous `routes/api/v1/`.
|
| Pourquoi : `api.php` reste le parent unique (≤ ~100 lignes) ; pas d’imbrication « teams » dans `auth.php`.
| Le préfixe `/api` vient de `bootstrap/app.php`.
*/
// Auth : login, logout, mot de passe oublié / reset, OTP e-mail, changement e-mail / mot de passe, credentials + pseudo, sports publics.
require __DIR__.'/api/v1/auth.php';
// Inscription : wizard register (localisation, profil, sports) sous `/api/v1/auth/register/...`.
require __DIR__.'/api/v1/register.php';
// Compte / social : utilisateur courant, profil, follow, profil public (`/api/v1/auth/...`, contrôleurs hors namespace Auth).
require __DIR__.'/api/v1/account.php';
// Facturation Stripe (abonnement) sous `/api/v1/auth/billing...`.
require __DIR__.'/api/v1/billing.php';
// Équipes : CRUD sous `/api/v1/auth/teams...`.
require __DIR__.'/api/v1/teams.php';
require __DIR__.'/api/v1/posts.php';
// Messagerie : provisionnement Sendbird + token de session sous `/api/v1/auth/sendbird/...`.
require __DIR__.'/api/v1/sendbird.php';

/*
| Auth WebSocket (Reverb) : Bearer Sanctum uniquement (WebView RN).
| Sans middleware « stateful » Sanctum : évite 403 si cookies session ≠ token.
*/
use Illuminate\Broadcasting\BroadcastController;
use Illuminate\Support\Facades\Route;
use Laravel\Sanctum\Http\Middleware\EnsureFrontendRequestsAreStateful;

Route::post('broadcasting/auth', [BroadcastController::class, 'authenticate'])
    ->middleware('auth:sanctum')
    ->withoutMiddleware([EnsureFrontendRequestsAreStateful::class]);

//     php artisan queue:work --queue=image-processing,post_notifications
//     (connexion Redis par défaut ; ne pas utiliser --connection=post_notifications seul)
// php artisan reverb:start
