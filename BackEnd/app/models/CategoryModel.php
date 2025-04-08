<?php

namespace App\Models;

use App\Core\Model;
use App\Core\Database;

class CategoryModel extends Model
{
    private $db;

    public function __construct()
    {
        parent::__construct();
        $this->db = Database::getInstance();
    }

    /**
     * Tạo danh mục mới
     */
    public function createCategory($data)
    {
        $sql = "INSERT INTO categories (name, slug, description, parent_id, type, created_at, updated_at) 
                VALUES (:name, :slug, :description, :parent_id, :type, NOW(), NOW())";
        
        $params = [
            ':name' => $data['name'],
            ':slug' => $data['slug'],
            ':description' => $data['description'] ?? null,
            ':parent_id' => $data['parent_id'] ?? null,
            ':type' => $data['type']
        ];

        return $this->db->insert($sql, $params);
    }

    /**
     * Cập nhật danh mục
     */
    public function updateCategory($id, $data)
    {
        $sql = "UPDATE categories 
                SET name = :name, 
                    slug = :slug, 
                    description = :description, 
                    parent_id = :parent_id,
                    updated_at = NOW() 
                WHERE id = :id";
        
        $params = [
            ':id' => $id,
            ':name' => $data['name'],
            ':slug' => $data['slug'],
            ':description' => $data['description'] ?? null,
            ':parent_id' => $data['parent_id'] ?? null
        ];

        return $this->db->update($sql, $params);
    }

    /**
     * Xóa danh mục
     */
    public function deleteCategory($id)
    {
        // Kiểm tra xem có danh mục con không
        $hasChildren = $this->hasChildren($id);
        if ($hasChildren) {
            return false;
        }

        // Kiểm tra xem có nội dung liên quan không
        $hasContent = $this->hasContent($id);
        if ($hasContent) {
            return false;
        }

        $sql = "DELETE FROM categories WHERE id = :id";
        return $this->db->delete($sql, [':id' => $id]);
    }

    /**
     * Lấy danh sách tất cả danh mục
     */
    public function getAllCategories()
    {
        $sql = "SELECT c.*, 
                       p.name as parent_name,
                       (SELECT COUNT(*) FROM categories WHERE parent_id = c.id) as child_count,
                       (SELECT COUNT(*) FROM games WHERE category_id = c.id) as game_count,
                       (SELECT COUNT(*) FROM news WHERE category_id = c.id) as news_count
                FROM categories c
                LEFT JOIN categories p ON c.parent_id = p.id
                ORDER BY c.type, c.parent_id, c.name";
        
        return $this->db->query($sql);
    }

    /**
     * Lấy danh sách danh mục theo loại
     */
    public function getCategoriesByType($type)
    {
        $sql = "SELECT c.*, 
                       p.name as parent_name,
                       (SELECT COUNT(*) FROM categories WHERE parent_id = c.id) as child_count,
                       (SELECT COUNT(*) FROM games WHERE category_id = c.id) as game_count,
                       (SELECT COUNT(*) FROM news WHERE category_id = c.id) as news_count
                FROM categories c
                LEFT JOIN categories p ON c.parent_id = p.id
                WHERE c.type = :type
                ORDER BY c.parent_id, c.name";
        
        return $this->db->query($sql, [':type' => $type]);
    }

    /**
     * Lấy danh sách danh mục con
     */
    public function getChildCategories($parentId)
    {
        $sql = "SELECT c.*, 
                       p.name as parent_name,
                       (SELECT COUNT(*) FROM categories WHERE parent_id = c.id) as child_count,
                       (SELECT COUNT(*) FROM games WHERE category_id = c.id) as game_count,
                       (SELECT COUNT(*) FROM news WHERE category_id = c.id) as news_count
                FROM categories c
                LEFT JOIN categories p ON c.parent_id = p.id
                WHERE c.parent_id = :parent_id
                ORDER BY c.name";
        
        return $this->db->query($sql, [':parent_id' => $parentId]);
    }

    /**
     * Lấy chi tiết danh mục
     */
    public function getCategoryDetail($id)
    {
        $sql = "SELECT c.*, 
                       p.name as parent_name,
                       (SELECT COUNT(*) FROM categories WHERE parent_id = c.id) as child_count,
                       (SELECT COUNT(*) FROM games WHERE category_id = c.id) as game_count,
                       (SELECT COUNT(*) FROM news WHERE category_id = c.id) as news_count
                FROM categories c
                LEFT JOIN categories p ON c.parent_id = p.id
                WHERE c.id = :id";
        
        return $this->db->queryOne($sql, [':id' => $id]);
    }

    /**
     * Kiểm tra danh mục có tồn tại không
     */
    public function exists($id)
    {
        $sql = "SELECT COUNT(*) as count FROM categories WHERE id = :id";
        $result = $this->db->queryOne($sql, [':id' => $id]);
        return $result['count'] > 0;
    }

    /**
     * Kiểm tra slug có tồn tại không
     */
    public function slugExists($slug, $excludeId = null)
    {
        $sql = "SELECT COUNT(*) as count FROM categories WHERE slug = :slug";
        $params = [':slug' => $slug];

        if ($excludeId) {
            $sql .= " AND id != :id";
            $params[':id'] = $excludeId;
        }

        $result = $this->db->queryOne($sql, $params);
        return $result['count'] > 0;
    }

    /**
     * Kiểm tra danh mục có danh mục con không
     */
    private function hasChildren($id)
    {
        $sql = "SELECT COUNT(*) as count FROM categories WHERE parent_id = :id";
        $result = $this->db->queryOne($sql, [':id' => $id]);
        return $result['count'] > 0;
    }

    /**
     * Kiểm tra danh mục có nội dung liên quan không
     */
    private function hasContent($id)
    {
        $sql = "SELECT 
                (SELECT COUNT(*) FROM games WHERE category_id = :id) +
                (SELECT COUNT(*) FROM news WHERE category_id = :id) as count";
        $result = $this->db->queryOne($sql, [':id' => $id]);
        return $result['count'] > 0;
    }

    /**
     * Lấy cấu trúc cây danh mục
     */
    public function getCategoryTree($type = null)
    {
        $sql = "SELECT c.*, 
                       p.name as parent_name,
                       (SELECT COUNT(*) FROM categories WHERE parent_id = c.id) as child_count,
                       (SELECT COUNT(*) FROM games WHERE category_id = c.id) as game_count,
                       (SELECT COUNT(*) FROM news WHERE category_id = c.id) as news_count
                FROM categories c
                LEFT JOIN categories p ON c.parent_id = p.id";
        
        $params = [];
        if ($type) {
            $sql .= " WHERE c.type = :type";
            $params[':type'] = $type;
        }
        
        $sql .= " ORDER BY c.type, c.parent_id, c.name";
        
        $categories = $this->db->query($sql, $params);
        return $this->buildTree($categories);
    }

    /**
     * Xây dựng cây danh mục
     */
    private function buildTree($categories, $parentId = null)
    {
        $tree = [];
        foreach ($categories as $category) {
            if ($category['parent_id'] == $parentId) {
                $children = $this->buildTree($categories, $category['id']);
                if ($children) {
                    $category['children'] = $children;
                }
                $tree[] = $category;
            }
        }
        return $tree;
    }

    // Lấy tất cả danh mục và số lượng tin
    public function getCategoriesWithCount() {
        $this->db->query('SELECT c.*, COUNT(n.id) as news_count 
                         FROM categories c 
                         LEFT JOIN news n ON c.id = n.category_id AND n.status = "published" 
                         GROUP BY c.id 
                         ORDER BY c.name ASC');
        
        return $this->db->resultSet();
    }

    // Lấy danh mục theo slug
    public function getCategoryBySlug($slug) {
        $this->db->query('SELECT * FROM categories WHERE slug = :slug');
        $this->db->bind(':slug', $slug);
        return $this->db->single();
    }

    // Lấy danh mục con
    public function getSubCategories($parentId) {
        $this->db->query('SELECT c.*, COUNT(n.id) as news_count 
                         FROM categories c 
                         LEFT JOIN news n ON c.id = n.category_id AND n.status = "published" 
                         WHERE c.parent_id = :parent_id 
                         GROUP BY c.id 
                         ORDER BY c.name ASC');
        
        $this->db->bind(':parent_id', $parentId);
        return $this->db->resultSet();
    }

    // Lấy danh mục gốc (không có parent)
    public function getRootCategories() {
        $this->db->query('SELECT c.*, COUNT(n.id) as news_count 
                         FROM categories c 
                         LEFT JOIN news n ON c.id = n.category_id AND n.status = "published" 
                         WHERE c.parent_id IS NULL 
                         GROUP BY c.id 
                         ORDER BY c.name ASC');
        
        return $this->db->resultSet();
    }

    // Lấy đường dẫn danh mục (breadcrumb)
    public function getCategoryPath($categoryId) {
        $path = [];
        $currentId = $categoryId;

        while ($currentId) {
            $this->db->query('SELECT id, name, slug, parent_id FROM categories WHERE id = :id');
            $this->db->bind(':id', $currentId);
            $category = $this->db->single();
            
            if ($category) {
                array_unshift($path, $category);
                $currentId = $category->parent_id;
            } else {
                break;
            }
        }

        return $path;
    }
} 