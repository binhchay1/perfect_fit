# 📧 .env Migration Guide - SMTP to Gmail OAuth2

## 🔄 Bạn đang có (SMTP):

```env
MAIL_MAILER=smtp
MAIL_HOST=smtp.gmail.com
MAIL_PORT=465
MAIL_USERNAME=binhchay1@gmail.com
MAIL_PASSWORD=exmwzapefvomutdl
MAIL_ENCRYPTION=ssl
MAIL_FROM_ADDRESS="perfect_fit@example.com"
MAIL_FROM_NAME="${APP_NAME}"
```

---

## ✅ OPTION 1: Thay thế hoàn toàn (Clean)

**Xóa config SMTP cũ, thay bằng:**

```env
# Gmail OAuth2 Configuration
MAIL_MAILER=gmail

# Common settings (keep)
MAIL_FROM_ADDRESS="perfect_fit@example.com"
MAIL_FROM_NAME="${APP_NAME}"
FRONTEND_URL=http://localhost:3000
```

---

## ✅ OPTION 2: Giữ cả 2 (Recommended - Best Practice!)

**Giữ cả SMTP (backup) và Gmail OAuth2:**

```env
# ================== ACTIVE MAILER ==================
MAIL_MAILER=gmail  # ← Change to 'smtp' if OAuth2 fails

# ================== GMAIL OAUTH2 (PRIMARY) ==================
GOOGLE_MAIL_CLIENT_ID=
GOOGLE_MAIL_CLIENT_SECRET=
GOOGLE_MAIL_REFRESH_TOKEN=
GOOGLE_MAIL_FROM=binhchay1@gmail.com
GOOGLE_MAIL_FROM_NAME="Perfect Fit"

# ================== SMTP BACKUP (FALLBACK) ==================
MAIL_HOST=smtp.gmail.com
MAIL_PORT=465
MAIL_USERNAME=binhchay1@gmail.com
MAIL_PASSWORD=exmwzapefvomutdl
MAIL_ENCRYPTION=ssl

# ================== COMMON SETTINGS ==================
MAIL_FROM_ADDRESS="perfect_fit@example.com"
MAIL_FROM_NAME="${APP_NAME}"
FRONTEND_URL=http://localhost:3000
```

**Benefit:** Nếu OAuth2 có vấn đề, chỉ cần đổi `MAIL_MAILER=smtp` → instant fallback!

---

## 📋 Step-by-Step Migration

### 1. Backup current .env
```bash
cp .env .env.backup.smtp
```

### 2. Update .env
Choose Option 1 or 2 above, update your `.env`

### 3. Install Gmail package
```bash
composer require dacastro4/laravel-gmail
```

### 4. Publish config
```bash
php artisan vendor:publish --provider="Dacastro4\LaravelGmail\LaravelGmailServiceProvider"
```

### 5. Clear cache
```bash
php artisan config:clear
php artisan cache:clear
```

### 6. Test
```bash
php artisan tinker
```

```php
Mail::mailer('gmail')->to('binhchay1@gmail.com')->send(
    new \App\Mail\SendUserEmail([
        'name' => 'Test User',
        'email' => 'test@example.com',
        'token' => \Str::random(64),
    ])
);
```

---

## 🔄 Switch Between Mailers

### Use Gmail OAuth2:
```env
MAIL_MAILER=gmail
```

### Fallback to SMTP:
```env
MAIL_MAILER=smtp
```

**That's it!** No code changes needed.

---

## ⚡ Complete .env Template

```env
# ================================================================
# PERFECT FIT - MAIL CONFIGURATION
# ================================================================

APP_NAME="Perfect Fit"
APP_ENV=local

# ================== MAIL MAILER ==================
MAIL_MAILER=gmail  # Options: gmail, smtp


# ================== SMTP BACKUP ==================
MAIL_HOST=smtp.gmail.com
MAIL_PORT=465
MAIL_USERNAME=binhchay1@gmail.com
MAIL_PASSWORD=exmwzapefvomutdl
MAIL_ENCRYPTION=ssl

# ================== COMMON SETTINGS ==================
MAIL_FROM_ADDRESS="perfect_fit@example.com"
MAIL_FROM_NAME="${APP_NAME}"
FRONTEND_URL=http://localhost:3000

# ================== QUEUE ==================
QUEUE_CONNECTION=redis

# ================== REDIS ==================
REDIS_HOST=127.0.0.1
REDIS_PASSWORD=null
REDIS_PORT=6379
REDIS_PREFIX=perfect_fit_
REDIS_DB=0
REDIS_CACHE_DB=1
REDIS_QUEUE=perfect_fit_queue
SESSION_DRIVER=redis
CACHE_DRIVER=redis

# ================== DATABASE ==================
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=perfect_fit
DB_USERNAME=root
DB_PASSWORD=
```

---

## ✅ Verification Checklist

- [ ] Backup `.env` to `.env.backup.smtp`
- [ ] Update `.env` with Gmail OAuth2 config
- [ ] Update `GOOGLE_MAIL_FROM=binhchay1@gmail.com`
- [ ] Run: `composer require dacastro4/laravel-gmail`
- [ ] Run: `php artisan vendor:publish`
- [ ] Run: `php artisan config:clear`
- [ ] Test: Send email via tinker
- [ ] Test: Register API
- [ ] Verify: Email received in inbox
- [ ] Keep SMTP config as backup (Option 2)

---

## 🎯 TL;DR - Quick Commands

```bash
# 1. Install package
composer require dacastro4/laravel-gmail

# 2. Publish config
php artisan vendor:publish --provider="Dacastro4\LaravelGmail\LaravelGmailServiceProvider"

# 3. Update .env (copy from gmail-oauth-config.txt)
# MAIL_MAILER=gmail
# GOOGLE_MAIL_CLIENT_ID=...
# GOOGLE_MAIL_CLIENT_SECRET=...
# GOOGLE_MAIL_REFRESH_TOKEN=...
# GOOGLE_MAIL_FROM=binhchay1@gmail.com

# 4. Clear cache
php artisan config:clear

# 5. Test
php artisan tinker
>>> Mail::mailer('gmail')->to('binhchay1@gmail.com')->send(...);
```

---

**Done!** 🎉

