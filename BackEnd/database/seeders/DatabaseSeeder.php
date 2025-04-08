<?php

use App\Core\Database;

class DatabaseSeeder
{
    private $db;

    public function __construct()
    {
        $this->db = Database::getInstance();
    }

    public function run()
    {
        // Run migrations first
        $this->runMigrations();

        // Run seeders in order
        $this->runSeeders();
    }

    private function runMigrations()
    {
        // Run migrations in order
        $migrations = [
            '001_create_users_table.php',
            '002_create_categories_table.php',
            '003_create_tags_table.php',
            '004_create_games_table.php',
            '005_create_news_table.php',
            '006_create_game_tags_table.php',
            '007_create_news_tags_table.php',
            '008_create_wallets_table.php',
            '009_create_transactions_table.php',
            '010_create_orders_table.php',
            '011_create_order_items_table.php',
            '012_create_comments_table.php',
            '013_create_reports_table.php'
        ];

        foreach ($migrations as $migration) {
            $migrationClass = str_replace('.php', '', $migration);
            $migrationClass = str_replace('_', ' ', $migrationClass);
            $migrationClass = ucwords($migrationClass);
            $migrationClass = str_replace(' ', '', $migrationClass);

            $migrationFile = __DIR__ . '/../migrations/' . $migration;
            if (file_exists($migrationFile)) {
                require_once $migrationFile;
                $migrationInstance = new $migrationClass();
                $migrationInstance->up();
            }
        }
    }

    private function runSeeders()
    {
        // Run seeders in order
        $seeders = [
            'UserSeeder',
            'CategorySeeder',
            'TagSeeder',
            'GameSeeder',
            'NewsSeeder',
            'GameTagSeeder',
            'NewsTagSeeder',
            'WalletSeeder',
            'TransactionSeeder',
            'OrderSeeder',
            'CommentSeeder',
            'ReportSeeder'
        ];

        foreach ($seeders as $seeder) {
            $seederFile = __DIR__ . '/' . $seeder . '.php';
            if (file_exists($seederFile)) {
                require_once $seederFile;
                $seederInstance = new $seeder();
                $seederInstance->run();
            }
        }
    }
} 