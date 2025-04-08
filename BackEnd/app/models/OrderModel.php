<?php

namespace App\Models;

use App\Core\Model;
use App\Core\Database;

class OrderModel extends Model
{
    protected $table = 'orders';
    protected $fillable = [
        'user_id', 'order_number', 'total_amount', 'discount_amount',
        'final_amount', 'payment_method', 'payment_status', 'order_status',
        'notes'
    ];

    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Tạo đơn hàng mới
     */
    public function createOrder($data)
    {
        $this->db->beginTransaction();

        try {
            // Tạo đơn hàng
            $orderId = $this->create($data);
            if (!$orderId) {
                throw new \Exception('Không thể tạo đơn hàng');
            }

            // Tạo chi tiết đơn hàng
            if (isset($data['items']) && is_array($data['items'])) {
                foreach ($data['items'] as $item) {
                    $item['order_id'] = $orderId;
                    if (!$this->createOrderItem($item)) {
                        throw new \Exception('Không thể tạo chi tiết đơn hàng');
                    }
                }
            }

            // Tạo giao dịch thanh toán
            if (isset($data['transaction'])) {
                $data['transaction']['order_id'] = $orderId;
                if (!$this->createTransaction($data['transaction'])) {
                    throw new \Exception('Không thể tạo giao dịch thanh toán');
                }
            }

            $this->db->commit();
            return $orderId;
        } catch (\Exception $e) {
            $this->db->rollBack();
            throw $e;
        }
    }

    /**
     * Tạo chi tiết đơn hàng
     */
    public function createOrderItem($data)
    {
        $sql = "INSERT INTO order_items (order_id, game_id, price, discount_price, final_price) 
                VALUES (?, ?, ?, ?, ?)";
        
        $stmt = $this->db->prepare($sql);
        return $stmt->execute([
            $data['order_id'],
            $data['game_id'],
            $data['price'],
            $data['discount_price'] ?? null,
            $data['final_price']
        ]);
    }

    /**
     * Tạo giao dịch thanh toán
     */
    public function createTransaction($data)
    {
        $sql = "INSERT INTO transactions (order_id, user_id, amount, payment_method, transaction_id, status, payment_details) 
                VALUES (?, ?, ?, ?, ?, ?, ?)";
        
        $stmt = $this->db->prepare($sql);
        return $stmt->execute([
            $data['order_id'],
            $data['user_id'],
            $data['amount'],
            $data['payment_method'],
            $data['transaction_id'] ?? null,
            $data['status'] ?? 'pending',
            $data['payment_details'] ?? null
        ]);
    }

    /**
     * Cập nhật trạng thái đơn hàng
     */
    public function updateOrderStatus($orderId, $status)
    {
        return $this->update($orderId, ['order_status' => $status]);
    }

    /**
     * Cập nhật trạng thái thanh toán
     */
    public function updatePaymentStatus($orderId, $status)
    {
        return $this->update($orderId, ['payment_status' => $status]);
    }

    /**
     * Lấy thông tin đơn hàng chi tiết
     */
    public function getOrderDetail($orderId)
    {
        $sql = "SELECT o.*, u.username, u.email 
                FROM orders o 
                JOIN users u ON o.user_id = u.id 
                WHERE o.id = ?";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$orderId]);
        return $stmt->fetch();
    }

    /**
     * Lấy danh sách chi tiết đơn hàng
     */
    public function getOrderItems($orderId)
    {
        $sql = "SELECT oi.*, g.name as game_name, g.thumbnail 
                FROM order_items oi 
                JOIN games g ON oi.game_id = g.id 
                WHERE oi.order_id = ?";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$orderId]);
        return $stmt->fetchAll();
    }

    /**
     * Lấy danh sách giao dịch của đơn hàng
     */
    public function getOrderTransactions($orderId)
    {
        $sql = "SELECT * FROM transactions WHERE order_id = ? ORDER BY created_at DESC";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$orderId]);
        return $stmt->fetchAll();
    }

    /**
     * Lấy danh sách đơn hàng của người dùng
     */
    public function getUserOrders($userId, $limit = null, $offset = null)
    {
        $sql = "SELECT * FROM orders WHERE user_id = ? ORDER BY created_at DESC";
        $params = [$userId];

        if ($limit !== null) {
            $sql .= " LIMIT ?";
            $params[] = $limit;
        }

        if ($offset !== null) {
            $sql .= " OFFSET ?";
            $params[] = $offset;
        }

        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }

    /**
     * Lấy danh sách đơn hàng theo trạng thái
     */
    public function getOrdersByStatus($status, $limit = null, $offset = null)
    {
        $sql = "SELECT o.*, u.username, u.email 
                FROM orders o 
                JOIN users u ON o.user_id = u.id 
                WHERE o.order_status = ? 
                ORDER BY o.created_at DESC";
        
        $params = [$status];

        if ($limit !== null) {
            $sql .= " LIMIT ?";
            $params[] = $limit;
        }

        if ($offset !== null) {
            $sql .= " OFFSET ?";
            $params[] = $offset;
        }

        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }

    /**
     * Lấy danh sách đơn hàng theo phương thức thanh toán
     */
    public function getOrdersByPaymentMethod($method, $limit = null, $offset = null)
    {
        $sql = "SELECT o.*, u.username, u.email 
                FROM orders o 
                JOIN users u ON o.user_id = u.id 
                WHERE o.payment_method = ? 
                ORDER BY o.created_at DESC";
        
        $params = [$method];

        if ($limit !== null) {
            $sql .= " LIMIT ?";
            $params[] = $limit;
        }

        if ($offset !== null) {
            $sql .= " OFFSET ?";
            $params[] = $offset;
        }

        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }

    /**
     * Tìm kiếm đơn hàng
     */
    public function searchOrders($keyword, $limit = null, $offset = null)
    {
        $sql = "SELECT o.*, u.username, u.email 
                FROM orders o 
                JOIN users u ON o.user_id = u.id 
                WHERE o.order_number LIKE ? 
                OR u.username LIKE ? 
                OR u.email LIKE ? 
                ORDER BY o.created_at DESC";
        
        $keyword = "%{$keyword}%";
        $params = [$keyword, $keyword, $keyword];

        if ($limit !== null) {
            $sql .= " LIMIT ?";
            $params[] = $limit;
        }

        if ($offset !== null) {
            $sql .= " OFFSET ?";
            $params[] = $offset;
        }

        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }

    /**
     * Tạo mã đơn hàng
     */
    public function generateOrderNumber()
    {
        $prefix = date('Ymd');
        $sql = "SELECT MAX(order_number) as max_number 
                FROM orders 
                WHERE order_number LIKE ?";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$prefix . '%']);
        $result = $stmt->fetch();

        if ($result && $result['max_number']) {
            $number = intval(substr($result['max_number'], -4)) + 1;
        } else {
            $number = 1;
        }

        return $prefix . str_pad($number, 4, '0', STR_PAD_LEFT);
    }
} 