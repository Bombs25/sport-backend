<?php

namespace App\Http\Controllers\Api\V1\Follow;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\Follow\UserFollowListRequest;
use App\Http\Support\Follow\FollowListResponseBuilder;
use App\Services\Register\RegisterUserPayloadBuilder;
use Illuminate\Http\JsonResponse;

/**
 * Liste followers / following d'un autre utilisateur (e-mail masqué ; accès = profil public).
 */
class UserFollowListController extends Controller
{
    public function __invoke(
        UserFollowListRequest $request,
        RegisterUserPayloadBuilder $payloadBuilder,
        FollowListResponseBuilder $responseBuilder,
    ): JsonResponse {
        $viewer = $request->user();
        $targetUserId = (int) $request->validated('user');

        $payloadBuilder->assertViewerCanAccessProfile($viewer, $targetUserId);

        return $responseBuilder->toResponse(
            listOwnerUserId: $targetUserId,
            viewerId: (int) $viewer->id,
            type: $request->validated('type'),
            limit: (int) ($request->validated('limit') ?? 10),
            cursor: $request->validated('cursor'),
            includeEmail: false,
        );
    }
}
