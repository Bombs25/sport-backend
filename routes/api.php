<?php

use App\Http\Controllers\Api\V1\Auth\CurrentUserController;
use App\Http\Controllers\Api\V1\Auth\EmailChangeRequestOtpController;
use App\Http\Controllers\Api\V1\Auth\EmailChangeVerifyOtpController;
use App\Http\Controllers\Api\V1\Auth\EmailResendVerificationController;
use App\Http\Controllers\Api\V1\Auth\EmailVerifyOtpController;
use App\Http\Controllers\Api\V1\Auth\ForgotPasswordController;
use App\Http\Controllers\Api\V1\Auth\LoginController;
use App\Http\Controllers\Api\V1\Auth\LogoutController;
use App\Http\Controllers\Api\V1\Auth\PasswordChangeController;
use App\Http\Controllers\Api\V1\Auth\UpdateProfileController;
use App\Http\Controllers\Api\V1\Auth\ResetPasswordWithOtpController;
use App\Http\Controllers\Api\V1\Register\RegisterCredentialsController;
use App\Http\Controllers\Api\V1\Register\RegisterHandleAvailabilityController;
use App\Http\Controllers\Api\V1\Register\RegisterLocationController;
use App\Http\Controllers\Api\V1\Register\RegisterProfileController;
use App\Http\Controllers\Api\V1\Register\RegisterSportsController;
use App\Http\Controllers\Api\V1\SportListController;
use Illuminate\Support\Facades\Route;

/*
| Ce qu’il fait : expose sous **un même préfixe** `/api/v1/auth` le login Sanctum (e-mail insensible à la casse,
| `accept_terms` optionnel), l’inscription multi-étapes,
| le **mot de passe oublié** (OTP par e-mail puis `forgot-password/reset` ou alias `forgot-password/update`), la vérification e-mail par **code OTP**
| (`email/verify`, `email/resend`), la liste sports, et les routes
| protégées par Bearer (profil, localisation, sports utilisateur, **logout**, « moi »).
|
| Pourquoi : le client React Native n’a qu’une base d’URL pour tout le module « compte » ; throttles dédiés
| sur les actions sensibles (login, création de compte) ; groupe `auth:sanctum` pour tout ce qui nécessite un token.
*/
Route::prefix('v1/auth')->group(function (): void {
    // Connexion utilisateur (email + mot de passe) et émission du token Sanctum.
    Route::post('login', LoginController::class)->middleware('throttle:auth-login');

    // Demande publique d'OTP pour mot de passe oublié (message générique anti-énumération).
    Route::post('forgot-password', ForgotPasswordController::class)->middleware('throttle:auth-forgot-password');
    // Validation OTP publique pour réinitialiser le mot de passe oublié.
    Route::post('forgot-password/reset', ResetPasswordWithOtpController::class)->middleware('throttle:auth-password-reset-otp');
    // Alias legacy de reset OTP (même traitement que forgot-password/reset).
    Route::post('forgot-password/update', ResetPasswordWithOtpController::class)->middleware('throttle:auth-password-reset-otp');

    // Étape 1 inscription : création du compte + token.
    Route::post('register/credentials', RegisterCredentialsController::class)
        ->middleware('throttle:register-credentials');
    // Vérifie la disponibilité d'un pseudo pendant l'inscription.
    Route::get('register/handle-availability', RegisterHandleAvailabilityController::class)
        ->middleware('throttle:60,1');
    // Liste publique des sports disponibles.
    Route::get('sports', SportListController::class)->middleware('throttle:120,1');

    Route::middleware('auth:sanctum')->group(function (): void {
        // Déconnexion : révocation du token courant.
        Route::post('logout', LogoutController::class)->middleware('throttle:60,1');
        // Récupère le profil de l'utilisateur connecté.
        Route::get('user', CurrentUserController::class);
        // Vérifie l'e-mail avec OTP (compte connecté).
        Route::post('email/verify', EmailVerifyOtpController::class)->middleware('throttle:auth-email-verify');
        // Renvoie un OTP de vérification d'e-mail.
        Route::post('email/resend', EmailResendVerificationController::class)->middleware('throttle:auth-email-resend');
        // Demande OTP pour changer l'e-mail du compte connecté.
        Route::post('email/change/request', EmailChangeRequestOtpController::class)->middleware('throttle:auth-email-change-request');
        // Confirme OTP et applique le nouvel e-mail.
        Route::post('email/change/verify', EmailChangeVerifyOtpController::class)->middleware('throttle:auth-email-change-verify');
        // Change le mot de passe du compte connecté (ancien + nouveau mot de passe).
        Route::post('password/change', PasswordChangeController::class)->middleware('throttle:auth-password-change');
        // Met à jour le profil utilisateur (nom, bio, confidentialité, localisation, etc.).
        Route::patch('profile', UpdateProfileController::class);
        // Étape wizard : mise à jour de la localisation.
        Route::patch('register/location', RegisterLocationController::class);
        // Étape wizard : mise à jour des infos profil (nom, pseudo, date de naissance).
        Route::patch('register/profile', RegisterProfileController::class);
        // Étape wizard : enregistre/synchronise les sports de l'utilisateur.
        Route::post('register/sports', RegisterSportsController::class);
    });
});
