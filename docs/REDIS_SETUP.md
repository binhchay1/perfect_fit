# 🔴 Redis Configuration - Perfect Fit

## ❗ LỖI TRONG CONFIG CỦA BẠN

```env
SESSION_DRIVER-redis  # ← SAI! Thiếu dấu =
```

**Phải sửa thành:**
```env
SESSION_DRIVER=redis  # ← ĐÚNG!
```

---

## ✅ CONFIG CHUẨN VỚI PREFIX

### `.env` - Updated Version

```env
# Application
APP_NAME="Perfect Fit"

# Broadcast, Cache, Queue, Session
BROADCAST_DRIVER=log
CACHE_DRIVER=redis
FILESYSTEM_DISK=local
QUEUE_CONNECTION=redis
SESSION_DRIVER=redis  # ← FIX: Đổi từ SESSION_DRIVER-redis
SESSION_LIFETIME=120

# Memcached (không dùng nếu dùng Redis)
MEMCACHED_HOST=127.0.0.1

# Redis Configuration
REDIS_HOST=127.0.0.1
REDIS_PASSWORD=null
REDIS_PORT=6379

# Redis Prefix - QUAN TRỌNG để tránh conflict!
REDIS_PREFIX=perfect_fit_  # ← THÊM DÒNG NÀY!

# Redis Databases (tách biệt cho từng mục đích)
REDIS_DB=0              # Default connection
REDIS_CACHE_DB=1        # Cache
REDIS_QUEUE=perfect_fit_queue  # Queue name
```

---

## 🎯 TẠI SAO CẦN PREFIX?

### Vấn đề khi KHÔNG có prefix:
```
Redis keys:
- laravel:cache:users        (Project A)
- laravel:cache:users        (Project B) ← CONFLICT!
- laravel_queue:default:1    (Project A)
- laravel_queue:default:1    (Project B) ← CONFLICT!
```

### Với PREFIX:
```
Redis keys:
- perfect_fit_cache:users        (Perfect Fit)
- ecommerce_cache:users          (Project khác)
- blog_cache:users               (Project khác)
```

**KHÔNG CONFLICT!** ✅

---

## 📊 REDIS DATABASE SEPARATION

Redis có 16 databases (0-15). Nên tách ra:

```env
REDIS_DB=0              # Default/General purpose
REDIS_CACHE_DB=1        # Cache riêng
REDIS_SESSION_DB=2      # Session riêng (optional)
```

### Cấu trúc keys với config trên:

```
Database 0 (REDIS_DB):
  - perfect_fit_database_general_data

Database 1 (REDIS_CACHE_DB):
  - perfect_fit_cache:users:1
  - perfect_fit_cache:products:all
  - perfect_fit_cache:config

Queue (name: perfect_fit_queue):
  - perfect_fit_queue:default
  - perfect_fit_queue:emails
  - perfect_fit_queue:jobs
```

---

## 🔧 ADVANCED CONFIGURATION

### Option 1: Prefix theo environment

```env
# Development
REDIS_PREFIX="${APP_ENV}_perfect_fit_"
# Result: local_perfect_fit_cache:users

# Production
REDIS_PREFIX="${APP_ENV}_perfect_fit_"
# Result: production_perfect_fit_cache:users
```

### Option 2: Custom prefix cho từng connection

Update `config/database.php`:

```php
'redis' => [
    'client' => env('REDIS_CLIENT', 'predis'),

    'options' => [
        'cluster' => env('REDIS_CLUSTER', 'redis'),
        'prefix' => env('REDIS_PREFIX', 'perfect_fit_'),
    ],

    'default' => [
        'host' => env('REDIS_HOST', '127.0.0.1'),
        'password' => env('REDIS_PASSWORD'),
        'port' => env('REDIS_PORT', '6379'),
        'database' => env('REDIS_DB', '0'),
    ],

    'cache' => [
        'host' => env('REDIS_HOST', '127.0.0.1'),
        'password' => env('REDIS_PASSWORD'),
        'port' => env('REDIS_PORT', '6379'),
        'database' => env('REDIS_CACHE_DB', '1'),
    ],

    'queue' => [
        'host' => env('REDIS_HOST', '127.0.0.1'),
        'password' => env('REDIS_PASSWORD'),
        'port' => env('REDIS_PORT', '6379'),
        'database' => env('REDIS_QUEUE_DB', '2'),
    ],

    'session' => [
        'host' => env('REDIS_HOST', '127.0.0.1'),
        'password' => env('REDIS_PASSWORD'),
        'port' => env('REDIS_PORT', '6379'),
        'database' => env('REDIS_SESSION_DB', '3'),
    ],
],
```

---

## 🧪 TEST REDIS

### 1. Test Connection

```bash
# Connect to Redis
redis-cli

# Test ping
127.0.0.1:6379> PING
# Should return: PONG

# Check keys with prefix
127.0.0.1:6379> KEYS perfect_fit_*

# Exit
127.0.0.1:6379> EXIT
```

### 2. Test trong Laravel

```bash
php artisan tinker
```

```php
// Test cache
Cache::put('test_key', 'test_value', 60);
Cache::get('test_key');

// Check Redis
Redis::keys('*');

// Should see:
// "perfect_fit_cache:test_key"
```

### 3. Test Queue

```bash
# Start queue worker
php artisan queue:work --verbose

# In another terminal, queue a job
php artisan tinker
```

```php
dispatch(function() {
    \Log::info('Test job from Redis queue');
});
```

Check Redis:
```bash
redis-cli
127.0.0.1:6379> KEYS perfect_fit_queue*
```

---

## 📋 RECOMMENDED .env SETTINGS

### Development (Localhost)

```env
APP_NAME="Perfect Fit"
APP_ENV=local

# Cache & Queue
CACHE_DRIVER=redis
QUEUE_CONNECTION=redis
SESSION_DRIVER=redis

# Redis
REDIS_HOST=127.0.0.1
REDIS_PASSWORD=null
REDIS_PORT=6379
REDIS_PREFIX=dev_perfect_fit_
REDIS_DB=0
REDIS_CACHE_DB=1
REDIS_QUEUE=dev_perfect_fit_queue
```

### Production

```env
APP_NAME="Perfect Fit"
APP_ENV=production

# Cache & Queue
CACHE_DRIVER=redis
QUEUE_CONNECTION=redis
SESSION_DRIVER=redis

# Redis
REDIS_HOST=127.0.0.1  # Hoặc Redis server IP
REDIS_PASSWORD=your_secure_password
REDIS_PORT=6379
REDIS_PREFIX=prod_perfect_fit_
REDIS_DB=0
REDIS_CACHE_DB=1
REDIS_QUEUE=prod_perfect_fit_queue
```

### Multiple Projects on Same Server

**Project 1 (Perfect Fit):**
```env
REDIS_PREFIX=perfect_fit_
REDIS_DB=0
REDIS_CACHE_DB=1
```

**Project 2 (Another App):**
```env
REDIS_PREFIX=another_app_
REDIS_DB=4
REDIS_CACHE_DB=5
```

**Project 3 (Blog):**
```env
REDIS_PREFIX=blog_
REDIS_DB=8
REDIS_CACHE_DB=9
```

---

## 🔍 MONITOR REDIS

### Check Redis Memory Usage

```bash
redis-cli INFO memory
```

### Check All Keys by Project

```bash
redis-cli
127.0.0.1:6379> KEYS perfect_fit_*
127.0.0.1:6379> KEYS another_app_*
```

### Clear Cache by Project

```bash
# Clear only Perfect Fit cache
redis-cli KEYS "perfect_fit_cache:*" | xargs redis-cli DEL

# Or trong Laravel
php artisan cache:clear
```

### Monitor in Real-time

```bash
redis-cli MONITOR
```

---

## ⚡ PERFORMANCE TIPS

### 1. Use Separate Databases

```env
REDIS_DB=0              # General
REDIS_CACHE_DB=1        # Cache (clear frequently)
REDIS_QUEUE_DB=2        # Queue (important data)
REDIS_SESSION_DB=3      # Sessions (sensitive)
```

**Benefits:**
- Easy to clear cache without affecting queue
- Better organization
- Separate monitoring

### 2. Set Cache TTL

```php
// Short-lived data
Cache::put('temp_data', $value, 300); // 5 minutes

// Long-lived data
Cache::put('config', $value, 86400); // 24 hours

// Forever (use carefully!)
Cache::forever('permanent_config', $value);
```

### 3. Use Tags (if needed)

```php
// Tag-based cache
Cache::tags(['users', 'profiles'])->put('user:1', $user, 3600);

// Clear specific tags
Cache::tags(['users'])->flush();
```

---

## 🚨 TROUBLESHOOTING

### Issue 1: Redis Not Found

```bash
# Check if Redis is running
redis-cli ping

# Start Redis
sudo systemctl start redis

# Or
redis-server
```

### Issue 2: Connection Refused

```bash
# Check Redis config
sudo nano /etc/redis/redis.conf

# Make sure bind is set:
bind 127.0.0.1

# Restart Redis
sudo systemctl restart redis
```

### Issue 3: Keys Conflict

```bash
# Check all keys
redis-cli KEYS *

# If seeing conflicts, add prefix
# Update .env:
REDIS_PREFIX=perfect_fit_

# Clear config cache
php artisan config:clear
```

### Issue 4: Memory Issues

```bash
# Check memory
redis-cli INFO memory

# Set max memory in redis.conf
maxmemory 256mb
maxmemory-policy allkeys-lru

# Restart Redis
sudo systemctl restart redis
```

---

## 📝 CHECKLIST

Setup Redis đúng cách:

- [ ] Redis installed và running
- [ ] `.env` có `REDIS_PREFIX` unique
- [ ] Fix typo: `SESSION_DRIVER=redis` (không phải `-`)
- [ ] Set `REDIS_CACHE_DB=1` riêng
- [ ] Set `REDIS_QUEUE` với prefix
- [ ] Test connection: `redis-cli PING`
- [ ] Test cache: `php artisan tinker`
- [ ] Clear config: `php artisan config:clear`
- [ ] Start queue: `php artisan queue:work`
- [ ] Monitor: `redis-cli MONITOR`

---

## 🎯 SUMMARY

### Current Issues:
❌ `SESSION_DRIVER-redis` (thiếu dấu =)
❌ Không có `REDIS_PREFIX` → Risk conflict

### Fixed Config:
```env
SESSION_DRIVER=redis
REDIS_PREFIX=perfect_fit_
REDIS_DB=0
REDIS_CACHE_DB=1
REDIS_QUEUE=perfect_fit_queue
```

### Benefits:
✅ No conflicts với projects khác
✅ Organized keys structure
✅ Easy monitoring & debugging
✅ Separate databases cho cache/queue
✅ Production-ready

---

**Setup xong chạy commands này:**

```bash
# Clear config
php artisan config:clear

# Test Redis
redis-cli PING

# Check keys
redis-cli KEYS perfect_fit_*

# Start queue worker
php artisan queue:work
```

Done! 🚀

