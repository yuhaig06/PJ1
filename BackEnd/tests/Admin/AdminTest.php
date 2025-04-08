<?php

namespace App\Tests\Admin;

use App\Tests\TestCase;
use App\Controllers\Admin\UserController;
use App\Controllers\Admin\GameController;
use App\Controllers\Admin\NewsController;
use App\Controllers\Admin\ReportController;
use App\Controllers\Admin\CommentController;

class AdminTest extends TestCase
{
    private $userController;
    private $gameController;
    private $newsController;
    private $reportController;
    private $commentController;

    protected function setUp(): void
    {
        parent::setUp();
        $this->userController = new UserController();
        $this->gameController = new GameController();
        $this->newsController = new NewsController();
        $this->reportController = new ReportController();
        $this->commentController = new CommentController();
    }

    public function testListUsers()
    {
        // Create multiple test users
        for ($i = 0; $i < 3; $i++) {
            $this->createTestUser();
        }

        $result = $this->userController->index();
        
        $this->assertTrue($result['status'] === 'success');
        $this->assertTrue(isset($result['data']));
        $this->assertGreaterThanOrEqual(3, count($result['data']));
    }

    public function testUpdateUserStatus()
    {
        // Create test user
        $userId = $this->createTestUser();
        
        $result = $this->userController->updateStatus($userId, 'banned');
        
        $this->assertTrue($result['status'] === 'success');
        
        // Verify user status was updated
        $user = $this->db->query(
            "SELECT * FROM users WHERE id = :id",
            ['id' => $userId]
        )->fetch();

        $this->assertEquals('banned', $user['status']);
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

    public function testUpdateGameStatus()
    {
        // Create test game
        $gameId = $this->createTestGame();
        
        $result = $this->gameController->updateStatus($gameId, 'draft');
        
        $this->assertTrue($result['status'] === 'success');
        
        // Verify game status was updated
        $game = $this->db->query(
            "SELECT * FROM games WHERE id = :id",
            ['id' => $gameId]
        )->fetch();

        $this->assertEquals('draft', $game['status']);
    }

    public function testListNews()
    {
        // Create multiple test news
        for ($i = 0; $i < 3; $i++) {
            $this->createTestNews();
        }

        $result = $this->newsController->index();
        
        $this->assertTrue($result['status'] === 'success');
        $this->assertTrue(isset($result['data']));
        $this->assertGreaterThanOrEqual(3, count($result['data']));
    }

    public function testUpdateNewsStatus()
    {
        // Create test news
        $newsId = $this->createTestNews();
        
        $result = $this->newsController->updateStatus($newsId, 'draft');
        
        $this->assertTrue($result['status'] === 'success');
        
        // Verify news status was updated
        $news = $this->db->query(
            "SELECT * FROM news WHERE id = :id",
            ['id' => $newsId]
        )->fetch();

        $this->assertEquals('draft', $news['status']);
    }

    public function testListReports()
    {
        // Create multiple test reports
        for ($i = 0; $i < 3; $i++) {
            $this->createTestReport();
        }

        $result = $this->reportController->index();
        
        $this->assertTrue($result['status'] === 'success');
        $this->assertTrue(isset($result['data']));
        $this->assertGreaterThanOrEqual(3, count($result['data']));
    }

    public function testHandleReport()
    {
        // Create test report
        $reportId = $this->createTestReport();
        
        $result = $this->reportController->handle($reportId, 'resolved', 'Issue has been resolved');
        
        $this->assertTrue($result['status'] === 'success');
        
        // Verify report was handled
        $report = $this->db->query(
            "SELECT * FROM reports WHERE id = :id",
            ['id' => $reportId]
        )->fetch();

        $this->assertEquals('resolved', $report['status']);
        $this->assertNotNull($report['handled_at']);
    }

    public function testListComments()
    {
        // Create multiple test comments
        for ($i = 0; $i < 3; $i++) {
            $this->createTestComment();
        }

        $result = $this->commentController->index();
        
        $this->assertTrue($result['status'] === 'success');
        $this->assertTrue(isset($result['data']));
        $this->assertGreaterThanOrEqual(3, count($result['data']));
    }

    public function testUpdateCommentStatus()
    {
        // Create test comment
        $commentId = $this->createTestComment();
        
        $result = $this->commentController->updateStatus($commentId, 'approved');
        
        $this->assertTrue($result['status'] === 'success');
        
        // Verify comment status was updated
        $comment = $this->db->query(
            "SELECT * FROM comments WHERE id = :id",
            ['id' => $commentId]
        )->fetch();

        $this->assertEquals('approved', $comment['status']);
    }

    public function testGetDashboardStats()
    {
        // Create test data
        $this->createTestUser();
        $this->createTestGame();
        $this->createTestNews();
        $this->createTestOrder();
        $this->createTestReport();

        $result = $this->userController->getDashboardStats();
        
        $this->assertTrue($result['status'] === 'success');
        $this->assertTrue(isset($result['data']));
        $this->assertTrue(isset($result['data']['total_users']));
        $this->assertTrue(isset($result['data']['total_games']));
        $this->assertTrue(isset($result['data']['total_news']));
        $this->assertTrue(isset($result['data']['total_orders']));
        $this->assertTrue(isset($result['data']['total_reports']));
    }
} 