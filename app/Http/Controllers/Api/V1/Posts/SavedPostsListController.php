<?php

namespace App\Http\Controllers\Api\V1\Posts;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\Posts\SavedPostsListRequest;
use App\Services\Post\PostSaveService;
use Illuminate\Http\JsonResponse;

/**
 * Ce qu'il fait : liste paginée (cursor) des publications sauvegardées par
 * l'utilisateur courant, **tous types confondus** (regular + automatic).
 *
 * Pourquoi : alimente l'onglet « Enregistrés » du ProfileScreen côté app.
 * Les items portent un champ `publication_type` discriminant ; l'app rend
 * `RegularPostCard` ou `MatchResultPostCard` selon le type.
 */
class SavedPostsListController extends Controller
{
    public function __invoke(SavedPostsListRequest $request, PostSaveService $service): JsonResponse
    {
        $userId = (int) $request->user()->id;
        $limit = (int) ($request->validated('limit') ?? 20);
        $cursor = $request->validated('cursor');
        $cursor = $cursor === null ? null : (int) $cursor;

        $result = $service->listSavedPosts($userId, $limit, $cursor);

        return response()->json([
            'data' => $result['data']->values(),
            'meta' => $result['meta'],
        ]);
    }
}
