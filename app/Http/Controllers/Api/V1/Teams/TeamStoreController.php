<?php

namespace App\Http\Controllers\Api\V1\Teams;

use App\Enums\ImageVariantLongEdge;
use App\Events\ImageProcessingEvent;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\Teams\TeamStoreRequest;
use App\Services\Team\TeamService;
use Illuminate\Http\JsonResponse;

/**
 * Ce qu’il fait : crée une équipe et rattache le créateur comme **captain** actif dans `team_members`.
 *
 * Les fichiers `cover_image_url` / `logo_url` (multipart, obligatoires) déclenchent le pipeline générique
 * {@see ImageProcessingEvent} avec {@code uniqueKey} {@code team-{id}} et variante {@see ImageVariantLongEdge::GridThumb}.
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
            contextId: $team->id,
            variant: ImageVariantLongEdge::GridThumb,
            type: 'team',
        );

        $team->refresh();

        return response()->json([
            'message' => __('Équipe créée.'),
            'team' => $service->buildDetailPayload($team),
        ], 201);
    }
}
