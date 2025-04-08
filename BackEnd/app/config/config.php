<?php

// Database Config
define('DB_HOST', 'localhost');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_NAME', 'warstorm_db');

// URL Config
define('URLROOT', 'http://localhost/PJ1');
define('APPROOT', dirname(dirname(__FILE__)));
define('SITENAME', 'WarStorm');

// App Configuration
define('APPVERSION', '1.0.0');

// Error Reporting
define('DISPLAY_ERRORS', true);
define('LOG_ERRORS', true);
define('ERROR_LOG_PATH', APPROOT . '/logs/error.log');

// File Upload Configuration
define('MAX_FILE_SIZE', 5242880); // 5MB
define('ALLOWED_FILE_TYPES', ['jpg', 'jpeg', 'png', 'gif']);
define('UPLOAD_PATH', APPROOT . '/public/uploads/');

// Security Configuration
define('CSRF_TOKEN_NAME', 'csrf_token');
define('PASSWORD_HASH_ALGO', PASSWORD_BCRYPT);
define('PASSWORD_HASH_COST', 12);

// Time Zone
date_default_timezone_set('Asia/Ho_Chi_Minh');

// Character Set
ini_set('default_charset', 'UTF-8');

// Error Reporting Configuration
if (DISPLAY_ERRORS) {
    error_reporting(E_ALL);
    ini_set('display_errors', 1);
} else {
    error_reporting(0);
    ini_set('display_errors', 0);
}

if (LOG_ERRORS) {
    ini_set('log_errors', 1);
    ini_set('error_log', ERROR_LOG_PATH);
}

try {
    $db = new PDO(
        "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8",
        DB_USER,
        DB_PASS
    );
    $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    die("Lỗi kết nối DB: " . $e->getMessage());
}