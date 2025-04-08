<?php

namespace Tests\Services;

use PHPUnit\Framework\TestCase;
use App\Services\PaymentService;
use App\Helpers\ErrorHandler;
use Mockery;

class PaymentServiceTest extends TestCase
{
    private $paymentService;
    private $db;
    private $errorHandler;

    protected function setUp(): void
    {
        parent::setUp();
        
        // Mock database
        $this->db = Mockery::mock('App\Database\Database');
        $this->db->shouldReceive('query')->andReturnSelf();
        $this->db->shouldReceive('fetch')->andReturn([
            'id' => 1,
            'user_id' => 1,
            'total_amount' => 100,
            'payment_status' => 'pending'
        ]);
        $this->db->shouldReceive('lastInsertId')->andReturn(1);

        // Mock error handler
        $this->errorHandler = Mockery::mock('App\Helpers\ErrorHandler');
        $this->errorHandler->shouldReceive('log')->andReturn(null);

        // Create payment service with mocked dependencies
        $this->paymentService = new PaymentService();
        $this->paymentService->setDependencies($this->db, $this->errorHandler);
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    public function testProcessCreditCardPayment()
    {
        // Mock Stripe charge
        $stripeCharge = Mockery::mock('Stripe\Charge');
        $stripeCharge->shouldReceive('getAttribute')->with('status')->andReturn('succeeded');
        $stripeCharge->shouldReceive('getAttribute')->with('id')->andReturn('ch_123456');

        // Mock Stripe class
        $stripe = Mockery::mock('alias:Stripe\Stripe');
        $stripe->shouldReceive('setApiKey')->andReturn(null);

        $stripeChargeClass = Mockery::mock('alias:Stripe\Charge');
        $stripeChargeClass->shouldReceive('create')->andReturn($stripeCharge);

        // Test credit card payment
        $result = $this->paymentService->processPayment(1, 'credit_card');

        $this->assertEquals('success', $result['status']);
        $this->assertEquals('Payment processed successfully', $result['message']);
        $this->assertEquals(1, $result['data']['transaction_id']);
        $this->assertEquals('ch_123456', $result['data']['charge_id']);
    }

    public function testProcessPayPalPayment()
    {
        // Mock PayPal payment
        $paypalPayment = Mockery::mock('PayPal\Api\Payment');
        $paypalPayment->shouldReceive('getId')->andReturn('PAY-123456');
        $paypalPayment->shouldReceive('getApprovalLink')->andReturn('https://www.paypal.com/checkout');

        // Mock PayPal classes
        $apiContext = Mockery::mock('PayPal\Rest\ApiContext');
        $oAuthTokenCredential = Mockery::mock('PayPal\Auth\OAuthTokenCredential');
        $oAuthTokenCredential->shouldReceive('getAccessToken')->andReturn('access_token');

        $paypalPaymentClass = Mockery::mock('alias:PayPal\Api\Payment');
        $paypalPaymentClass->shouldReceive('setIntent')->andReturnSelf();
        $paypalPaymentClass->shouldReceive('setPayer')->andReturnSelf();
        $paypalPaymentClass->shouldReceive('setTransactions')->andReturnSelf();
        $paypalPaymentClass->shouldReceive('setRedirectUrls')->andReturnSelf();
        $paypalPaymentClass->shouldReceive('create')->andReturn($paypalPayment);

        // Test PayPal payment
        $result = $this->paymentService->processPayment(1, 'paypal');

        $this->assertEquals('success', $result['status']);
        $this->assertEquals('PayPal payment initiated', $result['message']);
        $this->assertEquals(1, $result['data']['transaction_id']);
        $this->assertEquals('PAY-123456', $result['data']['payment_id']);
        $this->assertEquals('https://www.paypal.com/checkout', $result['data']['approval_url']);
    }

    public function testProcessMomoPayment()
    {
        // Mock MoMo response
        $momoResponse = [
            'errorCode' => 0,
            'payUrl' => 'https://payment.momo.vn/pay'
        ];

        // Mock cURL functions
        $ch = Mockery::mock('alias:curl_init');
        $ch->shouldReceive('curl_setopt')->andReturn(null);
        $ch->shouldReceive('curl_exec')->andReturn(json_encode($momoResponse));
        $ch->shouldReceive('curl_getinfo')->with(CURLINFO_HTTP_CODE)->andReturn(200);
        $ch->shouldReceive('curl_close')->andReturn(null);

        // Test MoMo payment
        $result = $this->paymentService->processPayment(1, 'momo');

        $this->assertEquals('success', $result['status']);
        $this->assertEquals('MoMo payment initiated', $result['message']);
        $this->assertEquals(1, $result['data']['transaction_id']);
        $this->assertEquals('https://payment.momo.vn/pay', $result['data']['payment_url']);
    }

    public function testProcessVNPayPayment()
    {
        // Test VNPay payment
        $result = $this->paymentService->processPayment(1, 'vnpay');

        $this->assertEquals('success', $result['status']);
        $this->assertEquals('VNPay payment initiated', $result['message']);
        $this->assertEquals(1, $result['data']['transaction_id']);
        $this->assertStringContainsString('https://sandbox.vnpayment.vn/paymentv2/vpcpay.html', $result['data']['payment_url']);
    }

    public function testHandleStripeWebhook()
    {
        // Mock Stripe event
        $stripeEvent = Mockery::mock('Stripe\Event');
        $stripeEvent->shouldReceive('getAttribute')->with('type')->andReturn('charge.succeeded');

        $charge = Mockery::mock('Stripe\Charge');
        $charge->shouldReceive('getAttribute')->with('metadata')->andReturn([
            'transaction_id' => 1,
            'order_id' => 1
        ]);

        $stripeEvent->shouldReceive('getAttribute')->with('data')->andReturn([
            'object' => $charge
        ]);

        // Mock Stripe Event class
        $stripeEventClass = Mockery::mock('alias:Stripe\Event');
        $stripeEventClass->shouldReceive('constructFrom')->andReturn($stripeEvent);

        // Test Stripe webhook
        $result = $this->paymentService->handleWebhook('stripe', json_encode([
            'type' => 'charge.succeeded',
            'data' => [
                'object' => [
                    'metadata' => [
                        'transaction_id' => 1,
                        'order_id' => 1
                    ]
                ]
            ]
        ]));

        $this->assertEquals('success', $result['status']);
    }

    public function testHandlePayPalWebhook()
    {
        // Test PayPal webhook
        $result = $this->paymentService->handleWebhook('paypal', json_encode([
            'event_type' => 'PAYMENT.CAPTURE.COMPLETED',
            'resource' => [
                'custom_id' => 1,
                'invoice_id' => 1
            ]
        ]));

        $this->assertEquals('success', $result['status']);
    }

    public function testHandleMomoWebhook()
    {
        // Test MoMo webhook
        $result = $this->paymentService->handleWebhook('momo', json_encode([
            'errorCode' => 0,
            'orderId' => 1,
            'orderInfo' => 1
        ]));

        $this->assertEquals('success', $result['status']);
    }

    public function testHandleVNPayWebhook()
    {
        // Test VNPay webhook
        $result = $this->paymentService->handleWebhook('vnpay', json_encode([
            'vnp_ResponseCode' => '00',
            'vnp_TxnRef' => 1,
            'vnp_OrderInfo' => 1
        ]));

        $this->assertEquals('success', $result['status']);
    }

    public function testProcessPaymentWithInvalidOrder()
    {
        // Mock database to return null for order
        $this->db->shouldReceive('fetch')->andReturn(null);

        // Test payment with invalid order
        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('Order not found');
        
        $this->paymentService->processPayment(999, 'credit_card');
    }

    public function testProcessPaymentWithInvalidMethod()
    {
        // Test payment with invalid method
        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('Invalid payment method');
        
        $this->paymentService->processPayment(1, 'invalid_method');
    }

    public function testProcessCreditCardPaymentFailure()
    {
        // Mock Stripe charge to fail
        $stripeCharge = Mockery::mock('Stripe\Charge');
        $stripeCharge->shouldReceive('getAttribute')->with('status')->andReturn('failed');

        // Mock Stripe class
        $stripe = Mockery::mock('alias:Stripe\Stripe');
        $stripe->shouldReceive('setApiKey')->andReturn(null);

        $stripeChargeClass = Mockery::mock('alias:Stripe\Charge');
        $stripeChargeClass->shouldReceive('create')->andReturn($stripeCharge);

        // Test credit card payment failure
        $this->expectException(\Exception::class);
        
        $this->paymentService->processPayment(1, 'credit_card');
    }

    public function testHandleStripeWebhookWithFailedCharge()
    {
        // Mock Stripe event for failed charge
        $stripeEvent = Mockery::mock('Stripe\Event');
        $stripeEvent->shouldReceive('getAttribute')->with('type')->andReturn('charge.failed');

        $charge = Mockery::mock('Stripe\Charge');
        $charge->shouldReceive('getAttribute')->with('metadata')->andReturn([
            'transaction_id' => 1,
            'order_id' => 1
        ]);

        $stripeEvent->shouldReceive('getAttribute')->with('data')->andReturn([
            'object' => $charge
        ]);

        // Mock Stripe Event class
        $stripeEventClass = Mockery::mock('alias:Stripe\Event');
        $stripeEventClass->shouldReceive('constructFrom')->andReturn($stripeEvent);

        // Test Stripe webhook with failed charge
        $result = $this->paymentService->handleWebhook('stripe', json_encode([
            'type' => 'charge.failed',
            'data' => [
                'object' => [
                    'metadata' => [
                        'transaction_id' => 1,
                        'order_id' => 1
                    ]
                ]
            ]
        ]));

        $this->assertEquals('success', $result['status']);
    }

    public function testHandlePayPalWebhookWithInvalidEvent()
    {
        // Test PayPal webhook with invalid event
        $result = $this->paymentService->handleWebhook('paypal', json_encode([
            'event_type' => 'INVALID.EVENT',
            'resource' => [
                'custom_id' => 1,
                'invoice_id' => 1
            ]
        ]));

        $this->assertEquals('success', $result['status']);
    }

    public function testHandleMomoWebhookWithError()
    {
        // Test MoMo webhook with error
        $result = $this->paymentService->handleWebhook('momo', json_encode([
            'errorCode' => 1,
            'orderId' => 1,
            'orderInfo' => 1
        ]));

        $this->assertEquals('success', $result['status']);
    }

    public function testHandleVNPayWebhookWithError()
    {
        // Test VNPay webhook with error
        $result = $this->paymentService->handleWebhook('vnpay', json_encode([
            'vnp_ResponseCode' => '99',
            'vnp_TxnRef' => 1,
            'vnp_OrderInfo' => 1
        ]));

        $this->assertEquals('success', $result['status']);
    }
} 