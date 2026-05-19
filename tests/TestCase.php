<?php

namespace Tests;

use Illuminate\Foundation\Testing\TestCase as BaseTestCase;

abstract class TestCase extends BaseTestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        $this->assertDatabaseIsTestIsolationTarget();

        /*
         * Les tests ne doivent jamais utiliser le transport SMTP du `.env` local (risque d’échec 535 / fuite).
         * `phpunit.xml` définit déjà `MAIL_MAILER=array` ; ce filet évite les overrides d’environnement shell.
         */
        config(['mail.default' => 'array']);

        /*
         * Typesense : `TypesenseSyncGuard` + `TYPESENSE_SYNC_ENABLED=false` dans phpunit.xml — aucune
         * suppression/recréation de collection ni import pendant les tests (index local préservé).
         */

        /*
         * `php artisan config:cache` fige `post_notifications` sur redis : sans ce correctif, les chaînes
         * `Bus::chain(...)->onConnection('post_notifications')` ne s’exécutent pas sous PHPUnit.
         */
        if ($this->app->environment('testing')) {
            config([
                'queue.connections.post_notifications' => [
                    'driver' => 'sync',
                ],
            ]);
            // Évite une instance de `QueueManager` résolue pendant le boot avec l’ancienne config (ex. redis).
            $this->app->forgetInstance('queue');
        }
    }

    /**
     * Bloque toute exécution de tests si la connexion par défaut ne pointe pas vers la base jetable
     * attendue (`PHPUNIT_GUARD_DATABASE`, défaut `laravel` = base jetable). Évite d’écrire dans ta base principale.
     */
    private function assertDatabaseIsTestIsolationTarget(): void
    {
        if (! $this->app->environment('testing')) {
            throw new \RuntimeException(
                'Tests refusés : APP_ENV doit être « testing » (voir tests/bootstrap.php et phpunit.xml).',
            );
        }

        $defaultMysql = defined('OSPORT_PHPUNIT_MYSQL_DATABASE') ? (string) OSPORT_PHPUNIT_MYSQL_DATABASE : 'laravel';
        $allowed = (string) env('PHPUNIT_GUARD_DATABASE', $defaultMysql);
        if ($allowed === '') {
            $allowed = $defaultMysql;
        }

        $connectionName = (string) config('database.default');
        $driver = (string) config("database.connections.{$connectionName}.driver");
        if (! in_array($driver, ['mysql', 'mariadb'], true)) {
            throw new \RuntimeException(
                "Tests refusés : connexion « {$connectionName} » (driver « {$driver} »). Ce projet exige mysql/mariadb pour les tests spatial.",
            );
        }

        $database = (string) config("database.connections.{$connectionName}.database");
        if ($database !== $allowed) {
            throw new \RuntimeException(
                "Tests refusés : DB actuelle « {$database} » ≠ base autorisée « {$allowed} ». ".
                'Ne lance pas les tests contre ta base principale : exporte PHPUNIT_GUARD_DATABASE + DB_DATABASE identiques, ou utilise uniquement phpunit.xml.',
            );
        }
    }
}
