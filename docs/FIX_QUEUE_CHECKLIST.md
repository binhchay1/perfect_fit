# ⚡ Queue Not Processing - Fix Checklist

## 🔍 DIAGNOSIS RESULTS

```bash
Queue Connection: sync              # ❌ WRONG!
Redis Prefix: laravel_database_     # ❌ WRONG!
Redis Queue: default                # ⚠️ OK but not optimal
```

---

## ❌ ROOT CAUSE

### Issue 1: QUEUE_CONNECTION=sync

**Current:** Jobs run **SYNCHRONOUSLY** (blocking)
- API waits for email to send
- No queue involved
- SMTP timeout = API timeout

**Should be:** `QUEUE_CONNECTION=redis`
- API returns immediately
- Job queued to Redis
- Worker processes in background

### Issue 2: Config Not Updated

Redis prefix is still `laravel_database_` → `.env` chưa được load hoặc cache cũ

---

## ✅ FIX - 5 STEPS

### Step 1: Check .env File

Open `.env` và verify:

```env
# Should be:
QUEUE_CONNECTION=redis      # NOT 'sync'
CACHE_DRIVER=redis
SESSION_DRIVER=redis        # NOT 'SESSION_DRIVER-redis'

# Add these:
REDIS_PREFIX=perfect_fit_
REDIS_DB=0
REDIS_CACHE_DB=1
```

### Step 2: Clear All Caches

```bash
php artisan config:clear
php artisan cache:clear
php artisan route:clear
php artisan view:clear
```

### Step 3: Verify Config Loaded

```bash
php artisan tinker --execute="
echo 'Queue: ' . config('queue.default') . PHP_EOL;
echo 'Redis Prefix: ' . config('database.redis.options.prefix') . PHP_EOL;
"
```

**Should output:**
```
Queue: redis
Redis Prefix: perfect_fit_
```

### Step 4: Start Horizon

```bash
# Terminal 1: Laravel Server
php artisan serve

# Terminal 2: Horizon Worker
cd /home/perfect_fit
php artisan horizon
```

### Step 5: Test

```bash
# Test register
curl -X POST http://localhost:8000/api/auth/register \
  -H "Content-Type: application/json" \
  -d '{
    "name": "Test User",
    "email": "test@example.com",
    "password": "password123"
  }'

# Check Horizon dashboard
# http://localhost:8000/horizon
```

---

## 🔍 DEBUGGING

### Check if Horizon is Running for Perfect Fit

```bash
ps aux | grep "perfect_fit" | grep horizon
```

**Should see:**
```
php artisan horizon (in /home/perfect_fit)
php artisan horizon:supervisor ...
php artisan horizon:work redis --queue=emails ...
```

### Check Failed Jobs

```bash
# List failed jobs
php artisan queue:failed

# Retry all failed
php artisan queue:retry all
```

### Check Redis Queue

```bash
redis-cli

# Check queue length
> LLEN perfect_fit_queue:emails
> LLEN perfect_fit_queue:default

# List all perfect_fit keys
> KEYS perfect_fit_*

# Monitor real-time
> MONITOR
```

### Check Logs

```bash
# Watch all logs
tail -f storage/logs/laravel.log

# Filter emails only
tail -f storage/logs/laravel.log | grep -i email

# Check Horizon logs
tail -f storage/logs/horizon.log
```

---

## 📊 VERIFY WORKING

### Signs Everything is OK:

1. **Config:**
   ```bash
   php artisan tinker
   >>> config('queue.default')  // 'redis'
   >>> config('database.redis.options.prefix')  // 'perfect_fit_'
   ```

2. **Horizon Running:**
   ```bash
   ps aux | grep perfect_fit | grep horizon
   # Should show processes
   ```

3. **Dashboard:**
   - Go to: `http://localhost:8000/horizon`
   - See supervisors running
   - See jobs processing

4. **Logs:**
   ```
   [timestamp] INFO: Email verification job queued
   [timestamp] INFO: Processing job on queue: emails
   [timestamp] INFO: Verification email sent successfully
   ```

5. **Email Inbox:**
   - Receive email with beautiful template!

---

## 🚨 COMMON MISTAKES

### Mistake 1: .env Not Updated

```env
# WRONG:
QUEUE_CONNECTION=sync

# RIGHT:
QUEUE_CONNECTION=redis
```

### Mistake 2: Config Cache

After updating `.env`, **MUST** run:
```bash
php artisan config:clear
```

### Mistake 3: Wrong Horizon Instance

Running Horizon from `/home/cms` instead of `/home/perfect_fit`

**Fix:** 
```bash
cd /home/perfect_fit  # ← IMPORTANT!
php artisan horizon
```

### Mistake 4: Typo in .env

```env
# WRONG:
SESSION_DRIVER-redis  # ← Missing '='

# RIGHT:
SESSION_DRIVER=redis
```

---

## ✅ COMPLETE .env TEMPLATE

Copy này vào `.env`:

```env
# ================== APPLICATION ==================
APP_NAME="Perfect Fit"
APP_ENV=production  # or 'local'

# ================== QUEUE & CACHE ==================
QUEUE_CONNECTION=redis
CACHE_DRIVER=redis
SESSION_DRIVER=redis
BROADCAST_DRIVER=log

# ================== REDIS ==================
REDIS_HOST=127.0.0.1
REDIS_PASSWORD=null
REDIS_PORT=6379
REDIS_PREFIX=perfect_fit_
REDIS_DB=0
REDIS_CACHE_DB=1

# ================== MAIL (Choose one) ==================

# Option 1: Gmail OAuth2 (Recommended)
MAIL_MAILER=gmail
GOOGLE_MAIL_CLIENT_ID=1081487120116-lh5ddb8nh536268pdcs7tpjvoadcdfju.apps.googleusercontent.com
GOOGLE_MAIL_CLIENT_SECRET=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
GOOGLE_MAIL_REFRESH_TOKEN=1//0eqq_14_A5kP8CgYIARAAGA4SNwF-L9Ir5PHz0gBKRsWoHuifeORFcDA8CkDOyS2NaFoJ6RxFrM-7TOO9-NPGNr6DL31S_lyEbXc
GOOGLE_MAIL_FROM=binhchay1@gmail.com

# Option 2: SMTP (Backup)
# MAIL_MAILER=smtp
MAIL_HOST=smtp.gmail.com
MAIL_PORT=465
MAIL_USERNAME=binhchay1@gmail.com
MAIL_PASSWORD=exmwzapefvomutdl
MAIL_ENCRYPTION=ssl

# Common
MAIL_FROM_ADDRESS="perfect_fit@example.com"
MAIL_FROM_NAME="${APP_NAME}"
FRONTEND_URL=http://localhost:3000
```

---

## 🎯 QUICK FIX NOW

```bash
# 1. Update .env với config trên
# 2. Clear cache
php artisan config:clear

# 3. Verify config
php artisan tinker --execute="echo config('queue.default');"
# Should output: redis

# 4. Start Horizon
php artisan horizon

# 5. Test register API
# 6. Check: http://localhost:8000/horizon
```

---

## 📞 NEED HELP?

If still not working:

1. Share output of:
   ```bash
   php artisan tinker --execute="echo config('queue.default');"
   cat .env | grep QUEUE_CONNECTION
   ```

2. Check Horizon dashboard: `http://localhost:8000/horizon`

3. Share logs:
   ```bash
   tail -20 storage/logs/laravel.log
   ```

---

**Fix .env first, then start Horizon!** 🚀

