<?php

namespace App\Tests;

use App\Core\Database;
use PHPUnit\Framework\TestCase as BaseTestCase;

class TestCase extends BaseTestCase
{
    protected $db;

    protected function setUp(): void
    {
        parent::setUp();
        $this->db = Database::getInstance();
        
        // Load environment variables
        $dotenv = \Dotenv\Dotenv::createImmutable(__DIR__ . '/..');
        $dotenv->load();
    }

    protected function tearDown(): void
    {
        parent::tearDown();
    }

    protected function createTestUser($role = 'user')
    {
        $username = 'test_' . uniqid();
        $email = $username . '@example.com';
        $password = password_hash('password123', PASSWORD_DEFAULT);

        $this->db->query(
            "INSERT INTO users (username, email, password, role, status) 
            VALUES (:username, :email, :password, :role, 'active')",
            [
                'username' => $username,
                'email' => $email,
                'password' => $password,
                'role' => $role
            ]
        );

        return $this->db->lastInsertId();
    }

    protected function createTestGame()
    {
        $name = 'Test Game ' . uniqid();
        $slug = strtolower(str_replace(' ', '-', $name));

        $this->db->query(
            "INSERT INTO games (name, slug, description, content, thumbnail, price, category_id, status) 
            VALUES (:name, :slug, :description, :content, :thumbnail, :price, :category_id, 'published')",
            [
                'name' => $name,
                'slug' => $slug,
                'description' => 'Test game description',
                'content' => 'Test game content',
                'thumbnail' => 'games/test.jpg',
                'price' => 29.99,
                'category_id' => 1
            ]
        );

        return $this->db->lastInsertId();
    }

    protected function createTestNews()
    {
        $title = 'Test News ' . uniqid();
        $slug = strtolower(str_replace(' ', '-', $title));

        $this->db->query(
            "INSERT INTO news (title, slug, content, thumbnail, category_id, author_id, status) 
            VALUES (:title, :slug, :content, :thumbnail, :category_id, :author_id, 'published')",
            [
                'title' => $title,
                'slug' => $slug,
                'content' => 'Test news content',
                'thumbnail' => 'news/test.jpg',
                'category_id' => 6,
                'author_id' => 1
            ]
        );

        return $this->db->lastInsertId();
    }

    protected function createTestWallet($userId)
    {
        $this->db->query(
            "INSERT INTO wallets (user_id, balance, status) 
            VALUES (:user_id, :balance, 'active')",
            [
                'user_id' => $userId,
                'balance' => 100.00
            ]
        );

        return $this->db->lastInsertId();
    }
} 