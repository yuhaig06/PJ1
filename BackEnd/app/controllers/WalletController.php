<?php

namespace App\Controllers;

use App\Core\Controller;
use App\Models\WalletModel;
use App\Middleware\AuthMiddleware;
use App\Middleware\CSRFMiddleware;
use App\Middleware\RateLimitMiddleware;

class WalletController extends Controller
{
    private $walletModel;
    private $authMiddleware;
    private $csrfMiddleware;
    private $rateLimitMiddleware;

    public function __construct()
    {
        parent::__construct();
        $this->walletModel = new WalletModel();
        $this->authMiddleware = new AuthMiddleware();
        $this->csrfMiddleware = new CSRFMiddleware();
        $this->rateLimitMiddleware = new RateLimitMiddleware();
    }

    /**
     * Lấy thông tin ví của người dùng
     */
    public function getWallet()
    {
        // Kiểm tra đăng nhập
        if (!$this->authMiddleware->check()) {
            return $this->json([
                'success' => false,
                'message' => 'Chưa đăng nhập'
            ], 401);
        }

        $userId = $_SESSION['user_id'];
        $wallet = $this->walletModel->getUserWallet($userId);

        if (!$wallet) {
            // Tạo ví mới nếu chưa có
            $walletId = $this->walletModel->createWallet($userId);
            if (!$walletId) {
                return $this->json([
                    'success' => false,
                    'message' => 'Không thể tạo ví'
                ], 500);
            }
            $wallet = $this->walletModel->getUserWallet($userId);
        }

        return $this->json([
            'success' => true,
            'data' => $wallet
        ]);
    }

    /**
     * Nạp tiền vào ví
     */
    public function deposit()
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
            'amount' => 'required|numeric|min:10000',
            'payment_method' => 'required|in:credit_card,bank_transfer'
        ]);

        if (!$data['success']) {
            return $this->json([
                'success' => false,
                'message' => 'Dữ liệu không hợp lệ',
                'errors' => $data['errors']
            ], 422);
        }

        $userId = $_SESSION['user_id'];
        $amount = $data['data']['amount'];
        $paymentMethod = $data['data']['payment_method'];

        // TODO: Tích hợp cổng thanh toán
        // Tạm thời cho phép nạp tiền trực tiếp
        $wallet = $this->walletModel->getUserWallet($userId);
        if (!$wallet) {
            return $this->json([
                'success' => false,
                'message' => 'Không tìm thấy ví'
            ], 404);
        }

        // Nạp tiền vào ví
        if (!$this->walletModel->deposit($userId, $amount, 'Nạp tiền vào ví')) {
            return $this->json([
                'success' => false,
                'message' => 'Không thể nạp tiền'
            ], 500);
        }

        return $this->json([
            'success' => true,
            'message' => 'Nạp tiền thành công'
        ]);
    }

    /**
     * Rút tiền từ ví
     */
    public function withdraw()
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
            'amount' => 'required|numeric|min:10000',
            'bank_account' => 'required|string|min:10'
        ]);

        if (!$data['success']) {
            return $this->json([
                'success' => false,
                'message' => 'Dữ liệu không hợp lệ',
                'errors' => $data['errors']
            ], 422);
        }

        $userId = $_SESSION['user_id'];
        $amount = $data['data']['amount'];
        $bankAccount = $data['data']['bank_account'];

        // Kiểm tra số dư
        if (!$this->walletModel->checkBalance($userId, $amount)) {
            return $this->json([
                'success' => false,
                'message' => 'Số dư không đủ'
            ], 400);
        }

        // Rút tiền từ ví
        if (!$this->walletModel->withdraw($userId, $amount, 'Rút tiền về tài khoản ' . $bankAccount)) {
            return $this->json([
                'success' => false,
                'message' => 'Không thể rút tiền'
            ], 500);
        }

        return $this->json([
            'success' => true,
            'message' => 'Rút tiền thành công'
        ]);
    }

    /**
     * Lấy lịch sử giao dịch
     */
    public function getTransactions()
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
            'type' => 'nullable|in:deposit,withdraw',
            'start_date' => 'nullable|date',
            'end_date' => 'nullable|date'
        ]);

        $userId = $_SESSION['user_id'];
        $limit = $data['success'] ? ($data['data']['limit'] ?? 10) : 10;
        $offset = $data['success'] ? ($data['data']['offset'] ?? 0) : 0;
        $type = $data['success'] ? ($data['data']['type'] ?? null) : null;
        $startDate = $data['success'] ? ($data['data']['start_date'] ?? null) : null;
        $endDate = $data['success'] ? ($data['data']['end_date'] ?? null) : null;

        $wallet = $this->walletModel->getUserWallet($userId);
        if (!$wallet) {
            return $this->json([
                'success' => false,
                'message' => 'Không tìm thấy ví'
            ], 404);
        }

        $transactions = $this->walletModel->getTransactions(
            $wallet['id'],
            $limit,
            $offset,
            $type,
            $startDate,
            $endDate
        );

        return $this->json([
            'success' => true,
            'data' => $transactions
        ]);
    }

    /**
     * Lấy thống kê giao dịch
     */
    public function getTransactionStats()
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
            'period' => 'required|in:day,week,month,year'
        ]);

        if (!$data['success']) {
            return $this->json([
                'success' => false,
                'message' => 'Dữ liệu không hợp lệ',
                'errors' => $data['errors']
            ], 422);
        }

        $userId = $_SESSION['user_id'];
        $period = $data['data']['period'];

        $wallet = $this->walletModel->getUserWallet($userId);
        if (!$wallet) {
            return $this->json([
                'success' => false,
                'message' => 'Không tìm thấy ví'
            ], 404);
        }

        $stats = $this->walletModel->getTransactionStats($wallet['id'], $period);

        return $this->json([
            'success' => true,
            'data' => $stats
        ]);
    }

    /**
     * Lấy thống kê giao dịch (cho admin)
     */
    public function getAllTransactionStats()
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
            'period' => 'required|in:day,week,month,year'
        ]);

        if (!$data['success']) {
            return $this->json([
                'success' => false,
                'message' => 'Dữ liệu không hợp lệ',
                'errors' => $data['errors']
            ], 422);
        }

        $period = $data['data']['period'];
        $stats = $this->walletModel->getAllTransactionStats($period);

        return $this->json([
            'success' => true,
            'data' => $stats
        ]);
    }
} 