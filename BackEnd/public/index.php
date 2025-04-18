<?php
session_start();

// Enable CORS
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');
header('Content-Type: application/json');

// Handle preflight requests
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

require_once '../vendor/autoload.php';

// Get request path
$uri = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
$uri = str_replace('/PJ1/BackEnd/public/', '', $uri);
$uri = explode('/', $uri);

try {
    if ($uri[0] === 'auth') {
        $controller = new \App\Controllers\AuthController();
        
        switch ($uri[1]) {
            case 'register':
                echo $controller->register();
                break;
            case 'login':
                echo $controller->login();
                break;
            default:
                throw new Exception('Route not found');
        }
    } else {
        throw new Exception('Invalid endpoint');
    }
} catch (Exception $e) {
    http_response_code(404);
    echo json_encode([
        'status' => 'error',
        'message' => $e->getMessage()
    ]);
}