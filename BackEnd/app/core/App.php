<?php

class App {
    protected $controller = 'HomeController';
    protected $method = 'index';
    protected $params = [];
    protected $accessControl = [
        'UserController' => [
            'login' => ['public'],
            'register' => ['public'],
            'logout' => ['user', 'admin']
        ],
        'AdminController' => [
            'index' => ['admin'],
            'users' => ['admin'],
            'settings' => ['admin']
        ]
    ];

    public function __construct() {
        $url = $this->parseUrl();
        
        // Kiểm tra và load controller
        if (isset($url[0])) {
            $controllerName = ucfirst(strtolower($url[0])) . 'Controller';
            $controllerPath = dirname(__DIR__) . '/controllers/' . $controllerName . '.php';

            if (file_exists($controllerPath)) {
                $this->controller = $controllerName;
                unset($url[0]);
            } else {
                $this->handleError('Controller không tồn tại');
            }
        }

        // Load controller
        require_once dirname(__DIR__) . '/controllers/' . $this->controller . '.php';
        $this->controller = new $this->controller();

        // Kiểm tra và load method
        if (isset($url[1])) {
            if (method_exists($this->controller, $url[1])) {
                $this->method = $url[1];
                unset($url[1]);
            } else {
                $this->handleError('Method không tồn tại');
            }
        }

        // Kiểm tra quyền truy cập
        if (!$this->checkAccess()) {
            $this->handleError('Bạn không có quyền truy cập trang này');
        }

        // Set params
        $this->params = $url ? array_values($url) : [];

        // Gọi method với params
        try {
            call_user_func_array([$this->controller, $this->method], $this->params);
        } catch (Exception $e) {
            $this->handleError($e->getMessage());
        }
    }

    private function parseUrl() {
        if (isset($_GET['url'])) {
            // Sanitize URL
            $url = filter_var(rtrim($_GET['url'], '/'), FILTER_SANITIZE_URL);
            // Chỉ cho phép chữ cái, số và dấu gạch ngang
            $url = preg_replace('/[^a-zA-Z0-9-]/', '', $url);
            return explode('/', $url);
        }
        return [];
    }

    private function checkAccess() {
        // Kiểm tra nếu controller và method có trong accessControl
        if (isset($this->accessControl[$this->controller][$this->method])) {
            $allowedRoles = $this->accessControl[$this->controller][$this->method];
            
            // Nếu là public thì cho phép truy cập
            if (in_array('public', $allowedRoles)) {
                return true;
            }
            
            // Kiểm tra user đã đăng nhập chưa
            if (!isset($_SESSION['user_id'])) {
                return false;
            }
            
            // Kiểm tra role của user
            $userRole = $_SESSION['user_role'] ?? 'user';
            return in_array($userRole, $allowedRoles);
        }
        
        // Nếu không có quy định thì mặc định cho phép truy cập
        return true;
    }

    private function handleError($message) {
        // Log lỗi
        error_log($message);
        
        // Chuyển hướng đến trang lỗi
        header('Location: ' . URLROOT . '/error/index');
        exit;
    }
}