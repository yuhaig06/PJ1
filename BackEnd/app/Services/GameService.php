<?php

namespace App\Services;

use App\Models\GameModel;
use App\Models\CategoryModel;
use App\Models\TagModel;
use App\Helpers\AuditLogger;
use App\Helpers\CacheManager;

class GameService {
    private static $instance = null;
    private $gameModel;
    private $categoryModel;
    private $tagModel;
    private $auditLogger;
    private $cache;

    private function __construct() {
        $this->gameModel = new GameModel();
        $this->categoryModel = new CategoryModel();
        $this->tagModel = new TagModel();
        $this->auditLogger = AuditLogger::getInstance();
        $this->cache = CacheManager::getInstance();
    }

    public static function getInstance() {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    public function createGame($data) {
        // Validate category
        if (!$this->categoryModel->findById($data['category_id'])) {
            throw new \Exception('Invalid category');
        }

        // Validate tags
        if (isset($data['tags'])) {
            foreach ($data['tags'] as $tagId) {
                if (!$this->tagModel->findById($tagId)) {
                    throw new \Exception('Invalid tag');
                }
            }
        }

        // Create game
        $gameId = $this->gameModel->create($data);

        // Add tags
        if (isset($data['tags'])) {
            $this->gameModel->addTags($gameId, $data['tags']);
        }

        // Clear cache
        $this->cache->delete('games:list');
        $this->cache->delete('games:featured');

        // Log creation
        $this->auditLogger->log('game', 'game_created', [
            'game_id' => $gameId,
            'user_id' => $data['user_id'] ?? null
        ]);

        return $gameId;
    }

    public function updateGame($gameId, $data) {
        $game = $this->gameModel->findById($gameId);
        if (!$game) {
            throw new \Exception('Game not found');
        }

        // Validate category
        if (isset($data['category_id']) && !$this->categoryModel->findById($data['category_id'])) {
            throw new \Exception('Invalid category');
        }

        // Update game
        $this->gameModel->update($gameId, $data);

        // Update tags
        if (isset($data['tags'])) {
            $this->gameModel->updateTags($gameId, $data['tags']);
        }

        // Clear cache
        $this->cache->delete("game:{$gameId}");
        $this->cache->delete('games:list');
        $this->cache->delete('games:featured');

        // Log update
        $this->auditLogger->log('game', 'game_updated', [
            'game_id' => $gameId,
            'user_id' => $data['user_id'] ?? null
        ]);
    }

    public function deleteGame($gameId) {
        $game = $this->gameModel->findById($gameId);
        if (!$game) {
            throw new \Exception('Game not found');
        }

        // Delete game
        $this->gameModel->delete($gameId);

        // Clear cache
        $this->cache->delete("game:{$gameId}");
        $this->cache->delete('games:list');
        $this->cache->delete('games:featured');

        // Log deletion
        $this->auditLogger->log('game', 'game_deleted', [
            'game_id' => $gameId
        ]);
    }

    public function getGame($gameId) {
        // Try cache first
        $game = $this->cache->get("game:{$gameId}");
        if (!$game) {
            $game = $this->gameModel->findById($gameId);
            if ($game) {
                $this->cache->set("game:{$gameId}", $game, 3600);
            }
        }

        if (!$game) {
            throw new \Exception('Game not found');
        }

        return $game;
    }

    public function getGames($filters = [], $page = 1, $limit = 10) {
        $cacheKey = 'games:list:' . md5(json_encode($filters) . $page . $limit);
        
        // Try cache first
        $result = $this->cache->get($cacheKey);
        if (!$result) {
            $result = $this->gameModel->findAll($filters, $page, $limit);
            if ($result) {
                $this->cache->set($cacheKey, $result, 3600);
            }
        }

        return $result;
    }

    public function getFeaturedGames($limit = 5) {
        // Try cache first
        $games = $this->cache->get('games:featured');
        if (!$games) {
            $games = $this->gameModel->findFeatured($limit);
            if ($games) {
                $this->cache->set('games:featured', $games, 3600);
            }
        }

        return $games;
    }

    public function searchGames($query, $page = 1, $limit = 10) {
        $cacheKey = 'games:search:' . md5($query . $page . $limit);
        
        // Try cache first
        $result = $this->cache->get($cacheKey);
        if (!$result) {
            $result = $this->gameModel->search($query, $page, $limit);
            if ($result) {
                $this->cache->set($cacheKey, $result, 3600);
            }
        }

        return $result;
    }

    public function getGamesByCategory($categoryId, $page = 1, $limit = 10) {
        $cacheKey = "games:category:{$categoryId}:{$page}:{$limit}";
        
        // Try cache first
        $result = $this->cache->get($cacheKey);
        if (!$result) {
            $result = $this->gameModel->findByCategory($categoryId, $page, $limit);
            if ($result) {
                $this->cache->set($cacheKey, $result, 3600);
            }
        }

        return $result;
    }

    public function getGamesByTag($tagId, $page = 1, $limit = 10) {
        $cacheKey = "games:tag:{$tagId}:{$page}:{$limit}";
        
        // Try cache first
        $result = $this->cache->get($cacheKey);
        if (!$result) {
            $result = $this->gameModel->findByTag($tagId, $page, $limit);
            if ($result) {
                $this->cache->set($cacheKey, $result, 3600);
            }
        }

        return $result;
    }

    public function updateGameStatus($gameId, $status) {
        $game = $this->gameModel->findById($gameId);
        if (!$game) {
            throw new \Exception('Game not found');
        }

        $this->gameModel->updateStatus($gameId, $status);

        // Clear cache
        $this->cache->delete("game:{$gameId}");
        $this->cache->delete('games:list');
        $this->cache->delete('games:featured');

        // Log status change
        $this->auditLogger->log('game', 'game_status_updated', [
            'game_id' => $gameId,
            'status' => $status
        ]);
    }
} 