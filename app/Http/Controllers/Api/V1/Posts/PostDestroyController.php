<?php

namespace App\Http\Controllers\Api\V1\Posts;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\Posts\PostDestroyRequest;
use App\Services\Post\PostService;
use Illuminate\Http\JsonResponse;

/**
 * Ce qu'il fait : supprime (soft-delete) un post régulier appartenant à l'utilisateur.
 *
 * Pourquoi : déclenché depuis le menu ⋮ d'un post sur le fil — l'autorisation
 * (auteur seulement) est appliquée dans `PostService::deleteRegularPost`.
 * Les posts de score validé vivent dans `match_results` et ne sont pas
 * supprimables par ce endpoint (par design).
 */
class PostDestroyController extends Controller
{
    public function __invoke(PostDestroyRequest $request, PostService $service): JsonResponse
    {
        $service->deleteRegularPost(
            (int) $request->validated('post_id'),
            (int) $request->user()->id,
        );

        return response()->json([
            'deleted' => true,
        ]);
    }
}
