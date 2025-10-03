# Perfect Fit - Authentication Setup Guide

Hướng dẫn cài đặt và cấu hình các phương thức xác thực cho Perfect Fit.

---

## 📋 Mục Lục

1. [Email Verification](#1-email-verification-setup)
2. [Google OAuth](#2-google-oauth-setup)
3. [Facebook OAuth](#3-facebook-oauth-setup)
4. [TikTok OAuth](#4-tiktok-oauth-setup)
5. [Phone OTP - SMS Providers](#5-phone-otp-sms-providers)
6. [Testing](#6-testing)

---

## 1. Email Verification Setup

### 1.1 Cấu hình Email Service

Thêm vào file `.env`:

```env
MAIL_MAILER=smtp
MAIL_HOST=smtp.gmail.com
MAIL_PORT=587
MAIL_USERNAME=your-email@gmail.com
MAIL_PASSWORD=your-app-password
MAIL_ENCRYPTION=tls
MAIL_FROM_ADDRESS=noreply@perfectfit.com
MAIL_FROM_NAME="Perfect Fit"

FRONTEND_URL=http://localhost:3000
```

### 1.2 Gmail Setup (Recommended for Development)

1. Enable 2-Step Verification cho Gmail account
2. Tạo App Password:
   - Vào [Google Account Security](https://myaccount.google.com/security)
   - Chọn "App passwords"
   - Tạo password mới cho "Mail"
   - Copy password vào `MAIL_PASSWORD`

### 1.3 Test Email Template

Email template đã được customize với:
- ✅ Modern gradient design
- ✅ Responsive layout
- ✅ Vietnamese language
- ✅ Security notices
- ✅ Social links

File: `resources/views/emails/verification.blade.php`

---

## 2. Google OAuth Setup

### 2.1 Tạo Google OAuth Credentials

1. Truy cập [Google Cloud Console](https://console.cloud.google.com)
2. Tạo project mới hoặc chọn project có sẵn
3. Enable **Google+ API**
4. Vào **APIs & Services** > **Credentials**
5. Click **Create Credentials** > **OAuth client ID**
6. Chọn **Web application**
7. Thêm **Authorized JavaScript origins**:
   ```
   http://localhost:3000
   https://yourdomain.com
   ```
8. Thêm **Authorized redirect URIs**:
   ```
   http://localhost:3000/auth/google/callback
   https://yourdomain.com/auth/google/callback
   ```

### 2.2 Cấu hình .env

```env
GOOGLE_CLIENT_ID=your-client-id.apps.googleusercontent.com
GOOGLE_CLIENT_SECRET=your-client-secret
GOOGLE_REDIRECT_URL=http://localhost:3000/auth/google/callback
```

### 2.3 Frontend Integration (React/Vue)

**Option 1: Using Google Identity Services**

```html
<!-- Add to index.html -->
<script src="https://accounts.google.com/gsi/client" async defer></script>
```

```javascript
// React Component
import { useEffect } from 'react';

function GoogleLoginButton() {
    useEffect(() => {
        google.accounts.id.initialize({
            client_id: 'YOUR_GOOGLE_CLIENT_ID',
            callback: handleGoogleResponse
        });
        
        google.accounts.id.renderButton(
            document.getElementById('googleButton'),
            { theme: 'outline', size: 'large' }
        );
    }, []);

    const handleGoogleResponse = async (response) => {
        const result = await fetch('/api/auth/social/google', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ token: response.credential })
        });
        
        const data = await result.json();
        if (data.success) {
            localStorage.setItem('token', data.data.token);
            // Redirect to dashboard
        }
    };

    return <div id="googleButton"></div>;
}
```

**Option 2: Using @react-oauth/google**

```bash
npm install @react-oauth/google
```

```javascript
import { GoogleOAuthProvider, GoogleLogin } from '@react-oauth/google';

function App() {
    return (
        <GoogleOAuthProvider clientId="YOUR_CLIENT_ID">
            <GoogleLogin
                onSuccess={async (credentialResponse) => {
                    const response = await fetch('/api/auth/social/google', {
                        method: 'POST',
                        headers: { 'Content-Type': 'application/json' },
                        body: JSON.stringify({ 
                            token: credentialResponse.credential 
                        })
                    });
                    // Handle response
                }}
                onError={() => console.log('Login Failed')}
            />
        </GoogleOAuthProvider>
    );
}
```

---

## 3. Facebook OAuth Setup

### 3.1 Tạo Facebook App

1. Truy cập [Facebook Developers](https://developers.facebook.com)
2. Click **My Apps** > **Create App**
3. Chọn **Consumer** > **Next**
4. Điền thông tin app và tạo
5. Vào **Settings** > **Basic**, copy **App ID** và **App Secret**
6. Vào **Facebook Login** > **Settings**
7. Thêm **Valid OAuth Redirect URIs**:
   ```
   http://localhost:3000/auth/facebook/callback
   https://yourdomain.com/auth/facebook/callback
   ```

### 3.2 Cấu hình .env

```env
FACEBOOK_CLIENT_ID=your-app-id
FACEBOOK_CLIENT_SECRET=your-app-secret
FACEBOOK_REDIRECT_URL=http://localhost:3000/auth/facebook/callback
```

### 3.3 Frontend Integration

```html
<!-- Add Facebook SDK -->
<script async defer crossorigin="anonymous" 
    src="https://connect.facebook.net/en_US/sdk.js"></script>
```

```javascript
// Initialize Facebook SDK
window.fbAsyncInit = function() {
    FB.init({
        appId: 'YOUR_APP_ID',
        cookie: true,
        xfbml: true,
        version: 'v18.0'
    });
};

// Login Function
function loginWithFacebook() {
    FB.login(async function(response) {
        if (response.authResponse) {
            const result = await fetch('/api/auth/social/facebook', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ 
                    token: response.authResponse.accessToken 
                })
            });
            
            const data = await result.json();
            if (data.success) {
                localStorage.setItem('token', data.data.token);
                // Redirect
            }
        }
    }, {scope: 'public_profile,email'});
}
```

---

## 4. TikTok OAuth Setup

### 4.1 Tạo TikTok App

1. Truy cập [TikTok Developers](https://developers.tiktok.com)
2. Đăng nhập và tạo app mới
3. Apply for **Login Kit** permission
4. Cấu hình **Redirect URI**:
   ```
   http://localhost:3000/auth/tiktok/callback
   https://yourdomain.com/auth/tiktok/callback
   ```

### 4.2 Cấu hình .env

```env
TIKTOK_CLIENT_KEY=your-client-key
TIKTOK_CLIENT_SECRET=your-client-secret
TIKTOK_REDIRECT_URL=http://localhost:3000/auth/tiktok/callback
```

### 4.3 Frontend Integration

```javascript
// TikTok Login - Follow official SDK documentation
// https://developers.tiktok.com/doc/login-kit-web

const TIKTOK_AUTH_URL = 'https://www.tiktok.com/auth/authorize/';
const CLIENT_KEY = 'your-client-key';
const REDIRECT_URI = 'http://localhost:3000/auth/tiktok/callback';

function loginWithTikTok() {
    const csrfState = Math.random().toString(36).substring(2);
    localStorage.setItem('tiktok_csrf_state', csrfState);
    
    const url = `${TIKTOK_AUTH_URL}?client_key=${CLIENT_KEY}&scope=user.info.basic&response_type=code&redirect_uri=${encodeURIComponent(REDIRECT_URI)}&state=${csrfState}`;
    
    window.location.href = url;
}

// In callback page
async function handleTikTokCallback() {
    const urlParams = new URLSearchParams(window.location.search);
    const code = urlParams.get('code');
    
    // Exchange code for access token (backend)
    const response = await fetch('/api/auth/social/tiktok', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ token: code })
    });
    
    // Handle response
}
```

---

## 5. Phone OTP - SMS Providers

### 5.1 Development Mode (Log Only)

Để test trong development, sử dụng log mode:

```env
SMS_PROVIDER=log
```

OTP sẽ được in ra console/log file thay vì gửi SMS thật.

### 5.2 Twilio (Recommended - Free Trial)

**🎁 Free Trial: $15 credit**

**Setup:**

1. Đăng ký tại [Twilio](https://www.twilio.com/try-twilio)
2. Verify email và phone number
3. Vào Console > **Account Info**:
   - Copy **Account SID**
   - Copy **Auth Token**
4. Vào **Phone Numbers** > **Manage** > **Buy a number**
   - Chọn số điện thoại có SMS capability
   - Copy số điện thoại

**Cấu hình .env:**

```env
SMS_PROVIDER=twilio
TWILIO_SID=ACxxxxxxxxxxxxxxxxxxxxxxxxxxxxx
TWILIO_TOKEN=your_auth_token
TWILIO_FROM=+1234567890
```

**Lưu ý Trial:**
- Free trial chỉ gửi được đến verified numbers
- Để verify number: Console > Phone Numbers > Verified Caller IDs
- Upgrade để gửi đến bất kỳ số nào

### 5.3 Firebase Phone Auth (Free - Unlimited)

**Setup:**

1. Tạo project tại [Firebase Console](https://console.firebase.google.com)
2. Enable **Authentication** > **Phone**
3. Thêm domain vào **Authorized domains**
4. Cấu hình reCAPTCHA

**Backend .env:**

```env
SMS_PROVIDER=firebase
```

**Frontend Integration (React):**

```bash
npm install firebase
```

```javascript
import { getAuth, RecaptchaVerifier, signInWithPhoneNumber } from 'firebase/auth';

const auth = getAuth();

// Setup reCAPTCHA
function setupRecaptcha() {
    window.recaptchaVerifier = new RecaptchaVerifier(
        'recaptcha-container',
        {
            'size': 'invisible',
            'callback': (response) => {
                // reCAPTCHA solved
            }
        },
        auth
    );
}

// Send OTP
async function sendOTP(phoneNumber) {
    setupRecaptcha();
    const appVerifier = window.recaptchaVerifier;
    
    const confirmationResult = await signInWithPhoneNumber(
        auth, 
        phoneNumber, 
        appVerifier
    );
    
    window.confirmationResult = confirmationResult;
}

// Verify OTP
async function verifyOTP(code) {
    const result = await window.confirmationResult.confirm(code);
    const user = result.user;
    
    // Get Firebase token
    const token = await user.getIdToken();
    
    // Send to backend for verification
    const response = await fetch('/api/auth/phone/verify-otp', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ 
            phone: user.phoneNumber,
            otp_code: code,
            firebase_token: token 
        })
    });
}
```

### 5.4 eSMS Vietnam (Paid - Cho thị trường VN)

**Setup:**

1. Đăng ký tại [eSMS.vn](https://esms.vn)
2. Nạp tiền vào tài khoản
3. Lấy **API Key** và **Secret Key** từ dashboard
4. Đăng ký **Brandname** (tên hiển thị khi gửi SMS)

**Cấu hình .env:**

```env
SMS_PROVIDER=esms
ESMS_API_KEY=your_api_key
ESMS_SECRET_KEY=your_secret_key
ESMS_BRANDNAME=PerfectFit
```

**Giá tham khảo:**
- SMS Brandname: ~600-800 VNĐ/tin
- SMS thường: ~300-500 VNĐ/tin

### 5.5 SpeedSMS Vietnam (Paid)

**Setup:**

1. Đăng ký tại [SpeedSMS.vn](https://speedsms.vn)
2. Nạp tiền
3. Lấy **Access Token** từ dashboard

**Cấu hình .env:**

```env
SMS_PROVIDER=speedsms
SPEEDSMS_ACCESS_TOKEN=your_access_token
SPEEDSMS_SENDER=PerfectFit
```

---

## 6. Testing

### 6.1 Test Email Verification

```bash
# Gửi email đăng ký
curl -X POST http://localhost:8000/api/auth/register \
  -H "Content-Type: application/json" \
  -d '{
    "name": "Test User",
    "email": "test@example.com",
    "password": "password123"
  }'

# Check log hoặc email để lấy verification link
# Click vào link hoặc:
curl -X GET http://localhost:8000/api/auth/verify/{token}
```

### 6.2 Test Google Login

```bash
# Frontend sẽ lấy Google ID token, sau đó:
curl -X POST http://localhost:8000/api/auth/social/google \
  -H "Content-Type: application/json" \
  -d '{
    "token": "google_id_token_from_frontend"
  }'
```

### 6.3 Test Phone OTP (Log Mode)

```bash
# Gửi OTP
curl -X POST http://localhost:8000/api/auth/phone/send-otp \
  -H "Content-Type: application/json" \
  -d '{
    "phone": "0987654321",
    "purpose": "login"
  }'

# Check log để lấy OTP code:
# ==> storage/logs/laravel.log

# Verify OTP
curl -X POST http://localhost:8000/api/auth/phone/verify-otp \
  -H "Content-Type: application/json" \
  -d '{
    "phone": "0987654321",
    "otp_code": "123456",
    "purpose": "login"
  }'
```

### 6.4 Test Phone OTP (Twilio)

```bash
# Set SMS_PROVIDER=twilio trong .env
# OTP sẽ được gửi thật qua SMS

# Gửi OTP
curl -X POST http://localhost:8000/api/auth/phone/send-otp \
  -H "Content-Type: application/json" \
  -d '{
    "phone": "+84987654321",
    "purpose": "login"
  }'

# Nhận SMS và verify
curl -X POST http://localhost:8000/api/auth/phone/verify-otp \
  -H "Content-Type: application/json" \
  -d '{
    "phone": "+84987654321",
    "otp_code": "received_otp_code",
    "purpose": "login"
  }'
```

---

## 7. Production Checklist

### Security

- [ ] Sử dụng HTTPS cho tất cả endpoints
- [ ] Enable rate limiting cho auth endpoints
- [ ] Cấu hình CORS đúng cách
- [ ] Không commit credentials vào Git
- [ ] Sử dụng environment variables
- [ ] Enable 2FA cho admin accounts

### Email

- [ ] Sử dụng professional email service (SendGrid, Mailgun, AWS SES)
- [ ] Verify domain để tránh spam
- [ ] Setup SPF, DKIM, DMARC records
- [ ] Monitor email delivery rates

### SMS/OTP

- [ ] Chọn SMS provider phù hợp với budget
- [ ] Setup rate limiting để tránh spam
- [ ] Monitor SMS costs
- [ ] Implement fraud detection

### OAuth

- [ ] Verify production domains với providers
- [ ] Không để lộ client secrets
- [ ] Monitor OAuth usage và errors
- [ ] Setup proper redirect URIs

---

## 8. Troubleshooting

### Email không gửi được

1. Check MAIL_* config trong .env
2. Test SMTP connection
3. Check spam folder
4. Verify App Password (Gmail)
5. Check queue jobs: `php artisan queue:work`

### Google Login không hoạt động

1. Verify Client ID và Secret
2. Check redirect URI match
3. Enable Google+ API
4. Check browser console for errors
5. Verify token ở backend

### OTP không nhận được

1. Check SMS_PROVIDER config
2. Verify phone number format (+84...)
3. Check Twilio balance/trial limits
4. Check logs: `storage/logs/laravel.log`
5. Verify SMS provider credentials

### TikTok Login Issues

1. Check Login Kit approval status
2. Verify redirect URI
3. Check scopes requested
4. Review TikTok API documentation

---

## 9. Support & Resources

### Documentation Links

- [Laravel Passport](https://laravel.com/docs/11.x/passport)
- [Google OAuth](https://developers.google.com/identity/protocols/oauth2)
- [Facebook Login](https://developers.facebook.com/docs/facebook-login)
- [TikTok Login Kit](https://developers.tiktok.com/doc/login-kit-web)
- [Twilio SMS](https://www.twilio.com/docs/sms)
- [Firebase Auth](https://firebase.google.com/docs/auth)

### Local Documentation

- [Authentication Guide](./authentication.md)
- [API Documentation](./api-documentation.md)
- [Security Guide](./security.md)

---

## 🎉 Hoàn thành!

Sau khi setup xong:

1. ✅ Email verification với custom template
2. ✅ Google, Facebook, TikTok login
3. ✅ Phone OTP với nhiều SMS providers
4. ✅ Secure và scalable authentication

**Happy Coding! 🚀**

