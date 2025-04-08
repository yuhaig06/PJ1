<?php

use App\Core\Database;

class TagSeeder
{
    private $db;

    public function __construct()
    {
        $this->db = Database::getInstance();
    }

    public function run()
    {
        $tags = [
            // Game tags
            [
                'name' => 'Multiplayer',
                'slug' => 'multiplayer',
                'description' => 'Multiplayer games',
                'type' => 'game'
            ],
            [
                'name' => 'Single Player',
                'slug' => 'single-player',
                'description' => 'Single player games',
                'type' => 'game'
            ],
            [
                'name' => 'Open World',
                'slug' => 'open-world',
                'description' => 'Open world games',
                'type' => 'game'
            ],
            [
                'name' => 'Indie',
                'slug' => 'indie',
                'description' => 'Indie games',
                'type' => 'game'
            ],
            [
                'name' => 'AAA',
                'slug' => 'aaa',
                'description' => 'AAA games',
                'type' => 'game'
            ],
            // News tags
            [
                'name' => 'Release',
                'slug' => 'release',
                'description' => 'Game releases',
                'type' => 'news'
            ],
            [
                'name' => 'Update',
                'slug' => 'update',
                'description' => 'Game updates',
                'type' => 'news'
            ],
            [
                'name' => 'DLC',
                'slug' => 'dlc',
                'description' => 'Downloadable content',
                'type' => 'news'
            ],
            [
                'name' => 'Patch',
                'slug' => 'patch',
                'description' => 'Game patches',
                'type' => 'news'
            ]
        ];

        foreach ($tags as $tag) {
            $this->db->query(
                "INSERT INTO tags (name, slug, description, type) 
                VALUES (:name, :slug, :description, :type)",
                $tag
            );
        }
    }
} 