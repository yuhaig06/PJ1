<?php

namespace App\Middleware;

use App\Models\UserModel;
use Firebase\JWT\JWT;
use Firebase\JWT\Key;
use App\Config\Database;

class AuthMiddleware {
    private $db;
    private $userModel;

    public function __construct() {
        $this->db = Database::getInstance()->getConnection(); // Đảm bảo Database trả về kết nối PDO
        $this->userModel = new UserModel($this->db); // Sử dụng UserModel với kết nối DB
    }

    public function handle($request, $next) {
        $token = $request->header('Authorization');
        
        if (!$token) {
            return json_encode([
                'error' => 'Unauthorized',
                'message' => 'No token provided'
            ], JSON_PRETTY_PRINT);
        }
        
        try {
            $token = str_replace('Bearer ', '', $token);
            $decoded = JWT::decode($token, new Key($_ENV['JWT_SECRET'] ?? 'default_secret', 'HS256')); // Thêm giá trị mặc định nếu JWT_SECRET không tồn tại
            
            $user = $this->userModel->findById($decoded->sub); // Đảm bảo findById tồn tại trong UserModel
            if (!$user) {
                throw new \Exception('User not found');
            }
            
            $request->user = $user;
            return $next($request);
        } catch (\Exception $e) {
            return json_encode([
                'error' => 'Unauthorized',
                'message' => 'Invalid token'
            ], JSON_PRETTY_PRINT);
        }
    }
    
    public static function generateToken($user) {
        $payload = [
            'sub' => $user['id'], // Đảm bảo user là mảng và có key 'id'
            'name' => $user['username'],
            'email' => $user['email'],
            'iat' => time(),
            'exp' => time() + (60 * 60 * 24) // 24 hours
        ];
        
        return JWT::encode($payload, $_ENV['JWT_SECRET'] ?? 'default_secret', 'HS256'); // Thêm giá trị mặc định nếu JWT_SECRET không tồn tại
    }
    
    public static function validatePassword($password, $hash) {
        return password_verify($password, $hash);
    }
    
    public static function hashPassword($password) {
        return password_hash($password, PASSWORD_DEFAULT);
    }

    public function isAdmin() {
        $user = $this->userModel->findById($_SESSION['user_id']);
        return $user && $user['role'] === 'admin';
    }

    public function isModerator() {
        $user = $this->userModel->findById($_SESSION['user_id']);
        return $user && in_array($user['role'], ['admin', 'moderator']);
    }

    public function checkPermission($permission) {
        $user = $this->userModel->findById($_SESSION['user_id']);
        return $user && $this->userModel->hasPermission($user['id'], $permission);
    }

    public static function isGuest() {
        if (isset($_SESSION['user_id'])) {
            header('Location: /');
            exit;
        }
    }

    public static function hasRole($role) {
        if ($_SESSION['user_role'] !== $role) {
            header('Location: /');
            exit;
        }
    }

    public static function isUser() {
        self::hasRole('user');
    }
}