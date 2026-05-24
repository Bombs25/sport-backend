<?php

namespace App\Http\Controllers\Api\V1\Posts;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\Posts\PostSaveToggleRequest;
use App\Services\Post\PostSaveService;
use Illuminate\Http\JsonResponse;

/**
 * Ce qu'il fait : toggle save/unsave d'une publication pour l'utilisateur.
 *
 * Pourquoi : feature « Save post » (icône bookmark). Sync (pas de job),
 * réponse immédiate avec l'état — pas de notif à l'auteur (privé).
 */
class PostSaveToggleController extends Controller
{
    public function __invoke(PostSaveToggleRequest $request, PostSaveService $service): JsonResponse
    {
        $validated = $request->validated();

        $result = $service->toggleSave(
            (int) $validated['post_id'],
            (int) $request->user()->id,
            (string) $validated['post_type'],
            (string) $validated['action'],
        );

        return response()->json([
            'data' => [
                'saved' => $result['saved'],
                'changed' => $result['changed'],
            ],
        ]);
    }
}
