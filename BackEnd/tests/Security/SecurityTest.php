<?php

namespace Tests\Security;

use Tests\TestCase;
use App\Helpers\IPBlocker;
use App\Helpers\AuditLogger;
use App\Models\User;
use App\Services\AuthService;

class SecurityTest extends TestCase
{
    private $ipBlocker;
    private $auditLogger;
    private $authService;
    private $testUser;

    protected function setUp(): void
    {
        parent::setUp();
        $this->ipBlocker = IPBlocker::getInstance();
        $this->auditLogger = AuditLogger::getInstance();
        $this->authService = new AuthService();
        
        // Create test user
        $this->testUser = User::create([
            'username' => 'testuser',
            'email' => 'test@example.com',
            'password' => password_hash('password123', PASSWORD_DEFAULT),
            'role' => 'user'
        ]);
    }

    public function testSQLInjectionPrevention()
    {
        $maliciousInput = "'; DROP TABLE users; --";
        
        // Test login with SQL injection attempt
        $result = $this->authService->login([
            'email' => $maliciousInput,
            'password' => $maliciousInput
        ]);
        
        $this->assertFalse($result, 'SQL injection attempt should be prevented');
    }

    public function testXSSPrevention()
    {
        $xssPayload = '<script>alert("xss")</script>';
        
        // Test game creation with XSS attempt
        $response = $this->post('/api/games', [
            'title' => $xssPayload,
            'description' => $xssPayload
        ]);
        
        $this->assertStringNotContainsString('<script>', $response->getContent());
    }

    public function testCSRFProtection()
    {
        // Test request without CSRF token
        $response = $this->post('/api/games', [
            'title' => 'Test Game',
            'description' => 'Test Description'
        ]);
        
        $this->assertEquals(403, $response->getStatusCode(), 'Request without CSRF token should be rejected');
    }

    public function testRateLimiting()
    {
        $ip = '127.0.0.1';
        
        // Make multiple requests
        for ($i = 0; $i < 10; $i++) {
            $this->ipBlocker->logRequest($ip, 'GET', '/api/games');
        }
        
        $this->assertTrue($this->ipBlocker->isRateLimited($ip), 'IP should be rate limited after multiple requests');
    }

    public function testPasswordHashing()
    {
        $password = 'testPassword123';
        $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
        
        $this->assertTrue(password_verify($password, $hashedPassword), 'Password verification should work');
        $this->assertNotEquals($password, $hashedPassword, 'Password should be hashed');
    }

    public function testJWTTokenValidation()
    {
        // Test invalid token
        $response = $this->get('/api/user/profile', [
            'Authorization' => 'Bearer invalid_token'
        ]);
        
        $this->assertEquals(401, $response->getStatusCode(), 'Invalid JWT token should be rejected');
    }

    public function testRoleBasedAccess()
    {
        // Test admin-only route with user role
        $response = $this->get('/api/admin/dashboard', [
            'Authorization' => 'Bearer ' . $this->authService->generateToken($this->testUser)
        ]);
        
        $this->assertEquals(403, $response->getStatusCode(), 'User should not access admin routes');
    }

    public function testAuditLogging()
    {
        $action = 'test_action';
        $this->auditLogger->logUserAction($this->testUser->id, $action);
        
        $logs = $this->auditLogger->getRecentLogs();
        $this->assertNotEmpty($logs, 'Audit logs should be created');
    }

    protected function tearDown(): void
    {
        $this->testUser->delete();
        parent::tearDown();
    }
} 