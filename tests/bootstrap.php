<?php

declare(strict_types=1);

/*
| Base MySQL **réservée aux tests** PHPUnit (données jetables). Ne pas utiliser ta base principale
| (`laravel_testing` chez toi). Aligné sur `PHPUNIT_GUARD_DATABASE` / `DB_DATABASE` dans `phpunit.xml`.
*/
if (! defined('OSPORT_PHPUNIT_MYSQL_DATABASE')) {
    define('OSPORT_PHPUNIT_MYSQL_DATABASE', 'laravel');
}

/*
| Isolation base de données : avant tout chargement Laravel, on impose les variables d’environnement
| de test (même si le shell ou l’IDE exporte DB_* vers ta base de dev). Sinon LazilyRefreshDatabase
| peut migrer / vider la mauvaise base. Le nom exact est aligné sur `PHPUNIT_GUARD_DATABASE` (phpunit.xml).
*/
(function (): void {
    $defaultMysql = (string) OSPORT_PHPUNIT_MYSQL_DATABASE;
    $guardDatabase = $_ENV['PHPUNIT_GUARD_DATABASE']
        ?? $_SERVER['PHPUNIT_GUARD_DATABASE']
        ?? (getenv('PHPUNIT_GUARD_DATABASE') !== false ? (string) getenv('PHPUNIT_GUARD_DATABASE') : null)
        ?? $defaultMysql;
    $guardDatabase = trim($guardDatabase);
    if ($guardDatabase === '') {
        $guardDatabase = $defaultMysql;
    }

    $forced = [
        'APP_ENV' => 'testing',
        'DB_CONNECTION' => 'mysql',
        'DB_HOST' => '127.0.0.1',
        'DB_PORT' => '3306',
        'DB_DATABASE' => $guardDatabase,
        'DB_USERNAME' => 'root',
        'DB_PASSWORD' => '',
        'DB_URL' => '',
        'PHPUNIT_GUARD_DATABASE' => $guardDatabase,
    ];

    foreach ($forced as $key => $value) {
        putenv("{$key}={$value}");
        $_ENV[$key] = $value;
        $_SERVER[$key] = $value;
    }
})();

/*
| PHPUnit charge ce fichier avant toute classe de test. Si `config:cache` a été exécuté localement,
| `bootstrap/cache/config.php` fige `queue.connections.post_notifications` (ex. redis) et ignore
| `POST_NOTIFICATIONS_QUEUE_DRIVER=sync` défini dans phpunit.xml — les jobs chaînés ne s’exécutent pas.
*/
$basePath = dirname(__DIR__);
$cachedConfig = $basePath.'/bootstrap/cache/config.php';
if (is_file($cachedConfig)) {
    unlink($cachedConfig);
}

require $basePath.'/vendor/autoload.php';
