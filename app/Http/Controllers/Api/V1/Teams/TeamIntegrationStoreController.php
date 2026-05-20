<?php

namespace App\Http\Controllers\Api\V1\Teams;

use App\Http\Controllers\Controller;
use App\Jobs\TeamIntegrationNotificationJob;
use App\Models\Team;
use App\Services\Team\TeamService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class TeamIntegrationStoreController extends Controller
{
    public function __invoke(Request $request, int $team_id, TeamService $service): JsonResponse
    {
        $team = Team::query()->findOrFail($team_id);
        $askerUserId = (int) $request->user()->id;
        $service->requestIntegration($team, $askerUserId);

        TeamIntegrationNotificationJob::dispatch($team->id, $askerUserId)
            ->onQueue('post_notifications');

        return response()->json([
            'message' => __('Demande d’intégration envoyée.'),
        ], 201);
    }
}
