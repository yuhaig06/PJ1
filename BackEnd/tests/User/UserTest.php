<?php

namespace App\Tests\User;

use App\Tests\TestCase;
use App\Controllers\User\UserController;
use App\Controllers\User\WalletController;
use App\Controllers\User\OrderController;

class UserTest extends TestCase
{
    private $userController;
    private $walletController;
    private $orderController;

    protected function setUp(): void
    {
        parent::setUp();
        $this->userController = new UserController();
        $this->walletController = new WalletController();
        $this->orderController = new OrderController();
    }

    public function testGetUserProfile()
    {
        // Create test user
        $userId = $this->createTestUser();

        $result = $this->userController->profile($userId);
        
        $this->assertTrue($result['status'] === 'success');
        $this->assertTrue(isset($result['data']));
        $this->assertEquals($userId, $result['data']['id']);
    }

    public function testUpdateUserProfile()
    {
        // Create test user
        $userId = $this->createTestUser();
        
        $updateData = [
            'full_name' => 'Updated User Name',
            'phone' => '0123456789',
            'address' => 'Updated Address'
        ];

        $result = $this->userController->updateProfile($userId, $updateData);
        
        $this->assertTrue($result['status'] === 'success');
        
        // Verify user was updated in database
        $user = $this->db->query(
            "SELECT * FROM users WHERE id = :id",
            ['id' => $userId]
        )->fetch();

        $this->assertEquals($updateData['full_name'], $user['full_name']);
        $this->assertEquals($updateData['phone'], $user['phone']);
        $this->assertEquals($updateData['address'], $user['address']);
    }

    public function testChangePassword()
    {
        // Create test user
        $userId = $this->createTestUser();
        
        $passwordData = [
            'current_password' => 'password123',
            'new_password' => 'newpassword123',
            'new_password_confirmation' => 'newpassword123'
        ];

        $result = $this->userController->changePassword($userId, $passwordData);
        
        $this->assertTrue($result['status'] === 'success');
        
        // Verify password was changed
        $user = $this->db->query(
            "SELECT * FROM users WHERE id = :id",
            ['id' => $userId]
        )->fetch();

        $this->assertTrue(password_verify($passwordData['new_password'], $user['password']));
    }

    public function testGetUserWallet()
    {
        // Create test user and wallet
        $userId = $this->createTestUser();
        $this->createTestWallet($userId);

        $result = $this->walletController->getWallet($userId);
        
        $this->assertTrue($result['status'] === 'success');
        $this->assertTrue(isset($result['data']));
        $this->assertEquals($userId, $result['data']['user_id']);
    }

    public function testDepositToWallet()
    {
        // Create test user and wallet
        $userId = $this->createTestUser();
        $this->createTestWallet($userId);
        
        $depositData = [
            'amount' => 100.00,
            'payment_method' => 'credit_card'
        ];

        $result = $this->walletController->deposit($userId, $depositData);
        
        $this->assertTrue($result['status'] === 'success');
        $this->assertTrue(isset($result['data']['transaction_id']));
        
        // Verify transaction was created
        $transaction = $this->db->query(
            "SELECT * FROM transactions WHERE id = :id",
            ['id' => $result['data']['transaction_id']]
        )->fetch();

        $this->assertNotNull($transaction);
        $this->assertEquals($depositData['amount'], $transaction['amount']);
        $this->assertEquals('deposit', $transaction['type']);
    }

    public function testGetUserOrders()
    {
        // Create test user and orders
        $userId = $this->createTestUser();
        for ($i = 0; $i < 3; $i++) {
            $this->createTestOrder($userId);
        }

        $result = $this->orderController->getUserOrders($userId);
        
        $this->assertTrue($result['status'] === 'success');
        $this->assertTrue(isset($result['data']));
        $this->assertGreaterThanOrEqual(3, count($result['data']));
    }

    public function testCreateOrder()
    {
        // Create test user and game
        $userId = $this->createTestUser();
        $gameId = $this->createTestGame();
        
        $orderData = [
            'game_id' => $gameId,
            'payment_method' => 'wallet'
        ];

        $result = $this->orderController->create($userId, $orderData);
        
        $this->assertTrue($result['status'] === 'success');
        $this->assertTrue(isset($result['data']['order_id']));
        
        // Verify order was created
        $order = $this->db->query(
            "SELECT * FROM orders WHERE id = :id",
            ['id' => $result['data']['order_id']]
        )->fetch();

        $this->assertNotNull($order);
        $this->assertEquals($userId, $order['user_id']);
        $this->assertEquals('pending', $order['payment_status']);
    }

    public function testGetOrderDetails()
    {
        // Create test user and order
        $userId = $this->createTestUser();
        $orderId = $this->createTestOrder($userId);

        $result = $this->orderController->show($orderId);
        
        $this->assertTrue($result['status'] === 'success');
        $this->assertTrue(isset($result['data']));
        $this->assertEquals($orderId, $result['data']['id']);
    }
} 