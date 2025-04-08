<?php

namespace App\Middleware;

class RateLimitMiddleware {
    private $db;
    private $maxRequests = 100; // Số request tối đa
    private $timeWindow = 3600; // Thời gian (giây) - mặc định 1 giờ

    public function __construct() {
        $this->db = Database::getInstance();
    }

    public function handle() {
        $ip = $this->getClientIP();
        $currentTime = time();

        // Xóa các request cũ hơn timeWindow
        $this->cleanOldRequests($currentTime);

        // Đếm số request trong timeWindow
        $requestCount = $this->countRequests($ip, $currentTime);

        if ($requestCount >= $this->maxRequests) {
            $this->denyRequest('Quá nhiều request. Vui lòng thử lại sau.');
        }

        // Lưu request mới
        $this->logRequest($ip, $currentTime);
    }

    private function getClientIP() {
        if (!empty($_SERVER['HTTP_CLIENT_IP'])) {
            return $_SERVER['HTTP_CLIENT_IP'];
        }
        if (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
            return $_SERVER['HTTP_X_FORWARDED_FOR'];
        }
        return $_SERVER['REMOTE_ADDR'];
    }

    private function cleanOldRequests($currentTime) {
        $this->db->query("DELETE FROM rate_limits WHERE timestamp < :time");
        $this->db->bind(':time', $currentTime - $this->timeWindow);
        $this->db->execute();
    }

    private function countRequests($ip, $currentTime) {
        $this->db->query("SELECT COUNT(*) as count FROM rate_limits 
                         WHERE ip = :ip AND timestamp > :time");
        $this->db->bind(':ip', $ip);
        $this->db->bind(':time', $currentTime - $this->timeWindow);
        $result = $this->db->single();
        return $result->count;
    }

    private function logRequest($ip, $timestamp) {
        $this->db->query("INSERT INTO rate_limits (ip, timestamp) VALUES (:ip, :timestamp)");
        $this->db->bind(':ip', $ip);
        $this->db->bind(':timestamp', $timestamp);
        $this->db->execute();
    }

    private function denyRequest($message) {
        header('HTTP/1.1 429 Too Many Requests');
        header('Retry-After: ' . $this->timeWindow);
        die('Rate Limit Exceeded: ' . $message);
    }

    // Set giới hạn request
    public function setLimit($maxRequests, $timeWindow = 3600) {
        $this->maxRequests = $maxRequests;
        $this->timeWindow = $timeWindow;
    }
}