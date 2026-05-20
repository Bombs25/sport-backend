<?php

namespace App\Http\Controllers\Api\V1\Teams;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\Teams\TeamMatchRequestDecisionRequest;
use App\Jobs\TeamMatchRequestDecisionNotificationJob;
use App\Services\Team\TeamService;
use Illuminate\Http\JsonResponse;

class TeamMatchRequestDecisionController extends Controller
{
    public function __invoke(TeamMatchRequestDecisionRequest $request, TeamService $service, int $match_event_id): JsonResponse
    {
        $actorUserId = (int) $request->user()->id;
        $decision = $request->validated()['decision'];

        $service->decideMatchRequest(
            $match_event_id,
            $actorUserId,
            $decision,
        );

        TeamMatchRequestDecisionNotificationJob::dispatch($match_event_id, $actorUserId, $decision)
            ->onQueue('post_notifications');

        return response()->json([
            'message' => __('Demande de match traitée.'),
        ]);
    }
}
