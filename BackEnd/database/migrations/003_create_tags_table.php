<?php

use App\Core\Database;

class CreateTagsTable
{
    private $db;

    public function __construct()
    {
        $this->db = Database::getInstance();
    }

    public function up()
    {
        $sql = "CREATE TABLE IF NOT EXISTS tags (
            id INT PRIMARY KEY AUTO_INCREMENT,
            name VARCHAR(50) NOT NULL,
            slug VARCHAR(50) NOT NULL UNIQUE,
            description TEXT,
            type ENUM('game', 'news') NOT NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            INDEX idx_slug (slug),
            INDEX idx_type (type)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;";

        $this->db->query($sql);
    }

    public function down()
    {
        $sql = "DROP TABLE IF EXISTS tags;";
        $this->db->query($sql);
    }
} 