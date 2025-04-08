<?php

namespace App\Controllers;

use App\Core\Controller;
use App\Models\UserModel;
use App\Models\GameModel;
use App\Models\NewsModel;
use App\Models\CategoryModel;
use App\Models\OrderModel;
use App\Models\WalletModel;
use App\Middleware\AuthMiddleware;
use App\Middleware\CSRFMiddleware;
use App\Middleware\RateLimitMiddleware;

class AdminController extends Controller
{
    private $userModel;
    private $gameModel;
    private $newsModel;
    private $categoryModel;
    private $orderModel;
    private $walletModel;
    private $authMiddleware;
    private $csrfMiddleware;
    private $rateLimitMiddleware;

    public function __construct()
    {
        parent::__construct();
        $this->userModel = new UserModel();
        $this->gameModel = new GameModel();
        $this->newsModel = new NewsModel();
        $this->categoryModel = new CategoryModel();
        $this->orderModel = new OrderModel();
        $this->walletModel = new WalletModel();
        $this->authMiddleware = new AuthMiddleware();
        $this->csrfMiddleware = new CSRFMiddleware();
        $this->rateLimitMiddleware = new RateLimitMiddleware();
    }

    /**
     * Lấy thống kê tổng quan
     */
    public function getDashboardStats()
    {
        // Kiểm tra quyền admin
        if (!$this->authMiddleware->checkRole('admin')) {
            return $this->json([
                'success' => false,
                'message' => 'Không có quyền truy cập'
            ], 403);
        }

        $stats = [
            'users' => [
                'total' => $this->userModel->count(),
                'active' => $this->userModel->countByStatus('active'),
                'suspended' => $this->userModel->countByStatus('suspended'),
                'banned' => $this->userModel->countByStatus('banned')
            ],
            'games' => [
                'total' => $this->gameModel->count(),
                'published' => $this->gameModel->countByStatus('published'),
                'draft' => $this->gameModel->countByStatus('draft'),
                'archived' => $this->gameModel->countByStatus('archived')
            ],
            'news' => [
                'total' => $this->newsModel->count(),
                'published' => $this->newsModel->countByStatus('published'),
                'draft' => $this->newsModel->countByStatus('draft'),
                'archived' => $this->newsModel->countByStatus('archived')
            ],
            'orders' => [
                'total' => $this->orderModel->count(),
                'pending' => $this->orderModel->countByStatus('pending'),
                'completed' => $this->orderModel->countByStatus('completed'),
                'cancelled' => $this->orderModel->countByStatus('cancelled')
            ],
            'revenue' => [
                'today' => $this->orderModel->getRevenueByPeriod('today'),
                'week' => $this->orderModel->getRevenueByPeriod('week'),
                'month' => $this->orderModel->getRevenueByPeriod('month'),
                'year' => $this->orderModel->getRevenueByPeriod('year')
            ]
        ];

        return $this->json([
            'success' => true,
            'data' => $stats
        ]);
    }

    /**
     * Quản lý người dùng
     */
    public function getUsers()
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
            'status' => 'nullable|in:active,suspended,banned',
            'role' => 'nullable|in:user,moderator,admin',
            'search' => 'nullable|string|min:2'
        ]);

        $limit = $data['success'] ? ($data['data']['limit'] ?? 10) : 10;
        $offset = $data['success'] ? ($data['data']['offset'] ?? 0) : 0;
        $status = $data['success'] ? ($data['data']['status'] ?? null) : null;
        $role = $data['success'] ? ($data['data']['role'] ?? null) : null;
        $search = $data['success'] ? ($data['data']['search'] ?? null) : null;

        $users = $this->userModel->getUsers($limit, $offset, $status, $role, $search);

        return $this->json([
            'success' => true,
            'data' => $users
        ]);
    }

    /**
     * Cập nhật trạng thái người dùng
     */
    public function updateUserStatus($userId)
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
            'status' => 'required|in:active,suspended,banned',
            'reason' => 'required|string|min:10'
        ]);

        if (!$data['success']) {
            return $this->json([
                'success' => false,
                'message' => 'Dữ liệu không hợp lệ',
                'errors' => $data['errors']
            ], 422);
        }

        $user = $this->userModel->find($userId);
        if (!$user) {
            return $this->json([
                'success' => false,
                'message' => 'Không tìm thấy người dùng'
            ], 404);
        }

        // Không cho phép thay đổi trạng thái admin
        if ($user['role'] === 'admin') {
            return $this->json([
                'success' => false,
                'message' => 'Không thể thay đổi trạng thái admin'
            ], 400);
        }

        $success = $this->userModel->updateStatus($userId, $data['data']['status']);
        if (!$success) {
            return $this->json([
                'success' => false,
                'message' => 'Không thể cập nhật trạng thái người dùng'
            ], 500);
        }

        return $this->json([
            'success' => true,
            'message' => 'Cập nhật trạng thái người dùng thành công'
        ]);
    }

    /**
     * Cập nhật vai trò người dùng
     */
    public function updateUserRole($userId)
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
            'role' => 'required|in:user,moderator,admin'
        ]);

        if (!$data['success']) {
            return $this->json([
                'success' => false,
                'message' => 'Dữ liệu không hợp lệ',
                'errors' => $data['errors']
            ], 422);
        }

        $user = $this->userModel->find($userId);
        if (!$user) {
            return $this->json([
                'success' => false,
                'message' => 'Không tìm thấy người dùng'
            ], 404);
        }

        $success = $this->userModel->updateRole($userId, $data['data']['role']);
        if (!$success) {
            return $this->json([
                'success' => false,
                'message' => 'Không thể cập nhật vai trò người dùng'
            ], 500);
        }

        return $this->json([
            'success' => true,
            'message' => 'Cập nhật vai trò người dùng thành công'
        ]);
    }

    /**
     * Quản lý danh mục
     */
    public function getCategories()
    {
        // Kiểm tra quyền admin
        if (!$this->authMiddleware->checkRole('admin')) {
            return $this->json([
                'success' => false,
                'message' => 'Không có quyền truy cập'
            ], 403);
        }

        $categories = $this->categoryModel->getAllCategories();

        return $this->json([
            'success' => true,
            'data' => $categories
        ]);
    }

    /**
     * Tạo danh mục mới
     */
    public function createCategory()
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
            'name' => 'required|string|min:2|max:50',
            'slug' => 'required|string|min:2|max:50',
            'description' => 'nullable|string|max:255',
            'parent_id' => 'nullable|integer',
            'type' => 'required|in:game,news'
        ]);

        if (!$data['success']) {
            return $this->json([
                'success' => false,
                'message' => 'Dữ liệu không hợp lệ',
                'errors' => $data['errors']
            ], 422);
        }

        $categoryId = $this->categoryModel->createCategory($data['data']);
        if (!$categoryId) {
            return $this->json([
                'success' => false,
                'message' => 'Không thể tạo danh mục'
            ], 500);
        }

        return $this->json([
            'success' => true,
            'message' => 'Tạo danh mục thành công',
            'data' => ['category_id' => $categoryId]
        ]);
    }

    /**
     * Cập nhật danh mục
     */
    public function updateCategory($id)
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
            'name' => 'required|string|min:2|max:50',
            'slug' => 'required|string|min:2|max:50',
            'description' => 'nullable|string|max:255',
            'parent_id' => 'nullable|integer'
        ]);

        if (!$data['success']) {
            return $this->json([
                'success' => false,
                'message' => 'Dữ liệu không hợp lệ',
                'errors' => $data['errors']
            ], 422);
        }

        $success = $this->categoryModel->updateCategory($id, $data['data']);
        if (!$success) {
            return $this->json([
                'success' => false,
                'message' => 'Không thể cập nhật danh mục'
            ], 500);
        }

        return $this->json([
            'success' => true,
            'message' => 'Cập nhật danh mục thành công'
        ]);
    }

    /**
     * Xóa danh mục
     */
    public function deleteCategory($id)
    {
        // Kiểm tra quyền admin
        if (!$this->authMiddleware->checkRole('admin')) {
            return $this->json([
                'success' => false,
                'message' => 'Không có quyền truy cập'
            ], 403);
        }

        $success = $this->categoryModel->deleteCategory($id);
        if (!$success) {
            return $this->json([
                'success' => false,
                'message' => 'Không thể xóa danh mục'
            ], 500);
        }

        return $this->json([
            'success' => true,
            'message' => 'Xóa danh mục thành công'
        ]);
    }

    /**
     * Lấy thống kê doanh thu
     */
    public function getRevenueStats()
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
            'period' => 'nullable|in:day,week,month,year'
        ]);

        $period = $data['success'] ? ($data['data']['period'] ?? 'month') : 'month';

        $stats = [
            'total' => $this->orderModel->getTotalRevenue(),
            'by_period' => $this->orderModel->getRevenueByPeriod($period),
            'by_payment_method' => $this->orderModel->getRevenueByPaymentMethod(),
            'by_category' => $this->orderModel->getRevenueByCategory()
        ];

        return $this->json([
            'success' => true,
            'data' => $stats
        ]);
    }

    /**
     * Lấy thống kê người dùng
     */
    public function getUserStats()
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
            'period' => 'nullable|in:day,week,month,year'
        ]);

        $period = $data['success'] ? ($data['data']['period'] ?? 'month') : 'month';

        $stats = [
            'total' => $this->userModel->count(),
            'by_status' => $this->userModel->countByStatus(),
            'by_role' => $this->userModel->countByRole(),
            'by_period' => $this->userModel->countByPeriod($period)
        ];

        return $this->json([
            'success' => true,
            'data' => $stats
        ]);
    }

    /**
     * Lấy thống kê nội dung
     */
    public function getContentStats()
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
            'period' => 'nullable|in:day,week,month,year'
        ]);

        $period = $data['success'] ? ($data['data']['period'] ?? 'month') : 'month';

        $stats = [
            'games' => [
                'total' => $this->gameModel->count(),
                'by_status' => $this->gameModel->countByStatus(),
                'by_category' => $this->gameModel->countByCategory(),
                'by_period' => $this->gameModel->countByPeriod($period)
            ],
            'news' => [
                'total' => $this->newsModel->count(),
                'by_status' => $this->newsModel->countByStatus(),
                'by_category' => $this->newsModel->countByCategory(),
                'by_period' => $this->newsModel->countByPeriod($period)
            ]
        ];

        return $this->json([
            'success' => true,
            'data' => $stats
        ]);
    }
} 