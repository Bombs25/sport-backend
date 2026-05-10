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

        ImageProcessingEvent::dispatch(
            $request->user(),
            [
                $request->file('cover_image_url'),
                $request->file('logo_url'),
            ],
            'team-' . $team->id,
            contextId: $team->id,
            variant: ImageVariantLongEdge::GridThumb,
            type: 'team',
        );

        return response()->json([
            'message' => __('Équipe mise à jour.'),
            'team' => $service->buildDetailPayload($team),
        ]);
    }
}
