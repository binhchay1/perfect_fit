#!/bin/bash

echo ""
echo "╔══════════════════════════════════════════════════════════════════╗"
echo "║           Perfect Fit - Queue Debug Report                       ║"
echo "╚══════════════════════════════════════════════════════════════════╝"
echo ""

echo "🔍 CHECKING CONFIGURATION..."
echo "─────────────────────────────────────────────────────────────────"
php artisan tinker --execute="
echo '1. Queue Connection: ' . config('queue.default') . PHP_EOL;
echo '2. Redis Prefix: ' . config('database.redis.options.prefix') . PHP_EOL;
echo '3. Mail Mailer: ' . config('mail.default') . PHP_EOL;
echo '4. App Env: ' . config('app.env') . PHP_EOL;
"

echo ""
echo "🔴 CHECKING REDIS..."
echo "─────────────────────────────────────────────────────────────────"
echo "Redis Connection:"
redis-cli PING 2>&1

echo ""
echo "Queue Keys in Redis:"
redis-cli KEYS "*queue*" | head -10

echo ""
echo "Email Queue Length:"
redis-cli LLEN "perfect_fit_queue:emails" 2>/dev/null || redis-cli LLEN "laravel_database_queue:emails" 2>/dev/null || echo "0"

echo ""
echo "Default Queue Length:"
redis-cli LLEN "perfect_fit_queue:default" 2>/dev/null || redis-cli LLEN "laravel_database_queue:default" 2>/dev/null || echo "0"

echo ""
echo "🔴 CHECKING HORIZON PROCESS..."
echo "─────────────────────────────────────────────────────────────────"
ps aux | grep -E "perfect_fit.*horizon" | grep -v grep || echo "❌ No Horizon process found for Perfect Fit"

echo ""
echo "📊 CHECKING FAILED JOBS..."
echo "─────────────────────────────────────────────────────────────────"
php artisan queue:failed | head -20 || echo "No failed jobs"

echo ""
echo "📝 RECENT EMAIL LOGS (Last 20 lines)..."
echo "─────────────────────────────────────────────────────────────────"
if [ -f storage/logs/email-debug.log ]; then
    tail -20 storage/logs/email-debug.log
else
    echo "❌ email-debug.log not found"
fi

echo ""
echo "📝 RECENT LARAVEL LOGS (Email related)..."
echo "─────────────────────────────────────────────────────────────────"
tail -50 storage/logs/laravel.log | grep -i email || echo "No email logs found"

echo ""
echo "╔══════════════════════════════════════════════════════════════════╗"
echo "║                        END OF REPORT                             ║"
echo "╚══════════════════════════════════════════════════════════════════╝"
echo ""
echo "💡 RECOMMENDATIONS:"
echo ""
echo "If Queue Connection = sync:"
echo "  → Update .env: QUEUE_CONNECTION=redis"
echo "  → Run: php artisan config:clear"
echo ""
echo "If no Horizon process:"
echo "  → Run: php artisan horizon"
echo ""
echo "If queue has jobs but not processing:"
echo "  → Restart: php artisan horizon:terminate && php artisan horizon"
echo ""

