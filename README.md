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

### Available API Modules

The application includes comprehensive API modules:

#### **Core APIs**
- ✅ **Authentication** - Login, Register, Email Verification, Password Reset
- ✅ **Social Auth** - Google, Facebook, Tiktok OAuth
- ✅ **OTP** - Phone number verification and login
- ✅ **User Management** - Profile, Change Password
- ✅ **Device Management** - Multi-device session tracking

#### **E-Commerce APIs**
- ✅ **Products** - CRUD, Search, Filters, by Brand/Gender
- ✅ **Brands** - Listing, Search, with Products
- ✅ **Cart** - Add, Update, Remove, Summary
- ✅ **Wishlist** - Add, Remove, Check, Count
- ✅ **Orders** - Create, List, Detail, Cancel, Tracking
- ✅ **Payment** - VNPay, COD, Payment Links
- ✅ **Shipping** - Carriers, Settings, Calculation

#### **Advanced Features**
- ✅ **Product Reviews** - Rating, Comments, Like/Dislike
- ✅ **Perfect Fit AI** - AI-powered size recommendation
- ✅ **Order Returns** - Return/Refund requests
- ✅ **Payment Accounts** - Bank account management (Admin)

#### **Admin Panel APIs**
- ✅ **Dashboard** - Analytics, Statistics, Reports
- ✅ **User Management** - CRUD, Status, Statistics
- ✅ **Order Management** - Status, Tracking, Refunds
- ✅ **Product Management** - CRUD, Bulk operations
- ✅ **Brand Management** - CRUD, Status
- ✅ **Shipping Management** - Carriers, Settings
- ✅ **Payment Accounts** - Bank accounts setup
- ✅ **Return Management** - Approve/Reject returns

### Documentation Files

Detailed documentation for each module is available in the `docs/` folder:
- `DEVICE_MANAGEMENT_API_DOCUMENTATION.md`
- `PRODUCT_REVIEWS_API_DOCUMENTATION.md`
- `PERFECT_FIT_AI_API_DOCUMENTATION.md`
- `ORDER_RETURNS_API_DOCUMENTATION.md`
- `PAYMENT_ACCOUNTS_API_DOCUMENTATION.md`
- `SOCIAL_AUTH_OTP_API_DOCUMENTATION.md`
- And more...

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
| `GOOGLE_CLIENT_ID` | Google OAuth client ID | - |
| `FACEBOOK_CLIENT_ID` | Facebook OAuth app ID | - |
| `TIKTOK_CLIENT_KEY` | Tiktok OAuth client key | - |
| `SMS_API_URL` | SMS service API URL | - |
| `SMS_API_KEY` | SMS service API key | - |
| `PERFECT_FIT_AI_URL` | AI service URL for size recommendation | - |
| `PERFECT_FIT_AI_KEY` | AI service API key | - |

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
│   ├── Enums/                    # Enumerations & Constants
│   │   ├── Users.php
│   │   ├── UserDevice.php
│   │   ├── PaymentAccount.php
│   │   ├── ProductReview.php
│   │   ├── BodyMeasurement.php
│   │   ├── OrderReturn.php
│   │   ├── SocialAuth.php
│   │   ├── OTP.php
│   │   └── Utility.php
│   ├── Http/Controllers/API/
│   │   ├── AuthController.php
│   │   ├── SocialAuthController.php
│   │   ├── OtpController.php
│   │   ├── DeviceController.php
│   │   ├── ProductController.php
│   │   ├── ProductReviewController.php
│   │   ├── PerfectFitController.php
│   │   ├── BrandController.php
│   │   ├── CartController.php
│   │   ├── WishlistController.php
│   │   ├── OrderController.php
│   │   ├── OrderReturnController.php
│   │   ├── PaymentController.php
│   │   └── Admin/...              # Admin controllers
│   ├── Models/
│   │   ├── User.php
│   │   ├── UserDevice.php
│   │   ├── Product.php
│   │   ├── ProductReview.php
│   │   ├── ReviewReaction.php
│   │   ├── UserBodyMeasurement.php
│   │   ├── Order.php
│   │   ├── OrderReturn.php
│   │   ├── PaymentAccount.php
│   │   └── ...
│   ├── Repositories/              # Database queries
│   │   ├── UserDeviceRepository.php
│   │   ├── ProductReviewRepository.php
│   │   ├── PaymentAccountRepository.php
│   │   ├── OrderReturnRepository.php
│   │   └── ...
│   ├── Services/                  # Business logic
│   │   ├── UserDeviceService.php
│   │   ├── ProductReviewService.php
│   │   ├── PerfectFitService.php
│   │   ├── OrderReturnService.php
│   │   ├── SocialAuthService.php
│   │   ├── OtpService.php
│   │   └── ...
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

## 📱 Device Management & Session Tracking

The application includes comprehensive device management and session tracking capabilities:

### Features

- **Multi-Device Support**: Users can login from multiple devices simultaneously
- **Device Tracking**: Track device information (type, model, OS version, app version)
- **Session Management**: Monitor and manage active sessions across devices
- **Trusted Devices**: Mark devices as trusted for enhanced security
- **FCM Integration**: Support for Firebase Cloud Messaging tokens
- **Device Revocation**: Ability to revoke access from specific devices or all devices

### Device Information Tracked

- Device ID (unique identifier)
- Device name (user-friendly name)
- Device type (iOS, Android, Web, Desktop, Tablet)
- Device model
- OS version
- App version
- FCM token (for push notifications)
- IP address
- User agent
- Last used timestamp
- Active status
- Trusted status

### API Endpoints

#### Device Management
- `GET /api/devices` - Get user's devices
- `PUT /api/devices/{id}/name` - Update device name
- `POST /api/devices/{id}/trust` - Toggle device trust status
- `DELETE /api/devices/{id}` - Revoke/deactivate device
- `POST /api/devices/revoke-others` - Revoke all other devices
- `PUT /api/devices/fcm-token` - Update FCM token

### Architecture

The device management follows clean architecture principles:

#### **Enum** (`app/Enums/UserDevice.php`)
- Defines device types, statuses, and constants
- Centralized configuration for device-related values

#### **Model** (`app/Models/UserDevice.php`)
- Defines table structure and relationships
- Contains only data definition (no business logic)

#### **Repository** (`app/Repositories/UserDeviceRepository.php`)
- Handles all database queries
- Provides data access layer
- Methods for CRUD operations and complex queries

#### **Service** (`app/Services/UserDeviceService.php`)
- Contains business logic for device management
- Orchestrates operations between repository and controllers
- Handles device registration, updates, and revocation logic

#### **Controller** (`app/Http/Controllers/API/DeviceController.php`)
- Handles HTTP requests and responses
- Uses service layer for business logic
- Returns formatted API responses

### Security Features

1. **Session Isolation**: Each device maintains its own token
2. **Selective Revocation**: Revoke specific devices without affecting others
3. **Trust Management**: Enhanced security for untrusted devices
4. **Activity Monitoring**: Track last used timestamp for security audits
5. **IP Tracking**: Monitor device locations for suspicious activity

### Usage Example

#### Login with Device Information
```bash
POST /api/auth/login
{
  "email": "user@example.com",
  "password": "password",
  "device_id": "unique-device-id",
  "device_name": "My iPhone",
  "device_type": "ios",
  "device_model": "iPhone 14 Pro",
  "os_version": "17.0",
  "app_version": "1.0.0",
  "fcm_token": "fcm-token-here",
  "remember_device": true
}
```

#### Get All Devices
```bash
GET /api/devices
Authorization: Bearer your_token_here
```

#### Revoke Device
```bash
DELETE /api/devices/{device_id}
Authorization: Bearer your_token_here
```

---

**Happy coding! 🚀**