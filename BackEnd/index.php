<?php
session_start();
error_reporting(E_ALL);
ini_set('display_errors', 0);

// Set one single CORS header
header('Access-Control-Allow-Origin: http://127.0.0.1:5501');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Accept, Authorization');
header('Access-Control-Allow-Credentials: true');
header('Content-Type: application/json; charset=utf-8');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

require_once __DIR__ . '/vendor/autoload.php';

try {
    // Get request path
    $uri = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
    $uri = str_replace('/PJ1/BackEnd/', '', $uri);
    $segments = explode('/', trim($uri, '/'));

    // Debug logging
    error_log('Request URI: ' . $uri);
    error_log('Request Method: ' . $_SERVER['REQUEST_METHOD']);
    error_log('Request Body: ' . file_get_contents('php://input'));

    if ($segments[0] === 'auth') {
        $controller = new \App\Controllers\AuthController();
        
        switch ($segments[1]) {
            case 'login':
                $data = json_decode(file_get_contents('php://input'), true);
                $result = $controller->login($data);
                echo $result;
                break;
            case 'register':
                $result = $controller->register();
                echo $result;
                break;
            default:
                throw new Exception('Endpoint không hợp lệ');
        }
    } else {
        throw new Exception('Request không hợp lệ');
    }
} catch (Exception $e) {
    error_log('Error: ' . $e->getMessage());
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ], JSON_UNESCAPED_UNICODE);
}
