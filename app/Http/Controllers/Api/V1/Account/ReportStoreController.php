<?php

namespace App\Http\Controllers\Api\V1\Account;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\Account\ReportStoreRequest;
use App\Services\Account\UserReportService;
use Illuminate\Http\JsonResponse;

/**
 * Ce qu'il fait : enregistre un signalement utilisateur.
 *
 * Pourquoi : déclenché depuis le menu ⋮ d'un profil (ActionSheet > Signaler).
 * Une seule entrée active par couple (reporter, reported) — voir
 * `UserReportService` pour la règle d'idempotence.
 */
class ReportStoreController extends Controller
{
    public function __invoke(ReportStoreRequest $request, UserReportService $service): JsonResponse
    {
        $result = $service->report(
            reporterId: (int) $request->user()->id,
            reportedUserId: (int) $request->validated('reported_user_id'),
            reason: (string) $request->validated('reason'),
            details: $request->validated('details'),
        );

        return response()->json([
            'reported' => true,
            'created' => $result['created'],
            'report_id' => $result['report_id'],
        ], $result['created'] ? 201 : 200);
    }
}
