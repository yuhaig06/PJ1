<?php

namespace App\Middleware;

class CSRFMiddleware {
    public function __construct()
    {
        // CSRF middleware logic
    }

    public function handle() {
        // Chỉ kiểm tra CSRF cho các request POST, PUT, DELETE
        if ($_SERVER['REQUEST_METHOD'] === 'POST' || 
            $_SERVER['REQUEST_METHOD'] === 'PUT' || 
            $_SERVER['REQUEST_METHOD'] === 'DELETE') {
            
            // Kiểm tra CSRF token
            if (!isset($_POST['csrf_token']) || !isset($_SESSION['csrf_token'])) {
                $this->denyRequest('CSRF token không tồn tại');
            }

            if ($_POST['csrf_token'] !== $_SESSION['csrf_token']) {
                $this->denyRequest('CSRF token không hợp lệ');
            }
        }

        // Tạo CSRF token mới cho mỗi request
        $this->generateToken();
    }

    public function validate(): bool {
        if ($_SERVER['REQUEST_METHOD'] === 'GET') {
            return true;
        }
        
        $token = $_POST['_csrf'] ?? $_SERVER['HTTP_X_CSRF_TOKEN'] ?? null;
        $sessionToken = $_SESSION['csrf_token'] ?? null;
        
        return $token && $sessionToken && hash_equals($sessionToken, $token);
    }

    private function generateToken() {
        if (!isset($_SESSION['csrf_token'])) {
            $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
        }
    }

    private function denyRequest($message) {
        // Log attempt
        error_log("CSRF Attack Attempt: " . $message . " from IP: " . $_SERVER['REMOTE_ADDR']);
        
        // Trả về lỗi
        header('HTTP/1.1 403 Forbidden');
        die('Access Denied: ' . $message);
    }

    // Helper function để lấy CSRF token cho form
    public static function getToken() {
        if (!isset($_SESSION['csrf_token'])) {
            $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
        }
        return $_SESSION['csrf_token'];
    }

    // Helper function để tạo CSRF input field
    public static function getTokenField() {
        return '<input type="hidden" name="csrf_token" value="' . self::getToken() . '">';
    }
}