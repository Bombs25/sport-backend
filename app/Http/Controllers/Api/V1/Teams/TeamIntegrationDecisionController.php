<?php

namespace App\Http\Controllers\Api\V1\Teams;

use App\Http\Controllers\Controller;
use App\Models\Team;
use App\Services\Team\TeamService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;

class TeamIntegrationDecisionController extends Controller
{
    public function __invoke(Request $request, int $team_id, int $asker_user_id, TeamService $service): JsonResponse
    {
        $team = Team::query()->findOrFail($team_id);

        $validated = Validator::validate(
            [
                'asker_user_id' => $asker_user_id,
                'decision' => $request->input('decision'),
            ],
            [
                'asker_user_id' => ['required', 'integer', 'exists:users,id'],
                'decision' => ['required', 'string', Rule::in(['accept', 'refuse'])],
            ],
        );

        $service->decideIntegration(
            $team,
            (int) $validated['asker_user_id'],
            $validated['decision'],
            (int) $request->user()->id,
        );

        return response()->json([
            'message' => __('Demande d’intégration traitée.'),
        ]);
    }
}
