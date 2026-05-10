<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

/**
 * Jeu de données cohérent pour toute l’app **telle que migrée** (pas de tables `posts`, `subscriptions`, etc. tant qu’elles n’existent pas).
 *
 * Couverture métier : sports, utilisateurs, profils, sports pratiqués, follows, équipes, adhésions, matchs,
 * résultats en masse ({@see DemoBulkMatchResultsSeeder}), évaluations, agrégation stats via {@see StatsFromMatchResultsSeeder}, commentaires, réponses, likes (autres seeders démo), jetons push (fcm_token), jetons Sanctum démo.
 *
 * Non couvert volontairement : `notifications` (morph UUID vs id utilisateur entier), tables framework (`jobs`, `cache`, …).
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
        ]);

        if ($this->command !== null) {
            $this->command->newLine();
            $this->command->info('Résumé démo : calendar.demo@osport.local et feed.demo@osport.local (password = password).');
        }
    }
}
