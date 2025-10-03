# 📧 Gmail OAuth2 Setup Guide - Perfect Fit

## 🎯 WHY Gmail OAuth2?

### ❌ SMTP with App Password:
- Less secure
- Can be revoked by Google
- Limited by Google's security policies
- Need 2FA enabled

### ✅ Gmail OAuth2:
- More secure
- Official Google API
- Better control
- Higher sending limits
- Professional approach

---

## 📋 STEP 1: Setup Google Cloud Project

### 1.1 Create Project

1. Vào [Google Cloud Console](https://console.cloud.google.com)
2. Click **Select Project** → **New Project**
3. Project name: `perfect-fit-mailer`
4. Click **Create**

### 1.2 Enable Gmail API

1. Vào **APIs & Services** → **Library**
2. Search: `Gmail API`
3. Click **Enable**

### 1.3 Create OAuth Client ID

1. Vào **APIs & Services** → **Credentials**
2. Click **Create Credentials** → **OAuth client ID**
3. Nếu chưa có consent screen → Click **Configure Consent Screen**

**OAuth Consent Screen:**
- User Type: **External**
- App name: `Perfect Fit`
- User support email: Your email
- Developer contact: Your email
- Scopes: Add `https://mail.google.com/`
- Test users: Add your Gmail
- Click **Save and Continue**
- Status: **Publish App** (hoặc để Testing nếu dùng test)

**Create OAuth Client ID:**
- Application type: **Web application**
- Name: `Perfect Fit Laravel`
- Authorized redirect URIs:
  ```
  http://localhost:8000/oauth2/callback
  https://yourdomain.com/oauth2/callback
  ```
- Click **Create**

**Save these:**
- ✅ Client ID
- ✅ Client Secret

---

## 🔑 STEP 2: Get Refresh Token

### 2.1 Create Token Script

Create file: `get-gmail-token.php` (root project)

```php
<?php
/**
 * Gmail OAuth2 Refresh Token Generator
 * Run: php get-gmail-token.php
 */

require 'vendor/autoload.php';

// Replace with your credentials
$clientId     = 'YOUR_GOOGLE_CLIENT_ID.apps.googleusercontent.com';
$clientSecret = 'YOUR_GOOGLE_CLIENT_SECRET';
$redirectUri  = 'http://localhost:8000/oauth2/callback';

$provider = new \League\OAuth2\Client\Provider\Google([
    'clientId'     => $clientId,
    'clientSecret' => $clientSecret,
    'redirectUri'  => $redirectUri,
    'accessType'   => 'offline',
    'prompt'       => 'consent',
]);

if (!isset($_GET['code'])) {
    // Step 1: Get authorization URL
    $authUrl = $provider->getAuthorizationUrl([
        'scope' => [
            'https://mail.google.com/',
        ],
    ]);

    $_SESSION['oauth2state'] = $provider->getState();

    echo "\n╔════════════════════════════════════════════════════════════╗\n";
    echo "║         Gmail OAuth2 - Get Refresh Token                  ║\n";
    echo "╚════════════════════════════════════════════════════════════╝\n\n";
    echo "Step 1: Open this URL in your browser:\n\n";
    echo "$authUrl\n\n";
    echo "Step 2: Login with Gmail → Authorize\n";
    echo "Step 3: Copy the 'code' parameter from callback URL\n";
    echo "Step 4: Run: php get-gmail-token.php?code=PASTE_CODE_HERE\n\n";

} else {
    // Step 2: Get token using code
    try {
        $token = $provider->getAccessToken('authorization_code', [
            'code' => $_GET['code']
        ]);

        echo "\n╔════════════════════════════════════════════════════════════╗\n";
        echo "║                  ✅ SUCCESS! TOKENS RECEIVED              ║\n";
        echo "╚════════════════════════════════════════════════════════════╝\n\n";
        
        echo "Access Token:\n";
        echo $token->getToken() . "\n\n";
        
        echo "Refresh Token (SAVE THIS!):\n";
        echo $token->getRefreshToken() . "\n\n";
        
        echo "Expires at: " . $token->getExpires() . "\n\n";
        
        echo "Add to your .env:\n\n";
        echo "GOOGLE_MAIL_CLIENT_ID={$clientId}\n";
        echo "GOOGLE_MAIL_CLIENT_SECRET={$clientSecret}\n";
        echo "GOOGLE_MAIL_REFRESH_TOKEN=" . $token->getRefreshToken() . "\n";
        echo "GOOGLE_MAIL_FROM=your-email@gmail.com\n\n";

    } catch (\Exception $e) {
        echo "Error: " . $e->getMessage() . "\n";
    }
}
```

### 2.2 Install OAuth2 Packages

```bash
composer require league/oauth2-client
composer require league/oauth2-google
```

### 2.3 Run Script

```bash
# Step 1: Get auth URL
php get-gmail-token.php

# Open URL in browser → Login → Copy code from callback URL

# Step 2: Get tokens
php get-gmail-token.php?code=PASTE_YOUR_CODE_HERE
```

**Save the Refresh Token!** ✅

---

## 🔧 STEP 3: Install Laravel Gmail Package

### 3.1 Install Package

```bash
composer require dacastro4/laravel-gmail
```

### 3.2 Publish Config

```bash
php artisan vendor:publish --provider="Dacastro4\LaravelGmail\LaravelGmailServiceProvider"
```

### 3.3 Update `.env`

```env
# Gmail OAuth2 Configuration
MAIL_MAILER=gmail

GOOGLE_MAIL_CLIENT_ID=your_client_id.apps.googleusercontent.com
GOOGLE_MAIL_CLIENT_SECRET=your_client_secret
GOOGLE_MAIL_REFRESH_TOKEN=your_refresh_token
GOOGLE_MAIL_FROM=your-email@gmail.com
GOOGLE_MAIL_FROM_NAME="Perfect Fit"
```

---

## ⚙️ STEP 4: Configure Laravel

### 4.1 Update `config/services.php`

```php
'gmail' => [
    'client_id'     => env('GOOGLE_MAIL_CLIENT_ID'),
    'client_secret' => env('GOOGLE_MAIL_CLIENT_SECRET'),
    'refresh_token' => env('GOOGLE_MAIL_REFRESH_TOKEN'),
    'from' => [
        'address' => env('GOOGLE_MAIL_FROM'),
        'name'    => env('GOOGLE_MAIL_FROM_NAME', 'Perfect Fit'),
    ],
],
```

### 4.2 Update `config/mail.php`

Add Gmail mailer:

```php
'mailers' => [
    'smtp' => [
        'transport' => 'smtp',
        // ... existing SMTP config
    ],

    'gmail' => [
        'transport' => 'gmail',
    ],

    // ... other mailers
],

'from' => [
    'address' => env('MAIL_FROM_ADDRESS', 'perfect_fit@example.com'),
    'name' => env('MAIL_FROM_NAME', 'Perfect Fit'),
],
```

---

## 📝 STEP 5: Update Application Code

### 5.1 Keep Current Email Job

Keep `app/Jobs/SendEmail.php` as is - it will work with Gmail OAuth2!

### 5.2 Test Gmail Sending

Create test route (routes/web.php):

```php
Route::get('/test-gmail', function () {
    try {
        Mail::mailer('gmail')->to('test@example.com')->send(
            new \App\Mail\SendUserEmail([
                'name' => 'Test User',
                'email' => 'test@example.com',
                'token' => Str::random(64),
            ])
        );
        
        return 'Gmail sent successfully!';
    } catch (\Exception $e) {
        return 'Error: ' . $e->getMessage();
    }
});
```

---

## 🧪 STEP 6: Testing

### Test 1: Direct Send

```bash
php artisan tinker
```

```php
Mail::mailer('gmail')->to('your-email@gmail.com')->send(
    new \App\Mail\SendUserEmail([
        'name' => 'Test User',
        'email' => 'test@example.com',
        'token' => 'test-token-123'
    ])
);
```

### Test 2: Register API

```bash
curl -X POST http://localhost:8000/api/auth/register \
  -H "Content-Type: application/json" \
  -d '{
    "name": "Test User",
    "email": "your-email@gmail.com",
    "password": "password123"
  }'
```

Check email inbox! 📧

---

## 🔄 BACKWARD COMPATIBILITY

### Support Both SMTP & Gmail

Update `.env` to choose:

```env
# Choose mailer
MAIL_MAILER=gmail  # or 'smtp'

# SMTP Config (backup)
MAIL_HOST=smtp.gmail.com
MAIL_PORT=587
MAIL_USERNAME=backup@gmail.com
MAIL_PASSWORD=app-password

# Gmail OAuth2 (recommended)
GOOGLE_MAIL_CLIENT_ID=xxx
GOOGLE_MAIL_CLIENT_SECRET=xxx
GOOGLE_MAIL_REFRESH_TOKEN=xxx
GOOGLE_MAIL_FROM=your-email@gmail.com
```

Laravel will automatically use the mailer specified in `MAIL_MAILER`.

---

## 🚨 TROUBLESHOOTING

### Issue 1: Invalid Grant Error

```
Error: invalid_grant
```

**Solution:**
- Refresh token might be expired
- Run `get-gmail-token.php` again
- Make sure to use `prompt=consent` in auth URL

### Issue 2: Scope Error

```
Error: insufficient scopes
```

**Solution:**
- Check OAuth consent screen has `https://mail.google.com/` scope
- Regenerate refresh token

### Issue 3: Rate Limiting

Gmail API limits:
- **Free:** 1 billion requests/day
- **Per user:** 250 emails/day (more with workspace)

**Solution:**
- Use queue for better rate management
- Implement retry logic

### Issue 4: Token Not Refreshing

**Solution:**
Check `config/gmail.php`:

```php
'credentials' => [
    'client_id' => env('GOOGLE_MAIL_CLIENT_ID'),
    'client_secret' => env('GOOGLE_MAIL_CLIENT_SECRET'),
    'refresh_token' => env('GOOGLE_MAIL_REFRESH_TOKEN'),
],
```

---

## 📊 COMPARISON

### SMTP App Password vs OAuth2

| Feature | SMTP App Password | Gmail OAuth2 |
|---------|------------------|--------------|
| Security | ⭐⭐⭐ | ⭐⭐⭐⭐⭐ |
| Setup | Easy | Medium |
| Rate Limit | 500/day | 1B/day (250/user) |
| Revocation | Manual | Automatic |
| Monitoring | Limited | Full API access |
| Cost | Free | Free |

**Recommended:** Gmail OAuth2 for production ✅

---

## 🎯 PRODUCTION CHECKLIST

- [ ] Google Cloud Project created
- [ ] Gmail API enabled
- [ ] OAuth consent screen published
- [ ] Client ID & Secret obtained
- [ ] Refresh token generated
- [ ] Package installed: `dacastro4/laravel-gmail`
- [ ] `.env` configured with OAuth2 credentials
- [ ] `config/services.php` updated
- [ ] `config/mail.php` updated
- [ ] Test email sent successfully
- [ ] Queue worker running
- [ ] Monitoring setup

---

## 📚 ADDITIONAL RESOURCES

### Documentation
- [Google OAuth2 Documentation](https://developers.google.com/identity/protocols/oauth2)
- [Gmail API Documentation](https://developers.google.com/gmail/api)
- [Laravel Gmail Package](https://github.com/dacastro4/laravel-gmail)

### Rate Limits
- [Gmail API Usage Limits](https://developers.google.com/gmail/api/reference/quota)

---

## 💡 TIPS

### 1. Use Different Gmail for Different Purposes

```env
# Development
GOOGLE_MAIL_FROM=dev-noreply@yourdomain.com

# Production
GOOGLE_MAIL_FROM=noreply@yourdomain.com
```

### 2. Monitor API Usage

Check usage in Google Cloud Console:
- APIs & Services → Dashboard
- Gmail API → Quotas

### 3. Implement Retry Logic

Update `app/Jobs/SendEmail.php`:

```php
public $tries = 3;
public $backoff = [60, 120, 300]; // Retry after 1, 2, 5 minutes
```

### 4. Log Email Events

```php
try {
    Mail::mailer('gmail')->send(...);
    Log::info('Gmail sent', ['to' => $email]);
} catch (\Exception $e) {
    Log::error('Gmail failed', [
        'to' => $email,
        'error' => $e->getMessage()
    ]);
}
```

---

## ✅ SUMMARY

### Setup Steps:
1. ✅ Create Google Cloud Project
2. ✅ Enable Gmail API
3. ✅ Create OAuth Client
4. ✅ Get Refresh Token
5. ✅ Install Laravel Package
6. ✅ Configure `.env`
7. ✅ Update configs
8. ✅ Test sending

### Benefits:
🚀 More secure than App Password
📊 Better monitoring & analytics
🔒 Automatic token refresh
📈 Higher rate limits
💪 Production-ready

---

**Ready to implement! Follow the steps above.** 🎉

