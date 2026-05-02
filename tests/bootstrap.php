<?php

declare(strict_types=1);

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
