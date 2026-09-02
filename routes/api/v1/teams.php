<?php

use App\Http\Controllers\Api\V1\Search\TeamNearbySearchController;
use App\Http\Controllers\Api\V1\Teams\TeamDestroyController;
use App\Http\Controllers\Api\V1\Teams\TeamIntegrationDecisionController;
use App\Http\Controllers\Api\V1\Teams\TeamIntegrationPendingListController;
use App\Http\Controllers\Api\V1\Teams\TeamIntegrationStoreController;
use App\Http\Controllers\Api\V1\Teams\TeamLatestMatchShowController;
use App\Http\Controllers\Api\V1\Teams\TeamListController;
use App\Http\Controllers\Api\V1\Teams\TeamMatchDisputeResolveController;
use App\Http\Controllers\Api\V1\Teams\TeamMatchDisputeStoreController;
use App\Http\Controllers\Api\V1\Teams\TeamMatchHistoryListController;
use App\Http\Controllers\Api\V1\Teams\TeamMatchRequestCancelController;
use App\Http\Controllers\Api\V1\Teams\TeamMatchRequestDecisionController;
use App\Http\Controllers\Api\V1\Teams\TeamMatchRequestListController;
use App\Http\Controllers\Api\V1\Teams\TeamMatchRequestStoreController;
use App\Http\Controllers\Api\V1\Teams\TeamMatchRequestUpdateController;
use App\Http\Controllers\Api\V1\Teams\TeamMatchResultRespondController;
use App\Http\Controllers\Api\V1\Teams\TeamMatchResultShowController;
use App\Http\Controllers\Api\V1\Teams\TeamMatchResultStoreController;
use App\Http\Controllers\Api\V1\Teams\TeamMemberDestroyController;
use App\Http\Controllers\Api\V1\Teams\TeamMembershipStatusShowController;
use App\Http\Controllers\Api\V1\Teams\TeamPalmaresShowController;
use App\Http\Controllers\Api\V1\Teams\TeamProfileShowController;
use App\Http\Controllers\Api\V1\Teams\TeamRankingListController;
use App\Http\Controllers\Api\V1\Teams\TeamRankingYearsListController;
use App\Http\Controllers\Api\V1\Teams\TeamSeasonStatsShowController;
use App\Http\Controllers\Api\V1\Teams\TeamShowController;
use App\Http\Controllers\Api\V1\Teams\TeamStoreController;
use App\Http\Controllers\Api\V1\Teams\TeamUpdateController;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\V1\MapApiCotntroller;
/*
| Ce qu’il fait : CRUD + liste des équipes sous `/api/v1/auth/teams...` (Bearer Sanctum).
|
| Pourquoi : domaine **Teams** (`App\Http\Controllers\Api\V1\Teams`) ; fichier chargé depuis `routes/api.php`.
|
| Abonnement : le middleware `ensure.subscribed` (Cashier, type `billing.subscription_type`) protège la création
| d’équipe, les demandes d’intégration, les demandes / décisions de match et la saisie des résultats / litiges.
*/

Route::prefix('v1/auth')->middleware('auth:sanctum')->group(function (): void {

    // ////////// page equipe ////////////
    // Liste « Mes équipes » : blocs créées / rejointes + effectifs (Bearer).
    Route::get('teams', TeamListController::class)->middleware('throttle:auth-team-read');
    // Recherche d'équipes autour de la localisation du profil connecté.
    Route::get('teams/search', TeamNearbySearchController::class)->middleware('throttle:auth-team-read');
    // Crée une équipe ; le créateur devient captain actif dans team_members.
    Route::post('teams', TeamStoreController::class)->middleware(['throttle:auth-team-write', 'ensure.subscribed']); //
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
    // Stats saison agrégées (played / won / lost / draw / points), année optionnelle (défaut = année courante).
    Route::get('teams/{team_id}/season-stats', TeamSeasonStatsShowController::class)->middleware('throttle:auth-team-read');
    // Trophées / palmarès (top 3 par saison dérivés des stats).
    Route::get('teams/{team_id}/palmares', TeamPalmaresShowController::class)->middleware('throttle:auth-team-read');
    // Dernier match à score validé (noms, logos, scores, issue pour l'équipe consultée).
    Route::get('teams/{team_id}/latest-match', TeamLatestMatchShowController::class)->middleware('throttle:auth-team-read');
    Route::get('teams/{team_id}/match-history', TeamMatchHistoryListController::class)->middleware('throttle:auth-team-read');
    // Demande d'intégration à une équipe (utilisateur connecté).
    Route::post('teams/{team_id}/integrations', TeamIntegrationStoreController::class)->middleware(['throttle:auth-team-write', 'ensure.subscribed']); //
    // Sortie d'équipe (self) ou suppression d'un membre (créateur/captain actif).
    Route::delete('teams/{team_id}/members/{member_user_id}', TeamMemberDestroyController::class)->middleware('throttle:auth-team-write');
    // Demande de match entre deux équipes du même sport.
    Route::post('teams/{team_id}/match-requests', TeamMatchRequestStoreController::class)->middleware(['throttle:auth-team-write', 'ensure.subscribed']); // 'ensure.subscribed'

    // ////////// page classement equipes ////////////
    // Classement des equipes pour un sport et une annee (saison resolue par SeasonStrategy bindee).
    Route::get('teams/rankings', TeamRankingListController::class)->middleware('throttle:auth-team-read');
    // Annees disponibles pour le dropdown classement d'un sport.
    Route::get('teams/rankings/years', TeamRankingYearsListController::class)->middleware('throttle:auth-team-read');

    // ////////// page match request ////////////
    // Liste des demandes de match (reçu/envoyé), paginée.
    Route::get('teams/match-requests', TeamMatchRequestListController::class)->middleware('throttle:auth-team-read');
    // Modification d'une demande envoyée (équipe demanderesse).
    Route::put('teams/match-requests/{match_event_id}', TeamMatchRequestUpdateController::class)->middleware(['throttle:auth-team-write', 'ensure.subscribed']); // 'ensure.subscribed'
    // Annulation d'une demande envoyée (équipe demanderesse).
    Route::delete('teams/match-requests/{match_event_id}', TeamMatchRequestCancelController::class)->middleware(['throttle:auth-team-write', 'ensure.subscribed']); // 'ensure.subscribed'
    // Décision sur une demande reçue: accept/refuse.
    Route::patch('teams/match-requests/{match_event_id}', TeamMatchRequestDecisionController::class)->middleware(['throttle:auth-team-write', 'ensure.subscribed']); // 'ensure.subscribed'

    // Premier envoi : score + 1re évaluation — uniquement **home_team_id** (demandeur du match, même sens que POST match-requests).
    Route::post('teams/{team_id}/match-events/{match_event_id}/result', TeamMatchResultStoreController::class)->middleware(['throttle:auth-team-write', 'ensure.subscribed']); // 'ensure.subscribed'
    // 2e envoi : validation ou refus (+ 2e évaluation si validation) — uniquement capitaine / créateur **away_team_id**.
    Route::patch('teams/match-events/{match_event_id}/result', TeamMatchResultRespondController::class)->middleware(['throttle:auth-team-write', 'ensure.subscribed']); // 'ensure.subscribed'
    // Détail résultat + litige actif (capitaine home ou away).
    Route::get('teams/match-events/{match_event_id}/result', TeamMatchResultShowController::class)->middleware('throttle:auth-team-read');
    // Litige après refus : même acteur que la réponse adverse (away).
    Route::post('teams/match-events/{match_event_id}/result/dispute', TeamMatchDisputeStoreController::class)->middleware(['throttle:auth-team-write', 'ensure.subscribed']); // 'ensure.subscribed'
    // Résolution litige (capitaine home ou away — modération app).
    Route::patch('teams/match-result-disputes/{match_result_dispute_id}/resolve', TeamMatchDisputeResolveController::class)->middleware(['throttle:auth-team-write', 'ensure.subscribed']); // 'ensure.subscribed'

    // Détail d'une équipe (membre actif uniquement). +++++++
    // Route::get('teams/{team_id}', TeamShowController::class)->middleware('throttle:auth-team-read');

    
});

// Route::get('/google-place-info', [MapApiCotntroller::class, 'getPlaceInfo']);