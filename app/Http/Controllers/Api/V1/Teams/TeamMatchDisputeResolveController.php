<?php

namespace App\Http\Controllers\Api\V1\Teams;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\Teams\TeamMatchDisputeResolveRequest;
use App\Jobs\TeamMatchDisputeResolvedNotificationJob;
use App\Services\Team\MatchResultService;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;

class TeamMatchDisputeResolveController extends Controller
{
    public function __invoke(
        TeamMatchDisputeResolveRequest $request,
        MatchResultService $service,
        int $match_result_dispute_id,
    ): JsonResponse {
        $validated = $request->validated();
        $actorUserId = (int) $request->user()->id;

        $service->resolveDispute($match_result_dispute_id, $actorUserId, [
            'resolution' => $validated['resolution'],
            'resolution_notes' => $validated['resolution_notes'] ?? null,
            'home_score' => $validated['home_score'] ?? null,
            'away_score' => $validated['away_score'] ?? null,
        ]);

        $matchEventId = (int) DB::table('match_result_disputes')
            ->join('match_results', 'match_results.id', '=', 'match_result_disputes.match_result_id')
            ->where('match_result_disputes.id', $match_result_dispute_id)
            ->value('match_results.match_event_id');

        if ($matchEventId > 0 && $validated['resolution'] !== 'under_review') {
            TeamMatchDisputeResolvedNotificationJob::dispatch(
                $matchEventId,
                $actorUserId,
                $match_result_dispute_id,
                $validated['resolution'],
            )->onQueue('post_notifications');
        }

        return response()->json([
            'message' => __('Litige mis à jour.'),
        ]);
    }
}
