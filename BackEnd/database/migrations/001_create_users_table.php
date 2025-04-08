<?php

use App\Core\Database;

class CreateUsersTable
{
    private $db;

    public function __construct()
    {
        $this->db = Database::getInstance();
    }

    public function up()
    {
        $sql = "CREATE TABLE IF NOT EXISTS users (
            id INT PRIMARY KEY AUTO_INCREMENT,
            username VARCHAR(50) NOT NULL UNIQUE,
            email VARCHAR(100) NOT NULL UNIQUE,
            password VARCHAR(255) NOT NULL,
            full_name VARCHAR(100),
            phone VARCHAR(20),
            address TEXT,
            avatar VARCHAR(255),
            role ENUM('user', 'moderator', 'admin') DEFAULT 'user',
            status ENUM('active', 'inactive', 'banned') DEFAULT 'active',
            email_verified_at TIMESTAMP NULL,
            remember_token VARCHAR(100),
            last_login_at TIMESTAMP NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            INDEX idx_email (email),
            INDEX idx_username (username),
            INDEX idx_role (role),
            INDEX idx_status (status)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;";

        $this->db->query($sql);
    }

    public function down()
    {
        $sql = "DROP TABLE IF EXISTS users;";
        $this->db->query($sql);
    }
}