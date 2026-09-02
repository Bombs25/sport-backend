<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Http\Controllers\Api\V1\MapApiCotntroller;

class addRegionsInformationToTeamsTable extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:teamregion';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'add Region info to teams table';

    /**
     * Execute the console command.
     */
    public function handle(MapApiCotntroller $map)
    {
        try {
            $map->add_region();
            $this->info('Regions ajouter avec success ✔');
            return self::SUCCESS;
        } catch (\Throwable $e) {
            $this->error('Échec : ' . $e->getMessage());
            return self::FAILURE;
        }
    }
}
