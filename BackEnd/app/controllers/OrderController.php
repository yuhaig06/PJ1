<?php

namespace App\Controllers;

use App\Core\Controller;
use App\Models\OrderModel;
use App\Models\GameModel;
use App\Models\WalletModel;
use App\Middleware\AuthMiddleware;
use App\Middleware\CSRFMiddleware;
use App\Middleware\RateLimitMiddleware;

class OrderController extends Controller
{
    private $orderModel;
    private $gameModel;
    private $walletModel;
    private $authMiddleware;
    private $csrfMiddleware;
    private $rateLimitMiddleware;

    public function __construct()
    {
        parent::__construct();
        $this->orderModel = new OrderModel();
        $this->gameModel = new GameModel();
        $this->walletModel = new WalletModel();
        $this->authMiddleware = new AuthMiddleware();
        $this->csrfMiddleware = new CSRFMiddleware();
        $this->rateLimitMiddleware = new RateLimitMiddleware();
    }

    /**
     * Tạo đơn hàng mới
     */
    public function createOrder()
    {
        // Kiểm tra đăng nhập
        if (!$this->authMiddleware->check()) {
            return $this->json([
                'success' => false,
                'message' => 'Chưa đăng nhập'
            ], 401);
        }

        // Validate dữ liệu
        $data = $this->validate([
            'items' => 'required|array|min:1',
            'items.*.game_id' => 'required|integer|min:1',
            'items.*.quantity' => 'required|integer|min:1',
            'payment_method' => 'required|in:wallet,credit_card,bank_transfer'
        ]);

        if (!$data['success']) {
            return $this->json([
                'success' => false,
                'message' => 'Dữ liệu không hợp lệ',
                'errors' => $data['errors']
            ], 422);
        }

        $userId = $_SESSION['user_id'];
        $items = $data['data']['items'];
        $paymentMethod = $data['data']['payment_method'];

        // Tính tổng tiền
        $totalAmount = 0;
        $orderItems = [];

        foreach ($items as $item) {
            $game = $this->gameModel->find($item['game_id']);
            if (!$game) {
                return $this->json([
                    'success' => false,
                    'message' => 'Game không tồn tại'
                ], 404);
            }

            // Kiểm tra xem người dùng đã mua game này chưa
            if ($this->gameModel->hasPurchased($game['id'], $userId)) {
                return $this->json([
                    'success' => false,
                    'message' => 'Bạn đã mua game ' . $game['name'] . ' rồi'
                ], 400);
            }

            $price = $game['discount_price'] ?? $game['price'];
            $totalAmount += $price * $item['quantity'];

            $orderItems[] = [
                'game_id' => $game['id'],
                'price' => $game['price'],
                'discount_price' => $game['discount_price'],
                'final_price' => $price
            ];
        }

        // Tạo đơn hàng
        $orderId = $this->orderModel->createOrder([
            'user_id' => $userId,
            'order_number' => $this->orderModel->generateOrderNumber(),
            'total_amount' => $totalAmount,
            'discount_amount' => 0,
            'final_amount' => $totalAmount,
            'payment_method' => $paymentMethod,
            'payment_status' => 'pending',
            'order_status' => 'pending',
            'items' => $orderItems
        ]);

        if (!$orderId) {
            return $this->json([
                'success' => false,
                'message' => 'Không thể tạo đơn hàng'
            ], 500);
        }

        return $this->json([
            'success' => true,
            'message' => 'Tạo đơn hàng thành công',
            'data' => [
                'order_id' => $orderId
            ]
        ]);
    }

    /**
     * Thanh toán đơn hàng
     */
    public function processPayment($orderId)
    {
        // Kiểm tra đăng nhập
        if (!$this->authMiddleware->check()) {
            return $this->json([
                'success' => false,
                'message' => 'Chưa đăng nhập'
            ], 401);
        }

        $userId = $_SESSION['user_id'];
        $order = $this->orderModel->getOrderDetail($orderId);

        if (!$order) {
            return $this->json([
                'success' => false,
                'message' => 'Không tìm thấy đơn hàng'
            ], 404);
        }

        // Kiểm tra quyền thanh toán
        if ($order['user_id'] != $userId) {
            return $this->json([
                'success' => false,
                'message' => 'Không có quyền thanh toán đơn hàng này'
            ], 403);
        }

        // Kiểm tra trạng thái đơn hàng
        if ($order['payment_status'] !== 'pending') {
            return $this->json([
                'success' => false,
                'message' => 'Đơn hàng đã được thanh toán hoặc đã hủy'
            ], 400);
        }

        // Xử lý thanh toán theo phương thức
        switch ($order['payment_method']) {
            case 'wallet':
                // Kiểm tra số dư ví
                if (!$this->walletModel->checkBalance($userId, $order['final_amount'])) {
                    return $this->json([
                        'success' => false,
                        'message' => 'Số dư ví không đủ'
                    ], 400);
                }

                // Trừ tiền ví
                $this->walletModel->withdraw($userId, $order['final_amount'], 'Thanh toán đơn hàng #' . $order['order_number']);
                break;

            case 'credit_card':
            case 'bank_transfer':
                // TODO: Tích hợp cổng thanh toán
                break;

            default:
                return $this->json([
                    'success' => false,
                    'message' => 'Phương thức thanh toán không hợp lệ'
                ], 400);
        }

        // Cập nhật trạng thái đơn hàng
        if (!$this->orderModel->updatePaymentStatus($orderId, 'completed')) {
            return $this->json([
                'success' => false,
                'message' => 'Không thể cập nhật trạng thái thanh toán'
            ], 500);
        }

        // Cập nhật trạng thái đơn hàng
        if (!$this->orderModel->updateOrderStatus($orderId, 'processing')) {
            return $this->json([
                'success' => false,
                'message' => 'Không thể cập nhật trạng thái đơn hàng'
            ], 500);
        }

        return $this->json([
            'success' => true,
            'message' => 'Thanh toán thành công'
        ]);
    }

    /**
     * Lấy chi tiết đơn hàng
     */
    public function getOrderDetail($orderId)
    {
        // Kiểm tra đăng nhập
        if (!$this->authMiddleware->check()) {
            return $this->json([
                'success' => false,
                'message' => 'Chưa đăng nhập'
            ], 401);
        }

        $userId = $_SESSION['user_id'];
        $order = $this->orderModel->getOrderDetail($orderId);

        if (!$order) {
            return $this->json([
                'success' => false,
                'message' => 'Không tìm thấy đơn hàng'
            ], 404);
        }

        // Kiểm tra quyền xem
        if ($order['user_id'] != $userId && !$this->authMiddleware->checkRole('admin')) {
            return $this->json([
                'success' => false,
                'message' => 'Không có quyền xem đơn hàng này'
            ], 403);
        }

        // Lấy chi tiết items
        $order['items'] = $this->orderModel->getOrderItems($orderId);

        // Lấy lịch sử giao dịch
        $order['transactions'] = $this->orderModel->getOrderTransactions($orderId);

        return $this->json([
            'success' => true,
            'data' => $order
        ]);
    }

    /**
     * Lấy danh sách đơn hàng của người dùng
     */
    public function getUserOrders()
    {
        // Kiểm tra đăng nhập
        if (!$this->authMiddleware->check()) {
            return $this->json([
                'success' => false,
                'message' => 'Chưa đăng nhập'
            ], 401);
        }

        // Validate dữ liệu
        $data = $this->validate([
            'limit' => 'nullable|integer|min:1|max:50',
            'offset' => 'nullable|integer|min:0',
            'status' => 'nullable|in:pending,processing,completed,cancelled'
        ]);

        $userId = $_SESSION['user_id'];
        $limit = $data['success'] ? ($data['data']['limit'] ?? 10) : 10;
        $offset = $data['success'] ? ($data['data']['offset'] ?? 0) : 0;
        $status = $data['success'] ? ($data['data']['status'] ?? null) : null;

        $orders = $this->orderModel->getUserOrders($userId, $limit, $offset, $status);

        return $this->json([
            'success' => true,
            'data' => $orders
        ]);
    }

    /**
     * Hủy đơn hàng
     */
    public function cancelOrder($orderId)
    {
        // Kiểm tra đăng nhập
        if (!$this->authMiddleware->check()) {
            return $this->json([
                'success' => false,
                'message' => 'Chưa đăng nhập'
            ], 401);
        }

        $userId = $_SESSION['user_id'];
        $order = $this->orderModel->getOrderDetail($orderId);

        if (!$order) {
            return $this->json([
                'success' => false,
                'message' => 'Không tìm thấy đơn hàng'
            ], 404);
        }

        // Kiểm tra quyền hủy
        if ($order['user_id'] != $userId && !$this->authMiddleware->checkRole('admin')) {
            return $this->json([
                'success' => false,
                'message' => 'Không có quyền hủy đơn hàng này'
            ], 403);
        }

        // Kiểm tra trạng thái đơn hàng
        if ($order['order_status'] !== 'pending') {
            return $this->json([
                'success' => false,
                'message' => 'Không thể hủy đơn hàng này'
            ], 400);
        }

        // Cập nhật trạng thái đơn hàng
        if (!$this->orderModel->updateOrderStatus($orderId, 'cancelled')) {
            return $this->json([
                'success' => false,
                'message' => 'Không thể hủy đơn hàng'
            ], 500);
        }

        // Hoàn tiền nếu đã thanh toán
        if ($order['payment_status'] === 'completed' && $order['payment_method'] === 'wallet') {
            $this->walletModel->deposit($userId, $order['final_amount'], 'Hoàn tiền đơn hàng #' . $order['order_number']);
        }

        return $this->json([
            'success' => true,
            'message' => 'Hủy đơn hàng thành công'
        ]);
    }

    /**
     * Lấy danh sách đơn hàng (cho admin)
     */
    public function getOrders()
    {
        // Kiểm tra quyền admin
        if (!$this->authMiddleware->checkRole('admin')) {
            return $this->json([
                'success' => false,
                'message' => 'Không có quyền truy cập'
            ], 403);
        }

        // Validate dữ liệu
        $data = $this->validate([
            'limit' => 'nullable|integer|min:1|max:50',
            'offset' => 'nullable|integer|min:0',
            'status' => 'nullable|in:pending,processing,completed,cancelled',
            'payment_method' => 'nullable|in:wallet,credit_card,bank_transfer',
            'keyword' => 'nullable|min:2'
        ]);

        $limit = $data['success'] ? ($data['data']['limit'] ?? 10) : 10;
        $offset = $data['success'] ? ($data['data']['offset'] ?? 0) : 0;
        $status = $data['success'] ? ($data['data']['status'] ?? null) : null;
        $paymentMethod = $data['success'] ? ($data['data']['payment_method'] ?? null) : null;
        $keyword = $data['success'] ? ($data['data']['keyword'] ?? null) : null;

        $orders = [];
        if ($status) {
            $orders = $this->orderModel->getOrdersByStatus($status, $limit, $offset);
        } elseif ($paymentMethod) {
            $orders = $this->orderModel->getOrdersByPaymentMethod($paymentMethod, $limit, $offset);
        } elseif ($keyword) {
            $orders = $this->orderModel->searchOrders($keyword, $limit, $offset);
        } else {
            $orders = $this->orderModel->getUserOrders(null, $limit, $offset);
        }

        return $this->json([
            'success' => true,
            'data' => $orders
        ]);
    }

    /**
     * Cập nhật trạng thái đơn hàng (cho admin)
     */
    public function updateOrderStatus($orderId)
    {
        // Kiểm tra quyền admin
        if (!$this->authMiddleware->checkRole('admin')) {
            return $this->json([
                'success' => false,
                'message' => 'Không có quyền truy cập'
            ], 403);
        }

        // Validate dữ liệu
        $data = $this->validate([
            'status' => 'required|in:pending,processing,completed,cancelled'
        ]);

        if (!$data['success']) {
            return $this->json([
                'success' => false,
                'message' => 'Dữ liệu không hợp lệ',
                'errors' => $data['errors']
            ], 422);
        }

        $order = $this->orderModel->getOrderDetail($orderId);

        if (!$order) {
            return $this->json([
                'success' => false,
                'message' => 'Không tìm thấy đơn hàng'
            ], 404);
        }

        // Cập nhật trạng thái đơn hàng
        if (!$this->orderModel->updateOrderStatus($orderId, $data['data']['status'])) {
            return $this->json([
                'success' => false,
                'message' => 'Không thể cập nhật trạng thái đơn hàng'
            ], 500);
        }

        return $this->json([
            'success' => true,
            'message' => 'Cập nhật trạng thái đơn hàng thành công'
        ]);
    }
} 