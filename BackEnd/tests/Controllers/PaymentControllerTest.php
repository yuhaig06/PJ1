<?php

namespace Tests\Controllers;

use PHPUnit\Framework\TestCase;
use App\Controllers\PaymentController;
use App\Services\PaymentService;
use App\Helpers\ErrorHandler;
use Mockery;

class PaymentControllerTest extends TestCase
{
    private $paymentController;
    private $paymentService;
    private $errorHandler;

    protected function setUp(): void
    {
        parent::setUp();
        
        // Mock payment service
        $this->paymentService = Mockery::mock('App\Services\PaymentService');
        
        // Mock error handler
        $this->errorHandler = Mockery::mock('App\Helpers\ErrorHandler');
        $this->errorHandler->shouldReceive('log')->andReturn(null);

        // Create payment controller with mocked dependencies
        $this->paymentController = new PaymentController();
        $this->paymentController->setDependencies($this->paymentService, $this->errorHandler);
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    public function testProcessPaymentSuccess()
    {
        // Mock payment service response
        $this->paymentService->shouldReceive('processPayment')
            ->with(1, 'credit_card')
            ->andReturn([
                'status' => 'success',
                'message' => 'Payment processed successfully',
                'data' => [
                    'transaction_id' => 1,
                    'charge_id' => 'ch_123456'
                ]
            ]);

        // Set up POST data
        $_POST['order_id'] = 1;
        $_POST['payment_method'] = 'credit_card';

        // Capture output
        ob_start();
        $this->paymentController->processPayment();
        $output = ob_get_clean();

        // Assert response
        $response = json_decode($output, true);
        $this->assertEquals('success', $response['status']);
        $this->assertEquals('Payment processed successfully', $response['message']);
        $this->assertEquals(1, $response['data']['transaction_id']);
        $this->assertEquals('ch_123456', $response['data']['charge_id']);
    }

    public function testProcessPaymentMissingParameters()
    {
        // Set up empty POST data
        $_POST = [];

        // Capture output
        ob_start();
        $this->paymentController->processPayment();
        $output = ob_get_clean();

        // Assert response
        $response = json_decode($output, true);
        $this->assertEquals('error', $response['status']);
        $this->assertEquals('Missing required parameters', $response['message']);
    }

    public function testProcessPaymentServiceException()
    {
        // Mock payment service to throw exception
        $this->paymentService->shouldReceive('processPayment')
            ->with(1, 'credit_card')
            ->andThrow(new \Exception('Payment processing failed'));

        // Set up POST data
        $_POST['order_id'] = 1;
        $_POST['payment_method'] = 'credit_card';

        // Capture output
        ob_start();
        $this->paymentController->processPayment();
        $output = ob_get_clean();

        // Assert response
        $response = json_decode($output, true);
        $this->assertEquals('error', $response['status']);
        $this->assertEquals('Payment processing failed', $response['message']);
    }

    public function testHandleWebhookSuccess()
    {
        // Mock payment service response
        $this->paymentService->shouldReceive('handleWebhook')
            ->with('stripe', Mockery::any())
            ->andReturn(['status' => 'success']);

        // Set up request URI
        $_SERVER['REQUEST_URI'] = '/payment/webhook/stripe';

        // Set up raw POST data
        $rawData = json_encode([
            'type' => 'charge.succeeded',
            'data' => [
                'object' => [
                    'metadata' => [
                        'transaction_id' => 1,
                        'order_id' => 1
                    ]
                ]
            ]
        ]);

        // Mock file_get_contents
        $fileGetContents = Mockery::mock('alias:file_get_contents');
        $fileGetContents->shouldReceive('file_get_contents')
            ->with('php://input')
            ->andReturn($rawData);

        // Capture output
        ob_start();
        $this->paymentController->handleWebhook();
        $output = ob_get_clean();

        // Assert response
        $response = json_decode($output, true);
        $this->assertEquals('success', $response['status']);
    }

    public function testHandleWebhookServiceException()
    {
        // Mock payment service to throw exception
        $this->paymentService->shouldReceive('handleWebhook')
            ->with('stripe', Mockery::any())
            ->andThrow(new \Exception('Webhook handling failed'));

        // Set up request URI
        $_SERVER['REQUEST_URI'] = '/payment/webhook/stripe';

        // Set up raw POST data
        $rawData = json_encode([
            'type' => 'charge.succeeded',
            'data' => [
                'object' => [
                    'metadata' => [
                        'transaction_id' => 1,
                        'order_id' => 1
                    ]
                ]
            ]
        ]);

        // Mock file_get_contents
        $fileGetContents = Mockery::mock('alias:file_get_contents');
        $fileGetContents->shouldReceive('file_get_contents')
            ->with('php://input')
            ->andReturn($rawData);

        // Capture output
        ob_start();
        $this->paymentController->handleWebhook();
        $output = ob_get_clean();

        // Assert response
        $response = json_decode($output, true);
        $this->assertEquals('error', $response['status']);
        $this->assertEquals('Webhook handling failed', $response['message']);
    }

    public function testHandleReturnSuccess()
    {
        // Set up request URI
        $_SERVER['REQUEST_URI'] = '/payment/return/paypal';

        // Set up GET parameters
        $_GET['paymentId'] = 'PAY-123456';
        $_GET['PayerID'] = 'PAYER123456';

        // Mock header function
        $header = Mockery::mock('alias:header');
        $header->shouldReceive('header')
            ->with(Mockery::any())
            ->andReturn(null);

        // Test PayPal return
        $this->paymentController->handleReturn();

        // No assertion needed as we're just testing that no exception is thrown
        $this->assertTrue(true);
    }

    public function testHandleReturnMissingParameters()
    {
        // Set up request URI
        $_SERVER['REQUEST_URI'] = '/payment/return/paypal';

        // Set up empty GET parameters
        $_GET = [];

        // Mock header function
        $header = Mockery::mock('alias:header');
        $header->shouldReceive('header')
            ->with(Mockery::any())
            ->andReturn(null);

        // Test PayPal return with missing parameters
        $this->paymentController->handleReturn();

        // No assertion needed as we're just testing that no exception is thrown
        $this->assertTrue(true);
    }

    public function testHandleReturnWithInvalidMethod()
    {
        // Set up request URI with invalid payment method
        $_SERVER['REQUEST_URI'] = '/payment/return/invalid_method';

        // Mock header function
        $header = Mockery::mock('alias:header');
        $header->shouldReceive('header')
            ->with(Mockery::any())
            ->andReturn(null);

        // Test return with invalid payment method
        $this->paymentController->handleReturn();

        // No assertion needed as we're just testing that no exception is thrown
        $this->assertTrue(true);
    }
} 