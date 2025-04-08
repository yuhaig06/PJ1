<?php

use App\Core\Database;

class ReportSeeder
{
    private $db;

    public function __construct()
    {
        $this->db = Database::getInstance();
    }

    public function run()
    {
        $reports = [
            [
                'user_id' => 3, // User 1
                'target_type' => 'comment',
                'target_id' => 4, // Comment ID
                'reason' => 'spam',
                'description' => 'Comment spam quảng cáo',
                'status' => 'pending',
                'created_at' => date('Y-m-d H:i:s', strtotime('-1 day')),
                'updated_at' => date('Y-m-d H:i:s', strtotime('-1 day'))
            ],
            [
                'user_id' => 4, // User 2
                'target_type' => 'user',
                'target_id' => 5, // User ID
                'reason' => 'harassment',
                'description' => 'Người dùng có hành vi quấy rối',
                'status' => 'resolved',
                'handled_by' => 2, // Admin ID
                'handled_at' => date('Y-m-d H:i:s', strtotime('-12 hours')),
                'created_at' => date('Y-m-d H:i:s', strtotime('-1 day')),
                'updated_at' => date('Y-m-d H:i:s', strtotime('-12 hours'))
            ],
            [
                'user_id' => 5, // User 3
                'target_type' => 'game',
                'target_id' => 1, // Game ID
                'reason' => 'inappropriate_content',
                'description' => 'Game có nội dung không phù hợp',
                'status' => 'rejected',
                'handled_by' => 2, // Admin ID
                'handled_at' => date('Y-m-d H:i:s', strtotime('-24 hours')),
                'created_at' => date('Y-m-d H:i:s', strtotime('-2 days')),
                'updated_at' => date('Y-m-d H:i:s', strtotime('-24 hours'))
            ]
        ];

        foreach ($reports as $report) {
            $this->db->query(
                "INSERT INTO reports (user_id, target_type, target_id, reason, description, status, handled_by, handled_at, created_at, updated_at) 
                VALUES (:user_id, :target_type, :target_id, :reason, :description, :status, :handled_by, :handled_at, :created_at, :updated_at)",
                $report
            );
        }
    }
} 