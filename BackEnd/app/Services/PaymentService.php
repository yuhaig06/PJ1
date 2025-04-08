<?php

namespace App\Services;

use App\Helpers\ErrorHandler;
use App\Models\Transaction;
use App\Models\Order;

class PaymentService
{
    private $db;
    private $errorHandler;
    private $config;

    public function __construct()
    {
        $this->db = Database::getInstance();
        $this->errorHandler = ErrorHandler::getInstance();
        $this->config = require_once __DIR__ . '/../../config/payment.php';
    }

    public function processPayment($orderId, $paymentMethod)
    {
        try {
            // Get order details
            $order = $this->db->query(
                "SELECT * FROM orders WHERE id = :id",
                ['id' => $orderId]
            )->fetch();

            if (!$order) {
                throw new \Exception('Order not found');
            }

            // Create transaction record
            $transactionId = $this->createTransaction($order, $paymentMethod);

            // Process payment based on method
            switch ($paymentMethod) {
                case 'credit_card':
                    return $this->processCreditCardPayment($order, $transactionId);
                case 'paypal':
                    return $this->processPayPalPayment($order, $transactionId);
                case 'momo':
                    return $this->processMomoPayment($order, $transactionId);
                case 'vnpay':
                    return $this->processVNPayPayment($order, $transactionId);
                default:
                    throw new \Exception('Invalid payment method');
            }
        } catch (\Exception $e) {
            $this->errorHandler->log('error', 'Payment processing failed', [
                'order_id' => $orderId,
                'payment_method' => $paymentMethod,
                'error' => $e->getMessage()
            ]);
            throw $e;
        }
    }

    private function createTransaction($order, $paymentMethod)
    {
        $transactionData = [
            'user_id' => $order['user_id'],
            'order_id' => $order['id'],
            'amount' => $order['total_amount'],
            'payment_method' => $paymentMethod,
            'status' => 'pending',
            'created_at' => date('Y-m-d H:i:s')
        ];

        $this->db->query(
            "INSERT INTO transactions (user_id, order_id, amount, payment_method, status, created_at) 
             VALUES (:user_id, :order_id, :amount, :payment_method, :status, :created_at)",
            $transactionData
        );

        return $this->db->lastInsertId();
    }

    private function processCreditCardPayment($order, $transactionId)
    {
        // Integrate with Stripe
        try {
            \Stripe\Stripe::setApiKey($this->config['stripe']['secret_key']);

            $charge = \Stripe\Charge::create([
                'amount' => $order['total_amount'] * 100, // Convert to cents
                'currency' => 'usd',
                'source' => $_POST['stripeToken'],
                'description' => "Order #{$order['id']}",
                'metadata' => [
                    'order_id' => $order['id'],
                    'transaction_id' => $transactionId
                ]
            ]);

            if ($charge->status === 'succeeded') {
                $this->updateTransactionStatus($transactionId, 'completed');
                $this->updateOrderStatus($order['id'], 'paid');
                return [
                    'status' => 'success',
                    'message' => 'Payment processed successfully',
                    'data' => [
                        'transaction_id' => $transactionId,
                        'charge_id' => $charge->id
                    ]
                ];
            }
        } catch (\Exception $e) {
            $this->updateTransactionStatus($transactionId, 'failed');
            throw $e;
        }
    }

    private function processPayPalPayment($order, $transactionId)
    {
        // Integrate with PayPal
        try {
            $apiContext = new \PayPal\Rest\ApiContext(
                new \PayPal\Auth\OAuthTokenCredential(
                    $this->config['paypal']['client_id'],
                    $this->config['paypal']['client_secret']
                )
            );

            $payment = new \PayPal\Api\Payment();
            $payment->setIntent('sale')
                ->setPayer([
                    'payment_method' => 'paypal'
                ])
                ->setTransactions([
                    [
                        'amount' => [
                            'total' => $order['total_amount'],
                            'currency' => 'USD'
                        ],
                        'description' => "Order #{$order['id']}"
                    ]
                ])
                ->setRedirectUrls([
                    'return_url' => $this->config['paypal']['return_url'],
                    'cancel_url' => $this->config['paypal']['cancel_url']
                ]);

            $payment->create($apiContext);

            return [
                'status' => 'success',
                'message' => 'PayPal payment initiated',
                'data' => [
                    'transaction_id' => $transactionId,
                    'payment_id' => $payment->getId(),
                    'approval_url' => $payment->getApprovalLink()
                ]
            ];
        } catch (\Exception $e) {
            $this->updateTransactionStatus($transactionId, 'failed');
            throw $e;
        }
    }

    private function processMomoPayment($order, $transactionId)
    {
        // Integrate with MoMo
        try {
            $endpoint = $this->config['momo']['endpoint'];
            $partnerCode = $this->config['momo']['partner_code'];
            $accessKey = $this->config['momo']['access_key'];
            $secretKey = $this->config['momo']['secret_key'];
            $orderInfo = "Order #{$order['id']}";
            $amount = $order['total_amount'];
            $orderId = $transactionId;
            $requestId = time() . "";
            $requestType = "captureWallet";
            $extraData = "";

            $rawHash = "partnerCode=" . $partnerCode . "&accessKey=" . $accessKey . "&requestId=" . $requestId . "&amount=" . $amount . "&orderId=" . $orderId . "&orderInfo=" . $orderInfo . "&requestType=" . $requestType . "&extraData=" . $extraData;
            $signature = hash_hmac('sha256', $rawHash, $secretKey);

            $data = [
                'partnerCode' => $partnerCode,
                'accessKey' => $accessKey,
                'requestId' => $requestId,
                'amount' => $amount,
                'orderId' => $orderId,
                'orderInfo' => $orderInfo,
                'requestType' => $requestType,
                'signature' => $signature,
                'extraData' => $extraData
            ];

            $ch = curl_init($endpoint);
            curl_setopt($ch, CURLOPT_POST, 1);
            curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
            curl_setopt($ch, CURLOPT_HTTPHEADER, [
                'Content-Type: application/json',
                'Content-Length: ' . strlen(json_encode($data))
            ]);

            $result = curl_exec($ch);
            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            curl_close($ch);

            if ($httpCode === 200) {
                $response = json_decode($result, true);
                if ($response['errorCode'] === 0) {
                    return [
                        'status' => 'success',
                        'message' => 'MoMo payment initiated',
                        'data' => [
                            'transaction_id' => $transactionId,
                            'payment_url' => $response['payUrl']
                        ]
                    ];
                }
            }

            throw new \Exception('MoMo payment failed');
        } catch (\Exception $e) {
            $this->updateTransactionStatus($transactionId, 'failed');
            throw $e;
        }
    }

    private function processVNPayPayment($order, $transactionId)
    {
        // Integrate with VNPay
        try {
            $vnp_TmnCode = $this->config['vnpay']['tmn_code'];
            $vnp_HashSecret = $this->config['vnpay']['hash_secret'];
            $vnp_Url = $this->config['vnpay']['url'];
            $vnp_ReturnUrl = $this->config['vnpay']['return_url'];

            $vnp_TxnRef = $transactionId;
            $vnp_OrderInfo = "Order #{$order['id']}";
            $vnp_OrderType = 'billpayment';
            $vnp_Amount = $order['total_amount'] * 100;
            $vnp_Locale = 'vn';
            $vnp_IpAddr = $_SERVER['REMOTE_ADDR'];

            $inputData = [
                "vnp_Version" => "2.1.0",
                "vnp_TmnCode" => $vnp_TmnCode,
                "vnp_Amount" => $vnp_Amount,
                "vnp_Command" => "pay",
                "vnp_CreateDate" => date('YmdHis'),
                "vnp_CurrCode" => "VND",
                "vnp_IpAddr" => $vnp_IpAddr,
                "vnp_Locale" => $vnp_Locale,
                "vnp_OrderInfo" => $vnp_OrderInfo,
                "vnp_OrderType" => $vnp_OrderType,
                "vnp_ReturnUrl" => $vnp_ReturnUrl,
                "vnp_TxnRef" => $vnp_TxnRef
            ];

            ksort($inputData);
            $query = "";
            $i = 0;
            $hashdata = "";
            foreach ($inputData as $key => $value) {
                if ($i == 1) {
                    $hashdata .= '&' . urlencode($key) . "=" . urlencode($value);
                } else {
                    $hashdata .= urlencode($key) . "=" . urlencode($value);
                    $i = 1;
                }
                $query .= urlencode($key) . "=" . urlencode($value) . '&';
            }

            $vnp_Url = $vnp_Url . "?" . $query;
            if (isset($vnp_HashSecret)) {
                $vnpSecureHash = hash_hmac('sha512', $hashdata, $vnp_HashSecret);
                $vnp_Url .= 'vnp_SecureHash=' . $vnpSecureHash;
            }

            return [
                'status' => 'success',
                'message' => 'VNPay payment initiated',
                'data' => [
                    'transaction_id' => $transactionId,
                    'payment_url' => $vnp_Url
                ]
            ];
        } catch (\Exception $e) {
            $this->updateTransactionStatus($transactionId, 'failed');
            throw $e;
        }
    }

    private function updateTransactionStatus($transactionId, $status)
    {
        $this->db->query(
            "UPDATE transactions SET status = :status, updated_at = :updated_at WHERE id = :id",
            [
                'id' => $transactionId,
                'status' => $status,
                'updated_at' => date('Y-m-d H:i:s')
            ]
        );
    }

    private function updateOrderStatus($orderId, $status)
    {
        $this->db->query(
            "UPDATE orders SET payment_status = :status, updated_at = :updated_at WHERE id = :id",
            [
                'id' => $orderId,
                'status' => $status,
                'updated_at' => date('Y-m-d H:i:s')
            ]
        );
    }

    public function handleWebhook($paymentMethod, $payload)
    {
        try {
            switch ($paymentMethod) {
                case 'stripe':
                    return $this->handleStripeWebhook($payload);
                case 'paypal':
                    return $this->handlePayPalWebhook($payload);
                case 'momo':
                    return $this->handleMomoWebhook($payload);
                case 'vnpay':
                    return $this->handleVNPayWebhook($payload);
                default:
                    throw new \Exception('Invalid payment method');
            }
        } catch (\Exception $e) {
            $this->errorHandler->log('error', 'Webhook handling failed', [
                'payment_method' => $paymentMethod,
                'error' => $e->getMessage()
            ]);
            throw $e;
        }
    }

    private function handleStripeWebhook($payload)
    {
        $event = \Stripe\Event::constructFrom($payload);

        switch ($event->type) {
            case 'charge.succeeded':
                $charge = $event->data->object;
                $transactionId = $charge->metadata->transaction_id;
                $this->updateTransactionStatus($transactionId, 'completed');
                $this->updateOrderStatus($charge->metadata->order_id, 'paid');
                break;
            case 'charge.failed':
                $charge = $event->data->object;
                $transactionId = $charge->metadata->transaction_id;
                $this->updateTransactionStatus($transactionId, 'failed');
                break;
        }

        return ['status' => 'success'];
    }

    private function handlePayPalWebhook($payload)
    {
        $event = json_decode($payload, true);

        if ($event['event_type'] === 'PAYMENT.CAPTURE.COMPLETED') {
            $transactionId = $event['resource']['custom_id'];
            $this->updateTransactionStatus($transactionId, 'completed');
            $this->updateOrderStatus($event['resource']['invoice_id'], 'paid');
        }

        return ['status' => 'success'];
    }

    private function handleMomoWebhook($payload)
    {
        $data = json_decode($payload, true);

        if ($data['errorCode'] === 0) {
            $transactionId = $data['orderId'];
            $this->updateTransactionStatus($transactionId, 'completed');
            $this->updateOrderStatus($data['orderInfo'], 'paid');
        }

        return ['status' => 'success'];
    }

    private function handleVNPayWebhook($payload)
    {
        $data = json_decode($payload, true);

        if ($data['vnp_ResponseCode'] === '00') {
            $transactionId = $data['vnp_TxnRef'];
            $this->updateTransactionStatus($transactionId, 'completed');
            $this->updateOrderStatus($data['vnp_OrderInfo'], 'paid');
        }

        return ['status' => 'success'];
    }
} 