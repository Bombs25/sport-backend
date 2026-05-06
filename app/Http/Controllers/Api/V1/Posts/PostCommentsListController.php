<?php

namespace App\Http\Controllers\Api\V1\Posts;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\Posts\FetchPostCommentsListRequest;
use App\Services\Post\FetchCommentService;
use Illuminate\Http\JsonResponse;

class PostCommentsListController extends Controller
{
    public function __invoke(FetchPostCommentsListRequest $request, FetchCommentService $service): JsonResponse
    {
        $viewerUserId = (int) $request->user()->id;
        $publicationId = (int) $request->validated('publication_id');
        $publicationType = (string) $request->validated('publication_type');
        $page = (int) ($request->validated('page') ?? 1);

        $pageData = $service->listForPublicationPaginated(
            $viewerUserId,
            $publicationId,
            $publicationType,
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
