<?php
namespace App\Models;

use App\Core\Model;
use PDO;

class CommentModel extends Model {
    protected $table = 'comments';
    protected $fillable = ['news_id', 'user_id', 'content', 'parent_id'];

    public function __construct() {
        parent::__construct();
    }

    public function getByNewsId($newsId, $limit = 10, $offset = 0) {
        $sql = "SELECT c.*, u.username, u.avatar 
                FROM comments c 
                JOIN users u ON c.user_id = u.id 
                WHERE c.news_id = ? AND c.status = 'approved' 
                ORDER BY c.created_at DESC 
                LIMIT ? OFFSET ?";

        return $this->query($sql, [$newsId, $limit, $offset])->fetchAll(PDO::FETCH_ASSOC);
    }

    // Lấy các bình luận con của một bình luận
    public function getReplies($parentId) {
        $this->db->query('SELECT c.*, u.username, u.avatar 
                         FROM comments c 
                         JOIN users u ON c.user_id = u.id 
                         WHERE c.parent_id = :parent_id AND c.status = "approved" 
                         ORDER BY c.created_at ASC');
        
        $this->db->bind(':parent_id', $parentId);
        return $this->db->resultSet();
    }

    // Thêm bình luận mới
    public function addComment($data) {
        $this->db->query('INSERT INTO comments (content, news_id, user_id, parent_id, status) 
                         VALUES (:content, :news_id, :user_id, :parent_id, :status)');
        
        $this->db->bind(':content', $data['content']);
        $this->db->bind(':news_id', $data['news_id']);
        $this->db->bind(':user_id', $data['user_id']);
        $this->db->bind(':parent_id', $data['parent_id'] ?? null);
        $this->db->bind(':status', $data['status'] ?? 'pending');
        
        return $this->db->execute();
    }

    // Cập nhật trạng thái bình luận
    public function updateCommentStatus($commentId, $status) {
        $this->db->query('UPDATE comments SET status = :status WHERE id = :id');
        $this->db->bind(':status', $status);
        $this->db->bind(':id', $commentId);
        
        return $this->db->execute();
    }

    // Xóa bình luận
    public function deleteComment($commentId) {
        // Xóa các bình luận con trước
        $this->db->query('DELETE FROM comments WHERE parent_id = :parent_id');
        $this->db->bind(':parent_id', $commentId);
        $this->db->execute();
        
        // Sau đó xóa bình luận chính
        $this->db->query('DELETE FROM comments WHERE id = :id');
        $this->db->bind(':id', $commentId);
        
        return $this->db->execute();
    }

    // Lấy số lượng bình luận của một tin tức
    public function getCommentCount($newsId) {
        $this->db->query('SELECT COUNT(*) as count 
                         FROM comments 
                         WHERE news_id = :news_id AND status = "approved"');
        
        $this->db->bind(':news_id', $newsId);
        $result = $this->db->single();
        
        return $result->count;
    }

    public function getById($id) {
        $sql = "SELECT c.*, u.username, u.avatar 
                FROM comments c 
                JOIN users u ON c.user_id = u.id 
                WHERE c.id = ?";

        return $this->query($sql, [$id])->fetch(PDO::FETCH_ASSOC);
    }
}