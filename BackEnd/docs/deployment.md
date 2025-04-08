# Deployment Guide

## System Requirements

- PHP 7.4 or higher
- MySQL 5.7 or higher
- Redis Server
- Composer
- Apache/Nginx web server
- SSL certificate (for production)

## Installation Steps

1. **Clone the Repository**
```bash
git clone [repository-url]
cd BackEnd
```

2. **Install Dependencies**
```bash
composer install
```

3. **Environment Configuration**
- Copy `.env.example` to `.env`
- Update the following configurations:
  - Database credentials
  - Redis settings
  - JWT secret
  - Payment gateway credentials
  - Mail settings
  - Application URL

4. **Database Setup**
```bash
php artisan migrate
php artisan db:seed
```

5. **Cache Setup**
```bash
php artisan cache:clear
php artisan config:cache
php artisan route:cache
```

6. **Permissions**
```bash
chmod -R 775 storage
chmod -R 775 bootstrap/cache
```

## Server Configuration

### Apache Configuration
```apache
<VirtualHost *:80>
    ServerName your-domain.com
    DocumentRoot /path/to/BackEnd/public
    
    <Directory /path/to/BackEnd/public>
        AllowOverride All
        Require all granted
    </Directory>
    
    ErrorLog ${APACHE_LOG_DIR}/error.log
    CustomLog ${APACHE_LOG_DIR}/access.log combined
</VirtualHost>
```

### Nginx Configuration
```nginx
server {
    listen 80;
    server_name your-domain.com;
    root /path/to/BackEnd/public;
    
    add_header X-Frame-Options "SAMEORIGIN";
    add_header X-Content-Type-Options "nosniff";
    
    index index.php;
    
    charset utf-8;
    
    location / {
        try_files $uri $uri/ /index.php?$query_string;
    }
    
    location = /favicon.ico { access_log off; log_not_found off; }
    location = /robots.txt  { access_log off; log_not_found off; }
    
    error_page 404 /index.php;
    
    location ~ \.php$ {
        fastcgi_pass unix:/var/run/php/php7.4-fpm.sock;
        fastcgi_param SCRIPT_FILENAME $realpath_root$fastcgi_script_name;
        include fastcgi_params;
    }
    
    location ~ /\.(?!well-known).* {
        deny all;
    }
}
```

## SSL Configuration

1. **Install SSL Certificate**
```bash
sudo certbot --nginx -d your-domain.com
```

2. **Configure HTTPS Redirect**
Add to server configuration:
```nginx
server {
    listen 443 ssl;
    ssl_certificate /etc/letsencrypt/live/your-domain.com/fullchain.pem;
    ssl_certificate_key /etc/letsencrypt/live/your-domain.com/privkey.pem;
    # ... rest of configuration
}
```

## Performance Optimization

1. **Enable OPcache**
```ini
opcache.enable=1
opcache.memory_consumption=128
opcache.interned_strings_buffer=8
opcache.max_accelerated_files=4000
opcache.revalidate_freq=60
opcache.fast_shutdown=1
opcache.enable_cli=1
```

2. **Configure Redis**
```ini
session.save_handler = redis
session.save_path = "tcp://127.0.0.1:6379"
```

3. **Enable Compression**
```apache
<IfModule mod_deflate.c>
    AddOutputFilterByType DEFLATE text/plain
    AddOutputFilterByType DEFLATE text/html
    AddOutputFilterByType DEFLATE text/xml
    AddOutputFilterByType DEFLATE text/css
    AddOutputFilterByType DEFLATE application/xml
    AddOutputFilterByType DEFLATE application/xhtml+xml
    AddOutputFilterByType DEFLATE application/rss+xml
    AddOutputFilterByType DEFLATE application/javascript
    AddOutputFilterByType DEFLATE application/x-javascript
</IfModule>
```

## Monitoring Setup

1. **Configure Logging**
```php
'channels' => [
    'stack' => [
        'driver' => 'stack',
        'channels' => ['daily', 'slack'],
        'ignore_exceptions' => false,
    ],
    'daily' => [
        'driver' => 'daily',
        'path' => storage_path('logs/laravel.log'),
        'level' => 'debug',
        'days' => 14,
    ],
]
```

2. **Setup Error Tracking**
- Configure error reporting service (e.g., Sentry)
- Add error tracking middleware

## Backup Strategy

1. **Database Backup**
```bash
# Daily backup script
mysqldump -u [user] -p[password] [database] > backup_$(date +%Y%m%d).sql
```

2. **File Backup**
```bash
# Weekly backup script
tar -czf backup_$(date +%Y%m%d).tar.gz /path/to/BackEnd
```

## Security Measures

1. **Firewall Configuration**
```bash
# Allow only necessary ports
ufw allow 80/tcp
ufw allow 443/tcp
ufw allow 22/tcp
```

2. **Rate Limiting**
```php
'rate_limiting' => [
    'enabled' => true,
    'max_attempts' => 60,
    'decay_minutes' => 1,
]
```

## Maintenance Mode

```bash
# Enable maintenance mode
php artisan down

# Disable maintenance mode
php artisan up
```

## Troubleshooting

Common issues and solutions:

1. **Permission Issues**
```bash
# Fix storage permissions
chmod -R 775 storage
chown -R www-data:www-data storage
```

2. **Cache Issues**
```bash
# Clear all caches
php artisan cache:clear
php artisan config:clear
php artisan route:clear
php artisan view:clear
```

3. **Database Connection Issues**
- Check database credentials in .env
- Verify database server is running
- Check network connectivity

## Scaling

1. **Horizontal Scaling**
- Setup load balancer
- Configure session handling
- Setup database replication

2. **Vertical Scaling**
- Increase server resources
- Optimize database queries
- Implement caching strategies

## Update Procedure

1. **Backup**
```bash
# Backup database and files
./backup.sh
```

2. **Update Code**
```bash
git pull origin main
composer install
php artisan migrate
php artisan cache:clear
```

3. **Verify**
- Check application logs
- Run test suite
- Monitor error rates 