#!/bin/bash

echo ""
echo "╔══════════════════════════════════════════════════════════════════╗"
echo "║          Restart Horizon với Code Mới - Perfect Fit             ║"
echo "╚══════════════════════════════════════════════════════════════════╝"
echo ""

echo "🛑 Step 1: Terminating old Horizon..."
echo "─────────────────────────────────────────────────────────────────"
php artisan horizon:terminate
sleep 3
echo "✅ Old Horizon terminated"
echo ""

echo "🗑️  Step 2: Clearing stuck jobs..."
echo "─────────────────────────────────────────────────────────────────"
redis-cli DEL "perfect_fit_queue:emails" 2>/dev/null || echo "No stuck jobs"
redis-cli DEL "perfect_fit_queue:default" 2>/dev/null || echo "No default jobs"
echo "✅ Stuck jobs cleared"
echo ""

echo "🧹 Step 3: Clearing caches..."
echo "─────────────────────────────────────────────────────────────────"
php artisan config:clear
php artisan cache:clear
echo "✅ Caches cleared"
echo ""

echo "🔍 Step 4: Verifying configuration..."
echo "─────────────────────────────────────────────────────────────────"
php artisan tinker --execute="
echo '✅ Queue: ' . config('queue.default') . PHP_EOL;
echo '✅ Mailer: ' . config('mail.default') . PHP_EOL;
echo '✅ Gmail From: ' . config('services.gmail.from.address') . PHP_EOL;
"
echo ""

echo "♻️  Step 5: Retrying any failed jobs..."
echo "─────────────────────────────────────────────────────────────────"
php artisan queue:retry all 2>/dev/null || echo "No failed jobs to retry"
echo "✅ Failed jobs retried"
echo ""

echo "╔══════════════════════════════════════════════════════════════════╗"
echo "║                    ✅ READY TO START HORIZON                     ║"
echo "╚══════════════════════════════════════════════════════════════════╝"
echo ""
echo "🚀 Start Horizon now:"
echo ""
echo "  Option 1 - Foreground (see logs):"
echo "    php artisan horizon"
echo ""
echo "  Option 2 - Background:"
echo "    nohup php artisan horizon > storage/logs/horizon-output.log 2>&1 &"
echo ""
echo "Then test register API and watch:"
echo "  tail -f storage/logs/email-debug.log"
echo ""

