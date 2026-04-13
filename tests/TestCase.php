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
    }
}
