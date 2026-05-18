<?php

namespace App\Http\Controllers\Api\V1\Follow;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\Follow\FollowRequestDecisionRequest;
use App\Services\Follow\FollowService;
use Illuminate\Http\JsonResponse;

class FollowRequestRejectController extends Controller
{
    public function __invoke(FollowRequestDecisionRequest $request, FollowService $service): JsonResponse
    {
        $service->rejectIncomingRequest(
            (int) $request->user()->id,
            (int) $request->validated('follow_request_id'),
        );

        return response()->json([
            'message' => __('Demande refusée.'),
        ]);
    }
}
