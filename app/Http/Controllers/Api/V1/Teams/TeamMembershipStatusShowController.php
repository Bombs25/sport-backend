<?php

namespace App\Http\Controllers\Api\V1\Teams;

use App\Http\Controllers\Controller;
use App\Models\Team;
use App\Services\Team\TeamService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class TeamMembershipStatusShowController extends Controller
{
    public function __invoke(Request $request, int $team_id, TeamService $service): JsonResponse
    {
        $team = Team::query()->findOrFail($team_id);
        $userId = (int) $request->user()->id;

        return response()->json([
            'data' => $service->membershipStatus($team, $userId),
        ]);
    }
}
