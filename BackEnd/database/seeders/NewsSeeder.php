<?php

use App\Core\Database;

class NewsSeeder
{
    private $db;

    public function __construct()
    {
        $this->db = Database::getInstance();
    }

    public function run()
    {
        $news = [
            [
                'title' => 'The Witcher 3 Next-Gen Update Released',
                'slug' => 'the-witcher-3-next-gen-update-released',
                'content' => 'CD Projekt Red has released the next-gen update for The Witcher 3: Wild Hunt. The update includes ray tracing, improved graphics, and new content inspired by the Netflix series.',
                'thumbnail' => 'news/witcher3-update.jpg',
                'category_id' => 6, // Game News
                'author_id' => 1, // Admin
                'status' => 'published'
            ],
            [
                'title' => 'Red Dead Redemption 2 Review',
                'slug' => 'red-dead-redemption-2-review',
                'content' => 'Red Dead Redemption 2 is a masterpiece of storytelling and world-building. The game offers an immersive experience in the Wild West with stunning graphics and engaging gameplay.',
                'thumbnail' => 'news/rdr2-review.jpg',
                'category_id' => 7, // Reviews
                'author_id' => 2, // Moderator
                'status' => 'published'
            ],
            [
                'title' => 'Cyberpunk 2077: Complete Guide',
                'slug' => 'cyberpunk-2077-complete-guide',
                'content' => 'This comprehensive guide covers everything you need to know about Cyberpunk 2077, from character creation to the best builds and strategies for completing the game.',
                'thumbnail' => 'news/cyberpunk-guide.jpg',
                'category_id' => 8, // Guides
                'author_id' => 1, // Admin
                'status' => 'published'
            ],
            [
                'title' => 'E3 2023 Gaming Conference Announced',
                'slug' => 'e3-2023-gaming-conference-announced',
                'content' => 'The Electronic Entertainment Expo (E3) has been officially announced for 2023. The event will feature major gaming companies showcasing their latest games and announcements.',
                'thumbnail' => 'news/e3-2023.jpg',
                'category_id' => 9, // Events
                'author_id' => 2, // Moderator
                'status' => 'published'
            ],
            [
                'title' => 'FIFA 23 Ultimate Team Tips',
                'slug' => 'fifa-23-ultimate-team-tips',
                'content' => 'Learn the best strategies for building and managing your Ultimate Team in FIFA 23. From squad building to trading, these tips will help you create the best team possible.',
                'thumbnail' => 'news/fifa23-tips.jpg',
                'category_id' => 8, // Guides
                'author_id' => 1, // Admin
                'status' => 'published'
            ]
        ];

        foreach ($news as $item) {
            $this->db->query(
                "INSERT INTO news (title, slug, content, thumbnail, category_id, author_id, status) 
                VALUES (:title, :slug, :content, :thumbnail, :category_id, :author_id, :status)",
                $item
            );
        }
    }
} 