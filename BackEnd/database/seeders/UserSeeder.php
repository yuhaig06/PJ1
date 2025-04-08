<?php

use App\Core\Database;

class UserSeeder
{
    private $db;

    public function __construct()
    {
        $this->db = Database::getInstance();
    }

    public function run()
    {
        $users = [
            [
                'username' => 'admin',
                'email' => 'admin@example.com',
                'password' => password_hash('admin123', PASSWORD_DEFAULT),
                'full_name' => 'Administrator',
                'role' => 'admin',
                'status' => 'active',
                'email_verified_at' => date('Y-m-d H:i:s')
            ],
            [
                'username' => 'moderator',
                'email' => 'moderator@example.com',
                'password' => password_hash('mod123', PASSWORD_DEFAULT),
                'full_name' => 'Moderator',
                'role' => 'moderator',
                'status' => 'active',
                'email_verified_at' => date('Y-m-d H:i:s')
            ],
            [
                'username' => 'user1',
                'email' => 'user1@example.com',
                'password' => password_hash('user123', PASSWORD_DEFAULT),
                'full_name' => 'User One',
                'role' => 'user',
                'status' => 'active',
                'email_verified_at' => date('Y-m-d H:i:s')
            ],
            [
                'username' => 'user2',
                'email' => 'user2@example.com',
                'password' => password_hash('user123', PASSWORD_DEFAULT),
                'full_name' => 'User Two',
                'role' => 'user',
                'status' => 'active',
                'email_verified_at' => date('Y-m-d H:i:s')
            ],
            [
                'username' => 'user3',
                'email' => 'user3@example.com',
                'password' => password_hash('user123', PASSWORD_DEFAULT),
                'full_name' => 'User Three',
                'role' => 'user',
                'status' => 'inactive',
                'email_verified_at' => null
            ]
        ];

        foreach ($users as $user) {
            $this->db->query(
                "INSERT INTO users (username, email, password, full_name, role, status, email_verified_at) 
                VALUES (:username, :email, :password, :full_name, :role, :status, :email_verified_at)",
                $user
            );
        }
    }
}