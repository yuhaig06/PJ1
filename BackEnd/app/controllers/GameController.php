<?php

namespace App\Controllers;

use App\Core\Controller;
use App\Models\GameModel;
use App\Models\ReviewModel;
use App\Config\Database;
use App\Middleware\AuthMiddleware;
use App\Middleware\CSRFMiddleware;
use App\Middleware\RateLimitMiddleware;

class GameController extends Controller
{
    private $db;
    private $gameModel;
    private $reviewModel;
    private $authMiddleware;
    private $csrfMiddleware;
    private $rateLimitMiddleware;

    public function __construct()
    {
        parent::__construct();
        $this->db = Database::getInstance()->getConnection();
        $this->gameModel = new GameModel($this->db);
        $this->reviewModel = new ReviewModel($this->db);
        $this->authMiddleware = new AuthMiddleware();
        $this->csrfMiddleware = new CSRFMiddleware();
        $this->rateLimitMiddleware = new RateLimitMiddleware();
    }

    /**
     * Lấy danh sách game theo danh mục
     */
    public function getGamesByCategory($categoryId)
    {
        // Validate dữ liệu
        $data = $this->validate([
            'limit' => 'nullable|integer|min:1|max:50',
            'offset' => 'nullable|integer|min:0'
        ]);

        $limit = $data['success'] ? ($data['data']['limit'] ?? 10) : 10;
        $offset = $data['success'] ? ($data['data']['offset'] ?? 0) : 0;

        $games = $this->gameModel->getGamesByCategory($categoryId, $limit, $offset);

        return $this->json([
            'success' => true,
            'data' => $games
        ]);
    }

    /**
     * Lấy danh sách game nổi bật
     */
    public function getFeaturedGames()
    {
        // Validate dữ liệu
        $data = $this->validate([
            'limit' => 'nullable|integer|min:1|max:50'
        ]);

        $limit = $data['success'] ? ($data['data']['limit'] ?? 10) : 10;

        $games = $this->gameModel->getFeaturedGames($limit);

        return $this->json([
            'success' => true,
            'data' => $games
        ]);
    }

    /**
     * Lấy danh sách game mới nhất
     */
    public function getLatestGames()
    {
        // Validate dữ liệu
        $data = $this->validate([
            'limit' => 'nullable|integer|min:1|max:50'
        ]);

        $limit = $data['success'] ? ($data['data']['limit'] ?? 10) : 10;

        $games = $this->gameModel->getLatestGames($limit);

        return $this->json([
            'success' => true,
            'data' => $games
        ]);
    }

    /**
     * Lấy danh sách game phổ biến
     */
    public function getPopularGames()
    {
        // Validate dữ liệu
        $data = $this->validate([
            'limit' => 'nullable|integer|min:1|max:50'
        ]);

        $limit = $data['success'] ? ($data['data']['limit'] ?? 10) : 10;

        $games = $this->gameModel->getPopularGames($limit);

        return $this->json([
            'success' => true,
            'data' => $games
        ]);
    }

    /**
     * Lấy danh sách game đang giảm giá
     */
    public function getDiscountedGames()
    {
        // Validate dữ liệu
        $data = $this->validate([
            'limit' => 'nullable|integer|min:1|max:50'
        ]);

        $limit = $data['success'] ? ($data['data']['limit'] ?? 10) : 10;

        $games = $this->gameModel->getDiscountedGames($limit);

        return $this->json([
            'success' => true,
            'data' => $games
        ]);
    }

    /**
     * Tìm kiếm game
     */
    public function searchGames()
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

        $games = $this->gameModel->searchGames($data['data']['keyword'], $limit, $offset);

        return $this->json([
            'success' => true,
            'data' => $games
        ]);
    }

    /**
     * Lấy chi tiết game
     */
    public function getGameDetail($id)
    {
        $game = $this->gameModel->getGameDetail($id);

        if (!$game) {
            return $this->json([
                'success' => false,
                'message' => 'Không tìm thấy game'
            ], 404);
        }

        // Tăng lượt xem
        $this->gameModel->incrementViews($id);

        // Lấy tags của game
        $game['tags'] = $this->gameModel->getGameTags($id);

        return $this->json([
            'success' => true,
            'data' => $game
        ]);
    }

    /**
     * Đánh giá game
     */
    public function rateGame($id)
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
            'rating' => 'required|integer|min:1|max:5',
            'comment' => 'nullable|max:500'
        ]);

        if (!$data['success']) {
            return $this->json([
                'success' => false,
                'message' => 'Dữ liệu không hợp lệ',
                'errors' => $data['errors']
            ], 422);
        }

        $userId = $_SESSION['user_id'];

        // Kiểm tra xem người dùng đã đánh giá game này chưa
        $existingRating = $this->gameModel->getGameRatings($id, 1, 0, $userId);
        if (!empty($existingRating)) {
            return $this->json([
                'success' => false,
                'message' => 'Bạn đã đánh giá game này rồi'
            ], 400);
        }

        // Thêm đánh giá
        $ratingId = $this->gameModel->addGameRating([
            'game_id' => $id,
            'user_id' => $userId,
            'rating' => $data['data']['rating'],
            'comment' => $data['data']['comment'] ?? null
        ]);

        if (!$ratingId) {
            return $this->json([
                'success' => false,
                'message' => 'Không thể thêm đánh giá'
            ], 500);
        }

        return $this->json([
            'success' => true,
            'message' => 'Đánh giá thành công'
        ]);
    }

    /**
     * Lấy danh sách đánh giá của game
     */
    public function getGameRatings($id)
    {
        // Validate dữ liệu
        $data = $this->validate([
            'limit' => 'nullable|integer|min:1|max:50',
            'offset' => 'nullable|integer|min:0'
        ]);

        $limit = $data['success'] ? ($data['data']['limit'] ?? 10) : 10;
        $offset = $data['success'] ? ($data['data']['offset'] ?? 0) : 0;

        $ratings = $this->gameModel->getGameRatings($id, $limit, $offset);

        return $this->json([
            'success' => true,
            'data' => $ratings
        ]);
    }

    /**
     * Tải game
     */
    public function downloadGame($id)
    {
        // Kiểm tra đăng nhập
        if (!$this->authMiddleware->check()) {
            return $this->json([
                'success' => false,
                'message' => 'Chưa đăng nhập'
            ], 401);
        }

        // Kiểm tra rate limit
        if (!$this->rateLimitMiddleware->checkLimit('download_game', 10, 3600)) {
            return $this->json([
                'success' => false,
                'message' => 'Quá nhiều yêu cầu tải game. Vui lòng thử lại sau.'
            ], 429);
        }

        $game = $this->gameModel->find($id);

        if (!$game) {
            return $this->json([
                'success' => false,
                'message' => 'Không tìm thấy game'
            ], 404);
        }

        // Kiểm tra quyền tải game
        $userId = $_SESSION['user_id'];
        if (!$this->gameModel->hasPurchased($id, $userId)) {
            return $this->json([
                'success' => false,
                'message' => 'Bạn chưa mua game này'
            ], 403);
        }

        // Ghi nhận lượt tải
        $this->gameModel->addGameDownload($id, $userId);

        return $this->json([
            'success' => true,
            'data' => [
                'download_url' => $game['download_url']
            ]
        ]);
    }

    /**
     * Lấy danh sách lượt tải của game
     */
    public function getGameDownloads($id)
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
            'offset' => 'nullable|integer|min:0'
        ]);

        $limit = $data['success'] ? ($data['data']['limit'] ?? 10) : 10;
        $offset = $data['success'] ? ($data['data']['offset'] ?? 0) : 0;

        $downloads = $this->gameModel->getGameDownloads($id, $limit, $offset);

        return $this->json([
            'success' => true,
            'data' => $downloads
        ]);
    }

    public function getGames()
    {
        try {
            $filters = $_GET;
            $games = $this->gameModel->getAll($filters);
            return $this->jsonResponse(['success' => true, 'data' => $games]);
        } catch (\Exception $e) {
            return $this->jsonResponse(['success' => false, 'message' => 'Failed to fetch games'], 500);
        }
    }

    public function getGameById($id)
    {
        try {
            $game = $this->gameModel->getById($id);
            
            if (!$game) {
                return $this->jsonResponse(['success' => false, 'message' => 'Game not found'], 404);
            }
            
            // Get reviews
            $reviews = $this->reviewModel->getByGameId($id);
            
            return $this->jsonResponse([
                'success' => true,
                'data' => [
                    'game' => $game,
                    'reviews' => $reviews
                ]
            ]);
        } catch (\Exception $e) {
            return $this->jsonResponse(['success' => false, 'message' => 'Failed to fetch game'], 500);
        }
    }

    public function addReview()
    {
        try {
            $data = json_decode(file_get_contents('php://input'), true);
            $userId = $_SESSION['user_id'] ?? null;
            
            if (!$userId) {
                return $this->jsonResponse(['success' => false, 'message' => 'Unauthorized'], 401);
            }
            
            if (!isset($data['gameId']) || !isset($data['rating']) || !isset($data['comment'])) {
                return $this->jsonResponse(['success' => false, 'message' => 'Game ID, rating and comment are required'], 400);
            }
            
            $game = $this->gameModel->getById($data['gameId']);
            
            if (!$game) {
                return $this->jsonResponse(['success' => false, 'message' => 'Game not found'], 404);
            }
            
            // Check if user already reviewed
            $existingReview = $this->reviewModel->getByUserAndGame($userId, $data['gameId']);
            
            if ($existingReview) {
                return $this->jsonResponse(['success' => false, 'message' => 'You have already reviewed this game'], 400);
            }
            
            $reviewData = [
                'user_id' => $userId,
                'game_id' => $data['gameId'],
                'rating' => $data['rating'],
                'comment' => $data['comment']
            ];
            
            $reviewId = $this->reviewModel->create($reviewData);
            
            if (!$reviewId) {
                return $this->jsonResponse(['success' => false, 'message' => 'Failed to add review'], 500);
            }
            
            // Update game rating
            $this->gameModel->updateRating($data['gameId']);
            
            $review = $this->reviewModel->getById($reviewId);
            
            return $this->jsonResponse([
                'success' => true,
                'message' => 'Review added successfully',
                'data' => $review
            ]);
        } catch (\Exception $e) {
            return $this->jsonResponse(['success' => false, 'message' => 'Failed to add review'], 500);
        }
    }

    public function updateReview($reviewId)
    {
        try {
            $data = json_decode(file_get_contents('php://input'), true);
            $userId = $_SESSION['user_id'] ?? null;
            
            if (!$userId) {
                return $this->jsonResponse(['success' => false, 'message' => 'Unauthorized'], 401);
            }
            
            if (!isset($data['rating']) || !isset($data['comment'])) {
                return $this->jsonResponse(['success' => false, 'message' => 'Rating and comment are required'], 400);
            }
            
            $review = $this->reviewModel->getById($reviewId);
            
            if (!$review) {
                return $this->jsonResponse(['success' => false, 'message' => 'Review not found'], 404);
            }
            
            if ($review['user_id'] !== $userId) {
                return $this->jsonResponse(['success' => false, 'message' => 'Unauthorized'], 403);
            }
            
            $reviewData = [
                'rating' => $data['rating'],
                'comment' => $data['comment']
            ];
            
            $this->reviewModel->update($reviewId, $reviewData);
            
            // Update game rating
            $this->gameModel->updateRating($review['game_id']);
            
            $updatedReview = $this->reviewModel->getById($reviewId);
            
            return $this->jsonResponse([
                'success' => true,
                'message' => 'Review updated successfully',
                'data' => $updatedReview
            ]);
        } catch (\Exception $e) {
            return $this->jsonResponse(['success' => false, 'message' => 'Failed to update review'], 500);
        }
    }

    public function deleteReview($reviewId)
    {
        try {
            $userId = $_SESSION['user_id'] ?? null;
            
            if (!$userId) {
                return $this->jsonResponse(['success' => false, 'message' => 'Unauthorized'], 401);
            }
            
            $review = $this->reviewModel->getById($reviewId);
            
            if (!$review) {
                return $this->jsonResponse(['success' => false, 'message' => 'Review not found'], 404);
            }
            
            if ($review['user_id'] !== $userId) {
                return $this->jsonResponse(['success' => false, 'message' => 'Unauthorized'], 403);
            }
            
            $this->reviewModel->delete($reviewId);
            
            // Update game rating
            $this->gameModel->updateRating($review['game_id']);
            
            return $this->jsonResponse([
                'success' => true,
                'message' => 'Review deleted successfully'
            ]);
        } catch (\Exception $e) {
            return $this->jsonResponse(['success' => false, 'message' => 'Failed to delete review'], 500);
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