<?php

namespace App\Http\Controllers\Api\V1\Follow;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\Follow\FollowStoreRequest;
use App\Services\Follow\FollowService;
use Illuminate\Http\JsonResponse;

class FollowStoreController extends Controller
{
    public function __invoke(FollowStoreRequest $request, FollowService $service): JsonResponse
    {
        $service->follow(
            $request->user()->id,
            (int) $request->validated('target_user_id'),
        );

        return response()->json([
            'message' => __('Abonnement enregistré.'),
        ]);
    }
}
