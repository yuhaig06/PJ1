<?php

namespace App\Middleware;

class SecurityMiddleware
{
    private $db;
    private $redis;
    private $config;

    public function __construct()
    {
        $this->db = Database::getInstance();
        $this->redis = Redis::getInstance();
        $this->config = require_once __DIR__ . '/../../config/security.php';
    }

    public function handle($request, $next)
    {
        // Rate Limiting
        if (!$this->checkRateLimit($request)) {
            return [
                'status' => 'error',
                'message' => 'Too many requests. Please try again later.',
                'code' => 429
            ];
        }

        // CSRF Protection
        if (!$this->validateCsrfToken($request)) {
            return [
                'status' => 'error',
                'message' => 'Invalid CSRF token.',
                'code' => 403
            ];
        }

        // Input Validation
        if (!$this->validateInput($request)) {
            return [
                'status' => 'error',
                'message' => 'Invalid input data.',
                'code' => 400
            ];
        }

        // XSS Protection
        $request = $this->sanitizeInput($request);

        // SQL Injection Protection
        $request = $this->preventSqlInjection($request);

        return $next($request);
    }

    private function checkRateLimit($request)
    {
        $ip = $request->getClientIp();
        $key = "rate_limit:{$ip}";
        $limit = $this->config['rate_limit']['requests'];
        $window = $this->config['rate_limit']['window'];

        $current = $this->redis->get($key) ?: 0;

        if ($current >= $limit) {
            return false;
        }

        $this->redis->incr($key);
        if ($current === 0) {
            $this->redis->expire($key, $window);
        }

        return true;
    }

    private function validateCsrfToken($request)
    {
        if ($request->getMethod() === 'GET') {
            return true;
        }

        $token = $request->getHeader('X-CSRF-TOKEN');
        $sessionToken = $_SESSION['csrf_token'] ?? null;

        if (!$token || !$sessionToken || $token !== $sessionToken) {
            return false;
        }

        return true;
    }

    private function validateInput($request)
    {
        $data = $request->getData();
        $rules = $this->config['validation_rules'];

        foreach ($rules as $field => $rule) {
            if (isset($data[$field])) {
                if (!$this->validateField($data[$field], $rule)) {
                    return false;
                }
            }
        }

        return true;
    }

    private function validateField($value, $rule)
    {
        switch ($rule) {
            case 'email':
                return filter_var($value, FILTER_VALIDATE_EMAIL) !== false;
            case 'int':
                return is_numeric($value) && (int)$value == $value;
            case 'float':
                return is_numeric($value);
            case 'string':
                return is_string($value);
            case 'array':
                return is_array($value);
            default:
                return true;
        }
    }

    private function sanitizeInput($request)
    {
        $data = $request->getData();
        
        foreach ($data as $key => $value) {
            if (is_string($value)) {
                $data[$key] = htmlspecialchars($value, ENT_QUOTES, 'UTF-8');
            } elseif (is_array($value)) {
                $data[$key] = $this->sanitizeInput($value);
            }
        }

        $request->setData($data);
        return $request;
    }

    private function preventSqlInjection($request)
    {
        $data = $request->getData();
        
        foreach ($data as $key => $value) {
            if (is_string($value)) {
                $data[$key] = $this->db->escape($value);
            } elseif (is_array($value)) {
                $data[$key] = $this->preventSqlInjection($value);
            }
        }

        $request->setData($data);
        return $request;
    }
} 