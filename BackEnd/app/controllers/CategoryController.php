<?php

namespace App\Controllers;

use App\Core\Controller;
use App\Models\CategoryModel;
use App\Middleware\AuthMiddleware;
use App\Middleware\CSRFMiddleware;
use App\Middleware\RateLimitMiddleware;

class CategoryController extends Controller
{
    private $categoryModel;
    private $authMiddleware;
    private $csrfMiddleware;
    private $rateLimitMiddleware;

    public function __construct()
    {
        parent::__construct();
        $this->categoryModel = new CategoryModel();
        $this->authMiddleware = new AuthMiddleware();
        $this->csrfMiddleware = new CSRFMiddleware();
        $this->rateLimitMiddleware = new RateLimitMiddleware();
    }

    /**
     * Lấy danh sách tất cả danh mục
     */
    public function getAllCategories()
    {
        $categories = $this->categoryModel->getAllCategories();

        return $this->json([
            'success' => true,
            'data' => $categories
        ]);
    }

    /**
     * Lấy danh sách danh mục theo loại
     */
    public function getCategoriesByType($type)
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

        $categories = $this->categoryModel->getCategoriesByType($type);

        return $this->json([
            'success' => true,
            'data' => $categories
        ]);
    }

    /**
     * Lấy danh sách danh mục con
     */
    public function getChildCategories($parentId)
    {
        // Validate dữ liệu
        $data = $this->validate([
            'parent_id' => 'required|integer|min:1'
        ]);

        if (!$data['success']) {
            return $this->json([
                'success' => false,
                'message' => 'Dữ liệu không hợp lệ',
                'errors' => $data['errors']
            ], 422);
        }

        $categories = $this->categoryModel->getChildCategories($parentId);

        return $this->json([
            'success' => true,
            'data' => $categories
        ]);
    }

    /**
     * Lấy chi tiết danh mục
     */
    public function getCategoryDetail($id)
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

        $category = $this->categoryModel->getCategoryDetail($id);
        if (!$category) {
            return $this->json([
                'success' => false,
                'message' => 'Không tìm thấy danh mục'
            ], 404);
        }

        return $this->json([
            'success' => true,
            'data' => $category
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
            'parent_id' => 'nullable|integer|min:1',
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
        if ($this->categoryModel->slugExists($data['data']['slug'])) {
            return $this->json([
                'success' => false,
                'message' => 'Slug đã tồn tại'
            ], 422);
        }

        // Kiểm tra danh mục cha tồn tại
        if (!empty($data['data']['parent_id'])) {
            if (!$this->categoryModel->exists($data['data']['parent_id'])) {
                return $this->json([
                    'success' => false,
                    'message' => 'Danh mục cha không tồn tại'
                ], 422);
            }
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
            'parent_id' => 'nullable|integer|min:1'
        ]);

        if (!$data['success']) {
            return $this->json([
                'success' => false,
                'message' => 'Dữ liệu không hợp lệ',
                'errors' => $data['errors']
            ], 422);
        }

        // Kiểm tra danh mục tồn tại
        if (!$this->categoryModel->exists($id)) {
            return $this->json([
                'success' => false,
                'message' => 'Không tìm thấy danh mục'
            ], 404);
        }

        // Kiểm tra slug trùng lặp
        if ($this->categoryModel->slugExists($data['data']['slug'], $id)) {
            return $this->json([
                'success' => false,
                'message' => 'Slug đã tồn tại'
            ], 422);
        }

        // Kiểm tra danh mục cha tồn tại
        if (!empty($data['data']['parent_id'])) {
            if (!$this->categoryModel->exists($data['data']['parent_id'])) {
                return $this->json([
                    'success' => false,
                    'message' => 'Danh mục cha không tồn tại'
                ], 422);
            }

            // Kiểm tra không cho phép chọn chính nó làm danh mục cha
            if ($data['data']['parent_id'] == $id) {
                return $this->json([
                    'success' => false,
                    'message' => 'Không thể chọn chính nó làm danh mục cha'
                ], 422);
            }
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

        // Kiểm tra danh mục tồn tại
        if (!$this->categoryModel->exists($id)) {
            return $this->json([
                'success' => false,
                'message' => 'Không tìm thấy danh mục'
            ], 404);
        }

        $success = $this->categoryModel->deleteCategory($id);
        if (!$success) {
            return $this->json([
                'success' => false,
                'message' => 'Không thể xóa danh mục. Danh mục có thể có danh mục con hoặc nội dung liên quan'
            ], 400);
        }

        return $this->json([
            'success' => true,
            'message' => 'Xóa danh mục thành công'
        ]);
    }

    /**
     * Lấy cấu trúc cây danh mục
     */
    public function getCategoryTree()
    {
        // Validate dữ liệu
        $data = $this->validate([
            'type' => 'nullable|in:game,news'
        ]);

        $type = $data['success'] ? ($data['data']['type'] ?? null) : null;

        $tree = $this->categoryModel->getCategoryTree($type);

        return $this->json([
            'success' => true,
            'data' => $tree
        ]);
    }
} 