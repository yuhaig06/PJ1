<?php

namespace App\Helpers;

use Monolog\Logger;
use Monolog\Handler\StreamHandler;
use Monolog\Handler\RotatingFileHandler;
use Monolog\Formatter\LineFormatter;

class AuditLogger
{
    private static $instance = null;
    private $logger;
    private $config;

    private function __construct()
    {
        $this->config = require __DIR__ . '/../../config/app.php';
        $this->initializeLogger();
    }

    public static function getInstance()
    {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    private function initializeLogger()
    {
        $this->logger = new Logger('audit');

        // Console handler for development
        if ($this->config['debug']) {
            $consoleHandler = new StreamHandler('php://stdout', Logger::DEBUG);
            $consoleHandler->setFormatter(new LineFormatter(
                "[%datetime%] %channel%.%level_name%: %message% %context% %extra%\n",
                "Y-m-d H:i:s"
            ));
            $this->logger->pushHandler($consoleHandler);
        }

        // File handler for production
        $fileHandler = new RotatingFileHandler(
            __DIR__ . '/../../logs/audit.log',
            30, // Keep logs for 30 days
            Logger::INFO
        );
        $fileHandler->setFormatter(new LineFormatter(
            "[%datetime%] %channel%.%level_name%: %message% %context% %extra%\n",
            "Y-m-d H:i:s"
        ));
        $this->logger->pushHandler($fileHandler);
    }

    public function logUserAction($userId, $action, $details = [], $ip = null)
    {
        $context = [
            'user_id' => $userId,
            'action' => $action,
            'ip' => $ip ?? $_SERVER['REMOTE_ADDR'] ?? 'unknown',
            'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? 'unknown',
            'timestamp' => date('Y-m-d H:i:s'),
            'details' => $details
        ];

        $this->logger->info("User Action: {$action}", $context);
    }

    public function logSystemAction($action, $details = [])
    {
        $context = [
            'action' => $action,
            'timestamp' => date('Y-m-d H:i:s'),
            'details' => $details
        ];

        $this->logger->info("System Action: {$action}", $context);
    }

    public function logSecurityEvent($event, $details = [], $severity = 'warning')
    {
        $context = [
            'event' => $event,
            'ip' => $_SERVER['REMOTE_ADDR'] ?? 'unknown',
            'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? 'unknown',
            'timestamp' => date('Y-m-d H:i:s'),
            'details' => $details
        ];

        switch ($severity) {
            case 'critical':
                $this->logger->critical("Security Event: {$event}", $context);
                break;
            case 'error':
                $this->logger->error("Security Event: {$event}", $context);
                break;
            case 'warning':
            default:
                $this->logger->warning("Security Event: {$event}", $context);
                break;
        }
    }

    public function logPaymentEvent($transactionId, $event, $details = [])
    {
        $context = [
            'transaction_id' => $transactionId,
            'event' => $event,
            'timestamp' => date('Y-m-d H:i:s'),
            'details' => $details
        ];

        $this->logger->info("Payment Event: {$event}", $context);
    }

    public function logAdminAction($adminId, $action, $details = [])
    {
        $context = [
            'admin_id' => $adminId,
            'action' => $action,
            'ip' => $_SERVER['REMOTE_ADDR'] ?? 'unknown',
            'timestamp' => date('Y-m-d H:i:s'),
            'details' => $details
        ];

        $this->logger->info("Admin Action: {$action}", $context);
    }

    public function getRecentLogs($limit = 100, $level = null)
    {
        // Implementation to retrieve recent logs
        // This would typically involve reading from the log file
        // and parsing the entries
        return [];
    }

    public function searchLogs($criteria)
    {
        // Implementation to search logs based on criteria
        // This would typically involve reading from the log file
        // and filtering entries based on the criteria
        return [];
    }
} 