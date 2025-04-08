<?php
use App\Core\App;

// Error reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Define base path
define('BASE_PATH', dirname(__DIR__));

// Check composer autoloader
$autoloadFile = BASE_PATH . '/vendor/autoload.php';
if (!file_exists($autoloadFile)) {
    die('Please run "composer install" in the project root directory');
}

require_once $autoloadFile;
require_once BASE_PATH . '/app/config/config.php';

try {
    $app = new App();
    $app->run(); // Thêm method run() để xử lý request
} catch (Exception $e) {
    echo json_encode([
        'status' => 'error',
        'message' => $e->getMessage()
    ]);
}