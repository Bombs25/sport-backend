<?php

use App\Http\Controllers\Api\V1\Register\RegisterLocationController;
use App\Http\Controllers\Api\V1\Register\RegisterProfileController;
use App\Http\Controllers\Api\V1\Register\RegisterSportsController;
use Illuminate\Support\Facades\Route;

/*
| Ce qu’il fait : étapes **wizard** d’inscription après création du compte (localisation, profil, sports).
| Même préfixe `/api/v1/auth/register/...` pour compatibilité client.
|
| Pourquoi : logique et contrôleurs déjà sous `App\Http\Controllers\Api\V1\Register` ; fichier de routes dédié, hors `auth.php`.
*/
Route::prefix('v1/auth')->middleware('auth:sanctum')->group(function (): void {
    // Étape wizard : mise à jour de la localisation.
    Route::patch('register/location', RegisterLocationController::class);
    // Étape wizard : mise à jour des infos profil (nom, pseudo, date de naissance).
    Route::patch('register/profile', RegisterProfileController::class);
    // Étape wizard : enregistre / synchronise les sports de l'utilisateur.
    Route::post('register/sports', RegisterSportsController::class);
});
