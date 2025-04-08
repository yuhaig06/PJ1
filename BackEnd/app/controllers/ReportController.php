<?php

namespace App\Controllers;

use App\Core\Controller;
use App\Models\ReportModel;
use App\Models\UserModel;
use App\Middleware\AuthMiddleware;
use App\Middleware\CSRFMiddleware;
use App\Middleware\RateLimitMiddleware;

class ReportController extends Controller
{
    private $reportModel;
    private $userModel;
    private $authMiddleware;
    private $csrfMiddleware;
    private $rateLimitMiddleware;

    public function __construct()
    {
        parent::__construct();
        $this->reportModel = new ReportModel();
        $this->userModel = new UserModel();
        $this->authMiddleware = new AuthMiddleware();
        $this->csrfMiddleware = new CSRFMiddleware();
        $this->rateLimitMiddleware = new RateLimitMiddleware();
    }

    /**
     * Tạo báo cáo mới
     */
    public function createReport()
    {
        // Kiểm tra đăng nhập
        if (!$this->authMiddleware->check()) {
            return $this->json([
                'success' => false,
                'message' => 'Vui lòng đăng nhập để thực hiện chức năng này'
            ], 401);
        }

        // Kiểm tra rate limit
        if (!$this->rateLimitMiddleware->check('report', 5, 3600)) {
            return $this->json([
                'success' => false,
                'message' => 'Bạn đã gửi quá nhiều báo cáo. Vui lòng thử lại sau.'
            ], 429);
        }

        // Validate dữ liệu
        $data = $this->validate([
            'type' => 'required|in:user,comment,content',
            'target_id' => 'required|integer',
            'target_type' => 'required|in:game,news',
            'reason' => 'required|string|min:10|max:255',
            'description' => 'required|string|min:20|max:1000'
        ]);

        if (!$data['success']) {
            return $this->json([
                'success' => false,
                'message' => 'Dữ liệu không hợp lệ',
                'errors' => $data['errors']
            ], 422);
        }

        // Kiểm tra xem đã báo cáo chưa
        if ($this->reportModel->hasReported($_SESSION['user_id'], $data['data']['target_id'], $data['data']['target_type'])) {
            return $this->json([
                'success' => false,
                'message' => 'Bạn đã báo cáo nội dung này rồi'
            ], 400);
        }

        // Tạo báo cáo
        $reportData = [
            'user_id' => $_SESSION['user_id'],
            'type' => $data['data']['type'],
            'target_id' => $data['data']['target_id'],
            'target_type' => $data['data']['target_type'],
            'reason' => $data['data']['reason'],
            'description' => $data['data']['description']
        ];

        $reportId = $this->reportModel->createReport($reportData);
        if (!$reportId) {
            return $this->json([
                'success' => false,
                'message' => 'Không thể tạo báo cáo'
            ], 500);
        }

        return $this->json([
            'success' => true,
            'message' => 'Gửi báo cáo thành công',
            'data' => ['report_id' => $reportId]
        ]);
    }

    /**
     * Lấy danh sách báo cáo (chỉ moderator)
     */
    public function getReports()
    {
        // Kiểm tra quyền moderator
        if (!$this->authMiddleware->checkRole('moderator')) {
            return $this->json([
                'success' => false,
                'message' => 'Không có quyền truy cập'
            ], 403);
        }

        // Validate dữ liệu
        $data = $this->validate([
            'limit' => 'nullable|integer|min:1|max:50',
            'offset' => 'nullable|integer|min:0',
            'status' => 'nullable|in:pending,resolved,rejected',
            'type' => 'nullable|in:user,comment,content'
        ]);

        $limit = $data['success'] ? ($data['data']['limit'] ?? 10) : 10;
        $offset = $data['success'] ? ($data['data']['offset'] ?? 0) : 0;
        $status = $data['success'] ? ($data['data']['status'] ?? null) : null;
        $type = $data['success'] ? ($data['data']['type'] ?? null) : null;

        $reports = $this->reportModel->getReports($limit, $offset, $status, $type);

        return $this->json([
            'success' => true,
            'data' => $reports
        ]);
    }

    /**
     * Lấy chi tiết báo cáo (chỉ moderator)
     */
    public function getReportDetail($id)
    {
        // Kiểm tra quyền moderator
        if (!$this->authMiddleware->checkRole('moderator')) {
            return $this->json([
                'success' => false,
                'message' => 'Không có quyền truy cập'
            ], 403);
        }

        $report = $this->reportModel->getReportDetail($id);
        if (!$report) {
            return $this->json([
                'success' => false,
                'message' => 'Không tìm thấy báo cáo'
            ], 404);
        }

        return $this->json([
            'success' => true,
            'data' => $report
        ]);
    }

    /**
     * Xử lý báo cáo (chỉ moderator)
     */
    public function handleReport($id)
    {
        // Kiểm tra quyền moderator
        if (!$this->authMiddleware->checkRole('moderator')) {
            return $this->json([
                'success' => false,
                'message' => 'Không có quyền truy cập'
            ], 403);
        }

        // Validate dữ liệu
        $data = $this->validate([
            'action' => 'required|in:resolve,reject',
            'note' => 'required|string|min:10'
        ]);

        if (!$data['success']) {
            return $this->json([
                'success' => false,
                'message' => 'Dữ liệu không hợp lệ',
                'errors' => $data['errors']
            ], 422);
        }

        $report = $this->reportModel->getReportDetail($id);
        if (!$report) {
            return $this->json([
                'success' => false,
                'message' => 'Không tìm thấy báo cáo'
            ], 404);
        }

        if ($report['status'] !== 'pending') {
            return $this->json([
                'success' => false,
                'message' => 'Báo cáo này đã được xử lý'
            ], 400);
        }

        $status = $data['data']['action'] === 'resolve' ? 'resolved' : 'rejected';
        $success = $this->reportModel->updateStatus($id, $status, $_SESSION['user_id'], $data['data']['note']);

        if (!$success) {
            return $this->json([
                'success' => false,
                'message' => 'Không thể xử lý báo cáo'
            ], 500);
        }

        return $this->json([
            'success' => true,
            'message' => 'Xử lý báo cáo thành công'
        ]);
    }

    /**
     * Lấy danh sách báo cáo của người dùng
     */
    public function getUserReports()
    {
        // Kiểm tra đăng nhập
        if (!$this->authMiddleware->check()) {
            return $this->json([
                'success' => false,
                'message' => 'Vui lòng đăng nhập để thực hiện chức năng này'
            ], 401);
        }

        // Validate dữ liệu
        $data = $this->validate([
            'limit' => 'nullable|integer|min:1|max:50',
            'offset' => 'nullable|integer|min:0'
        ]);

        $limit = $data['success'] ? ($data['data']['limit'] ?? 10) : 10;
        $offset = $data['success'] ? ($data['data']['offset'] ?? 0) : 0;

        $reports = $this->reportModel->getUserReports($_SESSION['user_id'], $limit, $offset);

        return $this->json([
            'success' => true,
            'data' => $reports
        ]);
    }

    /**
     * Lấy thống kê báo cáo (chỉ moderator)
     */
    public function getReportStats()
    {
        // Kiểm tra quyền moderator
        if (!$this->authMiddleware->checkRole('moderator')) {
            return $this->json([
                'success' => false,
                'message' => 'Không có quyền truy cập'
            ], 403);
        }

        // Validate dữ liệu
        $data = $this->validate([
            'period' => 'nullable|in:day,week,month,year'
        ]);

        $period = $data['success'] ? ($data['data']['period'] ?? 'day') : 'day';

        $stats = [
            'counts' => $this->reportModel->getReportCounts(),
            'type_counts' => $this->reportModel->getReportTypeCounts(),
            'time_stats' => $this->reportModel->getReportStats($period)
        ];

        return $this->json([
            'success' => true,
            'data' => $stats
        ]);
    }
} 