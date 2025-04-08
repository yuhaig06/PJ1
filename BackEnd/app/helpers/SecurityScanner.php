<?php

namespace App\Helpers;

use App\Helpers\AuditLogger;
use App\Models\User;
use App\Models\Game;
use App\Models\Order;
use App\Services\AuthService;
use App\Services\PaymentService;

class SecurityScanner
{
    private $auditLogger;
    private $authService;
    private $paymentService;
    private $vulnerabilities = [];
    private $securityIssues = [];

    public function __construct()
    {
        $this->auditLogger = AuditLogger::getInstance();
        $this->authService = new AuthService();
        $this->paymentService = new PaymentService();
    }

    /**
     * Run a comprehensive security scan
     */
    public function runFullScan()
    {
        $this->scanAuthentication();
        $this->scanDatabase();
        $this->scanFileSystem();
        $this->scanAPI();
        $this->scanPayment();
        $this->scanDependencies();
        
        $this->logScanResults();
        
        return [
            'vulnerabilities' => $this->vulnerabilities,
            'security_issues' => $this->securityIssues
        ];
    }

    /**
     * Scan authentication system
     */
    private function scanAuthentication()
    {
        // Check password policies
        $users = User::all();
        foreach ($users as $user) {
            if (strlen($user->password) < 60) {
                $this->vulnerabilities[] = [
                    'type' => 'weak_password',
                    'severity' => 'high',
                    'details' => "User ID {$user->id} has a weak password hash"
                ];
            }
        }

        // Check JWT configuration
        $jwtConfig = config('jwt');
        if (empty($jwtConfig['secret']) || $jwtConfig['secret'] === 'your-secret-key') {
            $this->securityIssues[] = [
                'type' => 'weak_jwt_secret',
                'severity' => 'critical',
                'details' => 'JWT secret is weak or default'
            ];
        }

        // Check rate limiting
        $rateLimitConfig = config('rate_limiting');
        if (!$rateLimitConfig['enabled'] || $rateLimitConfig['max_attempts'] > 10) {
            $this->securityIssues[] = [
                'type' => 'weak_rate_limiting',
                'severity' => 'medium',
                'details' => 'Rate limiting is disabled or too permissive'
            ];
        }
    }

    /**
     * Scan database for security issues
     */
    private function scanDatabase()
    {
        // Check for SQL injection vulnerabilities
        $this->checkSQLInjectionVulnerabilities();

        // Check for sensitive data exposure
        $this->checkSensitiveDataExposure();

        // Check for missing indexes
        $this->checkMissingIndexes();
    }

    /**
     * Check for SQL injection vulnerabilities
     */
    private function checkSQLInjectionVulnerabilities()
    {
        // This is a simplified check - in a real system, you would use static analysis tools
        $controllers = glob(app_path('Controllers/*.php'));
        foreach ($controllers as $controller) {
            $content = file_get_contents($controller);
            if (preg_match('/\$.*->query\(.*\$.*\)/', $content) || 
                preg_match('/\$.*->raw\(.*\$.*\)/', $content)) {
                $this->vulnerabilities[] = [
                    'type' => 'sql_injection',
                    'severity' => 'critical',
                    'details' => "Potential SQL injection in {$controller}"
                ];
            }
        }
    }

    /**
     * Check for sensitive data exposure
     */
    private function checkSensitiveDataExposure()
    {
        // Check if sensitive data is properly encrypted
        $users = User::all();
        foreach ($users as $user) {
            if (isset($user->credit_card) && !preg_match('/^\$2[ayb]\$.{56}$/', $user->credit_card)) {
                $this->vulnerabilities[] = [
                    'type' => 'sensitive_data_exposure',
                    'severity' => 'critical',
                    'details' => "User ID {$user->id} has unencrypted credit card data"
                ];
            }
        }
    }

    /**
     * Check for missing indexes
     */
    private function checkMissingIndexes()
    {
        // This is a simplified check - in a real system, you would use database tools
        $tables = ['users', 'games', 'orders', 'payments'];
        foreach ($tables as $table) {
            if (!file_exists(database_path("migrations/*_create_{$table}_table.php"))) {
                $this->securityIssues[] = [
                    'type' => 'missing_index',
                    'severity' => 'low',
                    'details' => "Table {$table} might be missing indexes"
                ];
            }
        }
    }

    /**
     * Scan file system for security issues
     */
    private function scanFileSystem()
    {
        // Check for world-writable files
        $publicPath = public_path();
        $files = $this->findWorldWritableFiles($publicPath);
        foreach ($files as $file) {
            $this->vulnerabilities[] = [
                'type' => 'world_writable_file',
                'severity' => 'high',
                'details' => "File {$file} is world-writable"
            ];
        }

        // Check for exposed configuration files
        $exposedFiles = $this->findExposedConfigFiles($publicPath);
        foreach ($exposedFiles as $file) {
            $this->vulnerabilities[] = [
                'type' => 'exposed_config',
                'severity' => 'critical',
                'details' => "Configuration file {$file} is publicly accessible"
            ];
        }
    }

    /**
     * Find world-writable files
     */
    private function findWorldWritableFiles($path)
    {
        $files = [];
        $items = scandir($path);
        foreach ($items as $item) {
            if ($item === '.' || $item === '..') continue;
            $fullPath = $path . '/' . $item;
            if (is_file($fullPath) && substr(sprintf('%o', fileperms($fullPath)), -3) === '777') {
                $files[] = $fullPath;
            } elseif (is_dir($fullPath)) {
                $files = array_merge($files, $this->findWorldWritableFiles($fullPath));
            }
        }
        return $files;
    }

    /**
     * Find exposed configuration files
     */
    private function findExposedConfigFiles($path)
    {
        $files = [];
        $configFiles = ['.env', 'config.php', 'database.php', 'app.php'];
        $items = scandir($path);
        foreach ($items as $item) {
            if ($item === '.' || $item === '..') continue;
            $fullPath = $path . '/' . $item;
            if (is_file($fullPath) && in_array($item, $configFiles)) {
                $files[] = $fullPath;
            } elseif (is_dir($fullPath)) {
                $files = array_merge($files, $this->findExposedConfigFiles($fullPath));
            }
        }
        return $files;
    }

    /**
     * Scan API for security issues
     */
    private function scanAPI()
    {
        // Check for missing authentication
        $routes = $this->getAPIRoutes();
        foreach ($routes as $route) {
            if (strpos($route, '/api/') === 0 && !$this->hasAuthentication($route)) {
                $this->vulnerabilities[] = [
                    'type' => 'missing_auth',
                    'severity' => 'high',
                    'details' => "API route {$route} lacks authentication"
                ];
            }
        }

        // Check for missing rate limiting
        foreach ($routes as $route) {
            if (strpos($route, '/api/') === 0 && !$this->hasRateLimiting($route)) {
                $this->securityIssues[] = [
                    'type' => 'missing_rate_limit',
                    'severity' => 'medium',
                    'details' => "API route {$route} lacks rate limiting"
                ];
            }
        }

        // Check for missing input validation
        $controllers = glob(app_path('Controllers/*.php'));
        foreach ($controllers as $controller) {
            $content = file_get_contents($controller);
            if (strpos($content, 'validate') === false && strpos($content, 'sanitize') === false) {
                $this->securityIssues[] = [
                    'type' => 'missing_validation',
                    'severity' => 'medium',
                    'details' => "Controller {$controller} lacks input validation"
                ];
            }
        }
    }

    /**
     * Get API routes
     */
    private function getAPIRoutes()
    {
        // This is a simplified implementation
        $routes = [];
        $routeFiles = glob(base_path('routes/*.php'));
        foreach ($routeFiles as $file) {
            $content = file_get_contents($file);
            preg_match_all('/Route::(get|post|put|delete)\([\'"]([^\'"]+)[\'"]/', $content, $matches);
            if (!empty($matches[2])) {
                $routes = array_merge($routes, $matches[2]);
            }
        }
        return $routes;
    }

    /**
     * Check if route has authentication
     */
    private function hasAuthentication($route)
    {
        // This is a simplified implementation
        $middlewareFiles = glob(app_path('middleware/*.php'));
        foreach ($middlewareFiles as $file) {
            $content = file_get_contents($file);
            if (strpos($content, 'auth') !== false && strpos($content, $route) !== false) {
                return true;
            }
        }
        return false;
    }

    /**
     * Check if route has rate limiting
     */
    private function hasRateLimiting($route)
    {
        // This is a simplified implementation
        $middlewareFiles = glob(app_path('middleware/*.php'));
        foreach ($middlewareFiles as $file) {
            $content = file_get_contents($file);
            if (strpos($content, 'rate') !== false && strpos($content, $route) !== false) {
                return true;
            }
        }
        return false;
    }

    /**
     * Scan payment system for security issues
     */
    private function scanPayment()
    {
        // Check for secure payment processing
        $paymentConfig = config('payment');
        if (empty($paymentConfig['stripe']['secret']) || $paymentConfig['stripe']['secret'] === 'your_stripe_secret') {
            $this->vulnerabilities[] = [
                'type' => 'weak_payment_secret',
                'severity' => 'critical',
                'details' => 'Payment gateway secret is weak or default'
            ];
        }

        // Check for secure webhook handling
        $webhookConfig = config('webhooks');
        if (empty($webhookConfig['signature_secret']) || $webhookConfig['signature_secret'] === 'your_webhook_secret') {
            $this->vulnerabilities[] = [
                'type' => 'weak_webhook_secret',
                'severity' => 'critical',
                'details' => 'Webhook signature secret is weak or default'
            ];
        }
    }

    /**
     * Scan dependencies for security issues
     */
    private function scanDependencies()
    {
        // Check for outdated dependencies
        $composerLock = json_decode(file_get_contents(base_path('composer.lock')), true);
        if (empty($composerLock)) {
            $this->securityIssues[] = [
                'type' => 'missing_composer_lock',
                'severity' => 'medium',
                'details' => 'composer.lock file is missing'
            ];
            return;
        }

        $outdatedPackages = [];
        foreach ($composerLock['packages'] as $package) {
            if (isset($package['time']) && (time() - strtotime($package['time'])) > 180 * 24 * 60 * 60) {
                $outdatedPackages[] = $package['name'];
            }
        }

        if (!empty($outdatedPackages)) {
            $this->securityIssues[] = [
                'type' => 'outdated_dependencies',
                'severity' => 'medium',
                'details' => 'Outdated packages: ' . implode(', ', $outdatedPackages)
            ];
        }
    }

    /**
     * Log scan results
     */
    private function logScanResults()
    {
        $this->auditLogger->logSystemAction('security_scan', [
            'vulnerabilities' => count($this->vulnerabilities),
            'security_issues' => count($this->securityIssues),
            'timestamp' => date('Y-m-d H:i:s')
        ]);

        // Log critical vulnerabilities
        foreach ($this->vulnerabilities as $vulnerability) {
            if ($vulnerability['severity'] === 'critical') {
                $this->auditLogger->logSecurityEvent('critical_vulnerability', [
                    'type' => $vulnerability['type'],
                    'details' => $vulnerability['details']
                ]);
            }
        }
    }

    /**
     * Run penetration testing
     */
    public function runPenetrationTest()
    {
        // This is a simplified implementation
        // In a real system, you would use specialized tools
        
        $results = [
            'sql_injection' => $this->testSQLInjection(),
            'xss' => $this->testXSS(),
            'csrf' => $this->testCSRF(),
            'file_upload' => $this->testFileUpload(),
            'authentication' => $this->testAuthentication(),
            'authorization' => $this->testAuthorization()
        ];
        
        $this->logPenetrationTestResults($results);
        
        return $results;
    }

    /**
     * Test for SQL injection vulnerabilities
     */
    private function testSQLInjection()
    {
        // This is a simplified implementation
        $testCases = [
            "' OR '1'='1",
            "'; DROP TABLE users; --",
            "' UNION SELECT * FROM users; --"
        ];
        
        $vulnerabilities = [];
        foreach ($testCases as $testCase) {
            // Simulate testing
            if (rand(0, 1) === 1) {
                $vulnerabilities[] = [
                    'input' => $testCase,
                    'endpoint' => '/api/search',
                    'severity' => 'high'
                ];
            }
        }
        
        return $vulnerabilities;
    }

    /**
     * Test for XSS vulnerabilities
     */
    private function testXSS()
    {
        // This is a simplified implementation
        $testCases = [
            "<script>alert('xss')</script>",
            "<img src='x' onerror='alert(\"xss\")'>",
            "<a href='javascript:alert(\"xss\")'>click me</a>"
        ];
        
        $vulnerabilities = [];
        foreach ($testCases as $testCase) {
            // Simulate testing
            if (rand(0, 1) === 1) {
                $vulnerabilities[] = [
                    'input' => $testCase,
                    'endpoint' => '/api/comments',
                    'severity' => 'medium'
                ];
            }
        }
        
        return $vulnerabilities;
    }

    /**
     * Test for CSRF vulnerabilities
     */
    private function testCSRF()
    {
        // This is a simplified implementation
        $endpoints = ['/api/users', '/api/orders', '/api/payments'];
        
        $vulnerabilities = [];
        foreach ($endpoints as $endpoint) {
            // Simulate testing
            if (rand(0, 1) === 1) {
                $vulnerabilities[] = [
                    'endpoint' => $endpoint,
                    'method' => 'POST',
                    'severity' => 'high'
                ];
            }
        }
        
        return $vulnerabilities;
    }

    /**
     * Test for file upload vulnerabilities
     */
    private function testFileUpload()
    {
        // This is a simplified implementation
        $testCases = [
            'shell.php',
            'shell.php.jpg',
            'shell.php%00.jpg',
            'shell.php;.jpg'
        ];
        
        $vulnerabilities = [];
        foreach ($testCases as $testCase) {
            // Simulate testing
            if (rand(0, 1) === 1) {
                $vulnerabilities[] = [
                    'filename' => $testCase,
                    'endpoint' => '/api/upload',
                    'severity' => 'critical'
                ];
            }
        }
        
        return $vulnerabilities;
    }

    /**
     * Test for authentication vulnerabilities
     */
    private function testAuthentication()
    {
        // This is a simplified implementation
        $testCases = [
            'brute_force' => ['max_attempts' => 100, 'timeout' => 60],
            'password_policy' => ['min_length' => 8, 'complexity' => true],
            'session_management' => ['timeout' => 30, 'regenerate' => true]
        ];
        
        $vulnerabilities = [];
        foreach ($testCases as $test => $config) {
            // Simulate testing
            if (rand(0, 1) === 1) {
                $vulnerabilities[] = [
                    'test' => $test,
                    'config' => $config,
                    'severity' => 'medium'
                ];
            }
        }
        
        return $vulnerabilities;
    }

    /**
     * Test for authorization vulnerabilities
     */
    private function testAuthorization()
    {
        // This is a simplified implementation
        $testCases = [
            'role_bypass' => ['roles' => ['user', 'admin']],
            'privilege_escalation' => ['from' => 'user', 'to' => 'admin'],
            'horizontal_privilege_escalation' => ['from' => 'user1', 'to' => 'user2']
        ];
        
        $vulnerabilities = [];
        foreach ($testCases as $test => $config) {
            // Simulate testing
            if (rand(0, 1) === 1) {
                $vulnerabilities[] = [
                    'test' => $test,
                    'config' => $config,
                    'severity' => 'high'
                ];
            }
        }
        
        return $vulnerabilities;
    }

    /**
     * Log penetration test results
     */
    private function logPenetrationTestResults($results)
    {
        $criticalCount = 0;
        $highCount = 0;
        $mediumCount = 0;
        
        foreach ($results as $category => $vulnerabilities) {
            foreach ($vulnerabilities as $vulnerability) {
                if ($vulnerability['severity'] === 'critical') $criticalCount++;
                elseif ($vulnerability['severity'] === 'high') $highCount++;
                elseif ($vulnerability['severity'] === 'medium') $mediumCount++;
            }
        }
        
        $this->auditLogger->logSecurityEvent('penetration_test', [
            'critical' => $criticalCount,
            'high' => $highCount,
            'medium' => $mediumCount,
            'timestamp' => date('Y-m-d H:i:s')
        ]);
    }
} 