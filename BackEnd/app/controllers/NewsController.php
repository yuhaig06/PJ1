<?php

namespace App\Controllers;

use App\Core\Controller;
use App\Models\NewsModel;
use App\Models\CommentModel;
use App\Config\Database;
use App\Middleware\AuthMiddleware;
use App\Middleware\CSRFMiddleware;
use App\Middleware\RateLimitMiddleware;
use App\Traits\JsonResponseTrait;
use App\Traits\ValidationTrait;
use App\Core\Validator;
use PDO;

class NewsController extends Controller
{
    use JsonResponseTrait, ValidationTrait;

    /** @var PDO */
    private $dbConnection;
    private NewsModel $newsModel;
    private CommentModel $commentModel;
    private AuthMiddleware $authMiddleware;
    private CSRFMiddleware $csrfMiddleware;
    private RateLimitMiddleware $rateLimitMiddleware;

    public function __construct()
    {
        parent::__construct();
        $this->dbConnection = Database::getInstance()->getConnection();
        $this->newsModel = new NewsModel($this->dbConnection);
        $this->commentModel = new CommentModel($this->dbConnection);
        $this->authMiddleware = new AuthMiddleware();
        $this->csrfMiddleware = new CSRFMiddleware();
        $this->rateLimitMiddleware = new RateLimitMiddleware();
    }

    protected function validatePaginationParams($params) {
        $limit = isset($params['limit']) ? filter_var($params['limit'], FILTER_VALIDATE_INT) : 10;
        $offset = isset($params['offset']) ? filter_var($params['offset'], FILTER_VALIDATE_INT) : 0;

        // Validate and sanitize values
        if ($limit === false || $limit < 1) $limit = 10;
        if ($limit > 50) $limit = 50;
        if ($offset === false || $offset < 0) $offset = 0;

        return [
            'limit' => $limit,
            'offset' => $offset
        ];
    }

    /**
     * Lấy danh sách tin tức theo danh mục
     */
    public function getNewsByCategory($categoryId)
    {
        // Validate pagination params
        $params = $this->validatePaginationParams($_GET);
        
        $news = $this->newsModel->getNewsByCategory(
            $categoryId, 
            $params['limit'], 
            $params['offset']
        );
        
        return $this->json([
            'status' => 'success',
            'data' => $news
        ]);
    }

    /**
     * Lấy danh sách tin tức nổi bật
     */
    public function getFeaturedNews()
    {
        $params = $this->validatePaginationParams($_GET);
        $news = $this->newsModel->getFeaturedNews($params['limit']);
        
        return $this->json([
            'status' => 'success',
            'data' => $news
        ]);
    }

    /**
     * Lấy danh sách tin tức mới nhất
     */
    public function getLatestNews()
    {
        $params = $this->validatePaginationParams($_GET);
        
        $news = $this->newsModel->getLatestNews($params['limit']);
        return $this->json([
            'status' => 'success',
            'data' => $news,
            'meta' => [
                'current_page' => isset($_GET['page']) ? (int)$_GET['page'] : 1,
                'per_page' => $params['limit'],
                'total' => count($news)
            ]
        ]);
    }

    /**
     * Lấy danh sách tin tức phổ biến
     */
    public function getPopularNews()
    {
        $params = $this->validatePaginationParams($_GET);
        $news = $this->newsModel->getPopularNews($params['limit']);

        return $this->json([
            'status' => 'success',
            'data' => $news
        ]);
    }

    /**
     * Tìm kiếm tin tức
     */
    public function searchNews()
    {
        // Get parameters
        $keyword = $_GET['keyword'] ?? '';
        $limit = isset($_GET['limit']) ? (int)$_GET['limit'] : 10;
        $offset = isset($_GET['offset']) ? (int)$_GET['offset'] : 0;

        // Validate parameters
        if (empty($keyword) || strlen($keyword) < 2) {
            return $this->json([
                'status' => 'error',
                'message' => 'Từ khóa tìm kiếm phải có ít nhất 2 ký tự'
            ], 422);
        }

        if ($limit < 1 || $limit > 50) {
            $limit = 10; // Set default if invalid
        }

        if ($offset < 0) {
            $offset = 0; // Set default if invalid
        }

        // Get news data
        $news = $this->newsModel->searchNews($keyword, $limit, $offset);

        return $this->json([
            'status' => 'success',
            'data' => $news
        ]);
    }

    /**
     * Lấy chi tiết tin tức
     */
    public function getNewsDetail($id)
    {
        try {
            $news = $this->newsModel->find($id);
            
            if (!$news) {
                return $this->json([
                    'status' => 'error',
                    'message' => 'News not found'
                ], 404);
            }
            
            // Increment views
            $this->newsModel->incrementViews($id);
            
            // Get related data
            $news['tags'] = $this->newsModel->getNewsTags($id);
            $news['comments'] = $this->commentModel->getByNewsId($id);
            $news['related_news'] = $this->newsModel->getRelatedNews($id);
            
            return $this->json([
                'status' => 'success',
                'data' => $news
            ]);

        } catch (\Exception $e) {
            return $this->json([
                'status' => 'error',
                'message' => 'Internal server error'
            ], 500);
        }
    }

    private function checkUserLoggedIn() {
        return isset($_SESSION['user_id']) && !empty($_SESSION['user_id']);
    }

    private function checkUserRole($roles = ['admin', 'moderator']) {
        if (!$this->checkUserLoggedIn() || !isset($_SESSION['role'])) {
            return false;
        }
        return in_array($_SESSION['role'], $roles);
    }

    private function requireAuth() {
        if (!$this->checkUserLoggedIn()) {
            return $this->json([
                'status' => 'error',
                'message' => 'Bạn cần đăng nhập để thực hiện hành động này'
            ], 401);
        }
        return true;
    }

    public function addComment($newsId)
    {
        $authCheck = $this->requireAuth();
        if ($authCheck !== true) {
            return $authCheck;
        }

        if (!$this->rateLimitMiddleware->checkLimit('add_comment', 10, 300)) {
            return $this->json([
                'status' => 'error',
                'message' => 'Too many comments. Please try again later.'
            ], 429);
        }

        $content = trim($_POST['content'] ?? '');
        $parentId = isset($_POST['parent_id']) ? (int)$_POST['parent_id'] : null;

        if (empty($content)) {
            return $this->json([
                'status' => 'error',
                'message' => 'Nội dung bình luận không được để trống'
            ], 422);
        }

        $userId = $_SESSION['user_id'];
        $commentId = $this->commentModel->create([
            'news_id' => $newsId,
            'user_id' => $userId,
            'parent_id' => $parentId,
            'content' => $content,
            'status' => 'pending'
        ]);

        if (!$commentId) {
            return $this->json([
                'status' => 'error',
                'message' => 'Không thể thêm bình luận'
            ], 500);
        }

        $comment = $this->commentModel->getById($commentId);
        return $this->json([
            'status' => 'success', 
            'message' => 'Bình luận đã được gửi và đang chờ duyệt',
            'data' => $comment
        ]);
    }

    /**
     * Lấy danh sách bình luận
     */
    public function getComments($newsId)
    {
        $params = $this->validatePaginationParams($_GET);
        $comments = $this->commentModel->getByNewsId($newsId, $params['limit'], $params['offset']);

        return $this->json([
            'status' => 'success',
            'data' => $comments
        ]);
    }

    /**
     * Duyệt bình luận
     */
    private function isAuthorized() {
        if (!$this->checkUserLoggedIn() || !isset($_SESSION['role'])) {
            return false;
        }
        return in_array($_SESSION['role'], ['admin', 'moderator']);
    }

    public function approveComment($commentId)
    {
        if (!$this->isAuthorized()) {
            return $this->json([
                'status' => 'error',
                'message' => 'Bạn không có quyền thực hiện hành động này'
            ], 403);
        }

        try {
            $success = $this->commentModel->update($commentId, ['status' => 'approved']);
            
            return $this->json([
                'status' => $success ? 'success' : 'error',
                'message' => $success ? 'Comment approved' : 'Failed to approve comment'
            ]);
        } catch (\Exception $e) {
            return $this->json([
                'status' => 'error',
                'message' => 'Internal server error'
            ], 500);
        }
    }

    /**
     * Từ chối bình luận
     */
    public function rejectComment($commentId)
    {
        if (!$this->isAuthorized()) {
            return $this->json([
                'status' => 'error',
                'message' => 'Bạn không có quyền thực hiện hành động này'
            ], 403);
        }

        try {
            $success = $this->commentModel->update($commentId, ['status' => 'rejected']);
            
            return $this->json([
                'status' => $success ? 'success' : 'error',
                'message' => $success ? 'Comment rejected' : 'Failed to reject comment'
            ]);
        } catch (\Exception $e) {
            return $this->json([
                'status' => 'error',
                'message' => 'Internal server error'
            ], 500);
        }
    }

    /**
     * Xóa bình luận
     */
    public function deleteComment($commentId)
    {
        if (!$this->isAuthorized()) {
            return $this->json([
                'status' => 'error',
                'message' => 'Bạn không có quyền thực hiện hành động này'
            ], 403);
        }

        try {
            $success = $this->commentModel->delete($commentId);
            
            return $this->json([
                'status' => $success ? 'success' : 'error',
                'message' => $success ? 'Comment deleted' : 'Failed to delete comment'
            ]);
        } catch (\Exception $e) {
            return $this->json([
                'status' => 'error',
                'message' => 'Internal server error'
            ], 500);
        }
    }

    public function getNews()
    {
        try {
            $params = $this->validatePaginationParams($_GET);
            $filters = array_diff_key($_GET, array_flip(['limit', 'offset']));
            
            $news = $this->newsModel->getAll($params['offset'], $params['limit'], $filters);
            $total = $this->newsModel->getTotal($filters);
            
            return $this->json([
                'status' => 'success',
                'data' => [
                    'news' => $news,
                    'meta' => [
                        'current_page' => floor($params['offset'] / $params['limit']) + 1,
                        'per_page' => $params['limit'],
                        'total' => $total,
                        'total_pages' => ceil($total / $params['limit'])
                    ]
                ]
            ]);
        } catch (\Exception $e) {
            return $this->json([
                'status' => 'error',
                'message' => 'Internal server error'
            ], 500);
        }
    }
}