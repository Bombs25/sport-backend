<?php

namespace App\Http\Controllers\Api\V1\Teams;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\Teams\TeamPalmaresRequest;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;

class TeamPalmaresShowController extends Controller
{
    public function __invoke(TeamPalmaresRequest $request): JsonResponse
    {
        $teamId = (int) $request->validated('team_id');

        $palmares = DB::table('palmares')
            ->where('team_id', $teamId)
            ->orderByDesc('season_years')
            ->get([
                'id',
                'sport_id',
                'team_id',
                'rank',
                'trophy',
                'season_years',
            ])
            ->map(static function (object $row): array {
                return [
                    'id' => (int) $row->id,
                    'sport_id' => (int) $row->sport_id,
                    'team_id' => (int) $row->team_id,
                    'rank' => (int) $row->rank,
                    'trophy' => (string) $row->trophy,
                    'season_years' => json_decode((string) $row->season_years, true),
                ];
            })
            ->values()
            ->all();

        return response()->json([
            'data' => [
                'team_id' => $teamId,
                'palmares' => $palmares,
            ],
        ]);
    }
}
