<?php

namespace App\Http\Controllers\Api\V1\Teams;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\Teams\TeamMatchRequestDecisionRequest;
use App\Services\Team\TeamService;
use Illuminate\Http\JsonResponse;

class TeamMatchRequestDecisionController extends Controller
{
    public function __invoke(TeamMatchRequestDecisionRequest $request, TeamService $service, int $match_event_id): JsonResponse
    {
        $service->decideMatchRequest(
            $match_event_id,
            (int) $request->user()->id,
            $request->validated()['decision'],
        );

        return response()->json([
            'message' => __('Demande de match traitée.'),
        ]);
    }
}
