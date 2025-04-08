# Troubleshooting Guide

## Common Issues and Solutions

### 1. Authentication Issues

#### JWT Token Invalid
**Symptoms:**
- 401 Unauthorized errors
- Token expiration errors

**Solutions:**
1. Check token expiration time in `.env`:
```
JWT_TTL=60
JWT_REFRESH_TTL=20160
```

2. Verify token format in request header:
```
Authorization: Bearer <token>
```

3. Clear token cache:
```bash
php artisan cache:clear
```

#### Login Failures
**Symptoms:**
- Invalid credentials errors
- Account locked messages

**Solutions:**
1. Check rate limiting settings:
```php
'rate_limiting' => [
    'enabled' => true,
    'max_attempts' => 5,
    'decay_minutes' => 1
]
```

2. Verify user status in database
3. Check password hashing configuration

### 2. Database Issues

#### Connection Errors
**Symptoms:**
- "Could not connect to database" errors
- Timeout errors

**Solutions:**
1. Verify database credentials in `.env`:
```
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=your_database
DB_USERNAME=your_username
DB_PASSWORD=your_password
```

2. Check database service status:
```bash
sudo service mysql status
```

3. Verify network connectivity:
```bash
telnet DB_HOST DB_PORT
```

#### Query Performance
**Symptoms:**
- Slow response times
- High CPU usage

**Solutions:**
1. Enable query logging:
```php
DB::enableQueryLog();
```

2. Optimize indexes:
```sql
EXPLAIN SELECT * FROM users WHERE email = 'test@example.com';
```

3. Clear query cache:
```bash
php artisan cache:clear
```

### 3. Cache Issues

#### Redis Connection
**Symptoms:**
- Cache miss errors
- Connection refused errors

**Solutions:**
1. Check Redis configuration:
```
REDIS_HOST=127.0.0.1
REDIS_PASSWORD=null
REDIS_PORT=6379
REDIS_DB=0
```

2. Verify Redis service:
```bash
redis-cli ping
```

3. Clear Redis cache:
```bash
redis-cli flushall
```

#### Cache Invalidation
**Symptoms:**
- Stale data
- Inconsistent results

**Solutions:**
1. Check cache tags:
```php
Cache::tags(['users'])->flush();
```

2. Verify cache keys:
```php
Cache::get('key');
```

3. Clear specific cache:
```bash
php artisan cache:clear --tag=users
```

### 4. Payment Issues

#### Gateway Connection
**Symptoms:**
- Payment gateway errors
- Timeout errors

**Solutions:**
1. Verify gateway credentials:
```
STRIPE_KEY=your_stripe_key
STRIPE_SECRET=your_stripe_secret
PAYPAL_CLIENT_ID=your_paypal_client_id
PAYPAL_SECRET=your_paypal_secret
```

2. Check SSL certificate
3. Verify webhook URLs

#### Transaction Failures
**Symptoms:**
- Failed transactions
- Error responses

**Solutions:**
1. Check transaction logs
2. Verify payment method
3. Check account balance/limits

### 5. File Upload Issues

#### Upload Failures
**Symptoms:**
- File upload errors
- Size limit errors

**Solutions:**
1. Check PHP configuration:
```ini
upload_max_filesize = 10M
post_max_size = 10M
```

2. Verify directory permissions:
```bash
chmod -R 775 storage/app/public
```

3. Check disk space:
```bash
df -h
```

#### Image Processing
**Symptoms:**
- Image processing errors
- Memory limit errors

**Solutions:**
1. Check memory limit:
```ini
memory_limit = 256M
```

2. Verify image library:
```bash
php -m | grep gd
```

3. Clear temporary files:
```bash
rm -rf storage/app/temp/*
```

### 6. API Issues

#### Rate Limiting
**Symptoms:**
- 429 Too Many Requests
- Rate limit exceeded

**Solutions:**
1. Check rate limit settings:
```php
'rate_limiting' => [
    'enabled' => true,
    'max_attempts' => 60,
    'decay_minutes' => 1
]
```

2. Verify IP whitelist
3. Check rate limit headers

#### CORS Issues
**Symptoms:**
- CORS errors
- Cross-origin request blocked

**Solutions:**
1. Check CORS configuration:
```php
'cors' => [
    'allowed_origins' => ['*'],
    'allowed_methods' => ['*'],
    'allowed_headers' => ['*'],
]
```

2. Verify origin headers
3. Check preflight requests

### 7. Performance Issues

#### Slow Response Times
**Symptoms:**
- High response times
- Timeout errors

**Solutions:**
1. Enable OPcache:
```ini
opcache.enable=1
opcache.memory_consumption=128
```

2. Check database indexes
3. Enable query caching

#### Memory Usage
**Symptoms:**
- Memory limit errors
- High memory usage

**Solutions:**
1. Check memory limit:
```ini
memory_limit = 256M
```

2. Optimize queries
3. Enable garbage collection

### 8. Logging Issues

#### Log File Access
**Symptoms:**
- Log file permission errors
- Missing log files

**Solutions:**
1. Check log permissions:
```bash
chmod -R 775 storage/logs
```

2. Verify log configuration:
```php
'channels' => [
    'daily' => [
        'driver' => 'daily',
        'path' => storage_path('logs/laravel.log'),
        'level' => 'debug',
        'days' => 14,
    ],
]
```

3. Clear old logs:
```bash
find storage/logs -name "*.log" -mtime +14 -delete
```

#### Log Rotation
**Symptoms:**
- Large log files
- Disk space issues

**Solutions:**
1. Configure log rotation:
```php
'channels' => [
    'daily' => [
        'driver' => 'daily',
        'days' => 14,
    ],
]
```

2. Set up logrotate:
```conf
/var/log/laravel/*.log {
    daily
    missingok
    rotate 14
    compress
    delaycompress
    notifempty
    create 0640 www-data www-data
}
```

### 9. Security Issues

#### CSRF Protection
**Symptoms:**
- CSRF token mismatch
- 419 errors

**Solutions:**
1. Check CSRF configuration:
```php
'csrf' => [
    'enabled' => true,
    'token_name' => 'csrf_token',
]
```

2. Verify token in requests
3. Check session configuration

#### XSS Protection
**Symptoms:**
- XSS vulnerabilities
- Script injection

**Solutions:**
1. Enable XSS protection:
```php
header('X-XSS-Protection: 1; mode=block');
```

2. Sanitize input data
3. Use proper escaping

### 10. Deployment Issues

#### Deployment Failures
**Symptoms:**
- Deployment errors
- Update failures

**Solutions:**
1. Check deployment script:
```bash
#!/bin/bash
git pull
composer install
php artisan migrate
php artisan cache:clear
```

2. Verify permissions
3. Check error logs

#### Version Conflicts
**Symptoms:**
- Dependency conflicts
- Version mismatch

**Solutions:**
1. Update composer:
```bash
composer update
```

2. Check version constraints
3. Clear composer cache:
```bash
composer clear-cache
```

## Getting Help

If you're still experiencing issues:

1. Check the logs:
```bash
tail -f storage/logs/laravel.log
```

2. Enable debug mode:
```
APP_DEBUG=true
```

3. Contact support with:
- Error messages
- Log files
- Steps to reproduce
- Environment details 