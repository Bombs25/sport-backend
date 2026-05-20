<?php

namespace App\Console\Commands;

use Database\Seeders\StatsFromMatchResultsSeeder;
use Illuminate\Console\Command;

class SyncStatsFromMatchResultsCommand extends Command
{
    protected $signature = 'stats:sync-from-match-results';

    protected $description = 'Recalcule la table stats à partir des match_results validés (utile si la file d’attente n’avait pas tourné).';

    public function handle(): int
    {
        $this->info('Synchronisation des stats depuis les scores validés…');
        $this->callSilent('db:seed', ['--class' => StatsFromMatchResultsSeeder::class]);
        $this->info('Terminé.');

        return self::SUCCESS;
    }
}
