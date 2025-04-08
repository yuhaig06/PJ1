<?php

namespace App\Core;

use App\Middleware\CSRFMiddleware;
use App\Controllers\ErrorController;

class Router {
    private $routes = [];
    private $middlewares = [];
    private $currentGroup = '';
    private $currentMiddleware = [];
    private CSRFMiddleware $csrfMiddleware;

    public function __construct() {
        $this->csrfMiddleware = new CSRFMiddleware();
        $this->addDefaultRoutes();
    }

    // Thêm route GET
    public function get($path, $callback) {
        $this->addRoute('GET', $path, $callback);
        return $this;
    }

    // Thêm route POST
    public function post($path, $callback) {
        $this->addRoute('POST', $path, $callback);
        return $this;
    }

    // Thêm route PUT
    public function put($path, $callback) {
        $this->addRoute('PUT', $path, $callback);
        return $this;
    }

    // Thêm route DELETE
    public function delete($path, $callback) {
        $this->addRoute('DELETE', $path, $callback);
        return $this;
    }

    // Thêm route với nhiều HTTP methods
    public function match($methods, $path, $callback) {
        foreach($methods as $method) {
            $this->addRoute(strtoupper($method), $path, $callback);
        }
        return $this;
    }

    // Thêm route với tất cả HTTP methods
    public function any($path, $callback) {
        $this->match(['GET', 'POST', 'PUT', 'DELETE', 'PATCH'], $path, $callback);
        return $this;
    }

    // Tạo route group
    public function group($prefix, $callback) {
        $previousGroup = $this->currentGroup;
        $previousMiddleware = $this->currentMiddleware;
        
        $this->currentGroup .= '/' . trim($prefix, '/');
        $callback($this);
        
        $this->currentGroup = $previousGroup;
        $this->currentMiddleware = $previousMiddleware;
        
        return $this;
    }

    // Thêm middleware cho route
    public function middleware($middleware) {
        if(is_array($middleware)) {
            $this->currentMiddleware = array_merge($this->currentMiddleware, $middleware);
        } else {
            $this->currentMiddleware[] = $middleware;
        }
        return $this;
    }

    // Đăng ký middleware
    public function registerMiddleware($name, $callback) {
        $this->middlewares[$name] = $callback;
        return $this;
    }

    // Xử lý request
    public function dispatch() {
        $method = $_SERVER['REQUEST_METHOD'];
        $path = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
        $path = trim($path, '/');

        // Xử lý PUT và DELETE methods từ form
        if($method === 'POST' && isset($_POST['_method'])) {
            $method = strtoupper($_POST['_method']);
        }

        foreach($this->routes as $route) {
            if($route['method'] !== $method) continue;

            $pattern = $this->convertPathToRegex($route['path']);
            if(preg_match($pattern, $path, $matches)) {
                // Kiểm tra middleware
                if(!$this->runMiddleware($route['middleware'])) {
                    return;
                }

                // Lấy parameters từ URL
                $params = array_slice($matches, 1);

                // Xử lý callback
                if(is_callable($route['callback'])) {
                    call_user_func_array($route['callback'], $params);
                    return;
                }

                // Xử lý controller@method
                if(is_string($route['callback'])) {
                    list($controller, $method) = explode('@', $route['callback']);
                    $controller = 'App\\Controllers\\' . $controller;
                    
                    if(class_exists($controller)) {
                        $controllerInstance = new $controller();
                        if(method_exists($controllerInstance, $method)) {
                            call_user_func_array([$controllerInstance, $method], $params);
                            return;
                        }
                    }
                }
            }
        }

        // Không tìm thấy route
        $this->handleNotFound();
    }

    // Chuyển đổi path thành regex pattern
    private function convertPathToRegex($path) {
        $path = preg_replace('/\{([a-zA-Z]+)\}/', '(?P<$1>[^/]+)', $path);
        return '#^' . $path . '$#';
    }

    // Chạy middleware
    private function runMiddleware($middleware) {
        foreach($middleware as $name) {
            if(isset($this->middlewares[$name])) {
                if(!call_user_func($this->middlewares[$name])) {
                    return false;
                }
            }
        }
        return true;
    }

    // Thêm route vào danh sách
    private function addRoute($method, $path, $callback) {
        $path = $this->currentGroup . '/' . trim($path, '/');
        $path = trim($path, '/');

        $this->routes[] = [
            'method' => $method,
            'path' => $path,
            'callback' => $callback,
            'middleware' => $this->currentMiddleware
        ];
    }

    // Thêm các route mặc định
    private function addDefaultRoutes() {
        // Route cho trang chủ
        $this->get('', 'HomeController@index');
        
        // Route cho trang 404
        $this->any('{path}', function() {
            $this->handleNotFound();
        })->where('path', '.*');
    }

    // Xử lý 404 Not Found
    private function handleNotFound() {
        header("HTTP/1.0 404 Not Found");
        $errorController = new ErrorController();
        $errorController->notFound();
    }

    // Tạo URL từ route name
    public function url($name, $params = []) {
        if(isset($this->routes[$name])) {
            $path = $this->routes[$name]['path'];
            foreach($params as $key => $value) {
                $path = str_replace('{' . $key . '}', $value, $path);
            }
            return '/' . $path;
        }
        return '/';
    }

    public function validateRequest(): bool {
        return $this->csrfMiddleware->validate();
    }

    private function handleErrors() {
        set_error_handler(function($errno, $errstr, $errfile, $errline) {
            throw new \ErrorException($errstr, 0, $errno, $errfile, $errline);
        });
    }

    private function validateRoute($path, $callback) {
        if(empty($path)) {
            throw new \InvalidArgumentException('Route path cannot be empty');
        }
        
        if(!is_callable($callback) && !is_string($callback)) {
            throw new \InvalidArgumentException('Route callback must be callable or string');
        }
    }

    protected function sanitizePath($path) {
        return htmlspecialchars(strip_tags($path));
    }

    // Thêm điều kiện ràng buộc cho route parameters
    public function where($param, $pattern) {
        $lastRoute = end($this->routes);
        if ($lastRoute) {
            $this->routes[key($this->routes)]['constraints'][$param] = $pattern;
        }
        return $this;
    }
}