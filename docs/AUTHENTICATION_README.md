# 🔐 Perfect Fit - Authentication System

## ✨ Tổng Quan

Perfect Fit cung cấp hệ thống xác thực toàn diện với nhiều phương thức đăng nhập hiện đại và bảo mật cao.

### 🎯 Các Tính Năng Đã Được Triển Khai

#### ✅ 1. Email & Password Authentication
- Đăng ký với email verification
- Email template custom hiện đại với gradient design
- Gửi lại email xác thực với rate limiting
- Auto-activation sau khi verify
- Job queue cho việc gửi email

#### ✅ 2. Social Login (OAuth 2.0)
- **Google Login** - Sử dụng Google ID token
- **Facebook Login** - Facebook Graph API
- **TikTok Login** - TikTok Login Kit
- Auto-create account nếu chưa tồn tại
- Auto-link account nếu email trùng
- Device tracking cho mỗi social login

#### ✅ 3. Phone OTP Authentication
- Đăng nhập bằng số điện thoại
- OTP 6 số, hết hạn sau 5 phút
- Auto-create account nếu số điện thoại chưa tồn tại
- Hỗ trợ nhiều SMS providers:
  - **Twilio** (Free trial $15)
  - **Firebase Phone Auth** (Free unlimited)
  - **eSMS Vietnam** (Paid)
  - **SpeedSMS Vietnam** (Paid)
  - **Log mode** (Development)

---

## 📋 API Endpoints

### Email Authentication

| Method | Endpoint | Description |
|--------|----------|-------------|
| POST | `/api/auth/register` | Đăng ký tài khoản mới |
| GET | `/api/auth/verify/{token}` | Xác thực email |
| POST | `/api/auth/resend-verify` | Gửi lại email xác thực |
| POST | `/api/auth/login` | Đăng nhập bằng email |

### Social Authentication

| Method | Endpoint | Description |
|--------|----------|-------------|
| POST | `/api/auth/social/google` | Đăng nhập bằng Google |
| POST | `/api/auth/social/facebook` | Đăng nhập bằng Facebook |
| POST | `/api/auth/social/tiktok` | Đăng nhập bằng TikTok |

### Phone OTP Authentication

| Method | Endpoint | Description |
|--------|----------|-------------|
| POST | `/api/auth/phone/send-otp` | Gửi OTP đến số điện thoại |
| POST | `/api/auth/phone/verify-otp` | Xác thực OTP và đăng nhập |
| POST | `/api/auth/phone/resend-otp` | Gửi lại OTP |

---

## 🚀 Quick Start

### 1. Cài Đặt Dependencies

```bash
# Already installed
composer require google/apiclient
```

### 2. Cấu Hình Environment

Thêm vào `.env`:

```env
# Email Configuration
MAIL_MAILER=smtp
MAIL_HOST=smtp.gmail.com
MAIL_PORT=587
MAIL_USERNAME=your-email@gmail.com
MAIL_PASSWORD=your-app-password
MAIL_FROM_ADDRESS=noreply@perfectfit.com
FRONTEND_URL=http://localhost:3000

# Google OAuth
GOOGLE_CLIENT_ID=your-client-id
GOOGLE_CLIENT_SECRET=your-client-secret

# Facebook OAuth
FACEBOOK_CLIENT_ID=your-app-id
FACEBOOK_CLIENT_SECRET=your-app-secret

# TikTok OAuth
TIKTOK_CLIENT_KEY=your-client-key
TIKTOK_CLIENT_SECRET=your-client-secret

# SMS Provider (log/twilio/firebase/esms/speedsms)
SMS_PROVIDER=log

# Twilio (Optional - Free trial)
TWILIO_SID=your-account-sid
TWILIO_TOKEN=your-auth-token
TWILIO_FROM=+1234567890
```

### 3. Run Migrations

```bash
php artisan migrate
```

### 4. Setup Queue Worker

```bash
# Để gửi email asynchronously
php artisan queue:work
```

---

## 📱 Frontend Integration Examples

### Email Registration

```javascript
const register = async (name, email, password) => {
    const response = await fetch('/api/auth/register', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ name, email, password })
    });
    
    const data = await response.json();
    
    if (data.success) {
        // Show success message
        alert('Please check your email to verify your account');
    }
};
```

### Google Login (React)

```jsx
import { GoogleOAuthProvider, GoogleLogin } from '@react-oauth/google';

function LoginPage() {
    const handleGoogleLogin = async (credentialResponse) => {
        const response = await fetch('/api/auth/social/google', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ 
                token: credentialResponse.credential 
            })
        });
        
        const data = await response.json();
        
        if (data.success) {
            localStorage.setItem('token', data.data.token);
            localStorage.setItem('user', JSON.stringify(data.data.user));
            window.location.href = '/dashboard';
        }
    };

    return (
        <GoogleOAuthProvider clientId="YOUR_CLIENT_ID">
            <GoogleLogin
                onSuccess={handleGoogleLogin}
                onError={() => console.log('Login Failed')}
            />
        </GoogleOAuthProvider>
    );
}
```

### Phone OTP Login (React)

```jsx
import { useState } from 'react';

function PhoneLogin() {
    const [phone, setPhone] = useState('');
    const [otp, setOtp] = useState('');
    const [step, setStep] = useState(1); // 1: phone, 2: otp

    const sendOTP = async () => {
        const response = await fetch('/api/auth/phone/send-otp', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ 
                phone: phone,
                purpose: 'login' 
            })
        });
        
        const data = await response.json();
        
        if (data.success) {
            setStep(2);
            // Start countdown timer
        }
    };

    const verifyOTP = async () => {
        const response = await fetch('/api/auth/phone/verify-otp', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ 
                phone: phone,
                otp_code: otp,
                purpose: 'login'
            })
        });
        
        const data = await response.json();
        
        if (data.success) {
            localStorage.setItem('token', data.data.token);
            window.location.href = '/dashboard';
        }
    };

    return (
        <div>
            {step === 1 ? (
                <div>
                    <input 
                        type="tel" 
                        value={phone}
                        onChange={(e) => setPhone(e.target.value)}
                        placeholder="0987654321"
                    />
                    <button onClick={sendOTP}>Send OTP</button>
                </div>
            ) : (
                <div>
                    <input 
                        type="text" 
                        value={otp}
                        onChange={(e) => setOtp(e.target.value)}
                        placeholder="Enter 6-digit OTP"
                        maxLength={6}
                    />
                    <button onClick={verifyOTP}>Verify</button>
                    <button onClick={sendOTP}>Resend OTP</button>
                </div>
            )}
        </div>
    );
}
```

---

## 🎨 Email Template Preview

Email verification được custom với:

### Design Features
- 🎨 Modern gradient background (Purple to Blue)
- 📱 Fully responsive design
- 🌟 Beautiful card layout with shadows
- 🔒 Security notices
- ⏰ Expiration information
- 📧 Fallback link for email clients

### Content (Vietnamese)
- Greeting với emoji
- Thông tin tài khoản
- Call-to-action button
- Thông tin bảo mật
- Lợi ích sau khi xác thực
- Social links
- Copyright footer

**File:** `resources/views/emails/verification.blade.php`

---

## 🔒 Security Features

### Email Verification
✅ Token hết hạn sau 24 giờ  
✅ Token chỉ sử dụng được 1 lần  
✅ Account inactive cho đến khi verify  
✅ Rate limiting (1 email/phút)  

### OTP Security
✅ 6-digit random code  
✅ Hết hạn sau 5 phút  
✅ Tự động xóa sau khi verify  
✅ Rate limiting để tránh spam  
✅ Purpose-based validation  

### Social Login
✅ Verify token với provider  
✅ Auto-link accounts  
✅ Secure token storage  
✅ Device tracking  

### General Security
✅ Password hashing với bcrypt  
✅ Laravel Passport tokens  
✅ CSRF protection  
✅ XSS prevention  
✅ Input sanitization  

---

## 📊 Database Schema

### Tables

**users**
- Standard fields + social IDs
- `google_id`, `facebook_id`, `tiktok_id`
- `avatar_url` for social profile pictures
- `email_verified_at` timestamp
- `status` (0=inactive, 1=active)

**user_verifies**
- Email verification tokens
- `token` (64 chars, unique)
- `expires_at` timestamp
- One-time use tokens

**otp_verifications**
- Phone OTP codes
- `otp_code` (6 digits)
- `purpose` (login/register/etc)
- `is_used` boolean
- `expires_at` timestamp

**user_devices**
- Device tracking
- `device_id`, `device_name`, `device_type`
- `is_trusted`, `is_active`
- `last_used_at`

---

## 🧪 Testing

### Test Email (Development)

```bash
# Register new user
curl -X POST http://localhost:8000/api/auth/register \
  -H "Content-Type: application/json" \
  -d '{
    "name": "Test User",
    "email": "test@example.com",
    "password": "password123"
  }'

# Check email or logs for verification link
# Click link or manually verify:
curl -X GET http://localhost:8000/api/auth/verify/{token}
```

### Test OTP (Log Mode)

```bash
# Send OTP
curl -X POST http://localhost:8000/api/auth/phone/send-otp \
  -H "Content-Type: application/json" \
  -d '{
    "phone": "0987654321",
    "purpose": "login"
  }'

# Check logs for OTP code
tail -f storage/logs/laravel.log

# Verify OTP
curl -X POST http://localhost:8000/api/auth/phone/verify-otp \
  -H "Content-Type: application/json" \
  -d '{
    "phone": "0987654321",
    "otp_code": "123456",
    "purpose": "login"
  }'
```

### Test Social Login

Sử dụng Postman hoặc frontend app để test OAuth flow.

---

## 📚 Documentation

### Chi Tiết Hơn

- 📖 [Authentication Guide](./authentication.md) - Hướng dẫn chi tiết tất cả features
- ⚙️ [Setup Guide](./SETUP_AUTHENTICATION.md) - Cài đặt từng provider
- 🔌 [API Documentation](./api-documentation.md) - API reference đầy đủ

### External Resources

- [Laravel Passport Docs](https://laravel.com/docs/11.x/passport)
- [Google OAuth 2.0](https://developers.google.com/identity/protocols/oauth2)
- [Facebook Login](https://developers.facebook.com/docs/facebook-login)
- [TikTok Login Kit](https://developers.tiktok.com/doc/login-kit-web)
- [Twilio SMS](https://www.twilio.com/docs/sms)
- [Firebase Phone Auth](https://firebase.google.com/docs/auth/web/phone-auth)

---

## 🛠️ SMS Providers Comparison

| Provider | Type | Cost | Vietnam Support | Setup Difficulty |
|----------|------|------|----------------|------------------|
| **Log** | Development | Free | ✅ | ⭐ Easy |
| **Twilio** | International | $15 trial + paid | ✅ | ⭐⭐ Medium |
| **Firebase** | Global | Free | ✅ | ⭐⭐⭐ Complex (client-side) |
| **eSMS** | Vietnam | Paid | ✅✅ | ⭐⭐ Medium |
| **SpeedSMS** | Vietnam | Paid | ✅✅ | ⭐⭐ Medium |

### Recommendations

**Development:**
- Use `log` mode - Free, simple, no setup

**Production (Budget):**
- **Firebase** - Free unlimited (requires client-side integration)
- **Twilio trial** - $15 credit miễn phí

**Production (Vietnam market):**
- **eSMS** or **SpeedSMS** - Brandname SMS, reliable

**Production (International):**
- **Twilio** - Best reliability, great documentation

---

## 🚦 Current Status

### ✅ Completed Features

- [x] Email registration with verification
- [x] Custom email template (gradient design)
- [x] Resend email verification
- [x] Google OAuth login
- [x] Facebook OAuth login
- [x] TikTok OAuth login
- [x] Phone OTP login
- [x] Multiple SMS providers support
- [x] Device tracking
- [x] Security features
- [x] Comprehensive documentation

### 📝 Files Modified/Created

**Modified:**
- ✏️ `resources/views/emails/verification.blade.php` - Beautiful custom template
- ✏️ `app/Services/OtpService.php` - Multi-provider SMS support
- ✏️ `config/services.php` - All OAuth and SMS configs

**Created:**
- ✨ `docs/authentication.md` - Complete authentication guide
- ✨ `docs/SETUP_AUTHENTICATION.md` - Detailed setup instructions
- ✨ `docs/AUTHENTICATION_README.md` - This file!

**Existing (Already implemented):**
- ✅ `app/Services/SocialAuthService.php`
- ✅ `app/Http/Controllers/API/SocialAuthController.php`
- ✅ `app/Http/Controllers/API/OtpController.php`
- ✅ All database migrations

---

## 🎯 Next Steps (Optional Enhancements)

### Potential Future Improvements

1. **Two-Factor Authentication (2FA)**
   - TOTP với Google Authenticator
   - Backup codes
   - SMS 2FA

2. **Biometric Authentication**
   - Fingerprint
   - Face ID
   - WebAuthn

3. **Magic Links**
   - Passwordless login
   - Email magic links

4. **Remember Device**
   - Trust device for 30 days
   - Skip 2FA on trusted devices

5. **Social Login Extensions**
   - Apple Sign In
   - Twitter/X Login
   - GitHub Login

---

## 💡 Usage Tips

### Development
1. Use `SMS_PROVIDER=log` để không tốn tiền
2. Check `storage/logs/laravel.log` cho OTP codes
3. Test với Gmail App Password cho email

### Production
1. Setup queue worker: `php artisan queue:work`
2. Monitor email delivery rates
3. Track SMS costs
4. Enable rate limiting
5. Setup proper error logging

### UX Best Practices
1. Show OTP expiration countdown
2. Auto-submit OTP when 6 digits entered
3. Provide clear error messages
4. Support OTP paste from clipboard
5. Show social login buttons prominently

---

## 🆘 Troubleshooting

### Email Issues
**Problem:** Email không gửi được  
**Solution:**
- Check MAIL_* config
- Verify Gmail App Password
- Run `php artisan queue:work`
- Check spam folder

### OTP Issues
**Problem:** OTP không nhận được  
**Solution:**
- Check SMS_PROVIDER config
- Verify phone format (+84...)
- Check Twilio balance
- Review logs

### Social Login Issues
**Problem:** Token invalid  
**Solution:**
- Verify Client ID/Secret
- Check redirect URI
- Enable APIs in console
- Check token expiration

---

## 📞 Support

Để được hỗ trợ:

1. Xem [Documentation](./authentication.md)
2. Check [Setup Guide](./SETUP_AUTHENTICATION.md)
3. Review logs: `storage/logs/laravel.log`
4. Test API với Postman

---

## 🎉 Conclusion

Perfect Fit authentication system hiện đã hoàn chỉnh với:

✅ **3 phương thức login chính**
✅ **5+ SMS providers**
✅ **Beautiful email templates**
✅ **Comprehensive security**
✅ **Full documentation**

**All features are production-ready!** 🚀

---

*Last Updated: October 3, 2025*
*Version: 1.0.0*

