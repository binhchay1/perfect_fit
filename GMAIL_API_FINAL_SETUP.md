# 🚀 Gmail API Setup - Final Solution (SMTP Ports Blocked)

## 🎯 SOLUTION: Gmail API (No SMTP Needed!)

Vì VPS block SMTP ports (25, 465, 587), tôi đã implement **Gmail API** để gửi email trực tiếp qua API.

---

## ✅ ĐÃ HOÀN THÀNH

### 1. Created GmailApiService
**File:** `app/Services/GmailApiService.php`

**Features:**
- ✅ Send email via Gmail API (bypass SMTP)
- ✅ OAuth2 với auto refresh token
- ✅ HTML email support
- ✅ Error handling & logging
- ✅ Connection testing

### 2. Updated SendEmail Job
**File:** `app/Jobs/SendEmail.php`

**Smart Mailer Selection:**
```php
if ($mailer === 'gmail') {
    // Use Gmail API (SMTP blocked)
    $gmailService->sendEmail(...);
} else {
    // Use Laravel Mail (SMTP, etc)
    Mail::to(...)->send(...);
}
```

### 3. Test Script
**File:** `test-gmail-api.php`

Test Gmail API connection and sending.

---

## ⚙️ GOOGLE CLOUD SETUP (IMPORTANT!)

### PHẢI UPDATE SCOPE!

Refresh token hiện tại của bạn có scope `https://mail.google.com/` (full Gmail access).

Nhưng để **GỬI EMAIL**, cần scope: `https://www.googleapis.com/auth/gmail.send`

### 2 Options:

#### Option 1: Scope hiện tại đã đủ (mail.google.com)
→ Bao gồm gmail.send rồi
→ Không cần làm gì
→ **Try test-gmail-api.php ngay**

#### Option 2: Nếu lỗi scope, regenerate token

**Steps:**

1. Go to [Google Cloud Console](https://console.cloud.google.com)
2. OAuth consent screen → Edit
3. Scopes → Add scope:
   ```
   https://www.googleapis.com/auth/gmail.send
   ```
4. Save
5. Regenerate refresh token:
   ```bash
   php get-gmail-token-interactive.php
   ```
6. Update `.env` với refresh token mới

---

## 🧪 TEST GMAIL API

### Step 1: Update .env

```env
# Fix this line:
GOOGLE_MAIL_FROM=binhchay1@gmail.com  # ← Update from 'your-email@gmail.com'
```

### Step 2: Clear Cache

```bash
php artisan config:clear
```

### Step 3: Test Gmail API

```bash
php test-gmail-api.php
```

**Expected output:**
```
✅ Gmail API connection successful!
✅ Test email sent successfully!
Check inbox: binhchay1@gmail.com
```

### Step 4: If Test OK, Start Horizon

```bash
php artisan horizon
```

### Step 5: Test Register API

```bash
curl -X POST https://hono.io.vn/api/auth/register \
  -H "Content-Type: application/json" \
  -d '{
    "name": "Test User",
    "email": "binhchay1@gmail.com",
    "password": "test123456"
  }'
```

### Step 6: Check Logs

```bash
tail -f storage/logs/email-debug.log
```

**Should see:**
```
=== SendEmail Job Started ===
Preparing to send email (mailer: gmail)
Using Gmail API (SMTP blocked)
✅ Email sent successfully! (method: Gmail API)
=== SendEmail Job Ended ===
```

---

## 🔍 TROUBLESHOOTING

### Error: Invalid Scope

```
Error: insufficient_scope
```

**Solution:**
1. Update scope in Google Cloud Console
2. Regenerate refresh token
3. Update `.env`

### Error: Invalid Grant

```
Error: invalid_grant
```

**Solution:**
Refresh token expired or revoked:
```bash
php get-gmail-token-interactive.php
# Get new refresh token
# Update .env
```

### Error: API Not Enabled

```
Error: Gmail API has not been used
```

**Solution:**
1. Go to Google Cloud Console
2. APIs & Services → Library
3. Search "Gmail API"
4. Click Enable

---

## 📊 HOW IT WORKS

### Traditional SMTP (Blocked):
```
Laravel → SMTP Port 465/587 → Gmail Server → ❌ Blocked by VPS
```

### Gmail API (Works!):
```
Laravel → HTTPS API Call → Google Gmail API → ✅ Works!
```

**Benefits:**
- ✅ No SMTP ports needed
- ✅ Uses HTTPS (port 443)
- ✅ More reliable
- ✅ Better error messages
- ✅ Higher rate limits

---

## 📋 CHECKLIST

Production Setup:

- [ ] `.env` has `MAIL_MAILER=gmail`
- [ ] `.env` has all `GOOGLE_MAIL_*` variables
- [ ] `GOOGLE_MAIL_FROM=binhchay1@gmail.com` (not your-email)
- [ ] Gmail API enabled in Google Cloud
- [ ] Scope includes `gmail.send` (or `mail.google.com`)
- [ ] Refresh token valid
- [ ] Run: `php test-gmail-api.php` ✅
- [ ] Config cleared
- [ ] Horizon running
- [ ] Test register API
- [ ] Email received!

---

## 🚀 QUICK START COMMANDS

```bash
# 1. Fix .env
sed -i 's/your-email@gmail.com/binhchay1@gmail.com/g' .env

# 2. Clear cache
php artisan config:clear

# 3. Test Gmail API
php test-gmail-api.php

# 4. If successful, start Horizon
php artisan horizon

# 5. In another terminal, watch logs
tail -f storage/logs/email-debug.log

# 6. Test register
curl -X POST https://hono.io.vn/api/auth/register \
  -H "Content-Type: application/json" \
  -d '{"name":"Test","email":"binhchay1@gmail.com","password":"test123"}'
```

---

## 📧 EMAIL WILL BE SENT VIA

**Method:** Gmail API (HTTPS)  
**Port:** 443 (HTTPS - never blocked)  
**Protocol:** OAuth2 Bearer Token  
**Rate Limit:** 1 billion requests/day  
**User Limit:** 250 emails/day (or more with Workspace)  

**No SMTP ports needed!** ✅

---

## 💡 ADVANTAGES

vs SMTP App Password:
- ✅ Works when SMTP ports blocked
- ✅ More secure (OAuth2)
- ✅ Higher rate limits
- ✅ Better monitoring
- ✅ Official Google API

vs Other Email Services:
- ✅ Free (no SendGrid/Mailgun cost)
- ✅ Use existing Gmail
- ✅ Professional
- ✅ Reliable

---

**Test now:**

```bash
php test-gmail-api.php
```

**Then start Horizon and test register!** 🚀

