<?php

use App\Http\Controllers\Api\V1\Account\UpdatePushTokenController;
use App\Http\Controllers\Api\V1\Follow\FollowCountsController;
use App\Http\Controllers\Api\V1\Follow\FollowDestroyController;
use App\Http\Controllers\Api\V1\Follow\FollowListController;
use App\Http\Controllers\Api\V1\Follow\FollowRequestAcceptController;
use App\Http\Controllers\Api\V1\Follow\FollowRequestListController;
use App\Http\Controllers\Api\V1\Follow\FollowRequestRejectController;
use App\Http\Controllers\Api\V1\Follow\FollowStoreController;
use App\Http\Controllers\Api\V1\Follow\UserFollowCountsController;
use App\Http\Controllers\Api\V1\Follow\UserFollowListController;
use App\Http\Controllers\Api\V1\Notifications\NotificationListController;
use App\Http\Controllers\Api\V1\Profile\UpdateProfileController;
use App\Http\Controllers\Api\V1\Profile\UserPublicProfileController;
use App\Http\Controllers\Api\V1\Search\UserNearbySearchController;
use App\Http\Controllers\Api\V1\Upload\UploadProgressPollController;
use App\Http\Controllers\Api\V1\Users\CurrentUserController;
use Illuminate\Support\Facades\Route;

/*
| Ce qu’il fait : compte connecté hors **auth** stricte — « moi », profil éditable, profil public d’un autre utilisateur, follow.
| Préfixe identique `/api/v1/auth/...` pour ne pas casser les clients existants.
|
| Pourquoi : séparer les routes métier (profil, social) des contrôleurs `Api\V1\Auth\*` (login, OTP, mot de passe).
*/
Route::prefix('v1/auth')->middleware('auth:sanctum')->group(function (): void {
    // Récupère le profil de l'utilisateur connecté (même format que login / register).
    Route::get('user', CurrentUserController::class);
    Route::patch('push-token', UpdatePushTokenController::class);
    Route::get('upload-progress', UploadProgressPollController::class)->middleware('throttle:auth-follow-read');
    // Met à jour le profil utilisateur (nom, bio, confidentialité, localisation, etc.).
    Route::patch('profile', UpdateProfileController::class);
    // Notifications en base (10 par page, query `page`).
    Route::get('notifications', NotificationListController::class)->middleware('throttle:auth-follow-read');
    // S'abonner à un utilisateur (follower → following).
    Route::post('follow', FollowStoreController::class)->middleware('throttle:auth-follow-write');
    // Se désabonner d'un utilisateur.
    Route::delete('follow', FollowDestroyController::class)->middleware('throttle:auth-follow-write');
    // Totaux followers / following du compte connecté (appel dédié, sans liste paginée).
    Route::get('follows/counts', FollowCountsController::class)->middleware('throttle:auth-follow-read');
    // Liste followers ou following du compte connecté (query : type, limit, cursor ; FlatList).
    Route::get('follows', FollowListController::class)->middleware('throttle:auth-follow-read');
    // Demandes de suivi entrantes (compte privé) — liste paginée.
    Route::get('follow-requests', FollowRequestListController::class)->middleware('throttle:auth-follow-read');
    Route::post('follow-requests/accept', FollowRequestAcceptController::class)->middleware('throttle:auth-follow-write');
    Route::post('follow-requests/reject', FollowRequestRejectController::class)->middleware('throttle:auth-follow-write');
    // Recherche de profils publics autour de la localisation du profil connecté.
    Route::get('users/search', UserNearbySearchController::class)->middleware('throttle:auth-follow-read');
    // Profil public d'un utilisateur (id) : e-mail masqué sauf soi-même ; comptes privés si non autorisé.
    Route::get('users/{user}/profile', UserPublicProfileController::class)->middleware('throttle:auth-follow-read');
    // Totaux posts / followers / following d'un utilisateur (mêmes règles d'accès que le profil public).
    Route::get('users/{user}/follows/counts', UserFollowCountsController::class)->middleware('throttle:auth-follow-read');
    // Liste followers ou following d'un utilisateur (e-mail masqué ; mêmes règles d'accès que le profil public).
    Route::get('users/{user}/follows', UserFollowListController::class)->middleware('throttle:auth-follow-read');
});
