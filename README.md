# Perfect Fit API – Laravel JWT API with Swagger & Advanced Auth 🛠️

![PHP](https://img.shields.io/badge/PHP-8.2-blue?logo=php) ![Laravel](https://img.shields.io/badge/Laravel-10.x-red?logo=laravel) ![JWT](https://img.shields.io/badge/JWT-Passport-green?logo=laravel) ![Swagger](https://img.shields.io/badge/Swagger-L5-yellow?logo=swagger) ![MySQL](https://img.shields.io/badge/MySQL-8.x-orange?logo=mysql) ![License](https://img.shields.io/badge/License-MIT-green)

Yo bro, welcome to **Perfect Fit API**! 🚀 This is a beast of a Laravel API built for e-commerce apps that need rock-solid authentication, device tracking, and a full suite of shopping features. Think of it as the backend powerhouse for a Next.js or React Native app – JWT auth via Passport, auto-generated Swagger docs for easy testing, and modules for everything from social logins to AI size recommendations. Whether you're building a mobile app or integrating with a web frontend, this API has you covered with clean, scalable code that follows Laravel best practices.

## 📋 Project Overview
As a web dev, imagine you're spinning up a backend for an online clothing store where users can login via Google, track their devices, add stuff to cart, and even get AI-powered size suggestions based on measurements. Perfect Fit handles all that, answering questions like:
- 🔐 How do I secure multi-device logins without session headaches?
- 📱 Can users switch between phone and web without re-authenticating every time?
- 🛒 What's the flow for cart, wishlist, orders, and payments in a stateless API?
- 📚 How do I document and test all these endpoints without writing Postman collections manually?

Powered by **Laravel Passport** for JWT tokens, **L5-Swagger** for interactive docs, and Redis for queues/caching, this API is production-ready out of the box. It's structured with repositories and services for that clean architecture vibe, making it easy to maintain and extend.

## 🗃️ Database
The system uses **MySQL** (or PostgreSQL) with key tables like:
- **Users**: Core user data with roles and profiles. Columns: `id`, `name`, `email`, `phone`, `verified_at`, `social_provider`.
- **UserDevices**: Tracks multi-device sessions. Columns: `id`, `user_id`, `device_id`, `device_type`, `fcm_token`, `trusted_at`, `last_used_at`.
- **Products**: E-commerce items. Columns: `id`, `name`, `price`, `stock`, `brand_id`, `gender`, `measurements` (JSON for sizes).
- **Carts**: User shopping carts. Columns: `id`, `user_id`, `product_id`, `quantity`.
- **Orders**: Purchase records. Columns: `id`, `user_id`, `total`, `status`, `payment_method`, `shipping_address`.
- **ProductReviews**: User feedback. Columns: `id`, `product_id`, `user_id`, `rating`, `comment`, `likes`.

📂 Migrations are in `database/migrations/`, with Eloquent models in `app/Models/` handling relationships (e.g., users have many devices and orders).

## 🛠️ Environment Requirements
To run Perfect Fit API, you need:
- **PHP**: 8.2+ (Laravel 10.x demands it) 🐘
- **Composer**: For PHP deps 📦
- **Node.js & NPM**: Optional, for asset compilation 🌐
- **Database**: MySQL 8.x or PostgreSQL 🗄️
- **Redis**: For queues, caching, and sessions (must-have for emails) 🚀
- **System**: Linux/macOS/Windows (WSL is king) 💻
- **Optional Services**:
  - **Twilio/Firebase/eSMS/SpeedSMS**: For phone OTP.
  - **Google/Facebook/TikTok**: For social auth.
  - **SMTP Server**: For email verification (Gmail works for dev).

Dependencies (in `composer.json`):
- `laravel/framework`: The backbone.
- `laravel/passport`: JWT/OAuth magic.
- `darkaonline/l5-swagger`: Auto-docs like magic.
- `laravel/socialite`: Social login handlers.

## ⚙️ Setup Instructions
Follow these steps to fire up the API, like bootstrapping a fresh Laravel project but with extra sauce:

1. **Clone the Repository** 📥:
   ```bash
   git clone https://github.com/binhchay1/perfect-fit-api.git
   cd perfect_fit
   ```

2. **Install Dependencies** 📦:
   ```bash
   composer install
   npm install  # If you need frontend assets
   ```

3. **Configure Environment** 🛠️:
   ```bash
   cp .env.example .env
   ```
   Edit `.env` – here's the must-haves:
   ```env
   APP_NAME="Perfect Fit"
   APP_ENV=local
   APP_DEBUG=true
   APP_URL=http://localhost:8000

   DB_CONNECTION=mysql
   DB_HOST=127.0.0.1
   DB_PORT=3306
   DB_DATABASE=perfect_fit
   DB_USERNAME=your_db_user
   DB_PASSWORD=your_db_pass

   REDIS_HOST=127.0.0.1
   REDIS_PASSWORD=null
   REDIS_PORT=6379

   QUEUE_CONNECTION=redis
   CACHE_DRIVER=redis
   SESSION_DRIVER=redis

   MAIL_MAILER=smtp
   MAIL_HOST=smtp.gmail.com  # Or your SMTP
   MAIL_PORT=587
   MAIL_USERNAME=your_email@gmail.com
   MAIL_PASSWORD=your_app_password
   MAIL_ENCRYPTION=tls
   MAIL_FROM_ADDRESS=noreply@perfectfit.com

   # Swagger
   L5_SWAGGER_GENERATE_ALWAYS=true

   # Auth (optional for prod)
   SMS_PROVIDER=log  # Start with log for dev
   GOOGLE_CLIENT_ID=your_google_id
   # ... other social keys
   ```

4. **Generate Keys & Setup** 🔑:
   ```bash
   php artisan key:generate
   php artisan passport:keys --force  # For JWT
   php artisan passport:client --personal  # Personal client for testing
   php artisan migrate  # Run migrations
   php artisan l5-swagger:generate  # Build Swagger docs
   ```

5. **Seed Data (Optional)** 🌱:
   ```bash
   php artisan db:seed  # If seeders exist
   ```

6. **Start Services** 🚀:
   - Redis: `redis-server` (or ensure it's running).
   - Queue Worker: In a new terminal, `php artisan queue:work` (for emails).
   - Server: `php artisan serve`.
   Hit `http://localhost:8000/docs` for Swagger!

## 🚀 How to Run & Test
1. **Launch the API** 🌐:
   Ensure Redis and queue worker are up, then `php artisan serve`.

2. **Access Swagger Docs** 📚:
   Open `http://localhost:8000/docs` – it's your interactive playground. Authorize with JWT tokens and test endpoints live.

3. **Test Auth Flow** 🔐:
   - Register: POST `/api/auth/register` with email/password.
   - Verify Email: Check your inbox (or logs if `MAIL_MAILER=log`).
   - Login: POST `/api/auth/login` → Get JWT token.
   - Use token: `Authorization: Bearer {token}` in headers.

4. **Device Management Example** 📱:
   On login, include device info:
   ```json
   {
     "email": "test@example.com",
     "password": "password",
     "device_id": "uuid-here",
     "device_name": "iPhone 14",
     "device_type": "ios"
   }
   ```
   Then GET `/api/devices` to list sessions.

5. **Stop Everything** 🛑:
   Ctrl+C on servers; `php artisan queue:stop` if needed.

## 📁 Project Structure
Like a well-organized Laravel API repo:
```
perfect_fit/
├── app/
│   ├── Enums/          # Constants like UserDevice types 📋
│   ├── Http/Controllers/API/  # Endpoint handlers 🛠️
│   │   ├── AuthController.php
│   │   ├── DeviceController.php
│   │   ├── ProductController.php
│   │   └── Admin/      # Admin-specific controllers
│   ├── Models/         # Eloquent models 📊
│   │   ├── User.php
│   │   ├── UserDevice.php
│   │   └── Order.php
│   ├── Repositories/   # Data access layer 🔍
│   │   └── UserDeviceRepository.php
│   └── Services/       # Business logic 🎯
│       ├── OtpService.php
│       └── PerfectFitService.php  # AI size recs
├── config/             # Configs 📄
│   └── l5-swagger.php
├── database/           # Migrations & seeds 🗄️
├── docs/               # Module docs 📖
│   ├── authentication.md
│   └── DEVICE_MANAGEMENT_API_DOCUMENTATION.md
├── routes/             # API routes 🚏
│   └── api.php
├── .env.example        # Env template 📋
├── composer.json       # PHP deps 📦
└── README.md           # You're reading it! 📖
```

## 📈 Key Features
- **Multi-Auth**: Email/password, social (Google/FB/TikTok), phone OTP (Twilio/eSMS) 🔐
- **Device Tracking**: Multi-device support with FCM tokens, revocation, and trust flags 📱
- **E-Commerce Core**: Products, cart, wishlist, orders, payments (VNPay/COD), shipping 🛒
- **Advanced Modules**: AI size recs (Perfect Fit AI), reviews with likes, returns/refunds 🤖
- **Admin APIs**: Dashboard stats, user/order/product CRUD 📊
- **Swagger Docs**: Auto-generated, interactive UI at `/docs` 📚
- **Queues & Caching**: Redis-powered for emails and perf 🚀

## 💡 API Modules Quick Ref
| Module | Key Endpoints | Notes |
|--------|---------------|-------|
| **Auth** | `/api/auth/register`, `/api/auth/login` | JWT + email verify |
| **Social** | `/api/auth/social/google` | OAuth token exchange |
| **OTP** | `/api/auth/phone/send-otp` | SMS via Twilio/etc. |
| **Devices** | `/api/devices`, DELETE `/api/devices/{id}` | Multi-session magic |
| **Products** | GET `/api/products`, POST `/api/cart/add` | Search/filters |
| **Orders** | POST `/api/orders`, GET `/api/orders/{id}` | Tracking + refunds |
| **Admin** | GET `/api/admin/dashboard`, POST `/api/admin/products` | Stats + CRUD |

## 🛠️ Troubleshooting
- **Passport Errors** ⚠️: `php artisan passport:keys --force` and check `.env` for `SESSION_DRIVER=redis`.
- **Email Not Sending** 📧: Ensure queue worker runs (`php artisan queue:work`) and Redis is up (`redis-cli ping`).
- **Swagger Blank** 🚫: `php artisan l5-swagger:generate && php artisan config:clear`.
- **DB Connection Fail** 🗄️: Verify `.env` creds; create DB manually if needed.
- **Social Auth Fails** 🔗: Double-check OAuth keys in `.env`; test with ngrok for callbacks.
- **Permissions** 🔒: `chmod -R 755 storage bootstrap/cache`.

Pro Tip: For dev, set `SMS_PROVIDER=log` to skip SMS costs – it logs OTPs to console.

## 🤝 Contributing
Fork it, PR it, or issue it! Follow Laravel's [contrib guide](https://laravel.com/docs/contributions). Let's make this API even more perfect. 🌟

## 📜 License
MIT License (see `LICENSE`).

## 📞 Support
Hit up [GitHub Issues](https://github.com/binhchay1/perfect-fit-api/issues) or check `docs/` for module guides. Email: binhchay1@gmail.com.

## 💡 Env Vars Quick Table
| Var | What It Does | Default |
|-----|--------------|---------|
| `REDIS_HOST` | Redis server | 127.0.0.1 |
| `QUEUE_CONNECTION` | Email queue driver | redis |
| `L5_SWAGGER_GENERATE_ALWAYS` | Auto-docs on request | false |
| `SMS_PROVIDER` | OTP service (log/twilio/etc.) | log |
| `GOOGLE_CLIENT_ID` | Google OAuth | - |
| `TWILIO_SID` | Twilio account | - |
