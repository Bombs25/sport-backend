<?php

namespace App\Http\Controllers\Api\V1\Teams;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\Teams\TeamMatchRequestUpdateRequest;
use App\Jobs\TeamMatchRequestUpdatedNotificationJob;
use App\Services\Team\TeamService;
use Illuminate\Http\JsonResponse;

class TeamMatchRequestUpdateController extends Controller
{
    public function __invoke(
        TeamMatchRequestUpdateRequest $request,
        TeamService $service,
        int $match_event_id,
    ): JsonResponse {
        $validatedMatchId = filter_var($match_event_id, FILTER_VALIDATE_INT);
        if ($validatedMatchId === false) {
            abort(400, __('Identifiant de demande de match invalide.'));
        }

        $actorUserId = (int) $request->user()->id;
        $validated = $request->validated();

        $service->updateMatchRequest(
            $validatedMatchId,
            $actorUserId,
            $validated['scheduled_at'],
            $validated['venue'] ?? null,
            $validated['notes'] ?? null,
        );

        TeamMatchRequestUpdatedNotificationJob::dispatch($validatedMatchId, $actorUserId)
            ->onQueue('post_notifications');

        return response()->json([
            'message' => __('Demande de match mise à jour.'),
        ]);
    }
}
