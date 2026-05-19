<?php

namespace App\Http\Controllers\Api\V1\Follow;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\Follow\FollowRequestDecisionRequest;
use App\Jobs\FollowNotificationJob;
use App\Services\Follow\FollowService;
use Illuminate\Http\JsonResponse;

class FollowRequestAcceptController extends Controller
{
    public function __invoke(FollowRequestDecisionRequest $request, FollowService $service): JsonResponse
    {
        $followingUserId = (int) $request->user()->id;

        $result = $service->acceptIncomingRequest(
            $followingUserId,
            (int) $request->validated('follow_request_id'),
        );

        if ($result['notify']) {
            FollowNotificationJob::dispatch(
                'follow_accepted',
                $followingUserId,
                $result['follower_id'],
                $result['follow_id'],
            )->onQueue('post_notifications');
        }

        return response()->json([
            'message' => __('Demande acceptée.'),
        ]);
    }
}
