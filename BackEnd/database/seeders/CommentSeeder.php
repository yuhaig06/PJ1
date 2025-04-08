<?php

use App\Core\Database;

class CommentSeeder
{
    private $db;

    public function __construct()
    {
        $this->db = Database::getInstance();
    }

    public function run()
    {
        $comments = [
            [
                'user_id' => 3, // User 1
                'game_id' => 1, // The Witcher 3
                'content' => 'Game hay quá! Đồ họa đẹp, cốt truyện hấp dẫn.',
                'status' => 'approved',
                'created_at' => date('Y-m-d H:i:s', strtotime('-2 days')),
                'updated_at' => date('Y-m-d H:i:s', strtotime('-2 days'))
            ],
            [
                'user_id' => 4, // User 2
                'game_id' => 1, // The Witcher 3
                'content' => 'Gameplay rất thú vị, đặc biệt là hệ thống combat.',
                'status' => 'approved',
                'created_at' => date('Y-m-d H:i:s', strtotime('-1 day')),
                'updated_at' => date('Y-m-d H:i:s', strtotime('-1 day'))
            ],
            [
                'user_id' => 5, // User 3
                'game_id' => 2, // Red Dead Redemption 2
                'content' => 'Một kiệt tác của Rockstar Games!',
                'status' => 'approved',
                'created_at' => date('Y-m-d H:i:s', strtotime('-3 days')),
                'updated_at' => date('Y-m-d H:i:s', strtotime('-3 days'))
            ],
            [
                'user_id' => 6, // User 4
                'game_id' => 2, // Red Dead Redemption 2
                'content' => 'Đồ họa siêu thực, thế giới mở rộng lớn.',
                'status' => 'pending',
                'created_at' => date('Y-m-d H:i:s'),
                'updated_at' => date('Y-m-d H:i:s')
            ]
        ];

        foreach ($comments as $comment) {
            $this->db->query(
                "INSERT INTO comments (user_id, game_id, content, status, created_at, updated_at) 
                VALUES (:user_id, :game_id, :content, :status, :created_at, :updated_at)",
                $comment
            );
        }
    }
} 