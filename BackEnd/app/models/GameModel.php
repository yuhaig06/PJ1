<?php

namespace App\Models;

use App\Core\Model;
use App\Core\Database;

class GameModel extends Model
{
    protected $table = 'games';
    protected $fillable = [
        'name', 'slug', 'description', 'content', 'category_id',
        'price', 'discount_price', 'discount_start', 'discount_end',
        'thumbnail', 'gallery', 'video_url', 'download_url',
        'system_requirements', 'views', 'downloads', 'rating',
        'status', 'featured'
    ];

    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Lấy danh sách game theo danh mục
     */
    public function getGamesByCategory($categoryId, $limit = null, $offset = null)
    {
        $sql = "SELECT * FROM games WHERE category_id = ? AND status = 'published'";
        $params = [$categoryId];

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
     * Lấy danh sách game nổi bật
     */
    public function getFeaturedGames($limit = 10)
    {
        $sql = "SELECT * FROM games 
                WHERE status = 'published' 
                AND featured = 1 
                ORDER BY created_at DESC 
                LIMIT ?";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$limit]);
        return $stmt->fetchAll();
    }

    /**
     * Lấy danh sách game mới nhất
     */
    public function getLatestGames($limit = 10)
    {
        $sql = "SELECT * FROM games 
                WHERE status = 'published' 
                ORDER BY created_at DESC 
                LIMIT ?";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$limit]);
        return $stmt->fetchAll();
    }

    /**
     * Lấy danh sách game phổ biến
     */
    public function getPopularGames($limit = 10)
    {
        $sql = "SELECT * FROM games 
                WHERE status = 'published' 
                ORDER BY views DESC 
                LIMIT ?";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$limit]);
        return $stmt->fetchAll();
    }

    /**
     * Lấy danh sách game đang giảm giá
     */
    public function getDiscountedGames($limit = 10)
    {
        $sql = "SELECT * FROM games 
                WHERE status = 'published' 
                AND discount_price IS NOT NULL 
                AND discount_start <= NOW() 
                AND discount_end >= NOW() 
                ORDER BY (price - discount_price) DESC 
                LIMIT ?";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$limit]);
        return $stmt->fetchAll();
    }

    /**
     * Tìm kiếm game
     */
    public function searchGames($keyword, $limit = null, $offset = null)
    {
        $sql = "SELECT * FROM games 
                WHERE status = 'published' 
                AND (name LIKE ? OR description LIKE ?)";
        
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
     * Lấy thông tin game chi tiết
     */
    public function getGameDetail($id)
    {
        $sql = "SELECT g.*, c.name as category_name 
                FROM games g 
                JOIN categories c ON g.category_id = c.id 
                WHERE g.id = ?";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$id]);
        return $stmt->fetch();
    }

    /**
     * Lấy danh sách tag của game
     */
    public function getGameTags($gameId)
    {
        $sql = "SELECT t.* 
                FROM tags t 
                JOIN game_tags gt ON t.id = gt.tag_id 
                WHERE gt.game_id = ?";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$gameId]);
        return $stmt->fetchAll();
    }

    /**
     * Thêm tag cho game
     */
    public function addGameTag($gameId, $tagId)
    {
        $sql = "INSERT INTO game_tags (game_id, tag_id) VALUES (?, ?)";
        $stmt = $this->db->prepare($sql);
        return $stmt->execute([$gameId, $tagId]);
    }

    /**
     * Xóa tag của game
     */
    public function removeGameTag($gameId, $tagId)
    {
        $sql = "DELETE FROM game_tags WHERE game_id = ? AND tag_id = ?";
        $stmt = $this->db->prepare($sql);
        return $stmt->execute([$gameId, $tagId]);
    }

    /**
     * Tăng lượt xem game
     */
    public function incrementViews($gameId)
    {
        $sql = "UPDATE games SET views = views + 1 WHERE id = ?";
        $stmt = $this->db->prepare($sql);
        return $stmt->execute([$gameId]);
    }

    /**
     * Tăng lượt tải game
     */
    public function incrementDownloads($gameId)
    {
        $sql = "UPDATE games SET downloads = downloads + 1 WHERE id = ?";
        $stmt = $this->db->prepare($sql);
        return $stmt->execute([$gameId]);
    }

    /**
     * Lấy đánh giá của game
     */
    public function getGameRatings($gameId, $limit = null, $offset = null)
    {
        $sql = "SELECT gr.*, u.username, u.avatar 
                FROM game_ratings gr 
                JOIN users u ON gr.user_id = u.id 
                WHERE gr.game_id = ? 
                ORDER BY gr.created_at DESC";
        
        $params = [$gameId];

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
     * Thêm đánh giá cho game
     */
    public function addGameRating($data)
    {
        $sql = "INSERT INTO game_ratings (game_id, user_id, rating, comment) 
                VALUES (?, ?, ?, ?)";
        
        $stmt = $this->db->prepare($sql);
        $result = $stmt->execute([
            $data['game_id'],
            $data['user_id'],
            $data['rating'],
            $data['comment']
        ]);

        if ($result) {
            // Cập nhật điểm đánh giá trung bình của game
            $this->updateGameRating($data['game_id']);
        }

        return $result;
    }

    /**
     * Cập nhật điểm đánh giá trung bình của game
     */
    private function updateGameRating($gameId)
    {
        $sql = "UPDATE games 
                SET rating = (
                    SELECT AVG(rating) 
                    FROM game_ratings 
                    WHERE game_id = ?
                ) 
                WHERE id = ?";
        
        $stmt = $this->db->prepare($sql);
        return $stmt->execute([$gameId, $gameId]);
    }

    /**
     * Lấy lịch sử tải game
     */
    public function getGameDownloads($gameId, $limit = null, $offset = null)
    {
        $sql = "SELECT gd.*, u.username 
                FROM game_downloads gd 
                JOIN users u ON gd.user_id = u.id 
                WHERE gd.game_id = ? 
                ORDER BY gd.created_at DESC";
        
        $params = [$gameId];

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
     * Thêm lịch sử tải game
     */
    public function addGameDownload($gameId, $userId)
    {
        $sql = "INSERT INTO game_downloads (game_id, user_id, ip_address, user_agent) 
                VALUES (?, ?, ?, ?)";
        
        $stmt = $this->db->prepare($sql);
        $result = $stmt->execute([
            $gameId,
            $userId,
            $_SERVER['REMOTE_ADDR'] ?? null,
            $_SERVER['HTTP_USER_AGENT'] ?? null
        ]);

        if ($result) {
            $this->incrementDownloads($gameId);
        }

        return $result;
    }
} 