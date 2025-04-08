<?php

namespace App\Controllers;

use App\Core\Controller;
use App\Models\NewsModel;
use App\Models\CommentModel;
use App\Config\Database;
use App\Middleware\AuthMiddleware;
use App\Middleware\CSRFMiddleware;
use App\Middleware\RateLimitMiddleware;

class NewsController extends Controller
{
    private $db;
    private $newsModel;
    private $commentModel;
    private $authMiddleware;
    private $csrfMiddleware;
    private $rateLimitMiddleware;

    public function __construct()
    {
        parent::__construct();
        $this->db = Database::getInstance()->getConnection();
        $this->newsModel = new NewsModel($this->db);
        $this->commentModel = new CommentModel($this->db);
        $this->authMiddleware = new AuthMiddleware();
        $this->csrfMiddleware = new CSRFMiddleware();
        $this->rateLimitMiddleware = new RateLimitMiddleware();
    }

    /**
     * Lấy danh sách tin tức theo danh mục
     */
    public function getNewsByCategory($categoryId)
    {
        // Validate dữ liệu
        $data = $this->validate([
            'limit' => 'nullable|integer|min:1|max:50',
            'offset' => 'nullable|integer|min:0'
        ]);

        $limit = $data['success'] ? ($data['data']['limit'] ?? 10) : 10;
        $offset = $data['success'] ? ($data['data']['offset'] ?? 0) : 0;

        $news = $this->newsModel->getNewsByCategory($categoryId, $limit, $offset);

        return $this->json([
            'success' => true,
            'data' => $news
        ]);
    }

    /**
     * Lấy danh sách tin tức nổi bật
     */
    public function getFeaturedNews()
    {
        // Validate dữ liệu
        $data = $this->validate([
            'limit' => 'nullable|integer|min:1|max:50'
        ]);

        $limit = $data['success'] ? ($data['data']['limit'] ?? 10) : 10;

        $news = $this->newsModel->getFeaturedNews($limit);

        return $this->json([
            'success' => true,
            'data' => $news
        ]);
    }

    /**
     * Lấy danh sách tin tức mới nhất
     */
    public function getLatestNews()
    {
        // Validate dữ liệu
        $data = $this->validate([
            'limit' => 'nullable|integer|min:1|max:50'
        ]);

        $limit = $data['success'] ? ($data['data']['limit'] ?? 10) : 10;

        $news = $this->newsModel->getLatestNews($limit);

        return $this->json([
            'success' => true,
            'data' => $news
        ]);
    }

    /**
     * Lấy danh sách tin tức phổ biến
     */
    public function getPopularNews()
    {
        // Validate dữ liệu
        $data = $this->validate([
            'limit' => 'nullable|integer|min:1|max:50'
        ]);

        $limit = $data['success'] ? ($data['data']['limit'] ?? 10) : 10;

        $news = $this->newsModel->getPopularNews($limit);

        return $this->json([
            'success' => true,
            'data' => $news
        ]);
    }

    /**
     * Tìm kiếm tin tức
     */
    public function searchNews()
    {
        // Validate dữ liệu
        $data = $this->validate([
            'keyword' => 'required|min:2',
            'limit' => 'nullable|integer|min:1|max:50',
            'offset' => 'nullable|integer|min:0'
        ]);

        if (!$data['success']) {
            return $this->json([
                'success' => false,
                'message' => 'Dữ liệu không hợp lệ',
                'errors' => $data['errors']
            ], 422);
        }

        $limit = $data['data']['limit'] ?? 10;
        $offset = $data['data']['offset'] ?? 0;

        $news = $this->newsModel->searchNews($data['data']['keyword'], $limit, $offset);

        return $this->json([
            'success' => true,
            'data' => $news
        ]);
    }

    /**
     * Lấy chi tiết tin tức
     */
    public function getNewsDetail($id)
    {
        $news = $this->newsModel->getNewsDetail($id);

        if (!$news) {
            return $this->json([
                'success' => false,
                'message' => 'Không tìm thấy tin tức'
            ], 404);
        }

        // Tăng lượt xem
        $this->newsModel->incrementViews($id);

        // Lấy tags của tin tức
        $news['tags'] = $this->newsModel->getNewsTags($id);

        // Lấy tin tức liên quan
        $news['related_news'] = $this->newsModel->getRelatedNews($id);

        return $this->json([
            'success' => true,
            'data' => $news
        ]);
    }

    /**
     * Thêm bình luận
     */
    public function addComment($newsId)
    {
        // Kiểm tra đăng nhập
        if (!$this->authMiddleware->check()) {
            return $this->json([
                'success' => false,
                'message' => 'Chưa đăng nhập'
            ], 401);
        }

        // Kiểm tra rate limit
        if (!$this->rateLimitMiddleware->checkLimit('add_comment', 10, 300)) {
            return $this->json([
                'success' => false,
                'message' => 'Quá nhiều bình luận. Vui lòng thử lại sau.'
            ], 429);
        }

        // Validate dữ liệu
        $data = $this->validate([
            'content' => 'required|min:2|max:500',
            'parent_id' => 'nullable|integer|min:1'
        ]);

        if (!$data['success']) {
            return $this->json([
                'success' => false,
                'message' => 'Dữ liệu không hợp lệ',
                'errors' => $data['errors']
            ], 422);
        }

        $userId = $_SESSION['user_id'];

        // Thêm bình luận
        $commentId = $this->commentModel->create([
            'news_id' => $newsId,
            'user_id' => $userId,
            'parent_id' => $data['data']['parent_id'] ?? null,
            'content' => $data['data']['content'],
            'status' => 'pending'
        ]);

        if (!$commentId) {
            return $this->json([
                'success' => false,
                'message' => 'Không thể thêm bình luận'
            ], 500);
        }

        $comment = $this->commentModel->getById($commentId);

        return $this->json([
            'success' => true,
            'message' => 'Bình luận đã được gửi và đang chờ duyệt',
            'data' => $comment
        ]);
    }

    /**
     * Lấy danh sách bình luận
     */
    public function getComments($newsId)
    {
        // Validate dữ liệu
        $data = $this->validate([
            'limit' => 'nullable|integer|min:1|max:50',
            'offset' => 'nullable|integer|min:0'
        ]);

        $limit = $data['success'] ? ($data['data']['limit'] ?? 10) : 10;
        $offset = $data['success'] ? ($data['data']['offset'] ?? 0) : 0;

        $comments = $this->commentModel->getByNewsId($newsId, $limit, $offset);

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
        // Kiểm tra quyền admin/moderator
        if (!$this->authMiddleware->checkRole(['admin', 'moderator'])) {
            return $this->json([
                'success' => false,
                'message' => 'Không có quyền truy cập'
            ], 403);
        }

        if (!$this->commentModel->updateStatus($commentId, 'approved')) {
            return $this->json([
                'success' => false,
                'message' => 'Không thể duyệt bình luận'
            ], 500);
        }

        return $this->json([
            'success' => true,
            'message' => 'Đã duyệt bình luận'
        ]);
    }

    /**
     * Từ chối bình luận
     */
    public function rejectComment($commentId)
    {
        // Kiểm tra quyền admin/moderator
        if (!$this->authMiddleware->checkRole(['admin', 'moderator'])) {
            return $this->json([
                'success' => false,
                'message' => 'Không có quyền truy cập'
            ], 403);
        }

        if (!$this->commentModel->updateStatus($commentId, 'rejected')) {
            return $this->json([
                'success' => false,
                'message' => 'Không thể từ chối bình luận'
            ], 500);
        }

        return $this->json([
            'success' => true,
            'message' => 'Đã từ chối bình luận'
        ]);
    }

    /**
     * Xóa bình luận
     */
    public function deleteComment($commentId)
    {
        // Kiểm tra quyền admin/moderator
        if (!$this->authMiddleware->checkRole(['admin', 'moderator'])) {
            return $this->json([
                'success' => false,
                'message' => 'Không có quyền truy cập'
            ], 403);
        }

        if (!$this->commentModel->delete($commentId)) {
            return $this->json([
                'success' => false,
                'message' => 'Không thể xóa bình luận'
            ], 500);
        }

        return $this->json([
            'success' => true,
            'message' => 'Đã xóa bình luận'
        ]);
    }

    public function getNews()
    {
        try {
            $page = $_GET['page'] ?? 1;
            $limit = $_GET['limit'] ?? 10;
            $filters = $_GET;
            
            unset($filters['page']);
            unset($filters['limit']);
            
            $news = $this->newsModel->getAll($page, $limit, $filters);
            $total = $this->newsModel->getTotal($filters);
            
            return $this->jsonResponse([
                'success' => true,
                'data' => [
                    'news' => $news,
                    'pagination' => [
                        'current_page' => $page,
                        'per_page' => $limit,
                        'total' => $total,
                        'total_pages' => ceil($total / $limit)
                    ]
                ]
            ]);
        } catch (\Exception $e) {
            return $this->jsonResponse(['success' => false, 'message' => 'Failed to fetch news'], 500);
        }
    }

    public function getNewsById($id)
    {
        try {
            $news = $this->newsModel->getById($id);
            
            if (!$news) {
                return $this->jsonResponse(['success' => false, 'message' => 'News not found'], 404);
            }
            
            // Get comments
            $comments = $this->commentModel->getByNewsId($id);
            
            return $this->jsonResponse([
                'success' => true,
                'data' => [
                    'news' => $news,
                    'comments' => $comments
                ]
            ]);
        } catch (\Exception $e) {
            return $this->jsonResponse(['success' => false, 'message' => 'Failed to fetch news'], 500);
        }
    }

    private function jsonResponse($data, $statusCode = 200)
    {
        http_response_code($statusCode);
        header('Content-Type: application/json');
        echo json_encode($data);
        exit;
    }
}