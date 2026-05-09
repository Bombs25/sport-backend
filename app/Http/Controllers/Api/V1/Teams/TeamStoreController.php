<?php

namespace App\Http\Controllers\Api\V1\Teams;

use App\Enums\ImageVariantLongEdge;
use App\Events\ImageProcessingEvent;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\Teams\TeamStoreRequest;
use App\Services\Team\TeamService;
use Illuminate\Http\JsonResponse;
use Throwable;

/**
 * Ce qu’il fait : crée une équipe et rattache le créateur comme **captain** actif dans `team_members`.
 *
 * Les fichiers `cover_image_url` / `logo_url` (multipart, obligatoires) déclenchent un {@see ImageProcessingEvent} :
 * variante pipeline fixée côté app ({@see ImageVariantLongEdge::Feed}, 1080 px) ; pas de paramètre client.
 */
class TeamStoreController extends Controller
{
    public function __invoke(TeamStoreRequest $request, TeamService $service): JsonResponse
    {

        $data = collect($request->validated())
            ->except(['cover_image_url', 'logo_url'])
            ->all();

        $team = $service->createForCreator((int) $request->user()->id, $data);

        ImageProcessingEvent::dispatch(
            $request->user(),
            [
                $request->file('cover_image_url'),
                $request->file('logo_url'),
            ],
            'team-'.$team->id,
            $team->id,
            ImageVariantLongEdge::GridThumb,
        );

        $team->refresh();

        return response()->json([
            'message' => __('Équipe créée.'),
            'team' => $service->buildDetailPayload($team),
        ], 201);
        // try {

        // } catch (Throwable $e) {
        //     report($e);

        //     return response()->json([
        //         'message' => __('Impossible de créer l’équipe. Réessaie plus tard.'),
        //     ], JsonResponse::HTTP_INTERNAL_SERVER_ERROR);
        // }
    }
}
