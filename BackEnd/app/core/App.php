<?php
namespace App\Core;

use App\Routes\Router;

class App {
    protected $controller = 'HomeController';
    protected $method = 'index';
    protected $params = [];
    protected $currentController = null;
    protected $routes = [
        'public' => ['home', 'login', 'register'],
        'protected' => ['profile', 'store', 'cart']
    ];

    public function __construct() {
        try {
            $url = $this->parseUrl();
            
            // Kiểm tra xem URL có tồn tại không
            if ($url !== false && is_array($url)) {
                // Handle API routes
                if ($url[0] === 'api') {
                    $this->handleApiRequest($url);
                    return;
                }

                // Get controller name from URL
                $controllerName = isset($url[0]) ? ucfirst($url[0]) . 'Controller' : $this->controller;
                $controllerClass = "App\\Controllers\\" . $controllerName;
                
                // Check access before initializing controller
                if (!$this->checkAccess($controllerName)) {
                    header('Location: /login');
                    exit;
                }

                // Initialize controller
                if (class_exists($controllerClass)) {
                    $this->currentController = new $controllerClass();
                    $this->controller = $controllerName;
                    unset($url[0]);
                } else {
                    throw new \Exception("Controller not found: $controllerClass");
                }

                // Set method
                if (isset($url[1])) {
                    if (method_exists($this->currentController, $url[1])) {
                        $this->method = $url[1];
                        unset($url[1]);
                    }
                }

                // Set params
                $this->params = $url ? array_values($url) : [];

                // Call controller method with parameters
                call_user_func_array([$this->currentController, $this->method], $this->params);
            }

        } catch (\Exception $e) {
            $this->sendJsonResponse([
                'status' => 'error',
                'message' => $e->getMessage()
            ], 500);
        }
    }

    protected function handleApiRequest($url) {
        array_shift($url); // Remove 'api' from url
        $module = $url[0] ?? null;
        $action = $url[1] ?? 'list';
        
        $routes = Router::getRoutes();
        
        if (!isset($routes[$module])) {
            $this->sendJsonResponse([
                'status' => 'error',
                'message' => 'Module not found'
            ], 404);
            return;
        }

        $route = $routes[$module][$action] ?? null;
        if (!$route) {
            $this->sendJsonResponse([
                'status' => 'error',
                'message' => 'Route not found'
            ], 404);
            return;
        }

        [$controller, $method, $httpMethod] = $route;
        
        if ($_SERVER['REQUEST_METHOD'] !== $httpMethod) {
            $this->sendJsonResponse([
                'status' => 'error',
                'message' => 'Method not allowed'
            ], 405);
            return;
        }

        $controllerClass = "App\\Controllers\\{$controller}";
        $controller = new $controllerClass();
        $response = $controller->$method($url);
        
        $this->sendJsonResponse($response);
    }

    protected function sendJsonResponse($data, $statusCode = 200) {
        http_response_code($statusCode);
        header('Content-Type: application/json');
        echo json_encode($data, JSON_PRETTY_PRINT);
        exit;
    }

    protected function parseUrl() {
        if (isset($_GET['url'])) {
            $url = rtrim($_GET['url'], '/');
            $url = filter_var($url, FILTER_SANITIZE_URL);
            return explode('/', $url);
        }
        return false;
    }

    protected function checkAccess($controllerName) {
        // Extract base name without 'Controller' suffix
        $routeName = strtolower(str_replace('Controller', '', $controllerName));

        // Public routes are always accessible
        if (in_array($routeName, $this->routes['public'])) {
            return true;
        }

        // Protected routes require authentication
        if (in_array($routeName, $this->routes['protected'])) {
            return isset($_SESSION['user_id']);
        }

        // Default to true for unspecified routes
        return true;
    }

    protected function handleError(\Exception $e) {
        // Log error
        error_log($e->getMessage());

        // Send appropriate response
        header('Content-Type: application/json');
        http_response_code(500);
        echo json_encode([
            'error' => true,
            'message' => 'Internal Server Error',
            'details' => $e->getMessage()
        ]);
        exit;
    }

    public function run() {
        try {
            // Thêm headers cho API
            header('Content-Type: application/json');
            header('Access-Control-Allow-Origin: *');
            header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
            
            // Xử lý request và trả về response
            $response = [
                'status' => 'success',
                'message' => 'Welcome to WarStorm API',
                'timestamp' => date('Y-m-d H:i:s'),
                'version' => '1.0.0'
            ];

            echo json_encode($response, JSON_PRETTY_PRINT);
        } catch (\Exception $e) {
            http_response_code(500);
            echo json_encode([
                'status' => 'error',
                'message' => $e->getMessage()
            ]);
        }
    }
}