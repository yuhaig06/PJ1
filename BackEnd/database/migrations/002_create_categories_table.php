<?php

use App\Core\Database;

class CreateCategoriesTable
{
    private $db;

    public function __construct()
    {
        $this->db = Database::getInstance();
    }

    public function up()
    {
        $sql = "CREATE TABLE IF NOT EXISTS categories (
            id INT PRIMARY KEY AUTO_INCREMENT,
            name VARCHAR(100) NOT NULL,
            slug VARCHAR(100) NOT NULL UNIQUE,
            description TEXT,
            parent_id INT,
            type ENUM('game', 'news') NOT NULL,
            status ENUM('active', 'inactive') DEFAULT 'active',
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            FOREIGN KEY (parent_id) REFERENCES categories(id) ON DELETE SET NULL,
            INDEX idx_slug (slug),
            INDEX idx_type (type),
            INDEX idx_status (status)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;";

        $this->db->query($sql);
    }

    public function down()
    {
        $sql = "DROP TABLE IF EXISTS categories;";
        $this->db->query($sql);
    }
} 