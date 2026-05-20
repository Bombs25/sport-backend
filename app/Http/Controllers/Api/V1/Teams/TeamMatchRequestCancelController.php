<?php

namespace App\Http\Controllers\Api\V1\Teams;

use App\Http\Controllers\Controller;
use App\Jobs\TeamMatchRequestCancelledNotificationJob;
use App\Services\Team\TeamService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class TeamMatchRequestCancelController extends Controller
{
    public function __invoke(Request $request, TeamService $service, int $match_event_id): JsonResponse
    {
        $validatedMatchId = filter_var($match_event_id, FILTER_VALIDATE_INT);
        if ($validatedMatchId === false) {
            abort(400, __('Identifiant de demande de match invalide.'));
        }

        $actorUserId = (int) $request->user()->id;

        $service->cancelMatchRequest($validatedMatchId, $actorUserId);

        TeamMatchRequestCancelledNotificationJob::dispatch($validatedMatchId, $actorUserId)
            ->onQueue('post_notifications');

        return response()->json([
            'message' => __('Demande de match annulée.'),
        ]);
    }
}
