<?php

use App\Core\Database;

class CreateGamesTable
{
    private $db;

    public function __construct()
    {
        $this->db = Database::getInstance();
    }

    public function up()
    {
        $sql = "CREATE TABLE IF NOT EXISTS games (
            id INT PRIMARY KEY AUTO_INCREMENT,
            name VARCHAR(255) NOT NULL,
            slug VARCHAR(255) NOT NULL UNIQUE,
            description TEXT,
            content TEXT,
            thumbnail VARCHAR(255),
            price DECIMAL(10,2) NOT NULL DEFAULT 0.00,
            discount_price DECIMAL(10,2),
            discount_start TIMESTAMP NULL,
            discount_end TIMESTAMP NULL,
            category_id INT,
            status ENUM('draft', 'published', 'hidden') DEFAULT 'draft',
            views INT DEFAULT 0,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            FOREIGN KEY (category_id) REFERENCES categories(id) ON DELETE SET NULL,
            INDEX idx_slug (slug),
            INDEX idx_category (category_id),
            INDEX idx_status (status),
            INDEX idx_price (price),
            INDEX idx_discount (discount_price, discount_start, discount_end)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;";

        $this->db->query($sql);
    }

    public function down()
    {
        $sql = "DROP TABLE IF EXISTS games;";
        $this->db->query($sql);
    }
} 