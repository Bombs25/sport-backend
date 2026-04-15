<?php

namespace App\Http\Controllers\Api\V1\Teams;

use App\Http\Controllers\Controller;
use App\Models\Team;
use App\Services\Team\TeamService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class TeamProfileShowController extends Controller
{
    public function __invoke(Request $request, int $team_id, TeamService $service): JsonResponse
    {
        $team = Team::query()->findOrFail($team_id);
        $validated = Validator::validate(
            ['page' => $request->query('page', 1)],
            ['page' => ['nullable', 'integer', 'min:1']],
        );

        return response()->json([
            'data' => $service->buildProfilePayload(
                $team,
                (int) ($validated['page'] ?? 1),
            ),
        ]);
    }
}
