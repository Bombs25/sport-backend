<?php

namespace App\Http\Controllers\Api\V1\Teams;

use App\Enums\ImageVariantLongEdge;
use App\Events\ImageProcessingEvent;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\Teams\TeamUpdateRequest;
use App\Models\Team;
use App\Services\Team\TeamService;
use Illuminate\Http\JsonResponse;

/**
 * Ce qu’il fait : met à jour une équipe (créateur ou **captain** actif) ; régénère le **slug** si le nom change.
 */
class TeamUpdateController extends Controller
{
    public function __invoke(TeamUpdateRequest $request, TeamService $service): JsonResponse
    {
        $team = Team::query()->findOrFail((int) $request->route('team_id'));

        $data = $request->validated();
        if ($request->hasFile('cover_image_url')) {
            unset($data['cover_image_url']);
        }
        if ($request->hasFile('logo_url')) {
            unset($data['logo_url']);
        }
        $service->updateTeam($team, $data);

        $files = [];
        $mediaFields = [];

        if ($request->hasFile('cover_image_url')) {
            $files[] = $request->file('cover_image_url');
            $mediaFields[] = 'cover_image_url';
        }

        if ($request->hasFile('logo_url')) {
            $files[] = $request->file('logo_url');
            $mediaFields[] = 'logo_url';
        }

        if ($files !== []) {
            ImageProcessingEvent::dispatch(
                $request->user(),
                $files,
                'team-'.$team->id,
                contextId: $team->id,
                variant: ImageVariantLongEdge::GridThumb,
                type: 'team',
                mediaFields: $mediaFields,
            );
        }

        return response()->json([
            'message' => __('Équipe mise à jour.'),
            'team' => $service->buildDetailPayload($team),
        ]);
    }
}
