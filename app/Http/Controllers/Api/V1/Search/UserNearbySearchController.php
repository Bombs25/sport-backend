<?php

namespace App\Http\Controllers\Api\V1\Search;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\Search\UserNearbySearchRequest;
use App\Services\Search\TypesenseUserService;
use App\Support\UserProfileLocation;
use Illuminate\Http\JsonResponse;
use Typesense\Exceptions\TypesenseClientError;

class UserNearbySearchController extends Controller
{
    public function __invoke(UserNearbySearchRequest $request, TypesenseUserService $search): JsonResponse
    {
        $userId = (int) $request->user()->id;
        $coordinates = UserProfileLocation::currentLatLngForUser($userId);

        if ($coordinates['latitude'] === null || $coordinates['longitude'] === null) {
            return response()->json([
                'message' => __('Ajoute une localisation à ton profil pour chercher des joueurs autour de toi.'),
            ], 422);
        }

        try {
            $results = $search->searchPublicUsersAround(
                latitude: $coordinates['latitude'],
                longitude: $coordinates['longitude'],
                query: (string) ($request->validated('q') ?? '*'),
                radiusKm: (float) ($request->validated('radius_km') ?? 100),
                page: (int) ($request->validated('page') ?? 1),
                perPage: (int) ($request->validated('per_page') ?? 10),
                excludeUserId: $userId,
            );
        } catch (TypesenseClientError $e) {
            return response()->json([
                'message' => __('Recherche indisponible pour le moment.'),
                'error' => $e->getMessage(),
            ], 502);
        }

        return response()->json($results);
    }
}
