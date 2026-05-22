<?php

namespace App\Http\Controllers\Api\V1\Posts;

use App\Http\Controllers\Controller;
use App\Services\Post\FetchPostService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Renvoie un post « automatic » (résultat de match validé), par id, pour l'écran
 * détail du client.
 *
 * Un post automatic n'a pas de ligne `posts` : son id est un `match_results.id`.
 * Un résultat introuvable ou non `validated` renvoie `null` côté service → 404 ici.
 */
class MatchResultShowController extends Controller
{
    public function __invoke(int $match_result_id, Request $request, FetchPostService $fetchPostService): JsonResponse
    {
        $post = $fetchPostService->fetchMatchResultById((int) $request->user()->id, $match_result_id);

        abort_if($post === null, 404);

        return response()->json(['data' => $post]);
    }
}
