<?php

namespace App\Services;

use App\Models\UserModel;
use App\Helpers\AuditLogger;
use App\Helpers\IPBlocker;
use App\Helpers\CacheManager;

class AuthService {
    private static $instance = null;
    private $userModel;
    private $auditLogger;
    private $ipBlocker;
    private $cache;

    private function __construct() {
        $this->userModel = new UserModel();
        $this->auditLogger = AuditLogger::getInstance();
        $this->ipBlocker = IPBlocker::getInstance();
        $this->cache = CacheManager::getInstance();
    }

    public static function getInstance() {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    public function login($email, $password) {
        // Check if IP is blocked
        if ($this->ipBlocker->isBlocked()) {
            throw new \Exception('Too many login attempts. Please try again later.');
        }

        // Get user from cache or database
        $user = $this->cache->get("user:{$email}");
        if (!$user) {
            $user = $this->userModel->findByEmail($email);
            if ($user) {
                $this->cache->set("user:{$email}", $user, 3600);
            }
        }

        if (!$user || !password_verify($password, $user['password'])) {
            $this->ipBlocker->recordFailedAttempt();
            $this->auditLogger->log('auth', 'login_failed', [
                'email' => $email,
                'ip' => $_SERVER['REMOTE_ADDR']
            ]);
            throw new \Exception('Invalid credentials');
        }

        // Generate JWT token
        $token = $this->generateJWT($user);

        // Log successful login
        $this->auditLogger->log('auth', 'login_success', [
            'user_id' => $user['id'],
            'ip' => $_SERVER['REMOTE_ADDR']
        ]);

        return [
            'token' => $token,
            'user' => $this->sanitizeUser($user)
        ];
    }

    public function register($data) {
        // Validate email uniqueness
        if ($this->userModel->findByEmail($data['email'])) {
            throw new \Exception('Email already exists');
        }

        // Hash password
        $data['password'] = password_hash($data['password'], PASSWORD_DEFAULT);

        // Create user
        $userId = $this->userModel->create($data);

        // Log registration
        $this->auditLogger->log('auth', 'user_registered', [
            'user_id' => $userId,
            'ip' => $_SERVER['REMOTE_ADDR']
        ]);

        return $userId;
    }

    public function logout($userId) {
        // Invalidate token in cache
        $this->cache->delete("token:{$userId}");

        // Log logout
        $this->auditLogger->log('auth', 'user_logout', [
            'user_id' => $userId,
            'ip' => $_SERVER['REMOTE_ADDR']
        ]);
    }

    public function verifyToken($token) {
        try {
            $decoded = $this->decodeJWT($token);
            $userId = $decoded['user_id'];

            // Check token in cache
            $cachedToken = $this->cache->get("token:{$userId}");
            if (!$cachedToken || $cachedToken !== $token) {
                throw new \Exception('Invalid token');
            }

            return $decoded;
        } catch (\Exception $e) {
            throw new \Exception('Invalid token');
        }
    }

    public function resetPassword($email) {
        $user = $this->userModel->findByEmail($email);
        if (!$user) {
            throw new \Exception('User not found');
        }

        // Generate reset token
        $resetToken = bin2hex(random_bytes(32));
        $expiry = date('Y-m-d H:i:s', strtotime('+1 hour'));

        // Save reset token
        $this->userModel->update($user['id'], [
            'reset_token' => $resetToken,
            'reset_token_expiry' => $expiry
        ]);

        // Log password reset request
        $this->auditLogger->log('auth', 'password_reset_requested', [
            'user_id' => $user['id'],
            'ip' => $_SERVER['REMOTE_ADDR']
        ]);

        return $resetToken;
    }

    public function changePassword($userId, $oldPassword, $newPassword) {
        $user = $this->userModel->findById($userId);
        if (!$user || !password_verify($oldPassword, $user['password'])) {
            throw new \Exception('Invalid current password');
        }

        // Update password
        $this->userModel->update($userId, [
            'password' => password_hash($newPassword, PASSWORD_DEFAULT)
        ]);

        // Log password change
        $this->auditLogger->log('auth', 'password_changed', [
            'user_id' => $userId,
            'ip' => $_SERVER['REMOTE_ADDR']
        ]);
    }

    private function generateJWT($user) {
        $header = json_encode(['typ' => 'JWT', 'alg' => 'HS256']);
        $payload = json_encode([
            'user_id' => $user['id'],
            'email' => $user['email'],
            'role' => $user['role'],
            'exp' => time() + (60 * 60 * 24) // 24 hours
        ]);

        $base64UrlHeader = str_replace(['+', '/', '='], ['-', '_', ''], base64_encode($header));
        $base64UrlPayload = str_replace(['+', '/', '='], ['-', '_', ''], base64_encode($payload));

        $signature = hash_hmac('sha256', 
            $base64UrlHeader . "." . $base64UrlPayload, 
            $_ENV['JWT_SECRET'], 
            true
        );
        $base64UrlSignature = str_replace(['+', '/', '='], ['-', '_', ''], base64_encode($signature));

        $token = $base64UrlHeader . "." . $base64UrlPayload . "." . $base64UrlSignature;

        // Cache token
        $this->cache->set("token:{$user['id']}", $token, 86400);

        return $token;
    }

    private function decodeJWT($token) {
        $parts = explode('.', $token);
        if (count($parts) != 3) {
            throw new \Exception('Invalid token format');
        }

        $payload = json_decode(base64_decode(str_replace(['-', '_'], ['+', '/'], $parts[1])), true);
        if (!$payload || !isset($payload['exp']) || $payload['exp'] < time()) {
            throw new \Exception('Token expired');
        }

        return $payload;
    }

    private function sanitizeUser($user) {
        unset($user['password']);
        unset($user['reset_token']);
        unset($user['reset_token_expiry']);
        return $user;
    }
} 