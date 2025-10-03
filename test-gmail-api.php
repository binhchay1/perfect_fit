<?php
/**
 * Test Gmail API Connection & Send
 */

require 'vendor/autoload.php';

$app = require_once __DIR__.'/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

echo "\n╔══════════════════════════════════════════════════════════════════╗\n";
echo "║              Gmail API Test - Perfect Fit                        ║\n";
echo "╚══════════════════════════════════════════════════════════════════╝\n\n";

echo "📋 Configuration:\n";
echo "─────────────────────────────────────────────────────────────────\n";
echo "Client ID: " . substr(config('services.gmail.client_id'), 0, 30) . "...\n";
echo "From: " . config('services.gmail.from.address') . "\n";
echo "Has Refresh Token: " . (config('services.gmail.refresh_token') ? 'YES ✅' : 'NO ❌') . "\n";
echo "\n";

try {
    echo "🔍 Testing Gmail API connection...\n";
    echo "─────────────────────────────────────────────────────────────────\n";
    
    $gmailService = app(\App\Services\GmailApiService::class);
    
    if ($gmailService->checkConnection()) {
        echo "✅ Gmail API connection successful!\n\n";
        
        echo "📧 Sending test email...\n";
        echo "─────────────────────────────────────────────────────────────────\n";
        
        $testHtml = '<h1>Test Email</h1><p>This is a test from Perfect Fit Gmail API!</p>';
        
        $sent = $gmailService->sendEmail(
            'binhchay1@gmail.com',
            'Test Email - Perfect Fit Gmail API',
            $testHtml
        );
        
        if ($sent) {
            echo "✅ Test email sent successfully!\n";
            echo "Check inbox: binhchay1@gmail.com\n\n";
        } else {
            echo "❌ Failed to send test email\n\n";
        }
        
    } else {
        echo "❌ Gmail API connection failed\n";
        echo "Check logs: storage/logs/laravel.log\n\n";
    }
    
} catch (\Exception $e) {
    echo "\n❌ ERROR: " . $e->getMessage() . "\n\n";
    echo "Details:\n";
    echo "─────────────────────────────────────────────────────────────────\n";
    echo $e->getTraceAsString() . "\n\n";
}

echo "📝 Check logs for details:\n";
echo "  tail -f storage/logs/email-debug.log\n";
echo "  tail -f storage/logs/laravel.log\n\n";

