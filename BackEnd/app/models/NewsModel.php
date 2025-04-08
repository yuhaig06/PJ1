<?php

namespace App\Models;

use App\Core\Model;
use PDO;

class NewsModel extends Model
{
    protected $table = 'news';
    protected $fillable = [
        'title', 'slug', 'content', 'excerpt', 'category_id',
        'author_id', 'thumbnail', 'gallery', 'views',
        'status', 'featured', 'published_at'
    ];

    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Lấy danh sách tin tức theo danh mục
     */
    public function getNewsByCategory($categoryId, $limit = null, $offset = null)
    {
        $sql = "SELECT n.*, u.username as author_name, nc.name as category_name 
                FROM news n 
                JOIN users u ON n.author_id = u.id 
                JOIN news_categories nc ON n.category_id = nc.id 
                WHERE n.category_id = ? AND n.status = 'published' 
                AND n.published_at <= NOW()";
        
        $params = [$categoryId];

        if ($limit !== null) {
            $sql .= " LIMIT ?";
            $params[] = $limit;
        }

        if ($offset !== null) {
            $sql .= " OFFSET ?";
            $params[] = $offset;
        }

        $stmt = $this->query($sql, $params);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Lấy danh sách tin tức nổi bật
     */
    public function getFeaturedNews($limit = 10)
    {
        $sql = "SELECT n.*, u.username as author_name, nc.name as category_name 
                FROM news n 
                JOIN users u ON n.author_id = u.id 
                JOIN news_categories nc ON n.category_id = nc.id 
                WHERE n.status = 'published' 
                AND n.featured = 1 
                AND n.published_at <= NOW() 
                ORDER BY n.published_at DESC 
                LIMIT ?";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$limit]);
        return $stmt->fetchAll();
    }

    /**
     * Lấy danh sách tin tức mới nhất
     */
    public function getLatestNews($limit = 10)
    {
        $sql = "SELECT n.*, u.username as author_name, nc.name as category_name 
                FROM news n 
                JOIN users u ON n.author_id = u.id 
                JOIN news_categories nc ON n.category_id = nc.id 
                WHERE n.status = 'published' 
                AND n.published_at <= NOW() 
                ORDER BY n.published_at DESC 
                LIMIT ?";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$limit]);
        return $stmt->fetchAll();
    }

    /**
     * Lấy danh sách tin tức phổ biến
     */
    public function getPopularNews($limit = 10)
    {
        $sql = "SELECT n.*, u.username as author_name, nc.name as category_name 
                FROM news n 
                JOIN users u ON n.author_id = u.id 
                JOIN news_categories nc ON n.category_id = nc.id 
                WHERE n.status = 'published' 
                AND n.published_at <= NOW() 
                ORDER BY n.views DESC 
                LIMIT ?";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$limit]);
        return $stmt->fetchAll();
    }

    /**
     * Tìm kiếm tin tức
     */
    public function searchNews($keyword, $limit = null, $offset = null)
    {
        $sql = "SELECT n.*, u.username as author_name, nc.name as category_name 
                FROM news n 
                JOIN users u ON n.author_id = u.id 
                JOIN news_categories nc ON n.category_id = nc.id 
                WHERE n.status = 'published' 
                AND n.published_at <= NOW() 
                AND (n.title LIKE ? OR n.content LIKE ?)";
        
        $params = ["%{$keyword}%", "%{$keyword}%"];

        if ($limit !== null) {
            $sql .= " LIMIT ?";
            $params[] = $limit;
        }

        if ($offset !== null) {
            $sql .= " OFFSET ?";
            $params[] = $offset;
        }

        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }

    /**
     * Lấy thông tin tin tức chi tiết
     */
    public function getNewsDetail($id)
    {
        $sql = "SELECT n.*, u.username as author_name, nc.name as category_name 
                FROM news n 
                JOIN users u ON n.author_id = u.id 
                JOIN news_categories nc ON n.category_id = nc.id 
                WHERE n.id = ?";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$id]);
        return $stmt->fetch();
    }

    /**
     * Lấy danh sách tag của tin tức
     */
    public function getNewsTags($newsId)
    {
        $sql = "SELECT t.* 
                FROM tags t 
                JOIN news_tags nt ON t.id = nt.tag_id 
                WHERE nt.news_id = ?";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$newsId]);
        return $stmt->fetchAll();
    }

    /**
     * Thêm tag cho tin tức
     */
    public function addNewsTag($newsId, $tagId)
    {
        $sql = "INSERT INTO news_tags (news_id, tag_id) VALUES (?, ?)";
        $stmt = $this->db->prepare($sql);
        return $stmt->execute([$newsId, $tagId]);
    }

    /**
     * Xóa tag của tin tức
     */
    public function removeNewsTag($newsId, $tagId)
    {
        $sql = "DELETE FROM news_tags WHERE news_id = ? AND tag_id = ?";
        $stmt = $this->db->prepare($sql);
        return $stmt->execute([$newsId, $tagId]);
    }

    /**
     * Tăng lượt xem tin tức
     */
    public function incrementViews($newsId)
    {
        $sql = "UPDATE news SET views = views + 1 WHERE id = ?";
        $stmt = $this->db->prepare($sql);
        return $stmt->execute([$newsId]);
    }

    /**
     * Lấy danh sách bình luận của tin tức
     */
    public function getNewsComments($newsId, $limit = null, $offset = null)
    {
        $sql = "SELECT nc.*, u.username, u.avatar 
                FROM news_comments nc 
                JOIN users u ON nc.user_id = u.id 
                WHERE nc.news_id = ? AND nc.status = 'approved' 
                ORDER BY nc.created_at DESC";
        
        $params = [$newsId];

        if ($limit !== null) {
            $sql .= " LIMIT ?";
            $params[] = $limit;
        }

        if ($offset !== null) {
            $sql .= " OFFSET ?";
            $params[] = $offset;
        }

        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }

    /**
     * Thêm bình luận cho tin tức
     */
    public function addNewsComment($data)
    {
        $sql = "INSERT INTO news_comments (news_id, user_id, parent_id, content) 
                VALUES (?, ?, ?, ?)";
        
        $stmt = $this->db->prepare($sql);
        return $stmt->execute([
            $data['news_id'],
            $data['user_id'],
            $data['parent_id'] ?? null,
            $data['content']
        ]);
    }

    /**
     * Cập nhật trạng thái bình luận
     */
    public function updateCommentStatus($commentId, $status)
    {
        $sql = "UPDATE news_comments SET status = ? WHERE id = ?";
        $stmt = $this->db->prepare($sql);
        return $stmt->execute([$status, $commentId]);
    }

    /**
     * Xóa bình luận
     */
    public function deleteComment($commentId)
    {
        $sql = "DELETE FROM news_comments WHERE id = ?";
        $stmt = $this->db->prepare($sql);
        return $stmt->execute([$commentId]);
    }

    /**
     * Lấy danh sách tin tức liên quan
     */
    public function getRelatedNews($newsId, $limit = 5)
    {
        $sql = "SELECT n.*, u.username as author_name, nc.name as category_name 
                FROM news n 
                JOIN users u ON n.author_id = u.id 
                JOIN news_categories nc ON n.category_id = nc.id 
                WHERE n.status = 'published' 
                AND n.published_at <= NOW() 
                AND n.id != ? 
                AND n.category_id = (
                    SELECT category_id 
                    FROM news 
                    WHERE id = ?
                ) 
                ORDER BY n.published_at DESC 
                LIMIT ?";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$newsId, $newsId, $limit]);
        return $stmt->fetchAll();
    }

    public function getAll($page = 1, $limit = 10, $filters = []) {
        $offset = ($page - 1) * $limit;
        
        $sql = "SELECT n.*, u.username as author_name, nc.name as category_name 
                FROM news n 
                JOIN users u ON n.author_id = u.id 
                JOIN news_categories nc ON n.category_id = nc.id 
                WHERE 1=1";
                
        $params = [];

        // Add filters
        if (!empty($filters['category_id'])) {
            $sql .= " AND n.category_id = ?";
            $params[] = $filters['category_id'];
        }

        if (!empty($filters['status'])) {
            $sql .= " AND n.status = ?";
            $params[] = $filters['status'];
        }

        if (!empty($filters['author_id'])) {
            $sql .= " AND n.author_id = ?";
            $params[] = $filters['author_id'];
        }

        // Add sorting
        $sql .= " ORDER BY n.published_at DESC LIMIT ? OFFSET ?";
        $params[] = $limit;
        $params[] = $offset;

        $stmt = $this->query($sql, $params);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getTotal($filters = []) {
        $sql = "SELECT COUNT(*) as total FROM news WHERE 1=1";
        $params = [];

        // Add same filters as getAll
        if (!empty($filters['category_id'])) {
            $sql .= " AND category_id = ?";
            $params[] = $filters['category_id'];
        }

        if (!empty($filters['status'])) {
            $sql .= " AND status = ?";
            $params[] = $filters['status'];
        }

        if (!empty($filters['author_id'])) {
            $sql .= " AND author_id = ?";
            $params[] = $filters['author_id'];
        }

        $stmt = $this->query($sql, $params);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        return $result['total'];
    }
}
?>