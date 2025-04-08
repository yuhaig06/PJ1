<?php

namespace App\Tests\Game;

use App\Tests\TestCase;
use App\Controllers\Game\GameController;
use App\Controllers\Game\CategoryController;
use App\Controllers\Game\TagController;

class GameTest extends TestCase
{
    private $gameController;
    private $categoryController;
    private $tagController;

    protected function setUp(): void
    {
        parent::setUp();
        $this->gameController = new GameController();
        $this->categoryController = new CategoryController();
        $this->tagController = new TagController();
    }

    public function testCreateGame()
    {
        // Create test category
        $categoryId = $this->createTestCategory();
        
        $gameData = [
            'name' => 'Test Game ' . uniqid(),
            'slug' => 'test-game-' . uniqid(),
            'description' => 'Test game description',
            'content' => 'Test game content',
            'thumbnail' => 'games/test.jpg',
            'price' => 29.99,
            'category_id' => $categoryId,
            'status' => 'published'
        ];

        $result = $this->gameController->create($gameData);
        
        $this->assertTrue($result['status'] === 'success');
        $this->assertTrue(isset($result['data']['id']));
        
        // Verify game was created in database
        $game = $this->db->query(
            "SELECT * FROM games WHERE id = :id",
            ['id' => $result['data']['id']]
        )->fetch();

        $this->assertNotNull($game);
        $this->assertEquals($gameData['name'], $game['name']);
        $this->assertEquals($gameData['price'], $game['price']);
    }

    public function testUpdateGame()
    {
        // Create test game
        $gameId = $this->createTestGame();
        
        $updateData = [
            'name' => 'Updated Game ' . uniqid(),
            'price' => 39.99,
            'status' => 'draft'
        ];

        $result = $this->gameController->update($gameId, $updateData);
        
        $this->assertTrue($result['status'] === 'success');
        
        // Verify game was updated in database
        $game = $this->db->query(
            "SELECT * FROM games WHERE id = :id",
            ['id' => $gameId]
        )->fetch();

        $this->assertEquals($updateData['name'], $game['name']);
        $this->assertEquals($updateData['price'], $game['price']);
        $this->assertEquals($updateData['status'], $game['status']);
    }

    public function testDeleteGame()
    {
        // Create test game
        $gameId = $this->createTestGame();

        $result = $this->gameController->delete($gameId);
        
        $this->assertTrue($result['status'] === 'success');
        
        // Verify game was deleted from database
        $game = $this->db->query(
            "SELECT * FROM games WHERE id = :id",
            ['id' => $gameId]
        )->fetch();

        $this->assertNull($game);
    }

    public function testGetGameDetails()
    {
        // Create test game
        $gameId = $this->createTestGame();

        $result = $this->gameController->show($gameId);
        
        $this->assertTrue($result['status'] === 'success');
        $this->assertTrue(isset($result['data']));
        $this->assertEquals($gameId, $result['data']['id']);
    }

    public function testListGames()
    {
        // Create multiple test games
        for ($i = 0; $i < 3; $i++) {
            $this->createTestGame();
        }

        $result = $this->gameController->index();
        
        $this->assertTrue($result['status'] === 'success');
        $this->assertTrue(isset($result['data']));
        $this->assertGreaterThanOrEqual(3, count($result['data']));
    }

    public function testCreateCategory()
    {
        $categoryData = [
            'name' => 'Test Category ' . uniqid(),
            'slug' => 'test-category-' . uniqid(),
            'description' => 'Test category description'
        ];

        $result = $this->categoryController->create($categoryData);
        
        $this->assertTrue($result['status'] === 'success');
        $this->assertTrue(isset($result['data']['id']));
        
        // Verify category was created in database
        $category = $this->db->query(
            "SELECT * FROM categories WHERE id = :id",
            ['id' => $result['data']['id']]
        )->fetch();

        $this->assertNotNull($category);
        $this->assertEquals($categoryData['name'], $category['name']);
    }

    public function testCreateTag()
    {
        $tagData = [
            'name' => 'Test Tag ' . uniqid(),
            'slug' => 'test-tag-' . uniqid()
        ];

        $result = $this->tagController->create($tagData);
        
        $this->assertTrue($result['status'] === 'success');
        $this->assertTrue(isset($result['data']['id']));
        
        // Verify tag was created in database
        $tag = $this->db->query(
            "SELECT * FROM tags WHERE id = :id",
            ['id' => $result['data']['id']]
        )->fetch();

        $this->assertNotNull($tag);
        $this->assertEquals($tagData['name'], $tag['name']);
    }

    public function testAddTagToGame()
    {
        // Create test game and tag
        $gameId = $this->createTestGame();
        $tagId = $this->createTestTag();

        $result = $this->gameController->addTag($gameId, $tagId);
        
        $this->assertTrue($result['status'] === 'success');
        
        // Verify tag was added to game
        $gameTag = $this->db->query(
            "SELECT * FROM game_tags WHERE game_id = :game_id AND tag_id = :tag_id",
            ['game_id' => $gameId, 'tag_id' => $tagId]
        )->fetch();

        $this->assertNotNull($gameTag);
    }
} 