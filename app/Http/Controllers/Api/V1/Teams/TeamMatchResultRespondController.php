<?php

namespace App\Http\Controllers\Api\V1\Teams;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\Teams\TeamMatchResultRespondRequest;
use App\Services\Team\MatchResultService;
use Illuminate\Http\JsonResponse;

class TeamMatchResultRespondController extends Controller
{
    /**
     * Réservé au capitaine / créateur de l’équipe **away** (receveur de la demande de match).
     */
    public function __invoke(
        TeamMatchResultRespondRequest $request,
        MatchResultService $service,
        int $match_event_id,
    ): JsonResponse {
        $validated = $request->validated();
        $service->respondToMatchResult(
            $match_event_id,
            (int) $request->user()->id,
            [
                'decision' => $validated['decision'],
                'refusal_reason' => $validated['refusal_reason'] ?? null,
                'fair_play_rating' => $validated['fair_play_rating'] ?? null,
                'punctuality_rating' => $validated['punctuality_rating'] ?? null,
                'remarks' => $validated['remarks'] ?? null,
            ],
        );

        return response()->json([
            'message' => __('Réponse au score enregistrée.'),
        ]);
    }
}
