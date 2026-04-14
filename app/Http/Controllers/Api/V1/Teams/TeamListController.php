<?php

namespace App\Http\Controllers\Api\V1\Teams;

use App\Http\Controllers\Controller;
use App\Services\Team\TeamService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Ce qu’il fait : liste les équipes du compte connecté en deux blocs « créées » et « rejointes », avec effectifs.
 *
 * Pourquoi : alimenter l’écran « Mes équipes » (maquettes création d’équipe) sans N+1.
 */
class TeamListController extends Controller
{
    public function __invoke(Request $request, TeamService $service): JsonResponse
    {
        $payload = $service->listMine((int) $request->user()->id);

        return response()->json(['data' => $payload]);
    }
}
