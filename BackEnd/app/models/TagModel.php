<?php

namespace App\Models;

use App\Core\Model;
use App\Core\Database;

class TagModel extends Model
{
    private $db;

    public function __construct()
    {
        parent::__construct();
        $this->db = Database::getInstance();
    }

    /**
     * Tạo thẻ mới
     */
    public function createTag($data)
    {
        $sql = "INSERT INTO tags (name, slug, description, type, created_at, updated_at) 
                VALUES (:name, :slug, :description, :type, NOW(), NOW())";
        
        $params = [
            ':name' => $data['name'],
            ':slug' => $data['slug'],
            ':description' => $data['description'] ?? null,
            ':type' => $data['type']
        ];

        return $this->db->insert($sql, $params);
    }

    /**
     * Cập nhật thẻ
     */
    public function updateTag($id, $data)
    {
        $sql = "UPDATE tags 
                SET name = :name, 
                    slug = :slug, 
                    description = :description,
                    updated_at = NOW() 
                WHERE id = :id";
        
        $params = [
            ':id' => $id,
            ':name' => $data['name'],
            ':slug' => $data['slug'],
            ':description' => $data['description'] ?? null
        ];

        return $this->db->update($sql, $params);
    }

    /**
     * Xóa thẻ
     */
    public function deleteTag($id)
    {
        // Kiểm tra xem có nội dung liên quan không
        $hasContent = $this->hasContent($id);
        if ($hasContent) {
            return false;
        }

        $sql = "DELETE FROM tags WHERE id = :id";
        return $this->db->delete($sql, [':id' => $id]);
    }

    /**
     * Lấy danh sách tất cả thẻ
     */
    public function getAllTags()
    {
        $sql = "SELECT t.*, 
                       (SELECT COUNT(*) FROM game_tags WHERE tag_id = t.id) as game_count,
                       (SELECT COUNT(*) FROM news_tags WHERE tag_id = t.id) as news_count
                FROM tags t
                ORDER BY t.type, t.name";
        
        return $this->db->query($sql);
    }

    /**
     * Lấy danh sách thẻ theo loại
     */
    public function getTagsByType($type)
    {
        $sql = "SELECT t.*, 
                       (SELECT COUNT(*) FROM game_tags WHERE tag_id = t.id) as game_count,
                       (SELECT COUNT(*) FROM news_tags WHERE tag_id = t.id) as news_count
                FROM tags t
                WHERE t.type = :type
                ORDER BY t.name";
        
        return $this->db->query($sql, [':type' => $type]);
    }

    /**
     * Lấy chi tiết thẻ
     */
    public function getTagDetail($id)
    {
        $sql = "SELECT t.*, 
                       (SELECT COUNT(*) FROM game_tags WHERE tag_id = t.id) as game_count,
                       (SELECT COUNT(*) FROM news_tags WHERE tag_id = t.id) as news_count
                FROM tags t
                WHERE t.id = :id";
        
        return $this->db->queryOne($sql, [':id' => $id]);
    }

    /**
     * Kiểm tra thẻ có tồn tại không
     */
    public function exists($id)
    {
        $sql = "SELECT COUNT(*) as count FROM tags WHERE id = :id";
        $result = $this->db->queryOne($sql, [':id' => $id]);
        return $result['count'] > 0;
    }

    /**
     * Kiểm tra slug có tồn tại không
     */
    public function slugExists($slug, $excludeId = null)
    {
        $sql = "SELECT COUNT(*) as count FROM tags WHERE slug = :slug";
        $params = [':slug' => $slug];

        if ($excludeId) {
            $sql .= " AND id != :id";
            $params[':id'] = $excludeId;
        }

        $result = $this->db->queryOne($sql, $params);
        return $result['count'] > 0;
    }

    /**
     * Kiểm tra thẻ có nội dung liên quan không
     */
    private function hasContent($id)
    {
        $sql = "SELECT 
                (SELECT COUNT(*) FROM game_tags WHERE tag_id = :id) +
                (SELECT COUNT(*) FROM news_tags WHERE tag_id = :id) as count";
        $result = $this->db->queryOne($sql, [':id' => $id]);
        return $result['count'] > 0;
    }

    /**
     * Lấy danh sách thẻ phổ biến
     */
    public function getPopularTags($type = null, $limit = 10)
    {
        $sql = "SELECT t.*, 
                       (SELECT COUNT(*) FROM game_tags WHERE tag_id = t.id) +
                       (SELECT COUNT(*) FROM news_tags WHERE tag_id = t.id) as usage_count
                FROM tags t";
        
        $params = [];
        if ($type) {
            $sql .= " WHERE t.type = :type";
            $params[':type'] = $type;
        }
        
        $sql .= " ORDER BY usage_count DESC, t.name
                  LIMIT :limit";
        
        $params[':limit'] = $limit;
        return $this->db->query($sql, $params);
    }

    /**
     * Lấy danh sách thẻ theo từ khóa
     */
    public function searchTags($keyword, $type = null, $limit = 10)
    {
        $sql = "SELECT t.*, 
                       (SELECT COUNT(*) FROM game_tags WHERE tag_id = t.id) +
                       (SELECT COUNT(*) FROM news_tags WHERE tag_id = t.id) as usage_count
                FROM tags t
                WHERE t.name LIKE :keyword OR t.slug LIKE :keyword";
        
        $params = [':keyword' => "%{$keyword}%"];
        
        if ($type) {
            $sql .= " AND t.type = :type";
            $params[':type'] = $type;
        }
        
        $sql .= " ORDER BY usage_count DESC, t.name
                  LIMIT :limit";
        
        $params[':limit'] = $limit;
        return $this->db->query($sql, $params);
    }

    /**
     * Lấy danh sách thẻ của game
     */
    public function getGameTags($gameId)
    {
        $sql = "SELECT t.* 
                FROM tags t
                JOIN game_tags gt ON t.id = gt.tag_id
                WHERE gt.game_id = :game_id
                ORDER BY t.name";
        
        return $this->db->query($sql, [':game_id' => $gameId]);
    }

    /**
     * Lấy danh sách thẻ của tin tức
     */
    public function getNewsTags($newsId)
    {
        $sql = "SELECT t.* 
                FROM tags t
                JOIN news_tags nt ON t.id = nt.tag_id
                WHERE nt.news_id = :news_id
                ORDER BY t.name";
        
        return $this->db->query($sql, [':news_id' => $newsId]);
    }

    /**
     * Thêm thẻ cho game
     */
    public function addGameTag($gameId, $tagId)
    {
        $sql = "INSERT INTO game_tags (game_id, tag_id) VALUES (:game_id, :tag_id)";
        return $this->db->insert($sql, [
            ':game_id' => $gameId,
            ':tag_id' => $tagId
        ]);
    }

    /**
     * Thêm thẻ cho tin tức
     */
    public function addNewsTag($newsId, $tagId)
    {
        $sql = "INSERT INTO news_tags (news_id, tag_id) VALUES (:news_id, :tag_id)";
        return $this->db->insert($sql, [
            ':news_id' => $newsId,
            ':tag_id' => $tagId
        ]);
    }

    /**
     * Xóa thẻ khỏi game
     */
    public function removeGameTag($gameId, $tagId)
    {
        $sql = "DELETE FROM game_tags WHERE game_id = :game_id AND tag_id = :tag_id";
        return $this->db->delete($sql, [
            ':game_id' => $gameId,
            ':tag_id' => $tagId
        ]);
    }

    /**
     * Xóa thẻ khỏi tin tức
     */
    public function removeNewsTag($newsId, $tagId)
    {
        $sql = "DELETE FROM news_tags WHERE news_id = :news_id AND tag_id = :tag_id";
        return $this->db->delete($sql, [
            ':news_id' => $newsId,
            ':tag_id' => $tagId
        ]);
    }

    /**
     * Cập nhật thẻ cho game
     */
    public function updateGameTags($gameId, $tagIds)
    {
        // Xóa tất cả thẻ cũ
        $sql = "DELETE FROM game_tags WHERE game_id = :game_id";
        $this->db->delete($sql, [':game_id' => $gameId]);

        // Thêm các thẻ mới
        if (!empty($tagIds)) {
            $values = [];
            $params = [':game_id' => $gameId];
            foreach ($tagIds as $index => $tagId) {
                $values[] = "(:game_id, :tag_id{$index})";
                $params[":tag_id{$index}"] = $tagId;
            }

            $sql = "INSERT INTO game_tags (game_id, tag_id) VALUES " . implode(', ', $values);
            return $this->db->insert($sql, $params);
        }

        return true;
    }

    /**
     * Cập nhật thẻ cho tin tức
     */
    public function updateNewsTags($newsId, $tagIds)
    {
        // Xóa tất cả thẻ cũ
        $sql = "DELETE FROM news_tags WHERE news_id = :news_id";
        $this->db->delete($sql, [':news_id' => $newsId]);

        // Thêm các thẻ mới
        if (!empty($tagIds)) {
            $values = [];
            $params = [':news_id' => $newsId];
            foreach ($tagIds as $index => $tagId) {
                $values[] = "(:news_id, :tag_id{$index})";
                $params[":tag_id{$index}"] = $tagId;
            }

            $sql = "INSERT INTO news_tags (news_id, tag_id) VALUES " . implode(', ', $values);
            return $this->db->insert($sql, $params);
        }

        return true;
    }
} 