<?php

namespace App\Http\Controllers\Api\V1\Posts;

use App\Http\Controllers\Controller;
use App\Services\Post\FetchPostService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Renvoie un post régulier publié, par id, pour l'écran détail du client.
 *
 * Le contrôle de visibilité (`public` / `followers`) et le 404 sont délégués au
 * service : un post `followers`-only invisible pour l'observateur renvoie `null`,
 * traduit ici en 404 (on ne révèle pas l'existence du post).
 */
class RegularPostShowController extends Controller
{
    public function __invoke(int $post_id, Request $request, FetchPostService $fetchPostService): JsonResponse
    {
        $post = $fetchPostService->fetchRegularPostById((int) $request->user()->id, $post_id);

        abort_if($post === null, 404);

        return response()->json(['data' => $post]);
    }
}
