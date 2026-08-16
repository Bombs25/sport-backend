<?php

use Illuminate\Support\Str;
use Pdo\Mysql;

return [

    /*
    |--------------------------------------------------------------------------
    | Default Database Connection Name
    |--------------------------------------------------------------------------
    |
    | Here you may specify which of the database connections below you wish
    | to use as your default connection for database operations. This is
    | the connection which will be utilized unless another connection
    | is explicitly specified when you execute a query / statement.
    |
    */

    'default' => env('DB_CONNECTION', 'mysql'),

    /*
    |--------------------------------------------------------------------------
    | Database Connections
    |--------------------------------------------------------------------------
    |
    | Below are all of the database connections defined for your application.
    | An example configuration is provided for each database system which
    | is supported by Laravel. You're free to add / remove connections.
    |
    */

    'connections' => [

        'sqlite' => [
            'driver' => 'sqlite',
            'url' => env('DB_URL'),
            'database' => env('DB_DATABASE', database_path('database.sqlite')),
            'prefix' => '',
            'foreign_key_constraints' => env('DB_FOREIGN_KEYS', true),
            'busy_timeout' => null,
            'journal_mode' => null,
            'synchronous' => null,
            'transaction_mode' => 'DEFERRED',
        ],

        'mysql' => [
            'driver' => 'mysql',
            'url' => env('DB_URL'),
            'host' => env('DB_HOST', '127.0.0.1'),
            'port' => env('DB_PORT', '3306'),
            'database' => env('DB_DATABASE', 'laravel'),
            'username' => env('DB_USERNAME', 'root'),
            'password' => env('DB_PASSWORD', ''),
            'unix_socket' => env('DB_SOCKET', ''),
            'charset' => env('DB_CHARSET', 'utf8mb4'),
            'collation' => env('DB_COLLATION', 'utf8mb4_unicode_ci'),
            'prefix' => '',
            'prefix_indexes' => true,
            'strict' => true,
            'engine' => null,
            'options' => extension_loaded('pdo_mysql') ? array_filter([
                (PHP_VERSION_ID >= 80500 ? Mysql::ATTR_SSL_CA : PDO::MYSQL_ATTR_SSL_CA) => env('MYSQL_ATTR_SSL_CA'),
            ]) : [],
        ],

        'mariadb' => [
            'driver' => 'mariadb',
            'url' => env('DB_URL'),
            'host' => env('DB_HOST', '127.0.0.1'),
            'port' => env('DB_PORT', '3306'),
            'database' => env('DB_DATABASE', 'laravel'),
            'username' => env('DB_USERNAME', 'root'),
            'password' => env('DB_PASSWORD', ''),
            'unix_socket' => env('DB_SOCKET', ''),
            'charset' => env('DB_CHARSET', 'utf8mb4'),
            'collation' => env('DB_COLLATION', 'utf8mb4_unicode_ci'),
            'prefix' => '',
            'prefix_indexes' => true,
            'strict' => true,
            'engine' => null,
            'options' => extension_loaded('pdo_mysql') ? array_filter([
                (PHP_VERSION_ID >= 80500 ? Mysql::ATTR_SSL_CA : PDO::MYSQL_ATTR_SSL_CA) => env('MYSQL_ATTR_SSL_CA'),
            ]) : [],
        ],

        'pgsql' => [
            'driver' => 'pgsql',
            'url' => env('DB_URL'),
            'host' => env('DB_HOST', '127.0.0.1'),
            'port' => env('DB_PORT', '5432'),
            'database' => env('DB_DATABASE', 'laravel'),
            'username' => env('DB_USERNAME', 'root'),
            'password' => env('DB_PASSWORD', ''),
            'charset' => env('DB_CHARSET', 'utf8'),
            'prefix' => '',
            'prefix_indexes' => true,
            'search_path' => 'public',
            'sslmode' => env('DB_SSLMODE', 'prefer'),
        ],

        'sqlsrv' => [
            'driver' => 'sqlsrv',
            'url' => env('DB_URL'),
            'host' => env('DB_HOST', 'localhost'),
            'port' => env('DB_PORT', '1433'),
            'database' => env('DB_DATABASE', 'laravel'),
            'username' => env('DB_USERNAME', 'root'),
            'password' => env('DB_PASSWORD', ''),
            'charset' => env('DB_CHARSET', 'utf8'),
            'prefix' => '',
            'prefix_indexes' => true,
            // 'encrypt' => env('DB_ENCRYPT', 'yes'),
            // 'trust_server_certificate' => env('DB_TRUST_SERVER_CERTIFICATE', 'false'),
        ],

    ],

    /*
    |--------------------------------------------------------------------------
    | Migration Repository Table
    |--------------------------------------------------------------------------
    |
    | This table keeps track of all the migrations that have already run for
    | your application. Using this information, we can determine which of
    | the migrations on disk haven't actually been run on the database.
    |
    */

    'migrations' => [
        'table' => 'migrations',
        'update_date_on_publish' => true,
    ],

    /*
    |--------------------------------------------------------------------------
    | Redis Databases
    |--------------------------------------------------------------------------
    |
    | Redis is an open source, fast, and advanced key-value store that also
    | provides a richer body of commands than a typical key-value system
    | such as Memcached. You may define your connection settings here.
    |
    */

    'redis' => [

        'client' => extension_loaded('redis')
            ? env('REDIS_CLIENT', 'phpredis')
            : 'predis',

        'options' => [
            // « Cluster » = Redis en grappe native : plusieurs nœuds qui partagent les données par partitions (slots) et se font remplacer en cas de panne.
            // Ici, l’option indique à Laravel d’activer ce mode (client + routage des commandes vers le bon nœud) ; `false` = une seule instance (standalone). La valeur par défaut `redis` cible le cluster Redis « classique ».
            'cluster' => env('REDIS_CLUSTER', 'redis'),
            // Préfixe toutes les clés : évite les collisions si plusieurs apps ou environnements partagent le même serveur Redis.
            'prefix' => env('REDIS_PREFIX', Str::slug((string) env('APP_NAME', 'laravel')).'-database-'),
            // Connexions persistantes : réduit le coût des handshakes TCP à chaque requête, au prix d’une gestion des connexions un peu plus délicate en prod.
            'persistent' => env('REDIS_PERSISTENT', false),
        ],

        'default' => [
            // URL complète (schéma redis[s]://) : alternative pratique au trio host/port/mot de passe quand l’hébergeur fournit un DSN unique.
            'url' => env('REDIS_URL'),
            // Hôte Redis (ignoré si une URL complète est fournie et utilisée pour la connexion).
            'host' => env('REDIS_HOST'),
            // Nom d’utilisateur ACL (Redis 6+) ou identifiant cloud ; laisser vide si l’instance n’en a pas besoin.
            'username' => env('REDIS_USERNAME'),
            // Secret d’authentification ; obligatoire sur les instances protégées, vide en local si Redis est ouvert.
            'password' => env('REDIS_PASSWORD'),
            // Port TCP du serveur (6379 par défaut ; peut différer derrière un tunnel ou un load balancer).
            'port' => env('REDIS_PORT', '6379'),
            // Index de base logique Redis : isole les clés « générales » (sessions, queues, etc.) des autres usages sur le même serveur.
            'database' => env('REDIS_DB', '0'),
            // Nombre de nouvelles tentatives après une erreur transitoire (réseau, timeout, serveur occupé).
            'max_retries' => env('REDIS_MAX_RETRIES', 3),
            // Algorithme d’espacement entre les tentatives ; « decorrelated_jitter » réduit les pics synchronisés (thundering herd).
            'backoff_algorithm' => env('REDIS_BACKOFF_ALGORITHM', 'decorrelated_jitter'),
            // Délai de base (ms) servant de point de départ au calcul du temps d’attente entre deux essais.
            'backoff_base' => env('REDIS_BACKOFF_BASE', 100),
            // Plafond (ms) : aucun intervalle entre retries ne dépassera cette valeur, même si l’algorithme le demanderait plus long.
            'backoff_cap' => env('REDIS_BACKOFF_CAP', 1000),
        ],

        'cache' => [
            // DSN Redis pour cette connexion : même variable d’environnement que `default` si un seul cluster sert tout.
            'url' => env('REDIS_URL'),
            // Hôte du serveur utilisé pour le cache (souvent le même que `default`, base logique différente via `database`).
            'host' => env('REDIS_HOST'),
            // Utilisateur ACL / cloud ; aligné sur `default` quand le cache vit sur la même instance Redis.
            'username' => env('REDIS_USERNAME'),
            // Mot de passe ou secret ; identique à `default` si les deux connexions pointent vers le même endpoint.
            'password' => env('REDIS_PASSWORD'),
            // Port TCP ; cohérent avec `default` sauf si tu exposes un endpoint cache distinct.
            'port' => env('REDIS_PORT', '6379'),
            // Base logique dédiée au driver cache : évite d’écraser ou de mélanger les clés avec la connexion `default`.
            'database' => env('REDIS_CACHE_DB', '1'),
            // Tentatives en cas d’échec transitoire sur les lectures/écritures cache (même paramètres que `default` par défaut).
            'max_retries' => env('REDIS_MAX_RETRIES', 3),
            // Jitter décorrélé entre les retries pour ne pas saturer Redis ni le réseau lors d’incidents groupés.
            'backoff_algorithm' => env('REDIS_BACKOFF_ALGORITHM', 'decorrelated_jitter'),
            // Délai minimal de référence (ms) avant de retenter après une erreur.
            'backoff_base' => env('REDIS_BACKOFF_BASE', 100),
            // Borne supérieure (ms) du délai entre deux essais pour cette connexion cache.
            'backoff_cap' => env('REDIS_BACKOFF_CAP', 1000),
        ],

        'post_notifications' => [
            'url' => env('REDIS_URL'),
            'host' => env('REDIS_HOST'),
            'username' => env('REDIS_USERNAME'),
            'password' => env('REDIS_PASSWORD'),
            'port' => env('REDIS_PORT', '6379'),
            'database' => env('REDIS_POST_NOTIFICATIONS_DB', '4'),
            'max_retries' => env('REDIS_MAX_RETRIES', 3),
            'backoff_algorithm' => env('REDIS_BACKOFF_ALGORITHM', 'decorrelated_jitter'),
            'backoff_base' => env('REDIS_BACKOFF_BASE', 100),
            'backoff_cap' => env('REDIS_BACKOFF_CAP', 1000),
        ],

        'app_main_cache' => [
            'url' => env('REDIS_URL'),
            'host' => env('REDIS_HOST'),
            'username' => env('REDIS_USERNAME'),
            'password' => env('REDIS_PASSWORD'),
            'port' => env('REDIS_PORT', '6379'),
            'database' => env('REDIS_APP_MAIN_CACHE_DB', '5'),
            'max_retries' => env('REDIS_MAX_RETRIES', 3),
            'backoff_algorithm' => env('REDIS_BACKOFF_ALGORITHM', 'decorrelated_jitter'),
            'backoff_base' => env('REDIS_BACKOFF_BASE', 100),
            'backoff_cap' => env('REDIS_BACKOFF_CAP', 1000),
        ],

        // Les options de contrôle
        // max_retries (Nombre de tentatives)
        // C'est le nombre de fois que Laravel va essayer de "frapper à la porte" de Redis avant d'abandonner et d'envoyer une erreur 500 à l'utilisateur.

        // Conseil : On met souvent 3. Trop peu, et une micro-coupure fait planter ton site. Trop, et l'utilisateur attend 10 secondes devant une page blanche.

        // retry_interval (Intervalle fixe)
        // C'est le temps d'attente (en millisecondes) entre chaque tentative.

        // Note : Cette option est souvent ignorée si tu utilises un algorithme de backoff plus intelligent.

        // backoff_base (Le point de départ)
        // C'est le délai initial (en ms) avant la toute première tentative de reconnexion. Si tu mets 100, la première attente sera de 100ms.

        // backoff_cap (Le plafond)
        // C'est la limite maximale d'attente. Même si l'algorithme calcule qu'il faut attendre 30 secondes, si ton backoff_cap est à 1000, il n'attendra jamais plus de 1 seconde (1000ms). Cela évite que tes processus PHP ne restent bloqués indéfiniment.

        // Les algorithmes de Backoff (La stratégie)
        // Le but ici est d'éviter le "Thundering Herd" (le troupeau qui piétine) : si 5000 utilisateurs essaient de se reconnecter exactement à la même milliseconde après une panne, ils vont achever ton serveur Redis.

        // constant
        // Logique : Attend toujours le même temps (ex: 100ms, 100ms, 100ms).

        // Usage : Très basique, peu recommandé pour 200k users.

        // exponential
        // Logique : Double le temps à chaque essai (100ms, 200ms, 400ms, 800ms...).

        // Usage : Très efficace pour laisser le temps au serveur de "reprendre son souffle".

        // uniform
        // Logique : Ajoute un délai aléatoire mais de manière linéaire. C'est un entre-deux.

        // decorrelated_jitter (Le plus recommandé)
        // Logique : C'est l'algorithme de pointe (utilisé par AWS). Il utilise une dose de chaos (aléatoire) pour calculer le prochain essai en se basant sur le précédent.

        // Pourquoi pour ton réseau social ? C'est celui qui sépare le mieux les tentatives de reconnexion des milliers d'utilisateurs. Au lieu d'arriver par vagues, les connexions arrivent "en pluie" fine, ce qui est beaucoup plus facile à gérer pour un processeur.

        // equal_jitter
        // Logique : Un mélange d'exponentiel et d'aléatoire, mais avec une part fixe. C'est un bon milieu entre "prévisible" et "aléatoire".

        // 'max_retries' => 3,
        // 'backoff_algorithm' => 'decorrelated_jitter',
        // 'backoff_base' => 100, // Commence à 100ms
        // 'backoff_cap' => 1000, // Ne jamais attendre plus d'une seconde

    ],

];
