<?php

namespace App\Listeners;

use Illuminate\Database\Events\DatabaseRefreshed;
use Illuminate\Support\Facades\Cache;

/**
 * Vide le cache applicatif Redis (`app_main_cache`) après un `migrate:fresh` /
 * `migrate:refresh`.
 *
 * Pourquoi : la base est entièrement reconstruite ; sans ce nettoyage, des
 * entrées en cache (IDs de publications déjà vues, OTP, caches sport / suivis)
 * continueraient de pointer vers des lignes qui n'existent plus.
 */
class FlushAppMainCacheOnDatabaseRefreshed
{
    public function handle(DatabaseRefreshed $event): void
    {
        Cache::store('app_main_cache')->flush();
    }
}
