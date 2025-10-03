# Environment Variables Reference

Quick reference cho tất cả các biến môi trường cần thiết cho authentication system.

## 📧 Email Configuration

```env
# Email Service
MAIL_MAILER=smtp
MAIL_HOST=smtp.gmail.com
MAIL_PORT=587
MAIL_USERNAME=your-email@gmail.com
MAIL_PASSWORD=your-app-password
MAIL_ENCRYPTION=tls
MAIL_FROM_ADDRESS=noreply@perfectfit.com
MAIL_FROM_NAME="Perfect Fit"

# Frontend URL (for email links)
FRONTEND_URL=http://localhost:3000
```

### Gmail Setup
1. Enable 2-Step Verification
2. Generate App Password tại [Google Account](https://myaccount.google.com/security)
3. Use App Password làm `MAIL_PASSWORD`

---

## 🔐 Google OAuth

```env
GOOGLE_CLIENT_ID=xxxxx.apps.googleusercontent.com
GOOGLE_CLIENT_SECRET=GOCSPX-xxxxx
GOOGLE_REDIRECT_URL=http://localhost:3000/auth/google/callback
```

**Where to get:**
- [Google Cloud Console](https://console.cloud.google.com)
- Create OAuth 2.0 credentials
- Enable Google+ API

---

## 📘 Facebook OAuth

```env
FACEBOOK_CLIENT_ID=your-app-id
FACEBOOK_CLIENT_SECRET=your-app-secret
FACEBOOK_REDIRECT_URL=http://localhost:3000/auth/facebook/callback
```

**Where to get:**
- [Facebook Developers](https://developers.facebook.com)
- Create app → Settings → Basic
- Copy App ID and App Secret

---

## 🎵 TikTok OAuth

```env
TIKTOK_CLIENT_KEY=your-client-key
TIKTOK_CLIENT_SECRET=your-client-secret
TIKTOK_REDIRECT_URL=http://localhost:3000/auth/tiktok/callback
```

**Where to get:**
- [TikTok Developers](https://developers.tiktok.com)
- Create app → Apply for Login Kit
- Get Client Key and Secret

---

## 📱 SMS Configuration

### Provider Selection

```env
# Options: log, twilio, firebase, esms, speedsms
SMS_PROVIDER=log
```

### Development Mode (Log)

```env
SMS_PROVIDER=log
# OTP will be logged to storage/logs/laravel.log
```

### Twilio (International - Free Trial)

```env
SMS_PROVIDER=twilio
TWILIO_SID=ACxxxxxxxxxxxxxxxxxxxxxxxxxxxxx
TWILIO_TOKEN=your_auth_token
TWILIO_FROM=+1234567890
```

**Setup:**
- [Twilio Console](https://www.twilio.com/console)
- Get $15 free credit
- Account SID + Auth Token + Phone Number

### Firebase Phone Auth (Free)

```env
SMS_PROVIDER=firebase
# Note: Requires client-side integration
```

**Setup:**
- [Firebase Console](https://console.firebase.google.com)
- Enable Authentication → Phone
- Client-side only, backend just validates

### eSMS Vietnam (Paid)

```env
SMS_PROVIDER=esms
ESMS_API_KEY=your_api_key
ESMS_SECRET_KEY=your_secret_key
ESMS_BRANDNAME=PerfectFit
```

**Setup:**
- [eSMS.vn](https://esms.vn)
- Dashboard → API Key
- ~600-800 VNĐ/SMS

### SpeedSMS Vietnam (Paid)

```env
SMS_PROVIDER=speedsms
SPEEDSMS_ACCESS_TOKEN=your_access_token
SPEEDSMS_SENDER=PerfectFit
```

**Setup:**
- [SpeedSMS.vn](https://speedsms.vn)
- Dashboard → Access Token
- ~300-500 VNĐ/SMS

---

## ⚙️ Laravel Passport

```env
PASSPORT_PERSONAL_ACCESS_CLIENT_ID=
PASSPORT_PERSONAL_ACCESS_CLIENT_SECRET=
```

**Setup:**
```bash
php artisan passport:install
# Copy Client ID and Secret to .env
```

---

## 🗄️ Database

```env
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=perfect_fit
DB_USERNAME=root
DB_PASSWORD=
```

---

## 🚀 Queue & Cache

```env
# For email queue jobs
QUEUE_CONNECTION=database

# For rate limiting
CACHE_DRIVER=redis
REDIS_HOST=127.0.0.1
REDIS_PASSWORD=null
REDIS_PORT=6379
```

**Setup Queue:**
```bash
php artisan queue:table
php artisan migrate
php artisan queue:work
```

---

## 📊 Complete .env Template

```env
# App
APP_NAME=PerfectFit
APP_ENV=production
APP_KEY=base64:xxx
APP_DEBUG=false
APP_URL=https://perfectfit.com

# Database
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=perfect_fit
DB_USERNAME=root
DB_PASSWORD=

# Email
MAIL_MAILER=smtp
MAIL_HOST=smtp.gmail.com
MAIL_PORT=587
MAIL_USERNAME=your-email@gmail.com
MAIL_PASSWORD=your-app-password
MAIL_ENCRYPTION=tls
MAIL_FROM_ADDRESS=noreply@perfectfit.com
MAIL_FROM_NAME="${APP_NAME}"

# Frontend
FRONTEND_URL=https://perfectfit.com

# Laravel Passport
PASSPORT_PERSONAL_ACCESS_CLIENT_ID=1
PASSPORT_PERSONAL_ACCESS_CLIENT_SECRET=xxx

# Google OAuth
GOOGLE_CLIENT_ID=xxx.apps.googleusercontent.com
GOOGLE_CLIENT_SECRET=GOCSPX-xxx
GOOGLE_REDIRECT_URL=https://perfectfit.com/auth/google/callback

# Facebook OAuth
FACEBOOK_CLIENT_ID=xxx
FACEBOOK_CLIENT_SECRET=xxx
FACEBOOK_REDIRECT_URL=https://perfectfit.com/auth/facebook/callback

# TikTok OAuth
TIKTOK_CLIENT_KEY=xxx
TIKTOK_CLIENT_SECRET=xxx
TIKTOK_REDIRECT_URL=https://perfectfit.com/auth/tiktok/callback

# SMS Provider
SMS_PROVIDER=twilio

# Twilio
TWILIO_SID=ACxxx
TWILIO_TOKEN=xxx
TWILIO_FROM=+1234567890

# Or eSMS Vietnam
# SMS_PROVIDER=esms
# ESMS_API_KEY=xxx
# ESMS_SECRET_KEY=xxx
# ESMS_BRANDNAME=PerfectFit

# Or SpeedSMS Vietnam
# SMS_PROVIDER=speedsms
# SPEEDSMS_ACCESS_TOKEN=xxx
# SPEEDSMS_SENDER=PerfectFit

# Queue & Cache
QUEUE_CONNECTION=database
CACHE_DRIVER=redis
REDIS_HOST=127.0.0.1
REDIS_PASSWORD=null
REDIS_PORT=6379
```

---

## 🔍 Environment-Specific Settings

### Development

```env
APP_ENV=local
APP_DEBUG=true
SMS_PROVIDER=log
QUEUE_CONNECTION=sync
CACHE_DRIVER=file
```

### Staging

```env
APP_ENV=staging
APP_DEBUG=true
SMS_PROVIDER=twilio
QUEUE_CONNECTION=database
CACHE_DRIVER=redis
```

### Production

```env
APP_ENV=production
APP_DEBUG=false
SMS_PROVIDER=esms
QUEUE_CONNECTION=database
CACHE_DRIVER=redis
```

---

## ✅ Validation Checklist

### Before Going Live:

- [ ] All OAuth credentials configured
- [ ] Email service working (not Gmail in production)
- [ ] SMS provider selected and configured
- [ ] Queue worker running
- [ ] Redis cache configured
- [ ] Passport installed
- [ ] HTTPS enabled
- [ ] Frontend URL correct
- [ ] All secrets are secure
- [ ] .env not committed to git

---

## 🔐 Security Notes

1. **Never commit .env to Git**
   ```bash
   # Already in .gitignore
   .env
   .env.backup
   ```

2. **Use strong secrets**
   ```bash
   php artisan key:generate
   ```

3. **Rotate credentials regularly**
   - OAuth secrets
   - API keys
   - Database passwords

4. **Use environment-specific values**
   - Different OAuth apps for dev/staging/prod
   - Different SMS accounts
   - Different database credentials

---

## 🆘 Quick Troubleshooting

### Can't send email?
```bash
# Test SMTP connection
php artisan tinker
>>> Mail::raw('Test', fn($msg) => $msg->to('test@example.com')->subject('Test'));
```

### Can't send SMS?
```bash
# Check provider config
php artisan tinker
>>> config('services.sms.provider')
>>> config('services.twilio.sid')
```

### OAuth not working?
```bash
# Verify credentials
php artisan tinker
>>> config('services.google.client_id')
>>> config('services.facebook.client_id')
```

### Queue not processing?
```bash
# Start queue worker
php artisan queue:work --tries=3

# Check failed jobs
php artisan queue:failed
```

---

## 📚 Related Documentation

- [Authentication Guide](./authentication.md)
- [Setup Guide](./SETUP_AUTHENTICATION.md)
- [README](./AUTHENTICATION_README.md)

---

*Quick Reference Card - Perfect Fit Authentication*

