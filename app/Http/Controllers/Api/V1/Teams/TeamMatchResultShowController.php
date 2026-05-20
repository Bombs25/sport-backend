<?php

namespace App\Http\Controllers\Api\V1\Teams;

use App\Http\Controllers\Controller;
use App\Services\Team\MatchResultService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class TeamMatchResultShowController extends Controller
{
    public function __invoke(
        Request $request,
        MatchResultService $service,
        int $match_event_id,
    ): JsonResponse {
        $detail = $service->getMatchResultDetail($match_event_id, (int) $request->user()->id);

        return response()->json(['data' => $detail]);
    }
}
