<?php

namespace App\Controllers;

use App\Models\UserModel;
use App\Models\WalletModel;
use App\Models\NotificationModel;
use App\Config\Database;

class UserController {
    private $db;
    private $userModel;
    private $walletModel;
    private $notificationModel;

    public function __construct() {
        $this->db = Database::getInstance()->getConnection();
        $this->userModel = new UserModel($this->db);
        $this->walletModel = new WalletModel($this->db);
        $this->notificationModel = new NotificationModel($this->db);
    }

    public function getProfile() {
        try {
            $userId = $_SESSION['user_id'] ?? null;
            
            if (!$userId) {
                return $this->jsonResponse(['success' => false, 'message' => 'Unauthorized'], 401);
            }
            
            $user = $this->userModel->getById($userId);
            
            if (!$user) {
                return $this->jsonResponse(['success' => false, 'message' => 'User not found'], 404);
            }
            
            unset($user['password']);
            
            return $this->jsonResponse([
                'success' => true,
                'data' => $user
            ]);
        } catch (\Exception $e) {
            return $this->jsonResponse(['success' => false, 'message' => 'Failed to fetch profile'], 500);
        }
    }

    public function updateProfile() {
        try {
            $data = json_decode(file_get_contents('php://input'), true);
            $userId = $_SESSION['user_id'] ?? null;
            
            if (!$userId) {
                return $this->jsonResponse(['success' => false, 'message' => 'Unauthorized'], 401);
            }
            
            $user = $this->userModel->getById($userId);
            
            if (!$user) {
                return $this->jsonResponse(['success' => false, 'message' => 'User not found'], 404);
            }
            
            $updateData = array_intersect_key($data, array_flip([
                'username',
                'full_name',
                'avatar',
                'phone',
                'address'
            ]));
            
            if (empty($updateData)) {
                return $this->jsonResponse(['success' => false, 'message' => 'No valid fields to update'], 400);
            }
            
            $this->userModel->update($userId, $updateData);
            
            $updatedUser = $this->userModel->getById($userId);
            unset($updatedUser['password']);
            
            return $this->jsonResponse([
                'success' => true,
                'message' => 'Profile updated successfully',
                'data' => $updatedUser
            ]);
        } catch (\Exception $e) {
            return $this->jsonResponse(['success' => false, 'message' => 'Failed to update profile'], 500);
        }
    }

    public function getWallet() {
        try {
            $userId = $_SESSION['user_id'] ?? null;
            
            if (!$userId) {
                return $this->jsonResponse(['success' => false, 'message' => 'Unauthorized'], 401);
            }
            
            $wallet = $this->walletModel->getByUserId($userId);
            
            if (!$wallet) {
                return $this->jsonResponse(['success' => false, 'message' => 'Wallet not found'], 404);
            }
            
            return $this->jsonResponse([
                'success' => true,
                'data' => $wallet
            ]);
        } catch (\Exception $e) {
            return $this->jsonResponse(['success' => false, 'message' => 'Failed to fetch wallet'], 500);
        }
    }

    public function addFunds() {
        try {
            $data = json_decode(file_get_contents('php://input'), true);
            $userId = $_SESSION['user_id'] ?? null;
            
            if (!$userId) {
                return $this->jsonResponse(['success' => false, 'message' => 'Unauthorized'], 401);
            }
            
            if (!isset($data['amount']) || $data['amount'] <= 0) {
                return $this->jsonResponse(['success' => false, 'message' => 'Invalid amount'], 400);
            }
            
            $wallet = $this->walletModel->getByUserId($userId);
            
            if (!$wallet) {
                return $this->jsonResponse(['success' => false, 'message' => 'Wallet not found'], 404);
            }
            
            // Process payment here
            // For now, just add the amount
            $this->walletModel->addFunds($userId, $data['amount']);
            
            // Create notification
            $this->notificationModel->create([
                'user_id' => $userId,
                'type' => 'wallet',
                'message' => "Added {$data['amount']} to your wallet",
                'data' => json_encode(['amount' => $data['amount']])
            ]);
            
            $updatedWallet = $this->walletModel->getByUserId($userId);
            
            return $this->jsonResponse([
                'success' => true,
                'message' => 'Funds added successfully',
                'data' => $updatedWallet
            ]);
        } catch (\Exception $e) {
            return $this->jsonResponse(['success' => false, 'message' => 'Failed to add funds'], 500);
        }
    }

    public function getNotifications() {
        try {
            $userId = $_SESSION['user_id'] ?? null;
            
            if (!$userId) {
                return $this->jsonResponse(['success' => false, 'message' => 'Unauthorized'], 401);
            }
            
            $notifications = $this->notificationModel->getByUserId($userId);
            
            return $this->jsonResponse([
                'success' => true,
                'data' => $notifications
            ]);
        } catch (\Exception $e) {
            return $this->jsonResponse(['success' => false, 'message' => 'Failed to fetch notifications'], 500);
        }
    }

    public function markNotificationAsRead($notificationId) {
        try {
            $userId = $_SESSION['user_id'] ?? null;
            
            if (!$userId) {
                return $this->jsonResponse(['success' => false, 'message' => 'Unauthorized'], 401);
            }
            
            $notification = $this->notificationModel->getById($notificationId);
            
            if (!$notification) {
                return $this->jsonResponse(['success' => false, 'message' => 'Notification not found'], 404);
            }
            
            if ($notification['user_id'] !== $userId) {
                return $this->jsonResponse(['success' => false, 'message' => 'Unauthorized'], 403);
            }
            
            $this->notificationModel->markAsRead($notificationId);
            
            return $this->jsonResponse([
                'success' => true,
                'message' => 'Notification marked as read'
            ]);
        } catch (\Exception $e) {
            return $this->jsonResponse(['success' => false, 'message' => 'Failed to mark notification as read'], 500);
        }
    }

    private function jsonResponse($data, $statusCode = 200) {
        http_response_code($statusCode);
        header('Content-Type: application/json');
        echo json_encode($data);
        exit;
    }
}