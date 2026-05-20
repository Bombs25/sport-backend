<?php

namespace App\Http\Controllers\Api\V1\Teams;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\Teams\TeamMemberDestroyRequest;
use App\Jobs\TeamMemberDestroyNotificationJob;
use App\Models\Team;
use App\Services\Team\TeamService;
use Illuminate\Http\JsonResponse;

class TeamMemberDestroyController extends Controller
{
    public function __invoke(TeamMemberDestroyRequest $request, TeamService $service): JsonResponse
    {
        $team = Team::query()->findOrFail((int) $request->route('team_id'));
        $memberUserId = (int) $request->validated()['member_user_id'];
        $actorUserId = (int) $request->user()->id;

        $service->removeMember($team, $actorUserId, $memberUserId);

        TeamMemberDestroyNotificationJob::dispatch($team->id, $memberUserId, $actorUserId)
            ->onQueue('post_notifications');

        return response()->json([
            'message' => $actorUserId === $memberUserId
                ? __('Sortie de l’équipe effectuée.')
                : __('Membre supprimé de l’équipe.'),
        ]);
    }
}
