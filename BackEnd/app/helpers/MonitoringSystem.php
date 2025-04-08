<?php

namespace App\Helpers;

use App\Helpers\AuditLogger;
use App\Helpers\CacheManager;
use App\Helpers\LoadBalancer;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\RequestException;

class MonitoringSystem
{
    private static $instance = null;
    private $auditLogger;
    private $cacheManager;
    private $loadBalancer;
    private $httpClient;
    private $metrics = [];
    private $alerts = [];
    private $alertThresholds = [];
    private $lastMetricsUpdate = 0;
    private $metricsUpdateInterval = 60; // seconds

    private function __construct()
    {
        $this->auditLogger = AuditLogger::getInstance();
        $this->cacheManager = CacheManager::getInstance();
        $this->loadBalancer = LoadBalancer::getInstance();
        $this->httpClient = new Client(['timeout' => 5.0]);
        $this->loadAlertThresholds();
    }

    public static function getInstance()
    {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    /**
     * Load alert thresholds from configuration
     */
    private function loadAlertThresholds()
    {
        $this->alertThresholds = config('monitoring.alert_thresholds', [
            'cpu_usage' => 80,
            'memory_usage' => 80,
            'disk_usage' => 80,
            'response_time' => 1000,
            'error_rate' => 5
        ]);
    }

    /**
     * Collect system metrics
     */
    public function collectMetrics()
    {
        $now = time();
        if ($now - $this->lastMetricsUpdate < $this->metricsUpdateInterval) {
            return $this->metrics;
        }

        $this->lastMetricsUpdate = $now;

        // System metrics
        $this->metrics['system'] = [
            'cpu_usage' => $this->getCPUUsage(),
            'memory_usage' => $this->getMemoryUsage(),
            'disk_usage' => $this->getDiskUsage(),
            'timestamp' => $now
        ];

        // Application metrics
        $this->metrics['application'] = [
            'response_time' => $this->getAverageResponseTime(),
            'error_rate' => $this->getErrorRate(),
            'request_count' => $this->getRequestCount(),
            'active_users' => $this->getActiveUsers(),
            'timestamp' => $now
        ];

        // Database metrics
        $this->metrics['database'] = [
            'connections' => $this->getDatabaseConnections(),
            'query_time' => $this->getAverageQueryTime(),
            'slow_queries' => $this->getSlowQueries(),
            'timestamp' => $now
        ];

        // Cache metrics
        $this->metrics['cache'] = [
            'hit_rate' => $this->getCacheHitRate(),
            'memory_usage' => $this->getCacheMemoryUsage(),
            'timestamp' => $now
        ];

        // Store metrics in cache
        $this->cacheManager->set('system_metrics', $this->metrics, 300);

        // Check for alerts
        $this->checkAlerts();

        return $this->metrics;
    }

    /**
     * Get CPU usage
     */
    private function getCPUUsage()
    {
        if (function_exists('sys_getloadavg')) {
            $load = sys_getloadavg();
            return $load[0] * 100;
        }
        return 0;
    }

    /**
     * Get memory usage
     */
    private function getMemoryUsage()
    {
        if (function_exists('memory_get_usage')) {
            $memoryUsage = memory_get_usage(true);
            $memoryLimit = ini_get('memory_limit');
            return ($memoryUsage / $this->convertToBytes($memoryLimit)) * 100;
        }
        return 0;
    }

    /**
     * Get disk usage
     */
    private function getDiskUsage()
    {
        $totalSpace = disk_total_space('/');
        $freeSpace = disk_free_space('/');
        return (($totalSpace - $freeSpace) / $totalSpace) * 100;
    }

    /**
     * Convert memory limit to bytes
     */
    private function convertToBytes($memoryLimit)
    {
        $unit = strtolower(substr($memoryLimit, -1));
        $value = (int)substr($memoryLimit, 0, -1);
        
        switch ($unit) {
            case 'g':
                $value *= 1024;
            case 'm':
                $value *= 1024;
            case 'k':
                $value *= 1024;
        }
        
        return $value;
    }

    /**
     * Get average response time
     */
    private function getAverageResponseTime()
    {
        $responseTimes = $this->cacheManager->get('response_times', []);
        if (empty($responseTimes)) {
            return 0;
        }
        
        return array_sum($responseTimes) / count($responseTimes);
    }

    /**
     * Get error rate
     */
    private function getErrorRate()
    {
        $totalRequests = $this->cacheManager->get('total_requests', 0);
        $errorRequests = $this->cacheManager->get('error_requests', 0);
        
        if ($totalRequests === 0) {
            return 0;
        }
        
        return ($errorRequests / $totalRequests) * 100;
    }

    /**
     * Get request count
     */
    private function getRequestCount()
    {
        return $this->cacheManager->get('total_requests', 0);
    }

    /**
     * Get active users
     */
    private function getActiveUsers()
    {
        return $this->cacheManager->get('active_users', 0);
    }

    /**
     * Get database connections
     */
    private function getDatabaseConnections()
    {
        // This is a simplified implementation
        return rand(1, 10);
    }

    /**
     * Get average query time
     */
    private function getAverageQueryTime()
    {
        $queryTimes = $this->cacheManager->get('query_times', []);
        if (empty($queryTimes)) {
            return 0;
        }
        
        return array_sum($queryTimes) / count($queryTimes);
    }

    /**
     * Get slow queries
     */
    private function getSlowQueries()
    {
        return $this->cacheManager->get('slow_queries', []);
    }

    /**
     * Get cache hit rate
     */
    private function getCacheHitRate()
    {
        $hits = $this->cacheManager->get('cache_hits', 0);
        $misses = $this->cacheManager->get('cache_misses', 0);
        
        if ($hits + $misses === 0) {
            return 0;
        }
        
        return ($hits / ($hits + $misses)) * 100;
    }

    /**
     * Get cache memory usage
     */
    private function getCacheMemoryUsage()
    {
        return $this->cacheManager->getStats()['memory_usage'] ?? 0;
    }

    /**
     * Check for alerts
     */
    private function checkAlerts()
    {
        $alerts = [];

        // Check CPU usage
        if ($this->metrics['system']['cpu_usage'] > $this->alertThresholds['cpu_usage']) {
            $alerts[] = [
                'type' => 'cpu_usage',
                'severity' => 'high',
                'message' => "CPU usage is {$this->metrics['system']['cpu_usage']}%",
                'threshold' => $this->alertThresholds['cpu_usage'],
                'timestamp' => time()
            ];
        }

        // Check memory usage
        if ($this->metrics['system']['memory_usage'] > $this->alertThresholds['memory_usage']) {
            $alerts[] = [
                'type' => 'memory_usage',
                'severity' => 'high',
                'message' => "Memory usage is {$this->metrics['system']['memory_usage']}%",
                'threshold' => $this->alertThresholds['memory_usage'],
                'timestamp' => time()
            ];
        }

        // Check disk usage
        if ($this->metrics['system']['disk_usage'] > $this->alertThresholds['disk_usage']) {
            $alerts[] = [
                'type' => 'disk_usage',
                'severity' => 'high',
                'message' => "Disk usage is {$this->metrics['system']['disk_usage']}%",
                'threshold' => $this->alertThresholds['disk_usage'],
                'timestamp' => time()
            ];
        }

        // Check response time
        if ($this->metrics['application']['response_time'] > $this->alertThresholds['response_time']) {
            $alerts[] = [
                'type' => 'response_time',
                'severity' => 'medium',
                'message' => "Average response time is {$this->metrics['application']['response_time']}ms",
                'threshold' => $this->alertThresholds['response_time'],
                'timestamp' => time()
            ];
        }

        // Check error rate
        if ($this->metrics['application']['error_rate'] > $this->alertThresholds['error_rate']) {
            $alerts[] = [
                'type' => 'error_rate',
                'severity' => 'high',
                'message' => "Error rate is {$this->metrics['application']['error_rate']}%",
                'threshold' => $this->alertThresholds['error_rate'],
                'timestamp' => time()
            ];
        }

        if (!empty($alerts)) {
            $this->processAlerts($alerts);
        }

        $this->alerts = $alerts;
    }

    /**
     * Process alerts
     */
    private function processAlerts($alerts)
    {
        foreach ($alerts as $alert) {
            // Log alert
            $this->auditLogger->logSystemAction('alert', [
                'type' => $alert['type'],
                'severity' => $alert['severity'],
                'message' => $alert['message'],
                'timestamp' => date('Y-m-d H:i:s')
            ]);

            // Send notification if configured
            if (config('monitoring.notifications.enabled', false)) {
                $this->sendNotification($alert);
            }
        }
    }

    /**
     * Send notification
     */
    private function sendNotification($alert)
    {
        $notificationConfig = config('monitoring.notifications', []);
        
        if (empty($notificationConfig['webhook_url'])) {
            return;
        }

        try {
            $response = $this->httpClient->post($notificationConfig['webhook_url'], [
                'json' => [
                    'type' => $alert['type'],
                    'severity' => $alert['severity'],
                    'message' => $alert['message'],
                    'timestamp' => date('Y-m-d H:i:s')
                ]
            ]);

            $this->auditLogger->logSystemAction('notification_sent', [
                'type' => $alert['type'],
                'status' => $response->getStatusCode(),
                'timestamp' => date('Y-m-d H:i:s')
            ]);
        } catch (RequestException $e) {
            $this->auditLogger->logSystemAction('notification_failed', [
                'type' => $alert['type'],
                'error' => $e->getMessage(),
                'timestamp' => date('Y-m-d H:i:s')
            ]);
        }
    }

    /**
     * Get performance metrics dashboard data
     */
    public function getDashboardData()
    {
        $this->collectMetrics();

        return [
            'system' => [
                'cpu' => [
                    'current' => $this->metrics['system']['cpu_usage'],
                    'threshold' => $this->alertThresholds['cpu_usage'],
                    'history' => $this->getMetricHistory('cpu_usage')
                ],
                'memory' => [
                    'current' => $this->metrics['system']['memory_usage'],
                    'threshold' => $this->alertThresholds['memory_usage'],
                    'history' => $this->getMetricHistory('memory_usage')
                ],
                'disk' => [
                    'current' => $this->metrics['system']['disk_usage'],
                    'threshold' => $this->alertThresholds['disk_usage'],
                    'history' => $this->getMetricHistory('disk_usage')
                ]
            ],
            'application' => [
                'response_time' => [
                    'current' => $this->metrics['application']['response_time'],
                    'threshold' => $this->alertThresholds['response_time'],
                    'history' => $this->getMetricHistory('response_time')
                ],
                'error_rate' => [
                    'current' => $this->metrics['application']['error_rate'],
                    'threshold' => $this->alertThresholds['error_rate'],
                    'history' => $this->getMetricHistory('error_rate')
                ],
                'requests' => [
                    'current' => $this->metrics['application']['request_count'],
                    'history' => $this->getMetricHistory('request_count')
                ],
                'users' => [
                    'current' => $this->metrics['application']['active_users'],
                    'history' => $this->getMetricHistory('active_users')
                ]
            ],
            'database' => [
                'connections' => [
                    'current' => $this->metrics['database']['connections'],
                    'history' => $this->getMetricHistory('connections')
                ],
                'query_time' => [
                    'current' => $this->metrics['database']['query_time'],
                    'history' => $this->getMetricHistory('query_time')
                ],
                'slow_queries' => $this->metrics['database']['slow_queries']
            ],
            'cache' => [
                'hit_rate' => [
                    'current' => $this->metrics['cache']['hit_rate'],
                    'history' => $this->getMetricHistory('hit_rate')
                ],
                'memory_usage' => [
                    'current' => $this->metrics['cache']['memory_usage'],
                    'history' => $this->getMetricHistory('memory_usage')
                ]
            ],
            'alerts' => $this->alerts
        ];
    }

    /**
     * Get metric history
     */
    private function getMetricHistory($metric)
    {
        return $this->cacheManager->get("metric_history_{$metric}", []);
    }

    /**
     * Update metric history
     */
    public function updateMetricHistory($metric, $value)
    {
        $history = $this->getMetricHistory($metric);
        $history[] = [
            'value' => $value,
            'timestamp' => time()
        ];

        // Keep only last 24 hours of history
        $history = array_slice($history, -1440);

        $this->cacheManager->set("metric_history_{$metric}", $history, 86400);
    }

    /**
     * Get active alerts
     */
    public function getActiveAlerts()
    {
        return $this->alerts;
    }

    /**
     * Clear alerts
     */
    public function clearAlerts()
    {
        $this->alerts = [];
    }

    /**
     * Update alert thresholds
     */
    public function updateAlertThresholds($thresholds)
    {
        $this->alertThresholds = array_merge($this->alertThresholds, $thresholds);
        
        $this->auditLogger->logSystemAction('alert_thresholds_updated', [
            'thresholds' => $thresholds,
            'timestamp' => date('Y-m-d H:i:s')
        ]);
    }
} 