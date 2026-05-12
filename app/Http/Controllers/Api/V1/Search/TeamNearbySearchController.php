<?php

namespace App\Http\Controllers\Api\V1\Search;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\Search\TeamNearbySearchRequest;
use App\Services\Search\TypesenseTeamService;
use App\Support\UserProfileLocation;
use Illuminate\Http\JsonResponse;
use Typesense\Exceptions\TypesenseClientError;

class TeamNearbySearchController extends Controller
{
    public function __invoke(TeamNearbySearchRequest $request, TypesenseTeamService $search): JsonResponse
    {
        $userId = (int) $request->user()->id;
        $coordinates = UserProfileLocation::currentLatLngForUser($userId);

        if ($coordinates['latitude'] === null || $coordinates['longitude'] === null) {
            return response()->json([
                'message' => __('Ajoute une localisation à ton profil pour chercher des équipes autour de toi.'),
            ], 422);
        }

        try {
            $results = $search->searchPublicTeamsAround(
                latitude: $coordinates['latitude'],
                longitude: $coordinates['longitude'],
                query: (string) ($request->validated('q') ?? '*'),
                sportId: $request->validated('sport_id') !== null ? (int) $request->validated('sport_id') : null,
                competitionType: $request->validated('competition_type'),
                skillLevel: $request->validated('skill_level'),
                radiusKm: (float) ($request->validated('radius_km') ?? 100),
                page: (int) ($request->validated('page') ?? 1),
                perPage: (int) ($request->validated('per_page') ?? 10),
            );
        } catch (TypesenseClientError $e) {
            return response()->json([
                'message' => __('Recherche équipes indisponible pour le moment.'),
                'error' => $e->getMessage(),
            ], 502);
        }

        return response()->json($results);
    }
}
