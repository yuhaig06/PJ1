<?php

namespace App\Controllers;

use App\Core\Controller;
use App\Models\UserModel;
use App\Models\GameModel;
use App\Models\NewsModel;
use App\Middleware\AuthMiddleware;
use App\Middleware\CSRFMiddleware;
use App\Middleware\RateLimitMiddleware;

class ModeratorController extends Controller
{
    private $userModel;
    private $gameModel;
    private $newsModel;
    private $authMiddleware;
    private $csrfMiddleware;
    private $rateLimitMiddleware;

    public function __construct()
    {
        parent::__construct();
        $this->userModel = new UserModel();
        $this->gameModel = new GameModel();
        $this->newsModel = new NewsModel();
        $this->authMiddleware = new AuthMiddleware();
        $this->csrfMiddleware = new CSRFMiddleware();
        $this->rateLimitMiddleware = new RateLimitMiddleware();
    }

    /**
     * Lấy danh sách bình luận cần duyệt
     */
    public function getPendingComments()
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
            'type' => 'nullable|in:game,news'
        ]);

        $limit = $data['success'] ? ($data['data']['limit'] ?? 10) : 10;
        $offset = $data['success'] ? ($data['data']['offset'] ?? 0) : 0;
        $type = $data['success'] ? ($data['data']['type'] ?? null) : null;

        $comments = [];
        if ($type === 'game') {
            $comments = $this->gameModel->getComments($limit, $offset, 'pending');
        } else {
            $comments = $this->newsModel->getComments($limit, $offset, 'pending');
        }

        return $this->json([
            'success' => true,
            'data' => $comments
        ]);
    }

    /**
     * Duyệt bình luận
     */
    public function approveComment($commentId)
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
            'type' => 'required|in:game,news'
        ]);

        if (!$data['success']) {
            return $this->json([
                'success' => false,
                'message' => 'Dữ liệu không hợp lệ',
                'errors' => $data['errors']
            ], 422);
        }

        $success = false;
        if ($data['data']['type'] === 'game') {
            $success = $this->gameModel->updateCommentStatus($commentId, 'approved');
        } else {
            $success = $this->newsModel->updateCommentStatus($commentId, 'approved');
        }

        if (!$success) {
            return $this->json([
                'success' => false,
                'message' => 'Không thể duyệt bình luận'
            ], 500);
        }

        return $this->json([
            'success' => true,
            'message' => 'Duyệt bình luận thành công'
        ]);
    }

    /**
     * Từ chối bình luận
     */
    public function rejectComment($commentId)
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
            'type' => 'required|in:game,news',
            'reason' => 'required|string|min:10'
        ]);

        if (!$data['success']) {
            return $this->json([
                'success' => false,
                'message' => 'Dữ liệu không hợp lệ',
                'errors' => $data['errors']
            ], 422);
        }

        $success = false;
        if ($data['data']['type'] === 'game') {
            $success = $this->gameModel->updateCommentStatus($commentId, 'rejected');
        } else {
            $success = $this->newsModel->updateCommentStatus($commentId, 'rejected');
        }

        if (!$success) {
            return $this->json([
                'success' => false,
                'message' => 'Không thể từ chối bình luận'
            ], 500);
        }

        return $this->json([
            'success' => true,
            'message' => 'Từ chối bình luận thành công'
        ]);
    }

    /**
     * Lấy danh sách báo cáo
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

        // TODO: Implement report model
        $reports = [];

        return $this->json([
            'success' => true,
            'data' => $reports
        ]);
    }

    /**
     * Xử lý báo cáo
     */
    public function handleReport($reportId)
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
            'reason' => 'required|string|min:10'
        ]);

        if (!$data['success']) {
            return $this->json([
                'success' => false,
                'message' => 'Dữ liệu không hợp lệ',
                'errors' => $data['errors']
            ], 422);
        }

        // TODO: Implement report handling
        $success = true;

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
     * Lấy danh sách nội dung người dùng cần duyệt
     */
    public function getPendingContent()
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
            'type' => 'nullable|in:game,news'
        ]);

        $limit = $data['success'] ? ($data['data']['limit'] ?? 10) : 10;
        $offset = $data['success'] ? ($data['data']['offset'] ?? 0) : 0;
        $type = $data['success'] ? ($data['data']['type'] ?? null) : null;

        $content = [];
        if ($type === 'game') {
            $content = $this->gameModel->getGames($limit, $offset, null, 'draft');
        } else {
            $content = $this->newsModel->getNews($limit, $offset, null, 'draft');
        }

        return $this->json([
            'success' => true,
            'data' => $content
        ]);
    }

    /**
     * Duyệt nội dung
     */
    public function approveContent($contentId)
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
            'type' => 'required|in:game,news'
        ]);

        if (!$data['success']) {
            return $this->json([
                'success' => false,
                'message' => 'Dữ liệu không hợp lệ',
                'errors' => $data['errors']
            ], 422);
        }

        $success = false;
        if ($data['data']['type'] === 'game') {
            $success = $this->gameModel->updateStatus($contentId, 'published');
        } else {
            $success = $this->newsModel->updateStatus($contentId, 'published');
        }

        if (!$success) {
            return $this->json([
                'success' => false,
                'message' => 'Không thể duyệt nội dung'
            ], 500);
        }

        return $this->json([
            'success' => true,
            'message' => 'Duyệt nội dung thành công'
        ]);
    }

    /**
     * Từ chối nội dung
     */
    public function rejectContent($contentId)
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
            'type' => 'required|in:game,news',
            'reason' => 'required|string|min:10'
        ]);

        if (!$data['success']) {
            return $this->json([
                'success' => false,
                'message' => 'Dữ liệu không hợp lệ',
                'errors' => $data['errors']
            ], 422);
        }

        $success = false;
        if ($data['data']['type'] === 'game') {
            $success = $this->gameModel->updateStatus($contentId, 'archived');
        } else {
            $success = $this->newsModel->updateStatus($contentId, 'archived');
        }

        if (!$success) {
            return $this->json([
                'success' => false,
                'message' => 'Không thể từ chối nội dung'
            ], 500);
        }

        return $this->json([
            'success' => true,
            'message' => 'Từ chối nội dung thành công'
        ]);
    }

    /**
     * Lấy danh sách người dùng cần xem xét
     */
    public function getFlaggedUsers()
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
            'offset' => 'nullable|integer|min:0'
        ]);

        $limit = $data['success'] ? ($data['data']['limit'] ?? 10) : 10;
        $offset = $data['success'] ? ($data['data']['offset'] ?? 0) : 0;

        // TODO: Implement flagged users
        $users = [];

        return $this->json([
            'success' => true,
            'data' => $users
        ]);
    }

    /**
     * Xử lý người dùng vi phạm
     */
    public function handleFlaggedUser($userId)
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
            'action' => 'required|in:warn,suspend,ban',
            'reason' => 'required|string|min:10',
            'duration' => 'required_if:action,suspend|integer|min:1'
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

        // Không cho phép xử lý admin
        if ($user['role'] === 'admin') {
            return $this->json([
                'success' => false,
                'message' => 'Không thể xử lý tài khoản admin'
            ], 400);
        }

        $success = false;
        switch ($data['data']['action']) {
            case 'warn':
                // TODO: Implement warning system
                $success = true;
                break;
            case 'suspend':
                $success = $this->userModel->updateStatus($userId, 'suspended');
                break;
            case 'ban':
                $success = $this->userModel->updateStatus($userId, 'banned');
                break;
        }

        if (!$success) {
            return $this->json([
                'success' => false,
                'message' => 'Không thể xử lý người dùng'
            ], 500);
        }

        return $this->json([
            'success' => true,
            'message' => 'Xử lý người dùng thành công'
        ]);
    }
} 