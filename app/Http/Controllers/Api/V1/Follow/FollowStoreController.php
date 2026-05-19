<?php

namespace App\Http\Controllers\Api\V1\Follow;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\Follow\FollowStoreRequest;
use App\Jobs\FollowNotificationJob;
use App\Services\Follow\FollowService;
use Illuminate\Http\JsonResponse;

class FollowStoreController extends Controller
{
    public function __invoke(FollowStoreRequest $request, FollowService $service): JsonResponse
    {
        $followerId = (int) $request->user()->id;
        $targetUserId = (int) $request->validated('target_user_id');

        $result = $service->follow($followerId, $targetUserId);

        if ($result['notify']) {
            $kind = $result['status'] === 'pending' ? 'follow_request' : 'new_follower';
            FollowNotificationJob::dispatch(
                $kind,
                $followerId,
                $targetUserId,
                $result['follow_id'],
            )->onQueue('post_notifications');
        }

        return response()->json([
            'message' => $result['status'] === 'pending'
                ? __('Demande de suivi envoyée.')
                : __('Abonnement enregistré.'),
            'data' => [
                'status' => $result['status'],
            ],
        ]);
    }
}
