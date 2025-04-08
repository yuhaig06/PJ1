<?php

namespace App\Services;

use App\Models\UserModel;
use App\Helpers\AuditLogger;
use App\Helpers\CacheManager;

class UserService {
    private static $instance = null;
    private $userModel;
    private $auditLogger;
    private $cache;

    private function __construct() {
        $this->userModel = new UserModel();
        $this->auditLogger = AuditLogger::getInstance();
        $this->cache = CacheManager::getInstance();
    }

    public static function getInstance() {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    public function createUser($data) {
        // Validate email uniqueness
        if ($this->userModel->findByEmail($data['email'])) {
            throw new \Exception('Email already exists');
        }

        // Hash password
        $data['password'] = password_hash($data['password'], PASSWORD_DEFAULT);

        // Create user
        $userId = $this->userModel->create($data);

        // Log user creation
        $this->auditLogger->log('user', 'user_created', [
            'user_id' => $userId,
            'created_by' => $data['created_by'] ?? null
        ]);

        return $userId;
    }

    public function updateUser($userId, $data) {
        $user = $this->userModel->findById($userId);
        if (!$user) {
            throw new \Exception('User not found');
        }

        // Check email uniqueness if email is being updated
        if (isset($data['email']) && $data['email'] !== $user['email']) {
            if ($this->userModel->findByEmail($data['email'])) {
                throw new \Exception('Email already exists');
            }
        }

        // Hash password if being updated
        if (isset($data['password'])) {
            $data['password'] = password_hash($data['password'], PASSWORD_DEFAULT);
        }

        // Update user
        $this->userModel->update($userId, $data);

        // Clear cache
        $this->cache->delete("user:{$userId}");

        // Log user update
        $this->auditLogger->log('user', 'user_updated', [
            'user_id' => $userId,
            'updated_by' => $data['updated_by'] ?? null
        ]);
    }

    public function deleteUser($userId) {
        $user = $this->userModel->findById($userId);
        if (!$user) {
            throw new \Exception('User not found');
        }

        // Delete user
        $this->userModel->delete($userId);

        // Clear cache
        $this->cache->delete("user:{$userId}");

        // Log user deletion
        $this->auditLogger->log('user', 'user_deleted', [
            'user_id' => $userId
        ]);
    }

    public function getUser($userId) {
        // Try cache first
        $user = $this->cache->get("user:{$userId}");
        if (!$user) {
            $user = $this->userModel->findById($userId);
            if ($user) {
                $this->cache->set("user:{$userId}", $user, 3600);
            }
        }

        if (!$user) {
            throw new \Exception('User not found');
        }

        return $this->sanitizeUser($user);
    }

    public function getUsers($filters = [], $page = 1, $limit = 10) {
        $cacheKey = 'users:list:' . md5(json_encode($filters) . $page . $limit);
        
        // Try cache first
        $result = $this->cache->get($cacheKey);
        if (!$result) {
            $result = $this->userModel->findAll($filters, $page, $limit);
            if ($result) {
                $this->cache->set($cacheKey, $result, 3600);
            }
        }

        return array_map([$this, 'sanitizeUser'], $result);
    }

    public function updateUserRole($userId, $role) {
        $user = $this->userModel->findById($userId);
        if (!$user) {
            throw new \Exception('User not found');
        }

        $this->userModel->updateRole($userId, $role);

        // Clear cache
        $this->cache->delete("user:{$userId}");

        // Log role update
        $this->auditLogger->log('user', 'user_role_updated', [
            'user_id' => $userId,
            'role' => $role
        ]);
    }

    public function updateUserStatus($userId, $status) {
        $user = $this->userModel->findById($userId);
        if (!$user) {
            throw new \Exception('User not found');
        }

        $this->userModel->updateStatus($userId, $status);

        // Clear cache
        $this->cache->delete("user:{$userId}");

        // Log status update
        $this->auditLogger->log('user', 'user_status_updated', [
            'user_id' => $userId,
            'status' => $status
        ]);
    }

    public function searchUsers($query, $page = 1, $limit = 10) {
        $cacheKey = 'users:search:' . md5($query . $page . $limit);
        
        // Try cache first
        $result = $this->cache->get($cacheKey);
        if (!$result) {
            $result = $this->userModel->search($query, $page, $limit);
            if ($result) {
                $this->cache->set($cacheKey, $result, 3600);
            }
        }

        return array_map([$this, 'sanitizeUser'], $result);
    }

    private function sanitizeUser($user) {
        unset($user['password']);
        unset($user['reset_token']);
        unset($user['reset_token_expiry']);
        return $user;
    }
} 