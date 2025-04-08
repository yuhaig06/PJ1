<?php

namespace App\Tests\Auth;

use App\Tests\TestCase;
use App\Controllers\Auth\LoginController;
use App\Controllers\Auth\RegisterController;
use App\Controllers\Auth\ForgotPasswordController;

class AuthTest extends TestCase
{
    private $loginController;
    private $registerController;
    private $forgotPasswordController;

    protected function setUp(): void
    {
        parent::setUp();
        $this->loginController = new LoginController();
        $this->registerController = new RegisterController();
        $this->forgotPasswordController = new ForgotPasswordController();
    }

    public function testUserRegistration()
    {
        $userData = [
            'username' => 'testuser_' . uniqid(),
            'email' => 'test_' . uniqid() . '@example.com',
            'password' => 'password123',
            'password_confirmation' => 'password123',
            'full_name' => 'Test User'
        ];

        $result = $this->registerController->register($userData);
        
        $this->assertTrue($result['status'] === 'success');
        $this->assertTrue(isset($result['message']));
        
        // Verify user was created in database
        $user = $this->db->query(
            "SELECT * FROM users WHERE email = :email",
            ['email' => $userData['email']]
        )->fetch();

        $this->assertNotNull($user);
        $this->assertEquals($userData['username'], $user['username']);
        $this->assertEquals($userData['email'], $user['email']);
    }

    public function testUserLogin()
    {
        // Create test user
        $userId = $this->createTestUser();
        $user = $this->db->query(
            "SELECT * FROM users WHERE id = :id",
            ['id' => $userId]
        )->fetch();

        $loginData = [
            'email' => $user['email'],
            'password' => 'password123'
        ];

        $result = $this->loginController->login($loginData);
        
        $this->assertTrue($result['status'] === 'success');
        $this->assertTrue(isset($result['data']['token']));
        $this->assertTrue(isset($result['data']['user']));
        $this->assertEquals($user['email'], $result['data']['user']['email']);
    }

    public function testForgotPassword()
    {
        // Create test user
        $userId = $this->createTestUser();
        $user = $this->db->query(
            "SELECT * FROM users WHERE id = :id",
            ['id' => $userId]
        )->fetch();

        $result = $this->forgotPasswordController->forgotPassword(['email' => $user['email']]);
        
        $this->assertTrue($result['status'] === 'success');
        $this->assertTrue(isset($result['message']));
    }

    public function testInvalidLogin()
    {
        $loginData = [
            'email' => 'nonexistent@example.com',
            'password' => 'wrongpassword'
        ];

        $result = $this->loginController->login($loginData);
        
        $this->assertTrue($result['status'] === 'error');
        $this->assertTrue(isset($result['message']));
    }

    public function testInvalidRegistration()
    {
        $userData = [
            'username' => 'test',
            'email' => 'invalid-email',
            'password' => '123',
            'password_confirmation' => '456',
            'full_name' => ''
        ];

        $result = $this->registerController->register($userData);
        
        $this->assertTrue($result['status'] === 'error');
        $this->assertTrue(isset($result['errors']));
    }
} 