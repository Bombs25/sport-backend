<?php

namespace App\Http\Controllers\Api\V1\Teams;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\Teams\TeamMatchDisputeStoreRequest;
use App\Services\Team\MatchResultService;
use Illuminate\Http\JsonResponse;

class TeamMatchDisputeStoreController extends Controller
{
    /**
     * Ouvert par le capitaine / créateur **away** après refus du score.
     */
    public function __invoke(
        TeamMatchDisputeStoreRequest $request,
        MatchResultService $service,
        int $match_event_id,
    ): JsonResponse {
        $validated = $request->validated();
        $evidencePath = null;
        $evidenceDisk = null;
        if ($request->hasFile('evidence')) {
            $evidenceDisk = 'local';
            $evidencePath = $request->file('evidence')->store('match-disputes', $evidenceDisk);
        }

        $disputeId = $service->openDispute(
            $match_event_id,
            (int) $request->user()->id,
            [
                'dispute_reason_score_incorrect' => $validated['dispute_reason_score_incorrect'],
                'dispute_reason_fair_play' => $validated['dispute_reason_fair_play'],
                'dispute_reason_behavior' => $validated['dispute_reason_behavior'],
                'details' => $validated['details'],
            ],
            $evidencePath,
            $evidenceDisk,
        );

        return response()->json([
            'message' => __('Litige envoyé.'),
            'match_result_dispute_id' => $disputeId,
        ], 201);
    }
}
