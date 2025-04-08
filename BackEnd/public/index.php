<?php
use App\Core\App;
use App\Core\Database;

// Check composer autoloader
$autoloadFile = dirname(__DIR__) . '/vendor/autoload.php';
if (!file_exists($autoloadFile)) {
    die('Please run "composer install" in the project root directory');
}
require $autoloadFile;

// Error reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Session settings must be set before session starts
ini_set('session.cookie_httponly', 1);
ini_set('session.use_only_cookies', 1);
ini_set('session.cookie_secure', 0);
ini_set('session.cookie_samesite', 'Strict');
ini_set('session.gc_maxlifetime', 3600);
ini_set('session.cookie_lifetime', 0);
ini_set('session.use_strict_mode', 1);

// Start session
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Define base path
define('BASE_PATH', dirname(__DIR__));

// Load configuration
require_once BASE_PATH . '/app/config/config.php';

// Load core classes
require_once BASE_PATH . '/app/core/Model.php';
require_once BASE_PATH . '/app/core/App.php';
require_once BASE_PATH . '/app/core/Controller.php';
require_once BASE_PATH . '/app/core/Database.php';

// Load helpers
require_once BASE_PATH . '/app/helpers/url_helper.php';
require_once BASE_PATH . '/app/helpers/session_helper.php';

// Load middleware
require_once BASE_PATH . '/app/middleware/AuthMiddleware.php';

try {
    // Initialize database connection
    $db = new Database();
    
    // Initialize application
    $app = new App();
} catch (PDOException $e) {
    die("Lỗi kết nối database: " . $e->getMessage());
}