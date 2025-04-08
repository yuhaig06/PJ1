<?php

use App\Core\Database;

class WalletSeeder
{
    private $db;

    public function __construct()
    {
        $this->db = Database::getInstance();
    }

    public function run()
    {
        $wallets = [
            [
                'user_id' => 1, // Admin
                'balance' => 1000.00,
                'status' => 'active'
            ],
            [
                'user_id' => 2, // Moderator
                'balance' => 500.00,
                'status' => 'active'
            ],
            [
                'user_id' => 3, // User 1
                'balance' => 100.00,
                'status' => 'active'
            ],
            [
                'user_id' => 4, // User 2
                'balance' => 50.00,
                'status' => 'active'
            ],
            [
                'user_id' => 5, // User 3
                'balance' => 0.00,
                'status' => 'inactive'
            ]
        ];

        foreach ($wallets as $wallet) {
            $this->db->query(
                "INSERT INTO wallets (user_id, balance, status) 
                VALUES (:user_id, :balance, :status)",
                $wallet
            );
        }
    }
} 