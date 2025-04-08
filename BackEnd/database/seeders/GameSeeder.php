<?php

use App\Core\Database;

class GameSeeder
{
    private $db;

    public function __construct()
    {
        $this->db = Database::getInstance();
    }

    public function run()
    {
        $games = [
            [
                'name' => 'The Witcher 3: Wild Hunt',
                'slug' => 'the-witcher-3-wild-hunt',
                'description' => 'An epic action RPG set in a rich, open world fantasy universe full of meaningful choices and impactful consequences.',
                'content' => 'The Witcher 3: Wild Hunt is an action role-playing game with a third-person perspective. Players control Geralt of Rivia, a monster slayer known as a Witcher. The game features a complex, branching storyline with multiple endings.',
                'thumbnail' => 'games/witcher3.jpg',
                'price' => 29.99,
                'category_id' => 3, // RPG
                'status' => 'published'
            ],
            [
                'name' => 'Red Dead Redemption 2',
                'slug' => 'red-dead-redemption-2',
                'description' => 'An epic tale of honor and loyalty at the heart of America.',
                'content' => 'Red Dead Redemption 2 is an action-adventure game set in an open world environment. The game features both single-player and online multiplayer components.',
                'thumbnail' => 'games/rdr2.jpg',
                'price' => 59.99,
                'category_id' => 2, // Adventure
                'status' => 'published'
            ],
            [
                'name' => 'Cyberpunk 2077',
                'slug' => 'cyberpunk-2077',
                'description' => 'An open-world, action-adventure RPG set in Night City, a megalopolis obsessed with power, glamour and body modification.',
                'content' => 'Cyberpunk 2077 is an action role-playing video game. The game takes place in Night City, an open world with six distinct regions. Players assume the role of V, a customizable mercenary.',
                'thumbnail' => 'games/cyberpunk2077.jpg',
                'price' => 59.99,
                'category_id' => 3, // RPG
                'status' => 'published'
            ],
            [
                'name' => 'Grand Theft Auto V',
                'slug' => 'grand-theft-auto-v',
                'description' => 'Experience Los Santos and Blaine County in the ultimate Grand Theft Auto V package.',
                'content' => 'Grand Theft Auto V is an action-adventure game played from either a third-person or first-person perspective. Players complete missions to progress through the story.',
                'thumbnail' => 'games/gtav.jpg',
                'price' => 29.99,
                'category_id' => 1, // Action
                'status' => 'published'
            ],
            [
                'name' => 'FIFA 23',
                'slug' => 'fifa-23',
                'description' => 'Experience the beautiful game with FIFA 23.',
                'content' => 'FIFA 23 is a football simulation video game. The game features both single-player and multiplayer modes, including career mode, ultimate team, and various online modes.',
                'thumbnail' => 'games/fifa23.jpg',
                'price' => 59.99,
                'category_id' => 5, // Sports
                'status' => 'published'
            ]
        ];

        foreach ($games as $game) {
            $this->db->query(
                "INSERT INTO games (name, slug, description, content, thumbnail, price, category_id, status) 
                VALUES (:name, :slug, :description, :content, :thumbnail, :price, :category_id, :status)",
                $game
            );
        }
    }
} 