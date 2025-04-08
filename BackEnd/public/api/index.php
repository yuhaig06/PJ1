<?php

// Set headers for API
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With');

// Handle preflight requests
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

// Load environment variables
require_once __DIR__ . '/../../vendor/autoload.php';
$dotenv = Dotenv\Dotenv::createImmutable(__DIR__ . '/../../');
$dotenv->load();

// Initialize application
require_once __DIR__ . '/../../app/init.php';

// Get the request method and URI
$method = $_SERVER['REQUEST_METHOD'];
$uri = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
$uri = explode('/', trim($uri, '/'));

// Remove 'api' from the URI if present
if ($uri[0] === 'api') {
    array_shift($uri);
}

// Get the controller and action
$controller = isset($uri[0]) ? ucfirst($uri[0]) . 'Controller' : 'HomeController';
$action = isset($uri[1]) ? $uri[1] : 'index';

// Get query parameters
$params = [];
if (isset($uri[2])) {
    $params['id'] = $uri[2];
}

// Get request body for POST, PUT requests
$data = [];
if ($method === 'POST' || $method === 'PUT') {
    $data = json_decode(file_get_contents('php://input'), true);
}

// Include the controller
$controllerFile = __DIR__ . '/../../app/controllers/' . $controller . '.php';
if (file_exists($controllerFile)) {
    require_once $controllerFile;
    $controllerClass = 'App\\Controllers\\' . $controller;
    $controllerInstance = new $controllerClass();
    
    // Check if the action exists
    if (method_exists($controllerInstance, $action)) {
        // Call the action with parameters
        $response = $controllerInstance->$action($params, $data);
        echo json_encode($response);
    } else {
        http_response_code(404);
        echo json_encode(['error' => 'Action not found']);
    }
} else {
    http_response_code(404);
    echo json_encode(['error' => 'Controller not found']);
} 