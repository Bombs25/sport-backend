<?php

namespace App\Support\Search;

/**
 * Désactive toute écriture Typesense pendant PHPUnit (APP_ENV=testing).
 * Évite de supprimer/recréer la collection locale indexée à la main.
 *
 * En local, `php artisan migrate:fresh --seed` garde la sync active : la migration
 * supprime/recrée `users`, puis les seeders réimportent depuis MySQL.
 */
final class TypesenseSyncGuard
{
    public static function isEnabled(): bool
    {
        if (app()->environment('testing')) {
            return false;
        }

        return (bool) config('services.typesense.sync_enabled', true);
    }
}
