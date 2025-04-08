<?php

namespace App\Config;

use PDO;
use PDOException;

class Database
{
    private static $instance = null;
    private $connection;

    private function __construct()
    {
        try {
            $this->connection = new PDO(
                "mysql:host=" . $_ENV['DB_HOST'] . ";dbname=" . $_ENV['DB_NAME'],
                $_ENV['DB_USER'],
                $_ENV['DB_PASSWORD']
            );
            $this->connection->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        } catch (PDOException $e) {
            die("Database connection failed: " . $e->getMessage());
        }
    }

    public static function getInstance()
    {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    public function getConnection()
    {
        return $this->connection;
    }
}

return [
    /*
    |--------------------------------------------------------------------------
    | Database Configuration
    |--------------------------------------------------------------------------
    |
    | Here you can configure your database connection settings.
    |
    */

    'default' => 'mysql',

    'connections' => [
        'mysql' => [
            'driver' => 'mysql',
            'host' => 'localhost',
            'port' => 3306,
            'database' => 'warstorm_db',
            'username' => 'root',
            'password' => '',
            'charset' => 'utf8mb4',
            'collation' => 'utf8mb4_unicode_ci',
            'prefix' => '',
            'strict' => true,
            'engine' => 'InnoDB',
        ],

        'sqlite' => [
            'driver' => 'sqlite',
            'database' => __DIR__ . '/../database/database.sqlite',
            'prefix' => '',
        ],

        'pgsql' => [
            'driver' => 'pgsql',
            'host' => 'localhost',
            'port' => 5432,
            'database' => 'warstorm_db',
            'username' => 'root',
            'password' => '',
            'charset' => 'utf8',
            'prefix' => '',
            'schema' => 'public',
            'sslmode' => 'prefer',
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Migration Repository Table
    |--------------------------------------------------------------------------
    |
    | This table keeps track of all the migrations that have already run for
    | your application. Using this information, we can determine which of
    | the migrations on disk haven't actually been run in the database.
    |
    */
    'migrations' => 'migrations',

    /*
    |--------------------------------------------------------------------------
    | Redis Databases
    |--------------------------------------------------------------------------
    |
    | Redis is an open source, fast, and advanced key-value store that also
    | provides a richer set of commands than a typical key-value systems
    | such as APC or Memcached.
    |
    */
    'redis' => [
        'client' => 'predis',
        'default' => [
            'host' => '127.0.0.1',
            'password' => null,
            'port' => 6379,
            'database' => 0,
        ],
        'cache' => [
            'host' => '127.0.0.1',
            'password' => null,
            'port' => 6379,
            'database' => 1,
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Database Logging
    |--------------------------------------------------------------------------
    |
    | Enable database query logging for debugging purposes.
    |
    */
    'logging' => [
        'enabled' => true,
        'path' => __DIR__ . '/../logs/database.log',
        'level' => 'debug', // debug, info, warning, error
    ],

    /*
    |--------------------------------------------------------------------------
    | Query Cache
    |--------------------------------------------------------------------------
    |
    | Enable query caching to improve performance.
    |
    */
    'cache' => [
        'enabled' => true,
        'driver' => 'file', // file, redis
        'path' => __DIR__ . '/../cache/queries',
        'ttl' => 3600, // Time to live in seconds
    ],
];