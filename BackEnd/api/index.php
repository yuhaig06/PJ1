<?php
session_start();

// Cấu hình CORS
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');
header('Content-Type: application/json');

// Xử lý OPTIONS request
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

require_once '../vendor/autoload.php';

// Xử lý routing
$uri = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
$uri = str_replace('/PJ1/BackEnd/api/', '', $uri);
$uri = explode('/', $uri);

try {
    if ($uri[0] === 'auth') {
        $controller = new \App\Controllers\AuthController();
        
        switch ($uri[1]) {
            case 'register':
                echo $controller->register();
                break;
            default:
                throw new Exception('Endpoint không hợp lệ');
        }
    } else {
        throw new Exception('Request không hợp lệ');
    }
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'status' => 'error',
        'message' => $e->getMessage()
    ]);
}
