<?php

namespace App\Middleware;

use App\Models\UserModel;
use Firebase\JWT\JWT;
use Firebase\JWT\Key;
use App\Config\Database;
use App\Core\Request;

class AuthMiddleware {
    private \PDO $db;
    private UserModel $userModel;
    private string $secret;

    public function __construct() {
        $this->db = Database::getInstance()->getConnection();
        $this->userModel = new UserModel($this->db);
        $this->secret = $_ENV['JWT_SECRET'] ?? 'default_secret';
    }

    /**
     * @param Request $request
     * @param callable $next
     * @return mixed
     * @throws \Exception
     */
    public function handle(Request $request, callable $next) {
        $token = $request->header('Authorization');
        
        if (!$token) {
            return json_encode(['error' => 'Không có token']);
        }
        
        try {
            $token = str_replace('Bearer ', '', $token);
            $decoded = json_decode(base64_decode($token));
            
            if (!isset($decoded->sub) || !is_numeric($decoded->sub)) {
                return json_encode(['error' => 'ID không hợp lệ']);
            }
            
            $user = $this->userModel->findById((int)$decoded->sub);
            if (!$user) {
                return json_encode(['error' => 'User không tồn tại']);
            }
            
            $request->setUser($user);
            return $next($request);
        } catch (\Exception $e) {
            return json_encode(['error' => 'Token không hợp lệ']);
        }
    }

    private function errorResponse(string $message): string {
        return json_encode([
            'error' => 'Unauthorized',
            'message' => $message
        ]);
    }
    
    /**
     * @param array<string, mixed> $user
     * @return string
     */
    public static function generateToken(array $user): string {
        $payload = [
            'sub' => $user['id'],
            'role' => $user['role'],
            'exp' => time() + (60 * 60 * 24)
        ];
        
        return base64_encode(json_encode($payload));
    }
}