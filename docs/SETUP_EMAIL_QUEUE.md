# 📧 Email & Queue Setup Guide

## 🔥 Fix Email Connection Error

Bạn đang gặp lỗi:
```
Connection could not be established with host "smtp.gmail.com:587": 
stream_socket_client(): Unable to connect to smtp.gmail.com:587 
(Connection timed out)
```

---

## ✅ SOLUTION: Setup Queue + Fix Gmail

### Step 1: Update `.env` - **QUAN TRỌNG!**

```env
# Email Configuration (ĐÚNG RỒI!)
MAIL_MAILER=smtp
MAIL_HOST=smtp.gmail.com
MAIL_PORT=587
MAIL_USERNAME=binhchay1@gmail.com
MAIL_PASSWORD=exmwzapefvomutdl  # Gmail App Password
MAIL_ENCRYPTION=tls
MAIL_FROM_ADDRESS="perfect_fit@example.com"
MAIL_FROM_NAME="${APP_NAME}"

# Queue Configuration - PHẢI ĐỔI THÀNH DATABASE!
QUEUE_CONNECTION=database  # ← QUAN TRỌNG! Đổi từ 'sync' thành 'database'
```

### Step 2: Run Migration (Nếu chưa có)

```bash
# Check xem có migration jobs table chưa
php artisan migrate:status

# Nếu chưa có, tạo migration
php artisan queue:table
php artisan queue:failed-table

# Run migration
php artisan migrate
```

### Step 3: Start Queue Worker

**Terminal 1 - Laravel Server:**
```bash
php artisan serve
```

**Terminal 2 - Queue Worker:**
```bash
# Development
php artisan queue:work --verbose

# Hoặc với retry
php artisan queue:work --tries=3 --timeout=90
```

---

## 🔍 Fix Gmail Connection Issues

### Option 1: Check Gmail App Password

1. Gmail App Password phải là **16 ký tự không có dấu cách**
2. Format: `exmwzapefvomutdl` ✅ (ĐÚNG)

**Cách tạo App Password:**
1. Google Account → Security → 2-Step Verification → App passwords
2. Select "Mail" → Generate
3. Copy password vào `MAIL_PASSWORD`

### Option 2: Check Network/Firewall

```bash
# Test connection to Gmail SMTP
telnet smtp.gmail.com 587

# Hoặc
nc -zv smtp.gmail.com 587

# Hoặc
curl -v telnet://smtp.gmail.com:587
```

**Nếu không connect được:**
- Check firewall
- Try port 465 với `MAIL_ENCRYPTION=ssl`
- Contact ISP/network admin

### Option 3: Alternative - Use Port 465 (SSL)

```env
MAIL_MAILER=smtp
MAIL_HOST=smtp.gmail.com
MAIL_PORT=465  # ← Đổi thành 465
MAIL_USERNAME=binhchay1@gmail.com
MAIL_PASSWORD=exmwzapefvomutdl
MAIL_ENCRYPTION=ssl  # ← Đổi thành ssl
MAIL_FROM_ADDRESS="perfect_fit@example.com"
MAIL_FROM_NAME="${APP_NAME}"
```

---

## 🧪 Test Email

### Test 1: Check Queue

```bash
# Check jobs table
php artisan tinker
>>> \DB::table('jobs')->count()

# Check failed jobs
>>> \DB::table('failed_jobs')->count()
```

### Test 2: Manual Email Test

```bash
php artisan tinker
```

```php
// Test email send
Mail::raw('Test email', function($message) {
    $message->to('binhchay1@gmail.com')
            ->subject('Test from Perfect Fit');
});

// Check if queued
\DB::table('jobs')->latest()->first();
```

### Test 3: Register API

```bash
curl -X POST http://localhost:8000/api/auth/register \
  -H "Content-Type: application/json" \
  -d '{
    "name": "Test User",
    "email": "test@example.com",
    "password": "password123"
  }'
```

**Expected Response:**
```json
{
  "success": true,
  "message": "User created successfully. Verification email sent.",
  "data": {
    "user": {
      "id": 1,
      "name": "Test User",
      "email": "test@example.com",
      "role": "user",
      "status": 0
    }
  }
}
```

---

## 🔧 Code Changes Made

### AuthController.php - Line 269-280

**Before:**
```php
SendEmail::dispatch($userMail, $dataMail);
return $this->successResponse([...]);
```

**After:**
```php
// Queue email job - trả về response ngay, email gửi background
try {
    SendEmail::dispatch($userMail, $dataMail);
    Log::info('Email verification job queued', ['email' => $userMail]);
} catch (\Exception $emailException) {
    // Log error nhưng vẫn trả về success vì user đã được tạo
    Log::error('Failed to queue verification email', [
        'email' => $userMail,
        'error' => $emailException->getMessage()
    ]);
}

return $this->successResponse([...]);
```

**Benefits:**
- ✅ API trả về response **NGAY LẬP TỨC**
- ✅ Email gửi **BACKGROUND** qua queue
- ✅ Nếu email fail, user vẫn được tạo
- ✅ Error được log để debug

---

## 📊 Queue Monitor

### Check Queue Status

```bash
# List all jobs
php artisan queue:work --once --verbose

# Monitor queue realtime
php artisan queue:listen

# Check failed jobs
php artisan queue:failed

# Retry failed jobs
php artisan queue:retry all
```

### Queue Dashboard (Optional)

Install Laravel Horizon cho queue monitoring UI:

```bash
composer require laravel/horizon
php artisan horizon:install
php artisan migrate
php artisan horizon
```

Access: `http://localhost:8000/horizon`

---

## 🚨 Troubleshooting

### Issue 1: Queue Not Processing

```bash
# Check queue connection
php artisan tinker
>>> config('queue.default')  # Should be 'database'

# Clear config cache
php artisan config:clear

# Restart queue worker
php artisan queue:restart
```

### Issue 2: Email Still Failing

```bash
# Check logs
tail -f storage/logs/laravel.log

# Check failed jobs
php artisan queue:failed

# Get details of failed job
php artisan queue:failed | grep SendEmail
```

### Issue 3: Connection Timeout

**Try different ports:**

```env
# Option 1: Port 587 with TLS (current)
MAIL_PORT=587
MAIL_ENCRYPTION=tls

# Option 2: Port 465 with SSL
MAIL_PORT=465
MAIL_ENCRYPTION=ssl

# Option 3: Port 25 (if allowed)
MAIL_PORT=25
MAIL_ENCRYPTION=null
```

---

## 🎯 Production Setup

### Using Supervisor (Recommended)

1. Install Supervisor:
```bash
sudo apt-get install supervisor
```

2. Create config:
```bash
sudo nano /etc/supervisor/conf.d/laravel-worker.conf
```

```ini
[program:laravel-worker]
process_name=%(program_name)s_%(process_num)02d
command=php /home/perfect_fit/artisan queue:work --sleep=3 --tries=3 --max-time=3600
autostart=true
autorestart=true
stopasgroup=true
killasgroup=true
user=www-data
numprocs=2
redirect_stderr=true
stdout_logfile=/home/perfect_fit/storage/logs/worker.log
stopwaitsecs=3600
```

3. Start:
```bash
sudo supervisorctl reread
sudo supervisorctl update
sudo supervisorctl start laravel-worker:*
```

---

## ✅ Final Checklist

- [ ] `.env` có `QUEUE_CONNECTION=database`
- [ ] Gmail App Password đúng 16 ký tự
- [ ] `php artisan migrate` đã chạy (jobs table created)
- [ ] `php artisan queue:work` đang chạy
- [ ] Test connection: `telnet smtp.gmail.com 587`
- [ ] Clear cache: `php artisan config:clear`
- [ ] Test register API
- [ ] Check logs: `storage/logs/laravel.log`
- [ ] Production: Setup Supervisor

---

## 📝 Summary

### Vấn đề ban đầu:
❌ Email config OK nhưng connection timeout
❌ Queue connection = `sync` → Email gửi synchronously
❌ API chờ email send xong mới trả về response

### Solution:
✅ Đổi `QUEUE_CONNECTION=database`
✅ Start queue worker
✅ API trả về response ngay
✅ Email gửi background
✅ Add error handling để user vẫn được tạo nếu email fail

### Result:
🚀 **Fast API response** (không chờ email)
📧 **Email gửi background** (qua queue)
🛡️ **Error resilient** (user created dù email fail)
📊 **Trackable** (jobs table + logs)

---

**Giờ test lại register API nhé bro!** 🎉

