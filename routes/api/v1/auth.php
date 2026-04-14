<?php

use App\Http\Controllers\Api\V1\Auth\EmailChangeRequestOtpController;
use App\Http\Controllers\Api\V1\Auth\EmailChangeVerifyOtpController;
use App\Http\Controllers\Api\V1\Auth\EmailResendVerificationController;
use App\Http\Controllers\Api\V1\Auth\EmailVerifyOtpController;
use App\Http\Controllers\Api\V1\Auth\ForgotPasswordController;
use App\Http\Controllers\Api\V1\Auth\LoginController;
use App\Http\Controllers\Api\V1\Auth\LogoutController;
use App\Http\Controllers\Api\V1\Auth\PasswordChangeController;
use App\Http\Controllers\Api\V1\Auth\ResetPasswordWithOtpController;
use App\Http\Controllers\Api\V1\Register\RegisterCredentialsController;
use App\Http\Controllers\Api\V1\Register\RegisterHandleAvailabilityController;
use App\Http\Controllers\Api\V1\SportListController;
use Illuminate\Support\Facades\Route;

/*
| Ce qu’il fait : préfixe `/api/v1/auth` — **authentification** (login, logout), **sécurité compte** (OTP e-mail, changement e-mail / mot de passe),
| mot de passe oublié, **amorce inscription** (credentials + disponibilité pseudo), liste **sports** publics.
|
| Pourquoi : ne garder ici que l’auth au sens strict ; wizard register → `register.php` ; profil / follow → `account.php` ; équipes → `teams.php`.
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
        // Vérifie l'e-mail du compte connecté avec OTP.
        Route::post('email/verify', EmailVerifyOtpController::class)->middleware('throttle:auth-email-verify');
        // Renvoie un OTP de vérification d'e-mail.
        Route::post('email/resend', EmailResendVerificationController::class)->middleware('throttle:auth-email-resend');
        // Demande OTP pour changer l'e-mail du compte connecté.
        Route::post('email/change/request', EmailChangeRequestOtpController::class)->middleware('throttle:auth-email-change-request');
        // Confirme OTP et applique le nouvel e-mail.
        Route::post('email/change/verify', EmailChangeVerifyOtpController::class)->middleware('throttle:auth-email-change-verify');
        // Change le mot de passe du compte connecté (ancien + nouveau mot de passe).
        Route::post('password/change', PasswordChangeController::class)->middleware('throttle:auth-password-change');
    });
});
