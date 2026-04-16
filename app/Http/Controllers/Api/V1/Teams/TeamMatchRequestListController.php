<?php

namespace App\Http\Controllers\Api\V1\Teams;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\Teams\TeamMatchRequestListRequest;
use App\Services\Team\TeamService;
use Illuminate\Http\JsonResponse;

class TeamMatchRequestListController extends Controller
{
    public function __invoke(TeamMatchRequestListRequest $request, TeamService $service): JsonResponse
    {
        $validated = $request->validated();

        return response()->json([
            'data' => $service->listMatchRequests(
                (int) $request->user()->id,
                $validated['type'] ?? 'received',
                $validated['status'] ?? null,
                $validated['scheduled_at'] ?? null,
                $validated['sport_name'] ?? null,
                (int) ($validated['page'] ?? 1),
            ),
        ]);
    }
}
