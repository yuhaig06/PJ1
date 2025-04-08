<?php

namespace App\Models;

use App\Core\Model;
use App\Core\Database;

class ReportModel extends Model
{
    private $db;

    public function __construct()
    {
        parent::__construct();
        $this->db = Database::getInstance();
    }

    /**
     * Tạo báo cáo mới
     */
    public function createReport($data)
    {
        $sql = "INSERT INTO reports (
            user_id,
            type,
            target_id,
            target_type,
            reason,
            description,
            status,
            created_at
        ) VALUES (
            :user_id,
            :type,
            :target_id,
            :target_type,
            :reason,
            :description,
            :status,
            NOW()
        )";

        $params = [
            ':user_id' => $data['user_id'],
            ':type' => $data['type'],
            ':target_id' => $data['target_id'],
            ':target_type' => $data['target_type'],
            ':reason' => $data['reason'],
            ':description' => $data['description'],
            ':status' => $data['status'] ?? 'pending'
        ];

        return $this->db->insert($sql, $params);
    }

    /**
     * Lấy danh sách báo cáo
     */
    public function getReports($limit = null, $offset = null, $status = null, $type = null)
    {
        $sql = "SELECT r.*, u.username as reporter_name 
                FROM reports r
                LEFT JOIN users u ON r.user_id = u.id
                WHERE 1=1";
        $params = [];

        if ($status) {
            $sql .= " AND r.status = :status";
            $params[':status'] = $status;
        }

        if ($type) {
            $sql .= " AND r.type = :type";
            $params[':type'] = $type;
        }

        $sql .= " ORDER BY r.created_at DESC";

        if ($limit) {
            $sql .= " LIMIT :limit";
            $params[':limit'] = $limit;
        }

        if ($offset) {
            $sql .= " OFFSET :offset";
            $params[':offset'] = $offset;
        }

        return $this->db->query($sql, $params);
    }

    /**
     * Lấy chi tiết báo cáo
     */
    public function getReportDetail($id)
    {
        $sql = "SELECT r.*, u.username as reporter_name 
                FROM reports r
                LEFT JOIN users u ON r.user_id = u.id
                WHERE r.id = :id";

        return $this->db->queryOne($sql, [':id' => $id]);
    }

    /**
     * Cập nhật trạng thái báo cáo
     */
    public function updateStatus($id, $status, $handlerId = null, $note = null)
    {
        $sql = "UPDATE reports SET 
                status = :status,
                handler_id = :handler_id,
                note = :note,
                updated_at = NOW()
                WHERE id = :id";

        $params = [
            ':id' => $id,
            ':status' => $status,
            ':handler_id' => $handlerId,
            ':note' => $note
        ];

        return $this->db->update($sql, $params);
    }

    /**
     * Lấy số lượng báo cáo theo trạng thái
     */
    public function getReportCounts()
    {
        $sql = "SELECT status, COUNT(*) as count 
                FROM reports 
                GROUP BY status";

        return $this->db->query($sql);
    }

    /**
     * Lấy số lượng báo cáo theo loại
     */
    public function getReportTypeCounts()
    {
        $sql = "SELECT type, COUNT(*) as count 
                FROM reports 
                GROUP BY type";

        return $this->db->query($sql);
    }

    /**
     * Kiểm tra xem người dùng đã báo cáo chưa
     */
    public function hasReported($userId, $targetId, $targetType)
    {
        $sql = "SELECT COUNT(*) as count 
                FROM reports 
                WHERE user_id = :user_id 
                AND target_id = :target_id 
                AND target_type = :target_type 
                AND status = 'pending'";

        $params = [
            ':user_id' => $userId,
            ':target_id' => $targetId,
            ':target_type' => $targetType
        ];

        $result = $this->db->queryOne($sql, $params);
        return $result['count'] > 0;
    }

    /**
     * Lấy danh sách báo cáo của người dùng
     */
    public function getUserReports($userId, $limit = null, $offset = null)
    {
        $sql = "SELECT * FROM reports WHERE user_id = :user_id ORDER BY created_at DESC";
        $params = [':user_id' => $userId];

        if ($limit) {
            $sql .= " LIMIT :limit";
            $params[':limit'] = $limit;
        }

        if ($offset) {
            $sql .= " OFFSET :offset";
            $params[':offset'] = $offset;
        }

        return $this->db->query($sql, $params);
    }

    /**
     * Lấy danh sách báo cáo đã xử lý bởi moderator
     */
    public function getHandledReports($handlerId, $limit = null, $offset = null)
    {
        $sql = "SELECT r.*, u.username as reporter_name 
                FROM reports r
                LEFT JOIN users u ON r.user_id = u.id
                WHERE r.handler_id = :handler_id 
                ORDER BY r.updated_at DESC";
        
        $params = [':handler_id' => $handlerId];

        if ($limit) {
            $sql .= " LIMIT :limit";
            $params[':limit'] = $limit;
        }

        if ($offset) {
            $sql .= " OFFSET :offset";
            $params[':offset'] = $offset;
        }

        return $this->db->query($sql, $params);
    }

    /**
     * Lấy thống kê báo cáo theo thời gian
     */
    public function getReportStats($period = 'day')
    {
        $sql = "SELECT 
                DATE(created_at) as date,
                COUNT(*) as total,
                SUM(CASE WHEN status = 'pending' THEN 1 ELSE 0 END) as pending,
                SUM(CASE WHEN status = 'resolved' THEN 1 ELSE 0 END) as resolved,
                SUM(CASE WHEN status = 'rejected' THEN 1 ELSE 0 END) as rejected
                FROM reports
                WHERE created_at >= DATE_SUB(NOW(), INTERVAL 1 " . strtoupper($period) . ")
                GROUP BY DATE(created_at)
                ORDER BY date DESC";

        return $this->db->query($sql);
    }
} 