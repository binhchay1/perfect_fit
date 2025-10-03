# 🚀 Authentication Quick Start Guide

## 📝 TL;DR

Perfect Fit có **3 phương thức đăng nhập**:
1. ✉️ **Email + Password** (với email verification)
2. 🔐 **Social Login** (Google, Facebook, TikTok)
3. 📱 **Phone OTP** (với SMS)

---

## ⚡ 5-Minute Setup

### Step 1: Configure .env (2 min)

```bash
# Copy and edit
cp .env.example .env
```

**Minimum config cho development:**
```env
# Email (use Gmail for testing)
MAIL_MAILER=smtp
MAIL_HOST=smtp.gmail.com
MAIL_PORT=587
MAIL_USERNAME=your-email@gmail.com
MAIL_PASSWORD=your-gmail-app-password

# SMS (log mode - no setup needed)
SMS_PROVIDER=log

# Frontend
FRONTEND_URL=http://localhost:3000
```

### Step 2: Install & Migrate (1 min)

```bash
composer install
php artisan migrate
php artisan passport:install
```

### Step 3: Start Services (1 min)

```bash
# Terminal 1: Laravel server
php artisan serve

# Terminal 2: Queue worker (for emails)
php artisan queue:work
```

### Step 4: Test (1 min)

```bash
# Register user
curl -X POST http://localhost:8000/api/auth/register \
  -H "Content-Type: application/json" \
  -d '{
    "name": "Test User",
    "email": "test@example.com",
    "password": "password123"
  }'

# Check email or logs for verification link
```

**Done!** ✅ Basic auth is working!

---

## 🎯 Enable Social Login (10 min each)

### Google Login

**1. Get Credentials (5 min)**
- Go to [Google Cloud Console](https://console.cloud.google.com)
- Create project → APIs & Services → Credentials
- Create OAuth client ID
- Copy Client ID and Secret

**2. Configure (1 min)**
```env
GOOGLE_CLIENT_ID=xxx.apps.googleusercontent.com
GOOGLE_CLIENT_SECRET=GOCSPX-xxx
```

**3. Test**
```bash
curl -X POST http://localhost:8000/api/auth/social/google \
  -H "Content-Type: application/json" \
  -d '{"token":"google_id_token_from_frontend"}'
```

### Facebook Login

**1. Get Credentials (5 min)**
- Go to [Facebook Developers](https://developers.facebook.com)
- Create App → Settings → Basic
- Copy App ID and Secret

**2. Configure (1 min)**
```env
FACEBOOK_CLIENT_ID=your-app-id
FACEBOOK_CLIENT_SECRET=your-app-secret
```

### TikTok Login

**1. Get Credentials (5 min)**
- Go to [TikTok Developers](https://developers.tiktok.com)
- Create App → Apply for Login Kit
- Copy Client Key and Secret

**2. Configure (1 min)**
```env
TIKTOK_CLIENT_KEY=your-client-key
TIKTOK_CLIENT_SECRET=your-client-secret
```

---

## 📲 Enable Phone OTP

### Option 1: Free (Log Mode) - 0 min ✅
```env
SMS_PROVIDER=log
```
OTP được in ra `storage/logs/laravel.log`

### Option 2: Twilio (Free Trial) - 5 min

**1. Setup (3 min)**
- Sign up at [Twilio](https://www.twilio.com/try-twilio)
- Get $15 free credit
- Copy SID, Token, Phone Number

**2. Configure (1 min)**
```env
SMS_PROVIDER=twilio
TWILIO_SID=ACxxx
TWILIO_TOKEN=xxx
TWILIO_FROM=+1234567890
```

**3. Test (1 min)**
```bash
curl -X POST http://localhost:8000/api/auth/phone/send-otp \
  -H "Content-Type: application/json" \
  -d '{"phone":"0987654321","purpose":"login"}'
```

### Option 3: Firebase (Free Unlimited) - 10 min

**1. Setup Firebase (5 min)**
- Go to [Firebase Console](https://console.firebase.google.com)
- Create project
- Enable Authentication → Phone

**2. Configure (1 min)**
```env
SMS_PROVIDER=firebase
```

**3. Frontend Setup (4 min)**
- Install Firebase SDK
- Implement reCAPTCHA
- Handle OTP on client side

### Option 4: Vietnam SMS (eSMS/SpeedSMS) - 10 min

**eSMS:**
```env
SMS_PROVIDER=esms
ESMS_API_KEY=xxx
ESMS_SECRET_KEY=xxx
```

**SpeedSMS:**
```env
SMS_PROVIDER=speedsms
SPEEDSMS_ACCESS_TOKEN=xxx
```

---

## 🧪 Testing Endpoints

### 1. Email Auth

**Register:**
```bash
curl -X POST http://localhost:8000/api/auth/register \
  -H "Content-Type: application/json" \
  -d '{
    "name": "John Doe",
    "email": "john@example.com",
    "password": "password123"
  }'
```

**Login:**
```bash
curl -X POST http://localhost:8000/api/auth/login \
  -H "Content-Type: application/json" \
  -d '{
    "email": "john@example.com",
    "password": "password123"
  }'
```

### 2. Social Login

**Google:**
```bash
curl -X POST http://localhost:8000/api/auth/social/google \
  -H "Content-Type: application/json" \
  -d '{"token":"google_id_token"}'
```

**Facebook:**
```bash
curl -X POST http://localhost:8000/api/auth/social/facebook \
  -H "Content-Type: application/json" \
  -d '{"token":"facebook_access_token"}'
```

**TikTok:**
```bash
curl -X POST http://localhost:8000/api/auth/social/tiktok \
  -H "Content-Type: application/json" \
  -d '{"token":"tiktok_access_token"}'
```

### 3. Phone OTP

**Send OTP:**
```bash
curl -X POST http://localhost:8000/api/auth/phone/send-otp \
  -H "Content-Type: application/json" \
  -d '{
    "phone": "0987654321",
    "purpose": "login"
  }'
```

**Verify OTP:**
```bash
# Check logs for OTP: tail -f storage/logs/laravel.log

curl -X POST http://localhost:8000/api/auth/phone/verify-otp \
  -H "Content-Type: application/json" \
  -d '{
    "phone": "0987654321",
    "otp_code": "123456",
    "purpose": "login"
  }'
```

---

## 🎨 Email Template

Custom email template features:
- ✨ Modern gradient design
- 📱 Mobile responsive
- 🇻🇳 Vietnamese language
- 🔒 Security info
- ⏰ Expiration time

**Preview:** Open `resources/views/emails/verification.blade.php` in browser

---

## 📱 Frontend Integration

### React Example

**Install:**
```bash
npm install @react-oauth/google
```

**Google Login:**
```jsx
import { GoogleOAuthProvider, GoogleLogin } from '@react-oauth/google';

function App() {
    return (
        <GoogleOAuthProvider clientId="YOUR_CLIENT_ID">
            <GoogleLogin
                onSuccess={async (response) => {
                    const result = await fetch('/api/auth/social/google', {
                        method: 'POST',
                        headers: { 'Content-Type': 'application/json' },
                        body: JSON.stringify({ token: response.credential })
                    });
                    // Handle login
                }}
            />
        </GoogleOAuthProvider>
    );
}
```

**Phone OTP:**
```jsx
function PhoneLogin() {
    const [phone, setPhone] = useState('');
    const [otp, setOtp] = useState('');

    const sendOTP = async () => {
        await fetch('/api/auth/phone/send-otp', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ phone, purpose: 'login' })
        });
    };

    const verifyOTP = async () => {
        const res = await fetch('/api/auth/phone/verify-otp', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ phone, otp_code: otp, purpose: 'login' })
        });
        // Handle response
    };

    return (/* Your UI */);
}
```

---

## 🔍 Debugging

### Email not sending?
```bash
# Check queue
php artisan queue:work --verbose

# Test email config
php artisan tinker
>>> Mail::raw('Test', fn($m) => $m->to('test@example.com'));
```

### OTP not working?
```bash
# Check provider
php artisan tinker
>>> config('services.sms.provider')

# Check logs
tail -f storage/logs/laravel.log
```

### Social login failing?
```bash
# Verify config
php artisan tinker
>>> config('services.google.client_id')

# Check token validation
# Enable debug mode in .env
APP_DEBUG=true
```

---

## 📚 Documentation Files

| File | Purpose |
|------|---------|
| 📖 [authentication.md](authentication.md) | Complete guide |
| ⚙️ [SETUP_AUTHENTICATION.md](SETUP_AUTHENTICATION.md) | Detailed setup |
| 📋 [AUTHENTICATION_README.md](AUTHENTICATION_README.md) | Overview |
| 🔑 [ENV_REFERENCE.md](ENV_REFERENCE.md) | Environment vars |
| 🚀 [AUTHENTICATION_QUICKSTART.md](AUTHENTICATION_QUICKSTART.md) | This file |

---

## ✅ Checklist

### Development
- [ ] `.env` configured
- [ ] Database migrated
- [ ] Queue worker running
- [ ] Email sending works
- [ ] OTP sending works (log mode)
- [ ] Social login configured

### Production
- [ ] Professional email service
- [ ] SMS provider with credits
- [ ] OAuth apps verified
- [ ] HTTPS enabled
- [ ] Queue worker daemonized
- [ ] Error monitoring setup
- [ ] Backups configured

---

## 🆘 Quick Help

**Problem:** Email not sending
```bash
# Fix: Check MAIL_* config and queue worker
php artisan queue:work
```

**Problem:** OTP not received
```bash
# Fix: Check SMS_PROVIDER and credentials
# For dev, use SMS_PROVIDER=log
```

**Problem:** Social login fails
```bash
# Fix: Verify Client ID/Secret
# Check redirect URI matches
```

**Problem:** Token expired
```bash
# Fix: Check token expiration settings
# Regenerate if needed
```

---

## 🎉 Next Steps

1. **Test all methods** ✅
2. **Configure production** 🚀
3. **Add 2FA** (optional) 🔐
4. **Monitor usage** 📊
5. **Scale as needed** 📈

---

## 📞 Need Help?

1. Check [Full Documentation](authentication.md)
2. Review [Setup Guide](SETUP_AUTHENTICATION.md)
3. See [Environment Reference](ENV_REFERENCE.md)
4. Read [Summary](../AUTHENTICATION_SUMMARY.md)

---

**Quick Start Complete!** 🎊

Your authentication system is ready to use with:
- ✅ Email verification
- ✅ Social login (Google, Facebook, TikTok)
- ✅ Phone OTP (5 SMS providers)
- ✅ Beautiful email templates
- ✅ Complete documentation

**Happy Coding!** 🚀

