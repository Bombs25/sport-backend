<?php

namespace App\Services\Search\Concerns;

use App\Services\Search\TypesenseUserService;
use App\Support\Search\TypesenseSyncGuard;
use Illuminate\Support\Facades\Log;
use Typesense\Exceptions\TypesenseClientError;

trait SyncsUserToTypesense
{
    protected function syncUserToTypesense(TypesenseUserService $typesense, int $userId): void
    {
        if (! TypesenseSyncGuard::isEnabled()) {
            return;
        }

        try {
            $typesense->ensureCollection();
            $typesense->syncUserFromDatabase($userId);
        } catch (TypesenseClientError $e) {
            Log::warning('Typesense user sync failed.', [
                'user_id' => $userId,
                'message' => $e->getMessage(),
            ]);
        }
    }
}
