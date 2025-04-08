<?php

use App\Core\Database;

class GameTagSeeder
{
    private $db;

    public function __construct()
    {
        $this->db = Database::getInstance();
    }

    public function run()
    {
        $gameTags = [
            // The Witcher 3
            ['game_id' => 1, 'tag_id' => 2], // Single Player
            ['game_id' => 1, 'tag_id' => 3], // Open World
            ['game_id' => 1, 'tag_id' => 5], // AAA

            // Red Dead Redemption 2
            ['game_id' => 2, 'tag_id' => 2], // Single Player
            ['game_id' => 2, 'tag_id' => 3], // Open World
            ['game_id' => 2, 'tag_id' => 5], // AAA

            // Cyberpunk 2077
            ['game_id' => 3, 'tag_id' => 2], // Single Player
            ['game_id' => 3, 'tag_id' => 3], // Open World
            ['game_id' => 3, 'tag_id' => 5], // AAA

            // Grand Theft Auto V
            ['game_id' => 4, 'tag_id' => 1], // Multiplayer
            ['game_id' => 4, 'tag_id' => 2], // Single Player
            ['game_id' => 4, 'tag_id' => 3], // Open World
            ['game_id' => 4, 'tag_id' => 5], // AAA

            // FIFA 23
            ['game_id' => 5, 'tag_id' => 1], // Multiplayer
            ['game_id' => 5, 'tag_id' => 5]  // AAA
        ];

        foreach ($gameTags as $gameTag) {
            $this->db->query(
                "INSERT INTO game_tags (game_id, tag_id) 
                VALUES (:game_id, :tag_id)",
                $gameTag
            );
        }
    }
} 