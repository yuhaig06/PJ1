<?php

use App\Core\Database;

class OrderSeeder
{
    private $db;

    public function __construct()
    {
        $this->db = Database::getInstance();
    }

    public function run()
    {
        // Create orders
        $orders = [
            [
                'user_id' => 3, // User 1
                'total_amount' => 29.99,
                'payment_method' => 'wallet',
                'payment_status' => 'paid',
                'order_status' => 'completed',
                'transaction_id' => 5 // Payment transaction for The Witcher 3
            ]
        ];

        foreach ($orders as $order) {
            $this->db->query(
                "INSERT INTO orders (user_id, total_amount, payment_method, payment_status, order_status, transaction_id) 
                VALUES (:user_id, :total_amount, :payment_method, :payment_status, :order_status, :transaction_id)",
                $order
            );

            // Get the last inserted order ID
            $orderId = $this->db->lastInsertId();

            // Create order items
            $orderItems = [
                [
                    'order_id' => $orderId,
                    'game_id' => 1, // The Witcher 3
                    'price' => 29.99
                ]
            ];

            foreach ($orderItems as $item) {
                $this->db->query(
                    "INSERT INTO order_items (order_id, game_id, price) 
                    VALUES (:order_id, :game_id, :price)",
                    $item
                );
            }
        }
    }
} 