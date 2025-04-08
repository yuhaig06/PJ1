<?php

namespace App\Helpers;

use App\Helpers\CacheManager;
use App\Helpers\AuditLogger;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\RequestException;

class LoadBalancer
{
    private static $instance = null;
    private $cacheManager;
    private $auditLogger;
    private $httpClient;
    private $servers = [];
    private $cdnConfig = [];
    private $healthCheckInterval = 60; // seconds
    private $lastHealthCheck = 0;

    private function __construct()
    {
        $this->cacheManager = CacheManager::getInstance();
        $this->auditLogger = AuditLogger::getInstance();
        $this->httpClient = new Client(['timeout' => 5.0]);
        $this->loadConfig();
    }

    public static function getInstance()
    {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    /**
     * Load configuration from config file
     */
    private function loadConfig()
    {
        $this->servers = config('load_balancer.servers', []);
        $this->cdnConfig = config('cdn', []);
    }

    /**
     * Get the next available server using round-robin algorithm
     */
    public function getNextServer()
    {
        $this->checkHealth();
        
        $availableServers = array_filter($this->servers, function($server) {
            return $server['healthy'] === true;
        });
        
        if (empty($availableServers)) {
            $this->auditLogger->logSystemAction('load_balancer_error', [
                'error' => 'No healthy servers available',
                'timestamp' => date('Y-m-d H:i:s')
            ]);
            return null;
        }
        
        $server = current($availableServers);
        next($availableServers);
        if (current($availableServers) === false) {
            reset($availableServers);
        }
        
        return $server;
    }

    /**
     * Check health of all servers
     */
    private function checkHealth()
    {
        $now = time();
        if ($now - $this->lastHealthCheck < $this->healthCheckInterval) {
            return;
        }
        
        $this->lastHealthCheck = $now;
        
        foreach ($this->servers as &$server) {
            try {
                $response = $this->httpClient->get($server['health_check_url']);
                $server['healthy'] = $response->getStatusCode() === 200;
                $server['last_check'] = $now;
                $server['response_time'] = $response->getHeaderLine('X-Response-Time');
            } catch (RequestException $e) {
                $server['healthy'] = false;
                $server['last_check'] = $now;
                $server['error'] = $e->getMessage();
                
                $this->auditLogger->logSystemAction('server_health_check_failed', [
                    'server' => $server['url'],
                    'error' => $e->getMessage(),
                    'timestamp' => date('Y-m-d H:i:s')
                ]);
            }
        }
    }

    /**
     * Get CDN URL for a resource
     */
    public function getCDNUrl($path)
    {
        if (empty($this->cdnConfig['enabled']) || empty($this->cdnConfig['domain'])) {
            return $path;
        }
        
        $cdnUrl = rtrim($this->cdnConfig['domain'], '/') . '/' . ltrim($path, '/');
        
        // Add cache busting if enabled
        if (!empty($this->cdnConfig['cache_busting'])) {
            $hash = md5_file(public_path($path));
            $cdnUrl .= '?v=' . substr($hash, 0, 8);
        }
        
        return $cdnUrl;
    }

    /**
     * Purge CDN cache for a resource
     */
    public function purgeCDNCache($path)
    {
        if (empty($this->cdnConfig['enabled']) || empty($this->cdnConfig['purge_url'])) {
            return false;
        }
        
        try {
            $response = $this->httpClient->post($this->cdnConfig['purge_url'], [
                'json' => [
                    'path' => $path,
                    'api_key' => $this->cdnConfig['api_key']
                ]
            ]);
            
            $this->auditLogger->logSystemAction('cdn_cache_purged', [
                'path' => $path,
                'status' => $response->getStatusCode(),
                'timestamp' => date('Y-m-d H:i:s')
            ]);
            
            return $response->getStatusCode() === 200;
        } catch (RequestException $e) {
            $this->auditLogger->logSystemAction('cdn_cache_purge_failed', [
                'path' => $path,
                'error' => $e->getMessage(),
                'timestamp' => date('Y-m-d H:i:s')
            ]);
            
            return false;
        }
    }

    /**
     * Get server statistics
     */
    public function getServerStats()
    {
        $this->checkHealth();
        
        $stats = [
            'total_servers' => count($this->servers),
            'healthy_servers' => count(array_filter($this->servers, function($server) {
                return $server['healthy'] === true;
            })),
            'servers' => array_map(function($server) {
                return [
                    'url' => $server['url'],
                    'healthy' => $server['healthy'],
                    'last_check' => $server['last_check'],
                    'response_time' => $server['response_time'] ?? null,
                    'error' => $server['error'] ?? null
                ];
            }, $this->servers)
        ];
        
        return $stats;
    }

    /**
     * Add a new server to the load balancer
     */
    public function addServer($url, $healthCheckUrl)
    {
        $this->servers[] = [
            'url' => $url,
            'health_check_url' => $healthCheckUrl,
            'healthy' => true,
            'last_check' => time(),
            'response_time' => null
        ];
        
        $this->auditLogger->logSystemAction('server_added', [
            'url' => $url,
            'timestamp' => date('Y-m-d H:i:s')
        ]);
    }

    /**
     * Remove a server from the load balancer
     */
    public function removeServer($url)
    {
        $this->servers = array_filter($this->servers, function($server) use ($url) {
            return $server['url'] !== $url;
        });
        
        $this->auditLogger->logSystemAction('server_removed', [
            'url' => $url,
            'timestamp' => date('Y-m-d H:i:s')
        ]);
    }

    /**
     * Update server configuration
     */
    public function updateServer($url, $config)
    {
        foreach ($this->servers as &$server) {
            if ($server['url'] === $url) {
                $server = array_merge($server, $config);
                break;
            }
        }
        
        $this->auditLogger->logSystemAction('server_updated', [
            'url' => $url,
            'config' => $config,
            'timestamp' => date('Y-m-d H:i:s')
        ]);
    }

    /**
     * Get CDN statistics
     */
    public function getCDNStats()
    {
        if (empty($this->cdnConfig['enabled'])) {
            return [
                'enabled' => false,
                'message' => 'CDN is not enabled'
            ];
        }
        
        try {
            $response = $this->httpClient->get($this->cdnConfig['stats_url'], [
                'headers' => [
                    'Authorization' => 'Bearer ' . $this->cdnConfig['api_key']
                ]
            ]);
            
            $stats = json_decode($response->getBody(), true);
            
            $this->auditLogger->logSystemAction('cdn_stats_retrieved', [
                'timestamp' => date('Y-m-d H:i:s')
            ]);
            
            return array_merge(['enabled' => true], $stats);
        } catch (RequestException $e) {
            $this->auditLogger->logSystemAction('cdn_stats_retrieval_failed', [
                'error' => $e->getMessage(),
                'timestamp' => date('Y-m-d H:i:s')
            ]);
            
            return [
                'enabled' => true,
                'error' => $e->getMessage()
            ];
        }
    }
} 