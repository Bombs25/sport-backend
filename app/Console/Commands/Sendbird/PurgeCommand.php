<?php

namespace App\Console\Commands\Sendbird;

use App\Services\Sendbird\SendbirdService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\DB;
use Throwable;

/**
 * Vide intégralement l'application Sendbird (canaux + messages + utilisateurs)
 * et la table locale `sendbird_accounts`. Pensé pour suivre un `migrate:fresh`
 * en dev, afin que les anciens canaux 1-à-1 et users `osport_{id}` ne traînent
 * plus après reset de la base.
 *
 * Refuse `production` sauf `--force`.
 */
class PurgeCommand extends Command
{
    protected $signature = 'sendbird:purge
                            {--force : Bypass production safety check.}';

    protected $description = 'Wipe ALL Sendbird group channels, messages and users (and sendbird_accounts).';

    public function handle(SendbirdService $sendbird): int
    {
        if (App::environment('production') && ! $this->option('force')) {
            $this->error('Refus : APP_ENV=production. Relancer avec --force pour confirmer.');

            return self::FAILURE;
        }

        if (! $this->confirm('Ceci va SUPPRIMER tous les canaux et utilisateurs Sendbird. Continuer ?', false)) {
            $this->info('Annulé.');

            return self::SUCCESS;
        }

        try {
            $channels = $this->purgeChannels($sendbird);
            $users = $this->purgeUsers($sendbird);
            $accounts = DB::table('sendbird_accounts')->delete();

            $this->info("Sendbird purge terminée — canaux: {$channels}, users: {$users}, sendbird_accounts: {$accounts}.");

            return self::SUCCESS;
        } catch (Throwable $e) {
            $this->error('Échec : '.$e->getMessage());

            return self::FAILURE;
        }
    }

    private function purgeChannels(SendbirdService $sendbird): int
    {
        $token = null;
        $deleted = 0;
        do {
            $page = $sendbird->listGroupChannels($token, 100);
            foreach ($page['channels'] as $channel) {
                $url = (string) ($channel['channel_url'] ?? '');
                if ($url === '') {
                    continue;
                }
                $sendbird->deleteGroupChannel($url);
                $deleted++;
            }
            $token = $page['next'];
        } while (! empty($token));

        return $deleted;
    }

    private function purgeUsers(SendbirdService $sendbird): int
    {
        $token = null;
        $deleted = 0;
        do {
            $page = $sendbird->listUsers($token, 100);
            foreach ($page['users'] as $user) {
                $id = (string) ($user['user_id'] ?? '');
                if ($id === '') {
                    continue;
                }
                $sendbird->deleteUser($id);
                $deleted++;
            }
            $token = $page['next'];
        } while (! empty($token));

        return $deleted;
    }
}
