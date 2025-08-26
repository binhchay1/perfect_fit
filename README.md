# Perfect Fit API

Laravel-based API application with JWT authentication, Swagger documentation, and comprehensive user management.

## 🚀 Quick Setup

### Prerequisites
- PHP 8.2+
- Composer
- MySQL/PostgreSQL
- Redis (for queue handling and email processing)
- Node.js & NPM (optional, for frontend assets)

### 1. Installation

```bash
# Clone the repository
git clone <repository-url>
cd perfect_fit

# Install PHP dependencies
composer install

# Install NPM dependencies (if needed)
npm install

# Copy environment file
cp .env.example .env
```

### 2. Environment Configuration

Edit `.env` file with your database and application settings:

```env
APP_NAME="Perfect Fit"
APP_ENV=local
APP_KEY=
APP_DEBUG=true
APP_URL=http://localhost:8000

DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=perfect_fit
DB_USERNAME=your_username
DB_PASSWORD=your_password

# Redis Configuration (for queues and caching)
REDIS_HOST=127.0.0.1
REDIS_PASSWORD=null
REDIS_PORT=6379
REDIS_DB=0

# Queue Configuration (Redis-based)
QUEUE_CONNECTION=redis
CACHE_DRIVER=redis
SESSION_DRIVER=redis

# Mail Configuration (for email verification)
MAIL_MAILER=smtp
MAIL_HOST=your_smtp_host
MAIL_PORT=587
MAIL_USERNAME=your_email
MAIL_PASSWORD=your_password
MAIL_ENCRYPTION=tls
MAIL_FROM_ADDRESS=noreply@perfectfit.com
MAIL_FROM_NAME="${APP_NAME}"

# Swagger Configuration
L5_SWAGGER_GENERATE_ALWAYS=true
L5_SWAGGER_UI_DOC_EXPANSION=none
```

### 3. Application Setup

```bash
# Generate application key
php artisan key:generate

# Create database (make sure your DB server is running)
# Then run migrations
php artisan migrate

# Setup Laravel Passport for API authentication
php artisan passport:keys --force
php artisan passport:client --personal

# Generate Swagger documentation
php artisan l5-swagger:generate

# Clear and cache config (optional, for production)
php artisan config:cache
php artisan route:cache
```

### 4. Database Seeding (Optional)

```bash
# Run seeders if available
php artisan db:seed
```

### 5. Start Development Server

```bash
# Start Redis server (if not running as service)
redis-server

# Start Laravel queue worker (in separate terminal)
php artisan queue:work

# Start Laravel development server
php artisan serve

# Your application will be available at: http://localhost:8000
```

## 📚 API Documentation

### Access Swagger Documentation

Once your application is running, you can access the interactive API documentation at:

**🔗 http://localhost:8000/docs**

The Swagger UI provides:
- Interactive API testing
- Complete endpoint documentation
- Request/response examples
- Authentication testing

## 🔐 Authentication

This API uses **Laravel Passport** for authentication:

1. **Register** a new account via `/api/auth/register`
2. **Verify** your email using the verification link
3. **Login** via `/api/auth/login` to get your access token
4. **Include the token** in subsequent requests:
   ```
   Authorization: Bearer your_access_token_here
   ```

### Testing Authentication in Swagger

1. Go to `/docs`
2. Click the **Authorize** button (🔒)
3. Enter: `Bearer your_access_token_here`
4. Click **Authorize**
5. Now you can test protected endpoints

## 🛠 Development Commands

```bash
# Clear application cache
php artisan cache:clear

# Clear config cache
php artisan config:clear

# Clear route cache
php artisan route:clear

# Regenerate Swagger docs
php artisan l5-swagger:generate

# Run migrations with fresh start
php artisan migrate:fresh

# Run specific migration
php artisan migrate --path=/database/migrations/specific_migration.php

# Rollback migrations
php artisan migrate:rollback

# Check routes
php artisan route:list
```

## 📝 Environment Variables Reference

| Variable | Description | Default |
|----------|-------------|---------|
| `REDIS_HOST` | Redis server host | `127.0.0.1` |
| `REDIS_PORT` | Redis server port | `6379` |
| `QUEUE_CONNECTION` | Queue driver (should be redis) | `sync` |
| `CACHE_DRIVER` | Cache driver (redis recommended) | `file` |
| `L5_SWAGGER_GENERATE_ALWAYS` | Auto-regenerate docs on each request | `false` |
| `L5_SWAGGER_UI_DOC_EXPANSION` | Swagger UI default expansion | `none` |
| `DB_*` | Database connection settings | - |
| `MAIL_*` | Email service configuration | - |

## 🚨 Troubleshooting

### Common Issues

**Passport Keys Not Generated:**
```bash
php artisan passport:keys --force
php artisan passport:client --personal
```



**Database Connection Error:**
- Check your `.env` database credentials
- Ensure your database server is running
- Create the database if it doesn't exist

**Swagger Documentation Not Loading:**
```bash
php artisan l5-swagger:generate
php artisan config:clear
```

**Redis Connection Error:**
```bash
# Check if Redis is running
redis-cli ping

# Start Redis if not running
redis-server

# Or install Redis if not installed:
# Ubuntu/Debian: sudo apt install redis-server
# macOS: brew install redis
# Windows: Download from https://redis.io/download
```

**Queue Jobs Not Processing:**
```bash
# Make sure Redis is running
redis-cli ping

# Start queue worker
php artisan queue:work

# Clear failed jobs
php artisan queue:clear
```

**Permission Errors:**
```bash
sudo chown -R $USER:www-data storage
sudo chown -R $USER:www-data bootstrap/cache
chmod -R 755 storage
chmod -R 755 bootstrap/cache
```

## 🏗 Project Structure

```
perfect_fit/
├── app/
│   ├── Http/Controllers/API/
│   │   ├── AuthController.php    # Authentication endpoints
│   │   └── UserController.php    # User management endpoints
├── config/
│   └── l5-swagger.php            # Swagger configuration
├── routes/
│   ├── api.php                   # API routes
│   └── web.php                   # Web routes (including docs)
└── database/
    └── migrations/               # Database migrations
```

## 📧 Support

If you encounter any issues during setup, please check:
1. PHP version compatibility (8.2+)
2. All required extensions are installed
3. Database connection is working
4. Redis server is running and accessible
5. Queue worker is running for email processing
6. Environment variables are properly set

### Email Processing Flow
This application uses Redis queues for handling email sending:
1. **Email verification** and **password reset** emails are queued
2. **Queue worker** processes emails in background
3. **Redis** stores queue jobs and handles job distribution
4. Make sure both Redis and queue worker are running for emails to send

---

**Happy coding! 🚀**