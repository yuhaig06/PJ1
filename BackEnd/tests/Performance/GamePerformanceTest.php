<?php

namespace Tests\Performance;

use Tests\TestCase;
use App\Models\Game;
use App\Services\GameService;
use App\Helpers\CacheManager;

class GamePerformanceTest extends TestCase
{
    private $gameService;
    private $cacheManager;
    private $testGame;

    protected function setUp(): void
    {
        parent::setUp();
        $this->gameService = new GameService();
        $this->cacheManager = CacheManager::getInstance();
        
        // Create test game
        $this->testGame = Game::create([
            'title' => 'Test Game',
            'description' => 'Test Description',
            'price' => 29.99,
            'category' => 'Action',
            'release_date' => '2024-01-01',
            'developer' => 'Test Developer',
            'publisher' => 'Test Publisher',
            'status' => 'active'
        ]);
    }

    public function testGameListPerformance()
    {
        $startTime = microtime(true);
        
        // Test without cache
        $games = $this->gameService->getAllGames();
        $withoutCacheTime = microtime(true) - $startTime;
        
        // Test with cache
        $startTime = microtime(true);
        $cachedGames = $this->gameService->getAllGames();
        $withCacheTime = microtime(true) - $startTime;
        
        $this->assertLessThan($withoutCacheTime, $withCacheTime, 'Cached response should be faster');
        $this->assertLessThan(0.5, $withCacheTime, 'Cached response should be under 500ms');
    }

    public function testGameSearchPerformance()
    {
        $startTime = microtime(true);
        
        // Test search performance
        $results = $this->gameService->searchGames('Test');
        $searchTime = microtime(true) - $startTime;
        
        $this->assertLessThan(0.3, $searchTime, 'Search should be under 300ms');
    }

    public function testGameDetailPerformance()
    {
        $startTime = microtime(true);
        
        // Test without cache
        $game = $this->gameService->getGameById($this->testGame->id);
        $withoutCacheTime = microtime(true) - $startTime;
        
        // Test with cache
        $startTime = microtime(true);
        $cachedGame = $this->gameService->getGameById($this->testGame->id);
        $withCacheTime = microtime(true) - $startTime;
        
        $this->assertLessThan($withoutCacheTime, $withCacheTime, 'Cached response should be faster');
        $this->assertLessThan(0.2, $withCacheTime, 'Cached response should be under 200ms');
    }

    public function testConcurrentRequests()
    {
        $startTime = microtime(true);
        $requests = 10;
        $times = [];
        
        // Simulate concurrent requests
        for ($i = 0; $i < $requests; $i++) {
            $requestStart = microtime(true);
            $this->gameService->getAllGames();
            $times[] = microtime(true) - $requestStart;
        }
        
        $avgTime = array_sum($times) / count($times);
        $this->assertLessThan(0.5, $avgTime, 'Average response time should be under 500ms');
    }

    protected function tearDown(): void
    {
        $this->testGame->delete();
        $this->cacheManager->clear();
        parent::tearDown();
    }
} 