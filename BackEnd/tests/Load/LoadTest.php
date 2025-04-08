<?php

namespace Tests\Load;

use Tests\TestCase;
use App\Models\Game;
use App\Models\User;
use App\Services\GameService;
use App\Services\AuthService;
use App\Helpers\CacheManager;

class LoadTest extends TestCase
{
    private $gameService;
    private $authService;
    private $cacheManager;
    private $testUsers = [];
    private $testGames = [];

    protected function setUp(): void
    {
        parent::setUp();
        $this->gameService = new GameService();
        $this->authService = new AuthService();
        $this->cacheManager = CacheManager::getInstance();
        
        // Create test data
        $this->createTestData();
    }

    private function createTestData()
    {
        // Create test users
        for ($i = 0; $i < 10; $i++) {
            $this->testUsers[] = User::create([
                'username' => "testuser{$i}",
                'email' => "test{$i}@example.com",
                'password' => password_hash('password123', PASSWORD_DEFAULT),
                'role' => 'user'
            ]);
        }

        // Create test games
        for ($i = 0; $i < 20; $i++) {
            $this->testGames[] = Game::create([
                'title' => "Test Game {$i}",
                'description' => "Test Description {$i}",
                'price' => 29.99,
                'category' => 'Action',
                'release_date' => '2024-01-01',
                'developer' => 'Test Developer',
                'publisher' => 'Test Publisher',
                'status' => 'active'
            ]);
        }
    }

    public function testConcurrentUserLogin()
    {
        $startTime = microtime(true);
        $successCount = 0;
        $totalRequests = 50;

        // Simulate concurrent login attempts
        for ($i = 0; $i < $totalRequests; $i++) {
            $userIndex = $i % count($this->testUsers);
            $user = $this->testUsers[$userIndex];
            
            $result = $this->authService->login([
                'email' => $user->email,
                'password' => 'password123'
            ]);
            
            if ($result) {
                $successCount++;
            }
        }

        $totalTime = microtime(true) - $startTime;
        $requestsPerSecond = $totalRequests / $totalTime;

        $this->assertGreaterThan(10, $requestsPerSecond, 'Should handle at least 10 requests per second');
        $this->assertEquals($totalRequests, $successCount, 'All login attempts should succeed');
    }

    public function testConcurrentGameOperations()
    {
        $startTime = microtime(true);
        $operations = 100;
        $successCount = 0;

        // Simulate concurrent game operations
        for ($i = 0; $i < $operations; $i++) {
            $gameIndex = $i % count($this->testGames);
            $game = $this->testGames[$gameIndex];
            
            // Perform various operations
            $this->gameService->getGameById($game->id);
            $this->gameService->searchGames('Test');
            $this->gameService->getAllGames();
            
            $successCount++;
        }

        $totalTime = microtime(true) - $startTime;
        $operationsPerSecond = $operations / $totalTime;

        $this->assertGreaterThan(20, $operationsPerSecond, 'Should handle at least 20 operations per second');
        $this->assertEquals($operations, $successCount, 'All operations should succeed');
    }

    public function testCachePerformanceUnderLoad()
    {
        $startTime = microtime(true);
        $operations = 1000;
        $cacheHits = 0;

        // First, populate cache
        foreach ($this->testGames as $game) {
            $this->cacheManager->set("game:{$game->id}", $game, 3600);
        }

        // Test cache performance under load
        for ($i = 0; $i < $operations; $i++) {
            $gameIndex = $i % count($this->testGames);
            $game = $this->testGames[$gameIndex];
            
            if ($this->cacheManager->get("game:{$game->id}")) {
                $cacheHits++;
            }
        }

        $totalTime = microtime(true) - $startTime;
        $operationsPerSecond = $operations / $totalTime;
        $hitRate = ($cacheHits / $operations) * 100;

        $this->assertGreaterThan(100, $operationsPerSecond, 'Cache should handle at least 100 operations per second');
        $this->assertGreaterThan(95, $hitRate, 'Cache hit rate should be above 95%');
    }

    public function testDatabasePerformanceUnderLoad()
    {
        $startTime = microtime(true);
        $operations = 50;
        $successCount = 0;

        // Test database performance under load
        for ($i = 0; $i < $operations; $i++) {
            $gameIndex = $i % count($this->testGames);
            $game = $this->testGames[$gameIndex];
            
            // Perform database operations
            $updatedGame = Game::find($game->id);
            $updatedGame->title = "Updated Game {$i}";
            $updatedGame->save();
            
            $successCount++;
        }

        $totalTime = microtime(true) - $startTime;
        $operationsPerSecond = $operations / $totalTime;

        $this->assertGreaterThan(5, $operationsPerSecond, 'Database should handle at least 5 operations per second');
        $this->assertEquals($operations, $successCount, 'All database operations should succeed');
    }

    protected function tearDown(): void
    {
        // Clean up test data
        foreach ($this->testUsers as $user) {
            $user->delete();
        }
        foreach ($this->testGames as $game) {
            $game->delete();
        }
        $this->cacheManager->clear();
        parent::tearDown();
    }
} 