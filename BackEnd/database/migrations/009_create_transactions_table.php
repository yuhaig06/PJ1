<?php

use App\Core\Database;

class CreateTransactionsTable
{
    private $db;

    public function __construct()
    {
        $this->db = Database::getInstance();
    }

    public function up()
    {
        $sql = "CREATE TABLE IF NOT EXISTS transactions (
            id INT PRIMARY KEY AUTO_INCREMENT,
            wallet_id INT NOT NULL,
            type ENUM('deposit', 'withdraw', 'payment', 'refund') NOT NULL,
            amount DECIMAL(10,2) NOT NULL,
            balance_before DECIMAL(10,2) NOT NULL,
            balance_after DECIMAL(10,2) NOT NULL,
            description TEXT,
            status ENUM('pending', 'completed', 'failed', 'cancelled') DEFAULT 'pending',
            payment_method VARCHAR(50),
            payment_details JSON,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            FOREIGN KEY (wallet_id) REFERENCES wallets(id) ON DELETE CASCADE,
            INDEX idx_wallet (wallet_id),
            INDEX idx_type (type),
            INDEX idx_status (status),
            INDEX idx_created (created_at)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;";

        $this->db->query($sql);
    }

    public function down()
    {
        $sql = "DROP TABLE IF EXISTS transactions;";
        $this->db->query($sql);
    }
} 