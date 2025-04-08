<?php

namespace App\Helpers;

use Monolog\Logger;
use Monolog\Handler\StreamHandler;
use Monolog\Handler\RotatingFileHandler;
use Monolog\Formatter\LineFormatter;

class ErrorHandler
{
    private static $instance = null;
    private $logger;
    private $config;

    private function __construct()
    {
        $this->config = require_once __DIR__ . '/../../config/security.php';
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
        $this->logger = new Logger('app');

        // Console Handler
        $consoleHandler = new StreamHandler('php://stdout', Logger::DEBUG);
        $consoleHandler->setFormatter(new LineFormatter(
            "[%datetime%] %channel%.%level_name%: %message% %context% %extra%\n",
            "Y-m-d H:i:s"
        ));
        $this->logger->pushHandler($consoleHandler);

        // File Handler
        $fileHandler = new RotatingFileHandler(
            __DIR__ . '/../../logs/app.log',
            30, // Keep logs for 30 days
            Logger::DEBUG
        );
        $fileHandler->setFormatter(new LineFormatter(
            "[%datetime%] %channel%.%level_name%: %message% %context% %extra%\n",
            "Y-m-d H:i:s"
        ));
        $this->logger->pushHandler($fileHandler);

        // Error Handler
        $errorHandler = new RotatingFileHandler(
            __DIR__ . '/../../logs/error.log',
            30,
            Logger::ERROR
        );
        $errorHandler->setFormatter(new LineFormatter(
            "[%datetime%] %channel%.%level_name%: %message% %context% %extra%\n",
            "Y-m-d H:i:s"
        ));
        $this->logger->pushHandler($errorHandler);
    }

    public function handleError($errno, $errstr, $errfile, $errline)
    {
        $error = [
            'type' => $this->getErrorType($errno),
            'message' => $errstr,
            'file' => $errfile,
            'line' => $errline,
        ];

        $this->logger->error('PHP Error', $error);

        if ($this->config['display_errors']) {
            echo json_encode([
                'status' => 'error',
                'message' => $errstr,
                'code' => 500
            ]);
        } else {
            echo json_encode([
                'status' => 'error',
                'message' => 'Internal Server Error',
                'code' => 500
            ]);
        }

        return true;
    }

    public function handleException($exception)
    {
        $error = [
            'type' => get_class($exception),
            'message' => $exception->getMessage(),
            'file' => $exception->getFile(),
            'line' => $exception->getLine(),
            'trace' => $exception->getTraceAsString(),
        ];

        $this->logger->error('Uncaught Exception', $error);

        if ($this->config['display_errors']) {
            echo json_encode([
                'status' => 'error',
                'message' => $exception->getMessage(),
                'code' => $exception->getCode() ?: 500
            ]);
        } else {
            echo json_encode([
                'status' => 'error',
                'message' => 'Internal Server Error',
                'code' => 500
            ]);
        }
    }

    public function log($level, $message, array $context = [])
    {
        $this->logger->log($level, $message, $context);
    }

    private function getErrorType($errno)
    {
        switch ($errno) {
            case E_ERROR:
                return 'E_ERROR';
            case E_WARNING:
                return 'E_WARNING';
            case E_PARSE:
                return 'E_PARSE';
            case E_NOTICE:
                return 'E_NOTICE';
            case E_CORE_ERROR:
                return 'E_CORE_ERROR';
            case E_CORE_WARNING:
                return 'E_CORE_WARNING';
            case E_COMPILE_ERROR:
                return 'E_COMPILE_ERROR';
            case E_COMPILE_WARNING:
                return 'E_COMPILE_WARNING';
            case E_USER_ERROR:
                return 'E_USER_ERROR';
            case E_USER_WARNING:
                return 'E_USER_WARNING';
            case E_USER_NOTICE:
                return 'E_USER_NOTICE';
            case E_STRICT:
                return 'E_STRICT';
            case E_RECOVERABLE_ERROR:
                return 'E_RECOVERABLE_ERROR';
            case E_DEPRECATED:
                return 'E_DEPRECATED';
            case E_USER_DEPRECATED:
                return 'E_USER_DEPRECATED';
            default:
                return 'UNKNOWN';
        }
    }
} 