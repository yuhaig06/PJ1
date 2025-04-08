<?php

namespace App\Models;

class ProductModel
{
    private $db;

    public function __construct($db)
    {
        $this->db = $db;
    }

    /**
     * Lấy tất cả sản phẩm
     * @return array Danh sách sản phẩm
     */
    public function getAll($filters = [])
    {
        $sql = "SELECT * FROM products WHERE 1=1";
        $params = [];
        
        if (isset($filters['category'])) {
            $sql .= " AND category = :category";
            $params[':category'] = $filters['category'];
        }
        
        if (isset($filters['search'])) {
            $sql .= " AND (name LIKE :search OR description LIKE :search)";
            $params[':search'] = "%{$filters['search']}%";
        }
        
        if (isset($filters['min_price'])) {
            $sql .= " AND price >= :min_price";
            $params[':min_price'] = $filters['min_price'];
        }
        
        if (isset($filters['max_price'])) {
            $sql .= " AND price <= :max_price";
            $params[':max_price'] = $filters['max_price'];
        }
        
        if (isset($filters['sort'])) {
            switch ($filters['sort']) {
                case 'price_asc':
                    $sql .= " ORDER BY price ASC";
                    break;
                case 'price_desc':
                    $sql .= " ORDER BY price DESC";
                    break;
                case 'name_asc':
                    $sql .= " ORDER BY name ASC";
                    break;
                case 'name_desc':
                    $sql .= " ORDER BY name DESC";
                    break;
                default:
                    $sql .= " ORDER BY created_at DESC";
            }
        } else {
            $sql .= " ORDER BY created_at DESC";
        }
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        
        return $stmt->fetchAll(\PDO::FETCH_ASSOC);
    }

    /**
     * Lấy sản phẩm theo ID
     * @param int $id ID sản phẩm
     * @return object Thông tin sản phẩm
     */
    public function getById($id)
    {
        $sql = "SELECT * FROM products WHERE id = :id";
        $stmt = $this->db->prepare($sql);
        $stmt->bindParam(':id', $id);
        $stmt->execute();
        
        return $stmt->fetch(\PDO::FETCH_ASSOC);
    }

    /**
     * Lấy sản phẩm theo danh mục
     * @param int $categoryId ID danh mục
     * @return array Danh sách sản phẩm
     */
    public function getProductsByCategory($categoryId)
    {
        $this->db->query('SELECT * FROM products WHERE category_id = :category_id ORDER BY created_at DESC');
        $this->db->bind(':category_id', $categoryId);
        return $this->db->resultSet();
    }

    /**
     * Thêm sản phẩm mới
     * @param array $data Dữ liệu sản phẩm
     * @return bool Kết quả thêm sản phẩm
     */
    public function create($data)
    {
        $sql = "INSERT INTO products (name, description, price, stock, category, image_url, created_at) 
                VALUES (:name, :description, :price, :stock, :category, :image_url, NOW())";
        
        $stmt = $this->db->prepare($sql);
        
        $stmt->bindParam(':name', $data['name']);
        $stmt->bindParam(':description', $data['description']);
        $stmt->bindParam(':price', $data['price']);
        $stmt->bindParam(':stock', $data['stock']);
        $stmt->bindParam(':category', $data['category']);
        $stmt->bindParam(':image_url', $data['image_url']);
        
        if ($stmt->execute()) {
            return $this->db->lastInsertId();
        }
        
        return false;
    }

    /**
     * Cập nhật thông tin sản phẩm
     * @param array $data Dữ liệu sản phẩm
     * @return bool Kết quả cập nhật sản phẩm
     */
    public function update($id, $data)
    {
        $fields = [];
        $values = [];
        
        foreach ($data as $key => $value) {
            $fields[] = "$key = :$key";
            $values[":$key"] = $value;
        }
        
        $sql = "UPDATE products SET " . implode(', ', $fields) . " WHERE id = :id";
        $values[':id'] = $id;
        
        $stmt = $this->db->prepare($sql);
        return $stmt->execute($values);
    }

    /**
     * Xóa sản phẩm
     * @param int $id ID sản phẩm
     * @return bool Kết quả xóa sản phẩm
     */
    public function delete($id)
    {
        $sql = "DELETE FROM products WHERE id = :id";
        $stmt = $this->db->prepare($sql);
        
        $stmt->bindParam(':id', $id);
        
        return $stmt->execute();
    }

    /**
     * Cập nhật số lượng tồn kho
     * @param int $id ID sản phẩm
     * @param int $quantity Số lượng mua
     * @return bool Kết quả cập nhật
     */
    public function updateStock($id, $change)
    {
        $sql = "UPDATE products SET stock = stock + :change WHERE id = :id";
        $stmt = $this->db->prepare($sql);
        
        $stmt->bindParam(':change', $change);
        $stmt->bindParam(':id', $id);
        
        return $stmt->execute();
    }

    /**
     * Tìm kiếm sản phẩm
     * @param string $keyword Từ khóa tìm kiếm
     * @return array Danh sách sản phẩm
     */
    public function searchProducts($keyword)
    {
        $this->db->query('SELECT * FROM products WHERE name LIKE :keyword OR description LIKE :keyword ORDER BY created_at DESC');
        $this->db->bind(':keyword', '%' . $keyword . '%');
        return $this->db->resultSet();
    }

    /**
     * Lấy danh sách danh mục sản phẩm
     * @return array Danh sách danh mục
     */
    public function getCategories()
    {
        $this->db->query('SELECT * FROM product_categories ORDER BY name ASC');
        return $this->db->resultSet();
    }

    /**
     * Lấy danh mục theo ID
     * @param int $id ID danh mục
     * @return object Thông tin danh mục
     */
    public function getCategoryById($id)
    {
        $this->db->query('SELECT * FROM product_categories WHERE id = :id');
        $this->db->bind(':id', $id);
        return $this->db->single();
    }
} 