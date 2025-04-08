<?php

namespace App\Controllers;

use App\Core\Controller;
use App\Models\TagModel;
use App\Middleware\AuthMiddleware;
use App\Middleware\CSRFMiddleware;
use App\Middleware\RateLimitMiddleware;

class TagController extends Controller
{
    private $tagModel;
    private $authMiddleware;
    private $csrfMiddleware;
    private $rateLimitMiddleware;

    public function __construct()
    {
        parent::__construct();
        $this->tagModel = new TagModel();
        $this->authMiddleware = new AuthMiddleware();
        $this->csrfMiddleware = new CSRFMiddleware();
        $this->rateLimitMiddleware = new RateLimitMiddleware();
    }

    /**
     * Lấy danh sách tất cả thẻ
     */
    public function getAllTags()
    {
        $tags = $this->tagModel->getAllTags();

        return $this->json([
            'success' => true,
            'data' => $tags
        ]);
    }

    /**
     * Lấy danh sách thẻ theo loại
     */
    public function getTagsByType($type)
    {
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

        $tags = $this->tagModel->getTagsByType($type);

        return $this->json([
            'success' => true,
            'data' => $tags
        ]);
    }

    /**
     * Lấy chi tiết thẻ
     */
    public function getTagDetail($id)
    {
        // Validate dữ liệu
        $data = $this->validate([
            'id' => 'required|integer|min:1'
        ]);

        if (!$data['success']) {
            return $this->json([
                'success' => false,
                'message' => 'Dữ liệu không hợp lệ',
                'errors' => $data['errors']
            ], 422);
        }

        $tag = $this->tagModel->getTagDetail($id);
        if (!$tag) {
            return $this->json([
                'success' => false,
                'message' => 'Không tìm thấy thẻ'
            ], 404);
        }

        return $this->json([
            'success' => true,
            'data' => $tag
        ]);
    }

    /**
     * Tạo thẻ mới
     */
    public function createTag()
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
            'type' => 'required|in:game,news'
        ]);

        if (!$data['success']) {
            return $this->json([
                'success' => false,
                'message' => 'Dữ liệu không hợp lệ',
                'errors' => $data['errors']
            ], 422);
        }

        // Kiểm tra slug trùng lặp
        if ($this->tagModel->slugExists($data['data']['slug'])) {
            return $this->json([
                'success' => false,
                'message' => 'Slug đã tồn tại'
            ], 422);
        }

        $tagId = $this->tagModel->createTag($data['data']);
        if (!$tagId) {
            return $this->json([
                'success' => false,
                'message' => 'Không thể tạo thẻ'
            ], 500);
        }

        return $this->json([
            'success' => true,
            'message' => 'Tạo thẻ thành công',
            'data' => ['tag_id' => $tagId]
        ]);
    }

    /**
     * Cập nhật thẻ
     */
    public function updateTag($id)
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
            'description' => 'nullable|string|max:255'
        ]);

        if (!$data['success']) {
            return $this->json([
                'success' => false,
                'message' => 'Dữ liệu không hợp lệ',
                'errors' => $data['errors']
            ], 422);
        }

        // Kiểm tra thẻ tồn tại
        if (!$this->tagModel->exists($id)) {
            return $this->json([
                'success' => false,
                'message' => 'Không tìm thấy thẻ'
            ], 404);
        }

        // Kiểm tra slug trùng lặp
        if ($this->tagModel->slugExists($data['data']['slug'], $id)) {
            return $this->json([
                'success' => false,
                'message' => 'Slug đã tồn tại'
            ], 422);
        }

        $success = $this->tagModel->updateTag($id, $data['data']);
        if (!$success) {
            return $this->json([
                'success' => false,
                'message' => 'Không thể cập nhật thẻ'
            ], 500);
        }

        return $this->json([
            'success' => true,
            'message' => 'Cập nhật thẻ thành công'
        ]);
    }

    /**
     * Xóa thẻ
     */
    public function deleteTag($id)
    {
        // Kiểm tra quyền admin
        if (!$this->authMiddleware->checkRole('admin')) {
            return $this->json([
                'success' => false,
                'message' => 'Không có quyền truy cập'
            ], 403);
        }

        // Kiểm tra thẻ tồn tại
        if (!$this->tagModel->exists($id)) {
            return $this->json([
                'success' => false,
                'message' => 'Không tìm thấy thẻ'
            ], 404);
        }

        $success = $this->tagModel->deleteTag($id);
        if (!$success) {
            return $this->json([
                'success' => false,
                'message' => 'Không thể xóa thẻ. Thẻ có thể có nội dung liên quan'
            ], 400);
        }

        return $this->json([
            'success' => true,
            'message' => 'Xóa thẻ thành công'
        ]);
    }

    /**
     * Lấy danh sách thẻ phổ biến
     */
    public function getPopularTags()
    {
        // Validate dữ liệu
        $data = $this->validate([
            'type' => 'nullable|in:game,news',
            'limit' => 'nullable|integer|min:1|max:50'
        ]);

        $type = $data['success'] ? ($data['data']['type'] ?? null) : null;
        $limit = $data['success'] ? ($data['data']['limit'] ?? 10) : 10;

        $tags = $this->tagModel->getPopularTags($type, $limit);

        return $this->json([
            'success' => true,
            'data' => $tags
        ]);
    }

    /**
     * Tìm kiếm thẻ
     */
    public function searchTags()
    {
        // Validate dữ liệu
        $data = $this->validate([
            'keyword' => 'required|string|min:2',
            'type' => 'nullable|in:game,news',
            'limit' => 'nullable|integer|min:1|max:50'
        ]);

        if (!$data['success']) {
            return $this->json([
                'success' => false,
                'message' => 'Dữ liệu không hợp lệ',
                'errors' => $data['errors']
            ], 422);
        }

        $type = $data['data']['type'] ?? null;
        $limit = $data['data']['limit'] ?? 10;

        $tags = $this->tagModel->searchTags($data['data']['keyword'], $type, $limit);

        return $this->json([
            'success' => true,
            'data' => $tags
        ]);
    }

    /**
     * Lấy danh sách thẻ của game
     */
    public function getGameTags($gameId)
    {
        // Validate dữ liệu
        $data = $this->validate([
            'game_id' => 'required|integer|min:1'
        ]);

        if (!$data['success']) {
            return $this->json([
                'success' => false,
                'message' => 'Dữ liệu không hợp lệ',
                'errors' => $data['errors']
            ], 422);
        }

        $tags = $this->tagModel->getGameTags($gameId);

        return $this->json([
            'success' => true,
            'data' => $tags
        ]);
    }

    /**
     * Lấy danh sách thẻ của tin tức
     */
    public function getNewsTags($newsId)
    {
        // Validate dữ liệu
        $data = $this->validate([
            'news_id' => 'required|integer|min:1'
        ]);

        if (!$data['success']) {
            return $this->json([
                'success' => false,
                'message' => 'Dữ liệu không hợp lệ',
                'errors' => $data['errors']
            ], 422);
        }

        $tags = $this->tagModel->getNewsTags($newsId);

        return $this->json([
            'success' => true,
            'data' => $tags
        ]);
    }

    /**
     * Cập nhật thẻ cho game
     */
    public function updateGameTags($gameId)
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
            'tag_ids' => 'required|array',
            'tag_ids.*' => 'integer|min:1'
        ]);

        if (!$data['success']) {
            return $this->json([
                'success' => false,
                'message' => 'Dữ liệu không hợp lệ',
                'errors' => $data['errors']
            ], 422);
        }

        $success = $this->tagModel->updateGameTags($gameId, $data['data']['tag_ids']);
        if (!$success) {
            return $this->json([
                'success' => false,
                'message' => 'Không thể cập nhật thẻ cho game'
            ], 500);
        }

        return $this->json([
            'success' => true,
            'message' => 'Cập nhật thẻ cho game thành công'
        ]);
    }

    /**
     * Cập nhật thẻ cho tin tức
     */
    public function updateNewsTags($newsId)
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
            'tag_ids' => 'required|array',
            'tag_ids.*' => 'integer|min:1'
        ]);

        if (!$data['success']) {
            return $this->json([
                'success' => false,
                'message' => 'Dữ liệu không hợp lệ',
                'errors' => $data['errors']
            ], 422);
        }

        $success = $this->tagModel->updateNewsTags($newsId, $data['data']['tag_ids']);
        if (!$success) {
            return $this->json([
                'success' => false,
                'message' => 'Không thể cập nhật thẻ cho tin tức'
            ], 500);
        }

        return $this->json([
            'success' => true,
            'message' => 'Cập nhật thẻ cho tin tức thành công'
        ]);
    }
} 