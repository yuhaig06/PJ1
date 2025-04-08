<?php
// Error reporting - chỉ bật trong môi trường development
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Start session
session_start();

// Define base path
define('BASE_PATH', dirname(__DIR__));

// Load configuration
require_once BASE_PATH . '/app/config/config.php';

// Load core classes
require_once BASE_PATH . '/app/core/App.php';
require_once BASE_PATH . '/app/core/Controller.php';
require_once BASE_PATH . '/app/core/Database.php';

// Load helpers
require_once BASE_PATH . '/app/helpers/url_helper.php';
require_once BASE_PATH . '/app/helpers/session_helper.php';

// Load middleware
require_once BASE_PATH . '/app/middleware/AuthMiddleware.php';

// Initialize database connection
$db = new Database();

// Initialize application
$app = new App();