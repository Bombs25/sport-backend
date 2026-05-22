<?php

namespace App\Console\Commands\Sendbird;

use App\Models\User;
use App\Services\Sendbird\SendbirdService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Throwable;

/**
 * Resynchronise les comptes Sendbird existants (nickname + profile_url absolu).
 *
 * Utile après un changement de format d'avatar : les comptes provisionnés avant
 * le correctif gardent un `profile_url` relatif jusqu'à un nouvel `ensureUser`.
 *
 *   php artisan sendbird:sync-users
 */
class SyncUsersCommand extends Command
{
    protected $signature = 'sendbird:sync-users';

    protected $description = 'Resynchronise nickname + avatar des comptes Sendbird existants.';

    public function handle(SendbirdService $sendbird): int
    {
        $userIds = DB::table('sendbird_accounts')->pluck('user_id');

        if ($userIds->isEmpty()) {
            $this->info('Aucun compte Sendbird à resynchroniser.');

            return self::SUCCESS;
        }

        $this->info("Resynchronisation de {$userIds->count()} compte(s) Sendbird…");
        $synced = 0;
        $failed = 0;

        foreach ($userIds as $userId) {
            $user = User::query()->find($userId);
            if ($user === null) {
                continue;
            }

            try {
                $sendbird->ensureUser($user);
                $synced++;
                $this->line("  ✔ osport_{$userId}");
            } catch (Throwable $e) {
                $failed++;
                $this->line("  ✘ osport_{$userId} — {$e->getMessage()}");
            }
        }

        $this->info("Terminé : {$synced} synchronisé(s), {$failed} échec(s).");

        return $failed > 0 ? self::FAILURE : self::SUCCESS;
    }
}
