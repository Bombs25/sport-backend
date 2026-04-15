<?php

use App\Http\Controllers\Api\V1\Teams\TeamDestroyController;
use App\Http\Controllers\Api\V1\Teams\TeamIntegrationDecisionController;
use App\Http\Controllers\Api\V1\Teams\TeamIntegrationPendingListController;
use App\Http\Controllers\Api\V1\Teams\TeamIntegrationStoreController;
use App\Http\Controllers\Api\V1\Teams\TeamListController;
use App\Http\Controllers\Api\V1\Teams\TeamMatchRequestStoreController;
use App\Http\Controllers\Api\V1\Teams\TeamMemberDestroyController;
use App\Http\Controllers\Api\V1\Teams\TeamMembershipStatusShowController;
use App\Http\Controllers\Api\V1\Teams\TeamProfileShowController;
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

    // ////////// page equipe ////////////
    // Liste « Mes équipes » : blocs créées / rejointes + effectifs (Bearer).
    Route::get('teams', TeamListController::class)->middleware('throttle:auth-team-read');
    // Crée une équipe ; le créateur devient captain actif dans team_members.
    Route::post('teams', TeamStoreController::class)->middleware('throttle:auth-team-write');
    // Met à jour une équipe (créateur ou captain actif).
    Route::patch('teams/{team_id}', TeamUpdateController::class)->middleware('throttle:auth-team-write');
    // Supprime définitivement une équipe (créateur uniquement).
    Route::delete('teams/{team_id}', TeamDestroyController::class)->middleware('throttle:auth-team-write');

    // ////////// page integration equipe ////////////
    // Décision d'une demande (URL: asker_user_id = demandeur ; body: decision) par créateur ou captain actif.
    Route::patch('teams/{team_id}/integrations/{asker_user_id}', TeamIntegrationDecisionController::class)->middleware('throttle:auth-team-write');
    // Liste paginée des demandes d'intégration en attente (créateur/captain actif), 10 par page.
    Route::get('teams/{team_id}/integrations/pending', TeamIntegrationPendingListController::class)->middleware('throttle:auth-team-read');

    // ////////// page pfofile equipe ////////////
    // Statut du user connecté pour cette équipe: membre actif, demande en attente, etc.
    Route::get('teams/{team_id}/membership', TeamMembershipStatusShowController::class)->middleware('throttle:auth-team-read');
    // Données profil équipe (nom, ville, sport/type, membres + total).
    Route::get('teams/{team_id}/profile', TeamProfileShowController::class)->middleware('throttle:auth-team-read');
    // Demande d'intégration à une équipe (utilisateur connecté).
    Route::post('teams/{team_id}/integrations', TeamIntegrationStoreController::class)->middleware('throttle:auth-team-write');
    // Sortie d'équipe (self) ou suppression d'un membre (créateur/captain actif).
    Route::delete('teams/{team_id}/members/{member_user_id}', TeamMemberDestroyController::class)->middleware('throttle:auth-team-write');
    // Demande de match entre deux équipes du même sport.
    Route::post('teams/{team_id}/match-requests', TeamMatchRequestStoreController::class)->middleware('throttle:auth-team-write');

    // Détail d'une équipe (membre actif uniquement). +++++++
    // Route::get('teams/{team_id}', TeamShowController::class)->middleware('throttle:auth-team-read');
});
