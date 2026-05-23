<?php

namespace App\Http\Controllers\Api\V1\Messages;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\Messages\MessageableUsersSearchRequest;
use App\Services\Messages\MessageableUserFilterService;
use App\Services\Search\TypesenseUserService;
use Illuminate\Http\JsonResponse;
use Typesense\Exceptions\TypesenseClientError;

/**
 * Ce qu'il fait : liste les utilisateurs auxquels le viewer peut envoyer
 * un DM. Endpoint utilisé par le picker « partager à » (Instagram-like).
 *
 * Pourquoi distinct de `users/search` : on doit appliquer le filtre
 * `who_can_message_me` (everyone / followers / nobody) qui n'est pas
 * indexé dans Typesense ; on filtre donc côté app après le hit.
 */
class MessageableUsersSearchController extends Controller
{
    public function __invoke(
        MessageableUsersSearchRequest $request,
        TypesenseUserService $search,
        MessageableUserFilterService $filter,
    ): JsonResponse {
        $viewerId = (int) $request->user()->id;

        try {
            $results = $search->searchPublicUsersForDm(
                query: (string) ($request->validated('q') ?? '*'),
                page: (int) ($request->validated('page') ?? 1),
                perPage: (int) ($request->validated('per_page') ?? 20),
                excludeUserId: $viewerId,
            );
        } catch (TypesenseClientError $e) {
            return response()->json([
                'message' => __('Recherche indisponible pour le moment.'),
                'error' => $e->getMessage(),
            ], 502);
        }

        $candidateIds = array_map(static fn (array $hit): int => (int) ($hit['id'] ?? 0), $results['data']);
        $allowedIds = array_flip($filter->filterDmAllowed($viewerId, $candidateIds));

        $results['data'] = array_values(array_filter(
            $results['data'],
            static fn (array $hit): bool => isset($allowedIds[(int) ($hit['id'] ?? 0)]),
        ));

        return response()->json($results);
    }
}
