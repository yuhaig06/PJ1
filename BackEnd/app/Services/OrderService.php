<?php

namespace App\Services;

use App\Models\OrderModel;
use App\Models\GameModel;
use App\Models\UserModel;
use App\Helpers\AuditLogger;
use App\Helpers\CacheManager;

class OrderService {
    private static $instance = null;
    private $orderModel;
    private $gameModel;
    private $userModel;
    private $auditLogger;
    private $cache;

    private function __construct() {
        $this->orderModel = new OrderModel();
        $this->gameModel = new GameModel();
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

    public function createOrder($data) {
        // Validate user
        $user = $this->userModel->findById($data['user_id']);
        if (!$user) {
            throw new \Exception('User not found');
        }

        // Validate game
        $game = $this->gameModel->findById($data['game_id']);
        if (!$game) {
            throw new \Exception('Game not found');
        }

        // Check if game is available
        if ($game['status'] !== 'active') {
            throw new \Exception('Game is not available');
        }

        // Check if user already owns the game
        if ($this->orderModel->userOwnsGame($data['user_id'], $data['game_id'])) {
            throw new \Exception('User already owns this game');
        }

        // Create order
        $orderId = $this->orderModel->create($data);

        // Log order creation
        $this->auditLogger->log('order', 'order_created', [
            'order_id' => $orderId,
            'user_id' => $data['user_id'],
            'game_id' => $data['game_id']
        ]);

        return $orderId;
    }

    public function updateOrder($orderId, $data) {
        $order = $this->orderModel->findById($orderId);
        if (!$order) {
            throw new \Exception('Order not found');
        }

        // Update order
        $this->orderModel->update($orderId, $data);

        // Clear cache
        $this->cache->delete("order:{$orderId}");

        // Log order update
        $this->auditLogger->log('order', 'order_updated', [
            'order_id' => $orderId,
            'updated_by' => $data['updated_by'] ?? null
        ]);
    }

    public function cancelOrder($orderId) {
        $order = $this->orderModel->findById($orderId);
        if (!$order) {
            throw new \Exception('Order not found');
        }

        // Check if order can be cancelled
        if (!in_array($order['status'], ['pending', 'processing'])) {
            throw new \Exception('Order cannot be cancelled');
        }

        // Update order status
        $this->orderModel->updateStatus($orderId, 'cancelled');

        // Clear cache
        $this->cache->delete("order:{$orderId}");

        // Log order cancellation
        $this->auditLogger->log('order', 'order_cancelled', [
            'order_id' => $orderId,
            'user_id' => $order['user_id']
        ]);
    }

    public function getOrder($orderId) {
        // Try cache first
        $order = $this->cache->get("order:{$orderId}");
        if (!$order) {
            $order = $this->orderModel->findById($orderId);
            if ($order) {
                $this->cache->set("order:{$orderId}", $order, 3600);
            }
        }

        if (!$order) {
            throw new \Exception('Order not found');
        }

        return $order;
    }

    public function getOrders($filters = [], $page = 1, $limit = 10) {
        $cacheKey = 'orders:list:' . md5(json_encode($filters) . $page . $limit);
        
        // Try cache first
        $result = $this->cache->get($cacheKey);
        if (!$result) {
            $result = $this->orderModel->findAll($filters, $page, $limit);
            if ($result) {
                $this->cache->set($cacheKey, $result, 3600);
            }
        }

        return $result;
    }

    public function getUserOrders($userId, $page = 1, $limit = 10) {
        $cacheKey = "orders:user:{$userId}:{$page}:{$limit}";
        
        // Try cache first
        $result = $this->cache->get($cacheKey);
        if (!$result) {
            $result = $this->orderModel->findByUser($userId, $page, $limit);
            if ($result) {
                $this->cache->set($cacheKey, $result, 3600);
            }
        }

        return $result;
    }

    public function getGameOrders($gameId, $page = 1, $limit = 10) {
        $cacheKey = "orders:game:{$gameId}:{$page}:{$limit}";
        
        // Try cache first
        $result = $this->cache->get($cacheKey);
        if (!$result) {
            $result = $this->orderModel->findByGame($gameId, $page, $limit);
            if ($result) {
                $this->cache->set($cacheKey, $result, 3600);
            }
        }

        return $result;
    }

    public function processPayment($orderId, $paymentData) {
        $order = $this->orderModel->findById($orderId);
        if (!$order) {
            throw new \Exception('Order not found');
        }

        // Process payment (implement your payment gateway integration here)
        $paymentResult = $this->processPaymentWithGateway($paymentData);

        if ($paymentResult['status'] === 'success') {
            // Update order status
            $this->orderModel->updateStatus($orderId, 'completed');

            // Clear cache
            $this->cache->delete("order:{$orderId}");

            // Log successful payment
            $this->auditLogger->log('order', 'payment_successful', [
                'order_id' => $orderId,
                'user_id' => $order['user_id'],
                'amount' => $paymentResult['amount']
            ]);
        } else {
            // Update order status
            $this->orderModel->updateStatus($orderId, 'failed');

            // Log failed payment
            $this->auditLogger->log('order', 'payment_failed', [
                'order_id' => $orderId,
                'user_id' => $order['user_id'],
                'error' => $paymentResult['error']
            ]);

            throw new \Exception('Payment failed: ' . $paymentResult['error']);
        }

        return $paymentResult;
    }

    private function processPaymentWithGateway($paymentData) {
        // Implement your payment gateway integration here
        // This is a placeholder implementation
        return [
            'status' => 'success',
            'amount' => $paymentData['amount'],
            'transaction_id' => uniqid('TRX')
        ];
    }
} 