<?php

namespace App\Helpers;

class IPBlocker
{
    private static $instance = null;
    private $db;
    private $config;
    private $auditLogger;

    private function __construct()
    {
        $this->db = Database::getInstance();
        $this->config = require __DIR__ . '/../../config/security.php';
        $this->auditLogger = AuditLogger::getInstance();
    }

    public static function getInstance()
    {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    public function checkIP($ip)
    {
        // Check if IP is in blacklist
        if ($this->isBlacklisted($ip)) {
            $this->auditLogger->logSecurityEvent('blocked_ip_access', [
                'ip' => $ip,
                'reason' => 'blacklisted'
            ], 'warning');
            return false;
        }

        // Check rate limiting
        if ($this->isRateLimited($ip)) {
            $this->auditLogger->logSecurityEvent('rate_limit_exceeded', [
                'ip' => $ip
            ], 'warning');
            return false;
        }

        return true;
    }

    private function isBlacklisted($ip)
    {
        $stmt = $this->db->prepare("
            SELECT COUNT(*) FROM ip_blacklist 
            WHERE ip = ? AND (expires_at IS NULL OR expires_at > NOW())
        ");
        $stmt->execute([$ip]);
        return $stmt->fetchColumn() > 0;
    }

    private function isRateLimited($ip)
    {
        $stmt = $this->db->prepare("
            SELECT COUNT(*) FROM request_logs 
            WHERE ip = ? AND created_at > DATE_SUB(NOW(), INTERVAL 1 MINUTE)
        ");
        $stmt->execute([$ip]);
        return $stmt->fetchColumn() > $this->config['rate_limiting']['max_requests'];
    }

    public function logRequest($ip)
    {
        $stmt = $this->db->prepare("
            INSERT INTO request_logs (ip, created_at) 
            VALUES (?, NOW())
        ");
        $stmt->execute([$ip]);
    }

    public function blacklistIP($ip, $reason, $duration = null)
    {
        $expiresAt = $duration ? date('Y-m-d H:i:s', strtotime("+{$duration} hours")) : null;
        
        $stmt = $this->db->prepare("
            INSERT INTO ip_blacklist (ip, reason, created_at, expires_at) 
            VALUES (?, ?, NOW(), ?)
        ");
        $stmt->execute([$ip, $reason, $expiresAt]);

        $this->auditLogger->logSecurityEvent('ip_blacklisted', [
            'ip' => $ip,
            'reason' => $reason,
            'duration' => $duration,
            'expires_at' => $expiresAt
        ], 'warning');
    }

    public function removeFromBlacklist($ip)
    {
        $stmt = $this->db->prepare("
            DELETE FROM ip_blacklist 
            WHERE ip = ?
        ");
        $stmt->execute([$ip]);

        $this->auditLogger->logSecurityEvent('ip_removed_from_blacklist', [
            'ip' => $ip
        ], 'info');
    }

    public function getBlacklistedIPs()
    {
        $stmt = $this->db->prepare("
            SELECT ip, reason, created_at, expires_at 
            FROM ip_blacklist 
            WHERE expires_at IS NULL OR expires_at > NOW()
        ");
        $stmt->execute();
        return $stmt->fetchAll(\PDO::FETCH_ASSOC);
    }

    public function cleanupExpiredEntries()
    {
        $stmt = $this->db->prepare("
            DELETE FROM ip_blacklist 
            WHERE expires_at IS NOT NULL AND expires_at < NOW()
        ");
        $stmt->execute();

        $stmt = $this->db->prepare("
            DELETE FROM request_logs 
            WHERE created_at < DATE_SUB(NOW(), INTERVAL 24 HOUR)
        ");
        $stmt->execute();
    }
} 