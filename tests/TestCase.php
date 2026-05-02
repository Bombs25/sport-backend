<?php

namespace Tests;

use Illuminate\Foundation\Testing\TestCase as BaseTestCase;

abstract class TestCase extends BaseTestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        /*
         * Les tests ne doivent jamais utiliser le transport SMTP du `.env` local (risque d’échec 535 / fuite).
         * `phpunit.xml` définit déjà `MAIL_MAILER=array` ; ce filet évite les overrides d’environnement shell.
         */
        config(['mail.default' => 'array']);

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
}
