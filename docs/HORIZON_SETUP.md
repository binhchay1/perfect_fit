# 🔴 Horizon Setup - Perfect Fit

## ❌ VẤN ĐỀ BẠN GẶP

```
[2025-10-03 17:41:43] production.INFO: Email verification job queued
```

Job được queued nhưng **KHÔNG ĐƯỢC XỬ LÝ**!

**Nguyên nhân:**
- ✅ Horizon đang chạy nhưng của **PROJECT KHÁC** (`/home/cms`)
- ❌ Perfect Fit project (`/home/perfect_fit`) **CHƯA CÓ WORKER**
- ❌ Job vào queue nhưng không có worker lắng nghe

---

## ✅ ĐÃ FIX

### 1. SendEmail Job - Dedicated Queue

**Updated:** `app/Jobs/SendEmail.php`

```php
class SendEmail implements ShouldQueue
{
    public $queue = 'emails';  // ← Dedicated queue
    public $tries = 3;          // ← Retry 3 lần
    public $backoff = [60, 120, 300]; // ← Backoff strategy
    public $timeout = 120;      // ← 2 phút timeout
    
    // ... rest of code
}
```

**Benefits:**
- ✅ Dedicated queue riêng cho emails
- ✅ Retry logic (3 attempts)
- ✅ Smart backoff (1min → 2min → 5min)
- ✅ 2 minutes timeout

### 2. Horizon Config - Email Supervisor

**Updated:** `config/horizon.php`

Added `supervisor-emails` cho production và local:

```php
'supervisor-emails' => [
    'connection' => 'redis',
    'queue' => ['emails'],       // ← Listen to 'emails' queue
    'balance' => 'auto',
    'maxProcesses' => 5,         // ← 5 workers max
    'minProcesses' => 1,
    'tries' => 3,
    'timeout' => 120,
    'memory' => 256,
],
```

---

## 🚀 CÁCH SỬ DỤNG

### Option 1: Start Horizon (Recommended)

```bash
# Terminal 1: Laravel
php artisan serve

# Terminal 2: Horizon
cd /home/perfect_fit
php artisan horizon

# Or with output
php artisan horizon --verbose
```

**Horizon Dashboard:** `http://localhost:8000/horizon`

### Option 2: Simple Queue Worker

Nếu không muốn dùng Horizon:

```bash
php artisan queue:work redis --queue=emails,default --tries=3 --timeout=120
```

---

## 🔍 VERIFY HORIZON RUNNING

### Check Processes

```bash
ps aux | grep "perfect_fit" | grep horizon
```

Should see:
```
php artisan horizon
php artisan horizon:supervisor ...
php artisan horizon:work redis --queue=emails ...
```

### Check Horizon Dashboard

1. Open: `http://localhost:8000/horizon`
2. Should see:
   - `supervisor-1` processing `default` queue
   - `supervisor-emails` processing `emails` queue

### Check Redis Keys

```bash
redis-cli
127.0.0.1:6379> KEYS perfect_fit_queue:emails*
```

Should see queued jobs!

---

## 📊 MONITOR

### Horizon Dashboard Features:

- **Dashboard:** Overview của tất cả supervisors
- **Metrics:** Throughput, runtime, failed jobs
- **Recent Jobs:** Job history
- **Failed Jobs:** Failed jobs với retry option
- **Monitoring:** Real-time job processing

### CLI Monitoring

```bash
# Watch Horizon log
tail -f storage/logs/laravel.log | grep -i email

# Watch queue size
redis-cli
127.0.0.1:6379> LLEN perfect_fit_queue:emails
```

---

## 🧪 TEST EMAIL QUEUE

### Test 1: Register API

```bash
curl -X POST http://localhost:8000/api/auth/register \
  -H "Content-Type: application/json" \
  -d '{
    "name": "Test User",
    "email": "test@example.com",
    "password": "password123"
  }'
```

### Test 2: Check Horizon Dashboard

1. Go to: `http://localhost:8000/horizon/dashboard`
2. See job in **Recent Jobs**
3. Check **Metrics** for throughput

### Test 3: Check Logs

```bash
# Should see:
tail -f storage/logs/laravel.log

# [timestamp] INFO: Email verification job queued {"email":"test@example.com"}
# [timestamp] INFO: Processing job on queue: emails
# [timestamp] INFO: Verification email sent successfully {"email":"test@example.com"}
```

---

## 🔄 RESTART HORIZON

Sau khi update code, **PHẢI RESTART** Horizon:

```bash
# Graceful termination
php artisan horizon:terminate

# Horizon sẽ tự restart nếu chạy với supervisor
# Hoặc start lại manual:
php artisan horizon
```

**IMPORTANT:** Mỗi lần sửa Job class hoặc config, phải restart Horizon!

---

## 🐛 TROUBLESHOOTING

### Issue 1: Jobs Not Processing

**Check:**
```bash
# Is Horizon running?
ps aux | grep horizon | grep perfect_fit

# Is queue worker running?
ps aux | grep "queue=emails"
```

**Fix:**
```bash
cd /home/perfect_fit
php artisan horizon
```

### Issue 2: Failed Jobs

**Check failed jobs:**
```bash
php artisan queue:failed
```

**Retry all:**
```bash
php artisan queue:retry all
```

**Retry specific:**
```bash
php artisan queue:retry JOB_ID
```

### Issue 3: Jobs Stuck

**Clear queue:**
```bash
redis-cli
127.0.0.1:6379> DEL perfect_fit_queue:emails
```

**Restart Horizon:**
```bash
php artisan horizon:terminate
php artisan horizon
```

### Issue 4: Wrong Queue

Job vào `default` queue thay vì `emails`:

**Fix:** Clear config cache
```bash
php artisan config:clear
php artisan horizon:terminate
php artisan horizon
```

---

## 📋 PRODUCTION SETUP

### 1. Supervisor Configuration

Create `/etc/supervisor/conf.d/perfect-fit-horizon.conf`:

```ini
[program:perfect-fit-horizon]
process_name=%(program_name)s
command=php /home/perfect_fit/artisan horizon
autostart=true
autorestart=true
user=www-data
redirect_stderr=true
stdout_logfile=/home/perfect_fit/storage/logs/horizon.log
stopwaitsecs=3600
```

**Start:**
```bash
sudo supervisorctl reread
sudo supervisorctl update
sudo supervisorctl start perfect-fit-horizon:*
```

### 2. Deployment Script

Add to your deployment:

```bash
php artisan horizon:terminate
# Deploy code...
php artisan migrate --force
php artisan config:cache
php artisan route:cache
# Supervisor will auto-restart Horizon
```

---

## 🎯 QUEUE ARCHITECTURE

### Perfect Fit Queues:

```
Redis Connection: perfect_fit_
├── default queue          → General jobs
└── emails queue          → Email jobs (SendEmail)
    ├── supervisor-emails  → Dedicated supervisor
    ├── 2-5 workers       → Auto-scaling
    ├── 3 retries         → Failure tolerance
    └── 2min timeout      → Long email sends
```

### Benefits:

- ✅ **Isolated:** Email jobs không affect other jobs
- ✅ **Scalable:** Auto-scaling workers (1-5)
- ✅ **Resilient:** 3 retries với smart backoff
- ✅ **Monitored:** Dedicated metrics trong Horizon
- ✅ **Fast:** Dedicated workers = faster processing

---

## 📝 SUMMARY

### What Changed:

1. **SendEmail Job:**
   - ✅ Added `$queue = 'emails'`
   - ✅ Added retry logic (3 attempts)
   - ✅ Added backoff strategy
   - ✅ Added 2min timeout

2. **Horizon Config:**
   - ✅ Added `supervisor-emails` for production
   - ✅ Added `supervisor-emails` for local
   - ✅ Dedicated workers cho email queue

3. **Next Steps:**
   - ✅ Start Horizon: `php artisan horizon`
   - ✅ Test: Register API
   - ✅ Monitor: Horizon dashboard
   - ✅ Production: Setup Supervisor

---

## ✅ CHECKLIST

- [ ] Update code (already done!)
- [ ] Clear config: `php artisan config:clear`
- [ ] Start Horizon: `php artisan horizon`
- [ ] Check dashboard: `http://localhost:8000/horizon`
- [ ] Test register API
- [ ] Verify email sent
- [ ] Check logs: `tail -f storage/logs/laravel.log`
- [ ] Production: Setup Supervisor

---

**Start Horizon now:**

```bash
cd /home/perfect_fit
php artisan horizon --verbose
```

**Then test register API!** 🚀

