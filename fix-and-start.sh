#!/bin/bash

echo ""
echo "╔══════════════════════════════════════════════════════════════════╗"
echo "║         Perfect Fit - Quick Fix & Start Script                  ║"
echo "╚══════════════════════════════════════════════════════════════════╝"
echo ""

echo "🔧 Step 1: Fixing GOOGLE_MAIL_FROM in .env..."
echo "─────────────────────────────────────────────────────────────────"
sed -i 's/GOOGLE_MAIL_FROM=your-email@gmail.com/GOOGLE_MAIL_FROM=binhchay1@gmail.com/g' .env
echo "✅ Updated GOOGLE_MAIL_FROM to binhchay1@gmail.com"
echo ""

echo "🧹 Step 2: Clearing all caches..."
echo "─────────────────────────────────────────────────────────────────"
php artisan config:clear
php artisan cache:clear
php artisan route:clear
echo "✅ All caches cleared"
echo ""

echo "🔍 Step 3: Verifying configuration..."
echo "─────────────────────────────────────────────────────────────────"
php artisan tinker --execute="
echo '✅ Queue: ' . config('queue.default') . PHP_EOL;
echo '✅ Redis Prefix: ' . config('database.redis.options.prefix') . PHP_EOL;
echo '✅ Mail Mailer: ' . config('mail.default') . PHP_EOL;
echo '✅ Gmail From: ' . config('services.gmail.from.address') . PHP_EOL;
"
echo ""

echo "🔴 Step 4: Terminating old Horizon instances..."
echo "─────────────────────────────────────────────────────────────────"
php artisan horizon:terminate 2>/dev/null || echo "No Horizon running (OK)"
sleep 2
echo "✅ Old instances terminated"
echo ""

echo "╔══════════════════════════════════════════════════════════════════╗"
echo "║                    ✅ ALL FIXES APPLIED!                         ║"
echo "╚══════════════════════════════════════════════════════════════════╝"
echo ""
echo "🚀 Ready to start Horizon!"
echo ""
echo "Run in separate terminals:"
echo ""
echo "  Terminal 1: php artisan horizon"
echo "  Terminal 2: tail -f storage/logs/email-debug.log"
echo "  Terminal 3: Test register API"
echo ""
echo "Or start Horizon now in background:"
echo "  nohup php artisan horizon > /dev/null 2>&1 &"
echo ""
echo "Monitor: https://hono.io.vn/horizon"
echo ""

