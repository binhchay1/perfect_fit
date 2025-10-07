#!/bin/bash

echo ""
echo "╔══════════════════════════════════════════════════════════════════╗"
echo "║      Perfect Fit - Deploy Email Fix (Gmail API)                 ║"
echo "╚══════════════════════════════════════════════════════════════════╝"
echo ""

echo "🔧 Step 1: Fixing .env..."
echo "─────────────────────────────────────────────────────────────────"
sed -i 's/your-email@gmail.com/binhchay1@gmail.com/g' .env
echo "✅ GOOGLE_MAIL_FROM updated"
echo ""

echo "🗑️  Step 2: Clearing failed jobs and stuck queues..."
echo "─────────────────────────────────────────────────────────────────"
php artisan queue:flush 2>/dev/null || echo "No failed jobs"
redis-cli DEL "perfect_fit_queue:emails" 2>/dev/null
redis-cli DEL "perfect_fit_queue:default" 2>/dev/null
echo "✅ Queues cleared"
echo ""

echo "🧹 Step 3: Clearing all caches..."
echo "─────────────────────────────────────────────────────────────────"
php artisan config:clear
php artisan cache:clear
php artisan route:clear
php artisan view:clear
echo "✅ All caches cleared"
echo ""

echo "🔍 Step 4: Verifying configuration..."
echo "─────────────────────────────────────────────────────────────────"
php artisan tinker --execute="
echo '✅ Queue Connection: ' . config('queue.default') . PHP_EOL;
echo '✅ Mail Mailer: ' . config('mail.default') . PHP_EOL;
echo '✅ Gmail From: ' . config('services.gmail.from.address') . PHP_EOL;
echo '✅ Redis Prefix: ' . config('database.redis.options.prefix') . PHP_EOL;
"
echo ""

echo "🔴 Step 5: Finding and terminating old Horizon processes..."
echo "─────────────────────────────────────────────────────────────────"

# Try artisan terminate first
php artisan horizon:terminate 2>/dev/null

# Find and kill any remaining Horizon processes for this project
HORIZON_PIDS=$(ps aux | grep "pf/public_html.*horizon" | grep -v grep | awk '{print $2}')

if [ -n "$HORIZON_PIDS" ]; then
    echo "Found Horizon processes: $HORIZON_PIDS"
    echo "$HORIZON_PIDS" | xargs kill -TERM 2>/dev/null
    sleep 2
    # Force kill if still running
    echo "$HORIZON_PIDS" | xargs kill -9 2>/dev/null || true
    echo "✅ Old Horizon processes terminated"
else
    echo "✅ No old Horizon processes found"
fi
echo ""

echo "⏳ Step 6: Waiting for clean shutdown..."
echo "─────────────────────────────────────────────────────────────────"
sleep 3
echo "✅ Ready"
echo ""

echo "╔══════════════════════════════════════════════════════════════════╗"
echo "║                    ✅ DEPLOYMENT COMPLETE!                       ║"
echo "╚══════════════════════════════════════════════════════════════════╝"
echo ""
echo "🚀 Horizon is ready to start with NEW CODE!"
echo ""
echo "Start Horizon now:"
echo "─────────────────────────────────────────────────────────────────"
echo ""
echo "  Foreground (recommended for testing):"
echo "    php artisan horizon"
echo ""
echo "  Background (for production):"
echo "    nohup php artisan horizon > storage/logs/horizon-output.log 2>&1 &"
echo ""
echo "Then in another terminal:"
echo "─────────────────────────────────────────────────────────────────"
echo ""
echo "  Watch logs:"
echo "    tail -f storage/logs/email-debug.log"
echo ""
echo "  Test register:"
echo "    curl -X POST https://hono.io.vn/api/auth/register \\"
echo "      -H 'Content-Type: application/json' \\"
echo "      -d '{\"name\":\"Test\",\"email\":\"binhchay1@gmail.com\",\"password\":\"test123\"}'"
echo ""
echo "Expected in logs:"
echo "  === SendEmail Job Started ==="
echo "  Using Gmail API (SMTP blocked)"
echo "  Gmail API: Email sent via API"
echo "  ✅ Email sent successfully!"
echo "  === SendEmail Job Ended ==="
echo ""
echo "📧 Check inbox: binhchay1@gmail.com"
echo ""

