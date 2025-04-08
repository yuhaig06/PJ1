<?php

use App\Core\Database;

class TransactionSeeder
{
    private $db;

    public function __construct()
    {
        $this->db = Database::getInstance();
    }

    public function run()
    {
        $transactions = [
            // Admin deposits
            [
                'wallet_id' => 1,
                'type' => 'deposit',
                'amount' => 1000.00,
                'balance_before' => 0.00,
                'balance_after' => 1000.00,
                'description' => 'Initial deposit',
                'status' => 'completed',
                'payment_method' => 'bank_transfer',
                'payment_details' => json_encode(['bank_name' => 'Example Bank', 'account_number' => '1234567890'])
            ],
            // Moderator deposits
            [
                'wallet_id' => 2,
                'type' => 'deposit',
                'amount' => 500.00,
                'balance_before' => 0.00,
                'balance_after' => 500.00,
                'description' => 'Initial deposit',
                'status' => 'completed',
                'payment_method' => 'bank_transfer',
                'payment_details' => json_encode(['bank_name' => 'Example Bank', 'account_number' => '0987654321'])
            ],
            // User 1 deposits
            [
                'wallet_id' => 3,
                'type' => 'deposit',
                'amount' => 100.00,
                'balance_before' => 0.00,
                'balance_after' => 100.00,
                'description' => 'Initial deposit',
                'status' => 'completed',
                'payment_method' => 'credit_card',
                'payment_details' => json_encode(['card_type' => 'Visa', 'last4' => '1234'])
            ],
            // User 2 deposits
            [
                'wallet_id' => 4,
                'type' => 'deposit',
                'amount' => 50.00,
                'balance_before' => 0.00,
                'balance_after' => 50.00,
                'description' => 'Initial deposit',
                'status' => 'completed',
                'payment_method' => 'credit_card',
                'payment_details' => json_encode(['card_type' => 'Mastercard', 'last4' => '5678'])
            ],
            // User 1 payment for game
            [
                'wallet_id' => 3,
                'type' => 'payment',
                'amount' => 29.99,
                'balance_before' => 100.00,
                'balance_after' => 70.01,
                'description' => 'Payment for The Witcher 3: Wild Hunt',
                'status' => 'completed',
                'payment_method' => 'wallet'
            ]
        ];

        foreach ($transactions as $transaction) {
            $this->db->query(
                "INSERT INTO transactions (wallet_id, type, amount, balance_before, balance_after, description, status, payment_method, payment_details) 
                VALUES (:wallet_id, :type, :amount, :balance_before, :balance_after, :description, :status, :payment_method, :payment_details)",
                $transaction
            );
        }
    }
} 