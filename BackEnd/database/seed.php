<?php

require_once __DIR__ . '/../vendor/autoload.php';

// Load environment variables
$dotenv = Dotenv\Dotenv::createImmutable(__DIR__ . '/..');
$dotenv->load();

// Run database seeder
$seeder = new DatabaseSeeder();
$seeder->run();

echo "Database seeding completed successfully!\n"; 