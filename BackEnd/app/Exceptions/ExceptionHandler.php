<?php

namespace App\Exceptions;

use App\Helpers\AuditLogger;

class ExceptionHandler {
    private static $instance = null;
    private $auditLogger;

    private function __construct() {
        $this->auditLogger = AuditLogger::getInstance();
    }

    public static function getInstance() {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    public function handle(\Throwable $exception) {
        $response = [
            'success' => false,
            'message' => $exception->getMessage()
        ];

        // Add errors if available
        if (method_exists($exception, 'getErrors')) {
            $response['errors'] = $exception->getErrors();
        }

        // Log exception
        $this->logException($exception);

        // Set HTTP status code
        http_response_code($exception->getCode() ?: 500);

        // Return JSON response
        header('Content-Type: application/json');
        echo json_encode($response);
        exit;
    }

    private function logException(\Throwable $exception) {
        $context = [
            'file' => $exception->getFile(),
            'line' => $exception->getLine(),
            'trace' => $exception->getTraceAsString()
        ];

        // Add errors if available
        if (method_exists($exception, 'getErrors')) {
            $context['errors'] = $exception->getErrors();
        }

        // Determine severity based on exception type
        $severity = 'error';
        if ($exception instanceof ValidationException) {
            $severity = 'warning';
        } elseif ($exception instanceof NotFoundException) {
            $severity = 'info';
        }

        // Log to audit log
        $this->auditLogger->log(
            'system',
            'exception',
            $context,
            $severity
        );
    }
} 