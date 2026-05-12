<?php

namespace Database\Seeders;

use App\Services\Search\TypesenseTeamService;
use App\Services\Search\TypesenseUserService;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Typesense\Exceptions\TypesenseClientError;

/**
 * Jeu de données cohérent pour toute l’app **telle que migrée** (pas de tables `posts`, `subscriptions`, etc. tant qu’elles n’existent pas).
 *
 * Couverture métier : sports, utilisateurs, profils, sports pratiqués, follows, équipes, adhésions, matchs,
 * résultats en masse ({@see DemoBulkMatchResultsSeeder}), évaluations, agrégation stats via {@see StatsFromMatchResultsSeeder}, commentaires, réponses, likes (autres seeders démo), jetons push (fcm_token), jetons Sanctum démo.
 *
 * Notifications base de données démo (≥ 1000 pour feed.demo) : {@see DemoNotificationsSeeder}. Tables framework (`jobs`, `cache`, …) hors scope.
 *
 * Comptes démo (mot de passe habituel de la factory : « password ») :
 * - {@see DemoMatchCalendarSeeder} : calendar.demo@osport.local — token `demo-calendar-seed`
 * - {@see DemoMatchSocialInteractionsSeeder} : feed.demo@osport.local — token `demo-feed-seed`
 */
class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $this->call([
            SportsSeeder::class,
            DemoUsersSeeder::class,
            DemoTeamsSeeder::class,
            DemoMatchCalendarSeeder::class,
            DemoMatchSocialInteractionsSeeder::class,
            DemoBulkMatchResultsSeeder::class,
            StatsFromMatchResultsSeeder::class,
            DemoNotificationsSeeder::class,
        ]);

        $this->syncTypesenseUsers();
        $this->syncTypesenseTeams();

        if ($this->command !== null) {
            $this->command->newLine();
            $this->command->info('Résumé démo : calendar.demo@osport.local et feed.demo@osport.local (password = password).');
        }
    }

    private function syncTypesenseUsers(): void
    {
        try {
            $service = app(TypesenseUserService::class);
            $service->recreateCollection();
            $synced = $service->syncAllUsersFromDatabase();
        } catch (TypesenseClientError $e) {
            $this->command?->warn('Typesense indisponible, synchronisation finale ignorée : '.$e->getMessage());

            return;
        }

        $this->command?->info('Typesense users synchronisé depuis MySQL : '.$synced.' document(s).');
    }

    private function syncTypesenseTeams(): void
    {
        try {
            $service = app(TypesenseTeamService::class);
            $service->recreateCollection();
            $synced = $service->syncAllTeamsFromDatabase();
        } catch (TypesenseClientError $e) {
            $this->command?->warn('Typesense indisponible, synchronisation finale teams ignorée : '.$e->getMessage());

            return;
        }

        $this->command?->info('Typesense teams synchronisé depuis MySQL : '.$synced.' document(s).');
    }
}
