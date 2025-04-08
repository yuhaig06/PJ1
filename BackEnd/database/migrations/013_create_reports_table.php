<?php

use App\Core\Database;

class CreateReportsTable
{
    private $db;

    public function __construct()
    {
        $this->db = Database::getInstance();
    }

    public function up()
    {
        $sql = "CREATE TABLE IF NOT EXISTS reports (
            id INT PRIMARY KEY AUTO_INCREMENT,
            user_id INT NOT NULL,
            target_type ENUM('user', 'game', 'news', 'comment') NOT NULL,
            target_id INT NOT NULL,
            reason VARCHAR(255) NOT NULL,
            description TEXT,
            status ENUM('pending', 'resolved', 'rejected') DEFAULT 'pending',
            resolved_by INT,
            resolved_at TIMESTAMP NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
            FOREIGN KEY (resolved_by) REFERENCES users(id) ON DELETE SET NULL,
            INDEX idx_user (user_id),
            INDEX idx_target (target_type, target_id),
            INDEX idx_status (status),
            INDEX idx_created (created_at)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;";

        $this->db->query($sql);
    }

    public function down()
    {
        $sql = "DROP TABLE IF EXISTS reports;";
        $this->db->query($sql);
    }
} 