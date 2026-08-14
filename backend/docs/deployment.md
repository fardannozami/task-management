# Deployment Guide

## Prerequisites

- Server with PHP 8.2+, Composer, MySQL 5.7+
- SSL certificate (recommended for production)
- Supervisor for queue workers (recommended)
- Nginx or Apache web server

## Environment Configuration

1. Set production environment variables:
```env
APP_ENV=production
APP_DEBUG=false
APP_URL=https://api.example.com

DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=task_management
DB_USERNAME=your_user
DB_PASSWORD=your_password

QUEUE_CONNECTION=database
CACHE_STORE=database
SESSION_DRIVER=database

MAIL_MAILER=smtp
MAIL_HOST=smtp.mailtrap.io
MAIL_PORT=587
MAIL_USERNAME=your_username
MAIL_PASSWORD=your_password
MAIL_ENCRYPTION=tls
MAIL_FROM_ADDRESS=noreply@example.com
MAIL_FROM_NAME="Task Management System"

JWT_SECRET=your_jwt_secret_here
```

2. Generate JWT secret:
```bash
php artisan jwt:secret
```

## Deployment Steps

### 1. Code Deployment

```bash
# Pull latest code
git pull origin main

# Install dependencies
composer install --no-dev --optimize-autoloader

# Run migrations
php artisan migrate --force

# Seed database (first time only)
php artisan db:seed --force
```

### 2. Storage & Cache

```bash
# Create storage link
php artisan storage:link

# Clear and cache config
php artisan config:cache
php artisan route:cache
php artisan view:cache
```

### 3. Queue Worker Setup

Install Supervisor:
```bash
sudo apt install supervisor
```

Create `/etc/supervisor/conf.d/task-management-worker.conf`:
```ini
[program:task-management-worker]
process_name=%(program_name)s_%(process_num)02d
command=php /path/to/backend/artisan queue:work --sleep=3 --tries=3 --max-time=3600
autostart=true
autorestart=true
stopasgroup=true
killasgroup=true
numprocs=1
directory=/path/to/backend
user=www-data
redirect_stderr=true
stdout_logfile=/path/to/backend/storage/logs/worker.log
```

Start worker:
```bash
sudo supervisorctl reread
sudo supervisorctl update
sudo supervisorctl start task-management-worker:*
```

### 4. Scheduled Jobs

Add to crontab (`crontab -e`):
```bash
* * * * * cd /path/to/backend && php artisan schedule:run >> /dev/null 2>&1
```

### 5. Web Server Configuration

#### Nginx

```nginx
server {
    listen 80;
    server_name api.example.com;
    root /path/to/backend/public;

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
        fastcgi_pass unix:/var/run/php/php8.2-fpm.sock;
        fastcgi_param SCRIPT_FILENAME $realpath_root$fastcgi_script_name;
        include fastcgi_params;
        fastcgi_hide_header X-Powered-By;
    }

    location ~ /\.(?!well-known).* {
        deny all;
    }
}
```

#### Apache

Ensure `.htaccess` in `public/` directory is enabled and mod_rewrite is on.

### 6. SSL Certificate (Let's Encrypt)

```bash
sudo certbot --nginx -d api.example.com
```

### 7. Firewall

```bash
sudo ufw allow 22/tcp
sudo ufw allow 80/tcp
sudo ufw allow 443/tcp
sudo ufw enable
```

## Health Checks

- `GET /up` - Laravel health check
- `GET /api/auth/me` - Requires valid JWT, checks auth and database

## Monitoring

- Queue worker logs: `storage/logs/worker.log`
- Application logs: `storage/logs/laravel.log`
- Failed jobs: `php artisan queue:failed`
- Retry failed jobs: `php artisan queue:retry all`

## Backup Strategy

1. **Database**: Daily MySQL dump
```bash
mysqldump -u user -p task_management > backup_$(date +%F).sql
```

2. **Files**: Backup `storage/app/attachments/` and `storage/app/private/exports/`

3. **Code**: Git repository backup

## Scaling Considerations

- Use Redis for queue driver at high scale
- Use S3 or similar for file storage instead of local
- Implement load balancer for multiple app servers
- Use separate database for queue jobs
- Implement CDN for thumbnails
