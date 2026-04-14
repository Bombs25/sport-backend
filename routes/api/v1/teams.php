<?php

use App\Http\Controllers\Api\V1\Teams\TeamDestroyController;
use App\Http\Controllers\Api\V1\Teams\TeamListController;
use App\Http\Controllers\Api\V1\Teams\TeamShowController;
use App\Http\Controllers\Api\V1\Teams\TeamStoreController;
use App\Http\Controllers\Api\V1\Teams\TeamUpdateController;
use Illuminate\Support\Facades\Route;

/*
| Ce qu’il fait : CRUD + liste des équipes sous `/api/v1/auth/teams...` (Bearer Sanctum).
|
| Pourquoi : domaine **Teams** (`App\Http\Controllers\Api\V1\Teams`) ; fichier chargé depuis `routes/api.php`.
*/
Route::prefix('v1/auth')->middleware('auth:sanctum')->group(function (): void {
    // Liste « Mes équipes » : blocs créées / rejointes + effectifs (Bearer).
    Route::get('teams', TeamListController::class)->middleware('throttle:auth-team-read');
    // Crée une équipe ; le créateur devient captain actif dans team_members.
    Route::post('teams', TeamStoreController::class)->middleware('throttle:auth-team-write');
    // Détail d'une équipe (membre actif uniquement). +++++++
    Route::get('teams/{team}', TeamShowController::class)->middleware('throttle:auth-team-read');
    // Met à jour une équipe (créateur ou captain actif).
    Route::patch('teams/{team}', TeamUpdateController::class)->middleware('throttle:auth-team-write');
    // Supprime définitivement une équipe (créateur uniquement).
    Route::delete('teams/{team}', TeamDestroyController::class)->middleware('throttle:auth-team-write');
});
