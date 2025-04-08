<?php

namespace App\Controllers;

use App\Services\PaymentService;
use App\Helpers\ErrorHandler;

class PaymentController
{
    private $paymentService;
    private $errorHandler;

    public function __construct()
    {
        $this->paymentService = new PaymentService();
        $this->errorHandler = ErrorHandler::getInstance();
    }

    public function processPayment()
    {
        try {
            // Validate request
            if (!isset($_POST['order_id']) || !isset($_POST['payment_method'])) {
                throw new \Exception('Missing required parameters');
            }

            $orderId = $_POST['order_id'];
            $paymentMethod = $_POST['payment_method'];

            // Process payment
            $result = $this->paymentService->processPayment($orderId, $paymentMethod);

            // Return response
            header('Content-Type: application/json');
            echo json_encode($result);
        } catch (\Exception $e) {
            $this->errorHandler->log('error', 'Payment processing failed', [
                'error' => $e->getMessage()
            ]);

            header('Content-Type: application/json');
            http_response_code(400);
            echo json_encode([
                'status' => 'error',
                'message' => $e->getMessage()
            ]);
        }
    }

    public function handleWebhook()
    {
        try {
            // Get payment method from URL
            $paymentMethod = basename(dirname($_SERVER['REQUEST_URI']));

            // Get raw POST data
            $payload = file_get_contents('php://input');

            // Handle webhook
            $result = $this->paymentService->handleWebhook($paymentMethod, $payload);

            // Return response
            header('Content-Type: application/json');
            echo json_encode($result);
        } catch (\Exception $e) {
            $this->errorHandler->log('error', 'Webhook handling failed', [
                'error' => $e->getMessage()
            ]);

            header('Content-Type: application/json');
            http_response_code(400);
            echo json_encode([
                'status' => 'error',
                'message' => $e->getMessage()
            ]);
        }
    }

    public function handleReturn()
    {
        try {
            // Get payment method from URL
            $paymentMethod = basename(dirname($_SERVER['REQUEST_URI']));

            // Handle return URL based on payment method
            switch ($paymentMethod) {
                case 'paypal':
                    $this->handlePayPalReturn();
                    break;
                case 'momo':
                    $this->handleMomoReturn();
                    break;
                case 'vnpay':
                    $this->handleVNPayReturn();
                    break;
                default:
                    throw new \Exception('Invalid payment method');
            }

            // Redirect to success page
            header('Location: ' . getenv('APP_URL') . '/payment/success');
        } catch (\Exception $e) {
            $this->errorHandler->log('error', 'Payment return handling failed', [
                'error' => $e->getMessage()
            ]);

            // Redirect to error page
            header('Location: ' . getenv('APP_URL') . '/payment/error');
        }
    }

    private function handlePayPalReturn()
    {
        if (!isset($_GET['paymentId']) || !isset($_GET['PayerID'])) {
            throw new \Exception('Missing PayPal parameters');
        }

        // Verify payment with PayPal
        $paymentId = $_GET['paymentId'];
        $payerId = $_GET['PayerID'];

        // TODO: Implement PayPal payment verification
    }

    private function handleMomoReturn()
    {
        if (!isset($_GET['orderId']) || !isset($_GET['resultCode'])) {
            throw new \Exception('Missing MoMo parameters');
        }

        $orderId = $_GET['orderId'];
        $resultCode = $_GET['resultCode'];

        if ($resultCode !== '0') {
            throw new \Exception('MoMo payment failed');
        }

        // TODO: Implement MoMo payment verification
    }

    private function handleVNPayReturn()
    {
        if (!isset($_GET['vnp_ResponseCode'])) {
            throw new \Exception('Missing VNPay parameters');
        }

        $responseCode = $_GET['vnp_ResponseCode'];

        if ($responseCode !== '00') {
            throw new \Exception('VNPay payment failed');
        }

        // TODO: Implement VNPay payment verification
    }
} 