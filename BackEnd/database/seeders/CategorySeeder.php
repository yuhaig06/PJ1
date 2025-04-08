<?php

use App\Core\Database;

class CategorySeeder
{
    private $db;

    public function __construct()
    {
        $this->db = Database::getInstance();
    }

    public function run()
    {
        $categories = [
            // Game categories
            [
                'name' => 'Action',
                'slug' => 'action',
                'description' => 'Action games',
                'type' => 'game',
                'status' => 'active'
            ],
            [
                'name' => 'Adventure',
                'slug' => 'adventure',
                'description' => 'Adventure games',
                'type' => 'game',
                'status' => 'active'
            ],
            [
                'name' => 'RPG',
                'slug' => 'rpg',
                'description' => 'Role-playing games',
                'type' => 'game',
                'status' => 'active'
            ],
            [
                'name' => 'Strategy',
                'slug' => 'strategy',
                'description' => 'Strategy games',
                'type' => 'game',
                'status' => 'active'
            ],
            [
                'name' => 'Sports',
                'slug' => 'sports',
                'description' => 'Sports games',
                'type' => 'game',
                'status' => 'active'
            ],
            // News categories
            [
                'name' => 'Game News',
                'slug' => 'game-news',
                'description' => 'Latest game news and updates',
                'type' => 'news',
                'status' => 'active'
            ],
            [
                'name' => 'Reviews',
                'slug' => 'reviews',
                'description' => 'Game reviews and ratings',
                'type' => 'news',
                'status' => 'active'
            ],
            [
                'name' => 'Guides',
                'slug' => 'guides',
                'description' => 'Game guides and tutorials',
                'type' => 'news',
                'status' => 'active'
            ],
            [
                'name' => 'Events',
                'slug' => 'events',
                'description' => 'Gaming events and tournaments',
                'type' => 'news',
                'status' => 'active'
            ]
        ];

        foreach ($categories as $category) {
            $this->db->query(
                "INSERT INTO categories (name, slug, description, type, status) 
                VALUES (:name, :slug, :description, :type, :status)",
                $category
            );
        }
    }
}