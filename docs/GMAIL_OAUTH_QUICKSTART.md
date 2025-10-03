# 🚀 Gmail OAuth2 - Quick Start

## ⚡ 15 Minutes Setup

### 1️⃣ Google Cloud Setup (5 min)

1. Go to [Google Cloud Console](https://console.cloud.google.com)
2. Create project: `perfect-fit-mailer`
3. Enable **Gmail API**
4. Create **OAuth Client ID** (Web application)
5. Add redirect URI: `http://localhost:8000/oauth2/callback`
6. Save **Client ID** and **Client Secret**

### 2️⃣ Get Refresh Token (5 min)

```bash
# Install OAuth packages
composer require league/oauth2-client league/oauth2-google

# Edit get-gmail-token.php
# Replace YOUR_GOOGLE_CLIENT_ID and YOUR_GOOGLE_CLIENT_SECRET

# Run to get auth URL
php get-gmail-token.php

# Open URL → Login → Copy code

# Get tokens
php get-gmail-token.php?code=PASTE_CODE_HERE

# Save the Refresh Token!
```

### 3️⃣ Install Gmail Package (5 min)

```bash
# Install package
composer require dacastro4/laravel-gmail

# Publish config
php artisan vendor:publish --provider="Dacastro4\LaravelGmail\LaravelGmailServiceProvider"

# Update .env (copy from get-gmail-token.php output)
# MAIL_MAILER=gmail
# GOOGLE_MAIL_CLIENT_ID=...
# GOOGLE_MAIL_CLIENT_SECRET=...
# GOOGLE_MAIL_REFRESH_TOKEN=...
# GOOGLE_MAIL_FROM=your-email@gmail.com

# Clear config
php artisan config:clear

# Test
php artisan tinker
>>> Mail::mailer('gmail')->to('test@example.com')->send(new \App\Mail\SendUserEmail(...));
```

---

## 📋 .env Configuration

```env
# Choose Gmail OAuth2
MAIL_MAILER=gmail

# Gmail OAuth2 Credentials
GOOGLE_MAIL_CLIENT_ID=xxx.apps.googleusercontent.com
GOOGLE_MAIL_CLIENT_SECRET=xxx
GOOGLE_MAIL_REFRESH_TOKEN=xxx
GOOGLE_MAIL_FROM=your-email@gmail.com
GOOGLE_MAIL_FROM_NAME="Perfect Fit"

# Frontend URL (for email links)
FRONTEND_URL=http://localhost:3000

# Queue (for background email)
QUEUE_CONNECTION=redis
REDIS_PREFIX=perfect_fit_
```

---

## 🧪 Test Email

### Test 1: Tinker

```bash
php artisan tinker
```

```php
Mail::mailer('gmail')->to('your-email@gmail.com')->send(
    new \App\Mail\SendUserEmail([
        'name' => 'Test User',
        'email' => 'test@example.com',
        'token' => \Str::random(64),
    ])
);
```

### Test 2: Register API

```bash
curl -X POST http://localhost:8000/api/auth/register \
  -H "Content-Type: application/json" \
  -d '{
    "name": "Test User",
    "email": "your-email@gmail.com",
    "password": "password123"
  }'
```

Check inbox! 📧

---

## ✅ Checklist

- [ ] Google Cloud Project created
- [ ] Gmail API enabled  
- [ ] OAuth Client created
- [ ] Refresh token obtained
- [ ] Package installed: `dacastro4/laravel-gmail`
- [ ] `.env` updated with credentials
- [ ] Config cleared: `php artisan config:clear`
- [ ] Queue worker running: `php artisan queue:work`
- [ ] Test email sent successfully

---

## 🔄 Switch Between SMTP & Gmail

`.env` config cho cả 2:

```env
# Active mailer
MAIL_MAILER=gmail  # or 'smtp'

# SMTP Config (backup)
MAIL_HOST=smtp.gmail.com
MAIL_PORT=587
MAIL_USERNAME=backup@gmail.com
MAIL_PASSWORD=app_password

# Gmail OAuth2 (recommended)
GOOGLE_MAIL_CLIENT_ID=xxx
GOOGLE_MAIL_CLIENT_SECRET=xxx
GOOGLE_MAIL_REFRESH_TOKEN=xxx
GOOGLE_MAIL_FROM=your-email@gmail.com
```

Change `MAIL_MAILER` to switch!

---

## 📚 Full Documentation

👉 [`docs/GMAIL_OAUTH_SETUP.md`](docs/GMAIL_OAUTH_SETUP.md) - Complete guide
👉 [`get-gmail-token.php`](get-gmail-token.php) - Token generator script

---

**Quick & Easy! Start now:** 🚀

```bash
php get-gmail-token.php
```

