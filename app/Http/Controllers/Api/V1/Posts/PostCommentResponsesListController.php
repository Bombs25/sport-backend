<?php

namespace App\Http\Controllers\Api\V1\Posts;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\Posts\FetchPostCommentResponsesListRequest;
use App\Services\Post\FetchCommentService;
use Illuminate\Http\JsonResponse;

class PostCommentResponsesListController extends Controller
{
    public function __invoke(FetchPostCommentResponsesListRequest $request, FetchCommentService $service): JsonResponse
    {
        $viewerUserId = (int) $request->user()->id;
        $commentId = (int) $request->validated('comment_id');
        $page = (int) ($request->validated('page') ?? 1);

        $pageData = $service->listResponsesForCommentPaginated(
            $viewerUserId,
            $commentId,
            $page,
            10,
        );

        return response()->json([
            'data' => $pageData['items'],
            'meta' => [
                'pagination' => [
                    'current_page' => $pageData['pagination']['current_page'],
                    'per_page' => $pageData['pagination']['per_page'],
                    'total' => $pageData['pagination']['total'],
                    'last_page' => $pageData['pagination']['last_page'],
                ],
            ],
        ]);
    }
}
