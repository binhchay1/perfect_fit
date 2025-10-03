# Authentication Documentation

## Tổng Quan

Perfect Fit hỗ trợ nhiều phương thức xác thực người dùng:

1. **Email & Password** - Đăng ký và đăng nhập truyền thống
2. **Social Login** - Đăng nhập qua Google, Facebook, TikTok
3. **Phone OTP** - Đăng nhập bằng số điện thoại với mã OTP

---

## 1. Email Authentication

### 1.1 Đăng Ký (Register)

**Endpoint:** `POST /api/auth/register`

**Request Body:**
```json
{
    "name": "Nguyen Van A",
    "email": "user@example.com",
    "password": "password123"
}
```

**Response:**
```json
{
    "success": true,
    "message": "Registration successful. Please check your email to verify your account.",
    "data": {
        "user": {
            "id": 1,
            "name": "Nguyen Van A",
            "email": "user@example.com",
            "status": 0
        }
    }
}
```

**Lưu ý:**
- Người dùng mới sẽ có `status = 0` (inactive) cho đến khi xác thực email
- Email xác thực sẽ được gửi tự động qua job queue
- Link xác thực có hiệu lực trong 24 giờ

### 1.2 Xác Thực Email

**Endpoint:** `GET /api/auth/verify/{token}`

**Flow:**
1. User nhấn vào link trong email
2. Backend xác thực token
3. Cập nhật `email_verified_at` và `status = 1`
4. Redirect về frontend với kết quả

**Email Template:**
- Email được customize với giao diện hiện đại
- Hỗ trợ responsive design
- Có thông tin chi tiết về tài khoản và thời gian hiệu lực

### 1.3 Gửi Lại Email Xác Thực

**Endpoint:** `POST /api/auth/resend-verify`

**Request Body:**
```json
{
    "email": "user@example.com"
}
```

**Rate Limiting:** 1 email mỗi phút để tránh spam

---

## 2. Social Authentication

### 2.1 Google Login

**Endpoint:** `POST /api/auth/social/google`

**Request Body:**
```json
{
    "token": "google_id_token",
    "device_id": "optional_device_id",
    "device_name": "iPhone 13",
    "device_type": "mobile"
}
```

**Response:**
```json
{
    "success": true,
    "message": "Login successful",
    "data": {
        "user": {
            "id": 1,
            "name": "Nguyen Van A",
            "email": "user@gmail.com",
            "role": "user",
            "avatar": "https://..."
        },
        "token": "access_token",
        "token_type": "Bearer",
        "expires_at": "2025-10-04 12:00:00"
    }
}
```

**Setup Google OAuth:**

1. Tạo project tại [Google Cloud Console](https://console.cloud.google.com)
2. Enable Google+ API
3. Tạo OAuth 2.0 credentials
4. Thêm vào `.env`:
```env
GOOGLE_CLIENT_ID=your_client_id
GOOGLE_CLIENT_SECRET=your_client_secret
GOOGLE_REDIRECT_URL=your_redirect_url
```

**Frontend Integration:**
```javascript
// Using Google Sign-In SDK
google.accounts.id.initialize({
    client_id: 'YOUR_GOOGLE_CLIENT_ID',
    callback: handleGoogleResponse
});

function handleGoogleResponse(response) {
    // Send response.credential to backend
    fetch('/api/auth/social/google', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ token: response.credential })
    });
}
```

### 2.2 Facebook Login

**Endpoint:** `POST /api/auth/social/facebook`

**Request Body:**
```json
{
    "token": "facebook_access_token",
    "device_id": "optional_device_id"
}
```

**Setup Facebook OAuth:**

1. Tạo app tại [Facebook Developers](https://developers.facebook.com)
2. Enable Facebook Login
3. Thêm vào `.env`:
```env
FACEBOOK_CLIENT_ID=your_app_id
FACEBOOK_CLIENT_SECRET=your_app_secret
FACEBOOK_REDIRECT_URL=your_redirect_url
```

**Frontend Integration:**
```javascript
// Using Facebook SDK
FB.login(function(response) {
    if (response.authResponse) {
        fetch('/api/auth/social/facebook', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ 
                token: response.authResponse.accessToken 
            })
        });
    }
}, {scope: 'public_profile,email'});
```

### 2.3 TikTok Login

**Endpoint:** `POST /api/auth/social/tiktok`

**Request Body:**
```json
{
    "token": "tiktok_access_token",
    "device_id": "optional_device_id"
}
```

**Setup TikTok OAuth:**

1. Tạo app tại [TikTok Developers](https://developers.tiktok.com)
2. Apply for Login Kit
3. Thêm vào `.env`:
```env
TIKTOK_CLIENT_KEY=your_client_key
TIKTOK_CLIENT_SECRET=your_client_secret
TIKTOK_REDIRECT_URL=your_redirect_url
```

**Frontend Integration:**
```javascript
// Using TikTok Login Kit
// Follow TikTok's official documentation for web integration
```

---

## 3. Phone OTP Authentication

### 3.1 Gửi OTP

**Endpoint:** `POST /api/auth/phone/send-otp`

**Request Body:**
```json
{
    "phone": "0987654321",
    "purpose": "login"
}
```

**Purpose Options:**
- `login` - Đăng nhập
- `register` - Đăng ký
- `verify_phone` - Xác thực số điện thoại
- `order_confirm` - Xác nhận đơn hàng
- `password_reset` - Đặt lại mật khẩu

**Response:**
```json
{
    "success": true,
    "message": "OTP đã được gửi thành công",
    "data": {
        "expires_at": "2025-10-03 12:05:00"
    }
}
```

### 3.2 Xác Thực OTP và Đăng Nhập

**Endpoint:** `POST /api/auth/phone/verify-otp`

**Request Body:**
```json
{
    "phone": "0987654321",
    "otp_code": "123456",
    "purpose": "login",
    "device_id": "optional_device_id",
    "device_name": "iPhone 13"
}
```

**Response:**
```json
{
    "success": true,
    "message": "Login successful",
    "data": {
        "user": {
            "id": 1,
            "name": "User 4321",
            "phone": "0987654321",
            "email": "0987654321@phone.user",
            "role": "user"
        },
        "token": "access_token",
        "token_type": "Bearer",
        "expires_at": "2025-10-04 12:00:00"
    }
}
```

**Auto-Registration:**
- Nếu số điện thoại chưa tồn tại, hệ thống tự động tạo tài khoản
- Tên mặc định: "User {4 số cuối SĐT}"
- Email tạm: "{phone}@phone.user"

### 3.3 Gửi Lại OTP

**Endpoint:** `POST /api/auth/phone/resend-otp`

**Request Body:**
```json
{
    "phone": "0987654321",
    "purpose": "login"
}
```

---

## 4. SMS Provider Configuration

Hệ thống hỗ trợ nhiều nhà cung cấp SMS:

### 4.1 Twilio (Quốc tế - Free Trial)

**Setup:**
1. Đăng ký tại [Twilio](https://www.twilio.com)
2. Nhận $15 credit miễn phí
3. Lấy Account SID, Auth Token, và Phone Number
4. Cấu hình `.env`:

```env
SMS_PROVIDER=twilio
TWILIO_SID=your_account_sid
TWILIO_TOKEN=your_auth_token
TWILIO_FROM=+1234567890
```

**Features:**
- ✅ Free trial với $15 credit
- ✅ Hỗ trợ quốc tế
- ✅ Độ tin cậy cao
- ❌ Cần verify số điện thoại trong trial mode

### 4.2 eSMS (Vietnam)

**Setup:**
1. Đăng ký tại [eSMS.vn](https://esms.vn)
2. Lấy API Key và Secret Key
3. Cấu hình `.env`:

```env
SMS_PROVIDER=esms
ESMS_API_KEY=your_api_key
ESMS_SECRET_KEY=your_secret_key
ESMS_BRANDNAME=PerfectFit
```

**Features:**
- ✅ Chuyên Việt Nam
- ✅ Hỗ trợ Brandname
- ✅ Giá tốt
- ❌ Trả phí

### 4.3 SpeedSMS (Vietnam)

**Setup:**
1. Đăng ký tại [SpeedSMS.vn](https://speedsms.vn)
2. Lấy Access Token
3. Cấu hình `.env`:

```env
SMS_PROVIDER=speedsms
SPEEDSMS_ACCESS_TOKEN=your_access_token
SPEEDSMS_SENDER=PerfectFit
```

### 4.4 Firebase Phone Auth (Free - Client Side)

**Setup:**
1. Enable Phone Authentication trong Firebase Console
2. Implement reCAPTCHA verification ở frontend
3. Backend chỉ verify OTP

```env
SMS_PROVIDER=firebase
```

**Lưu ý:** 
- Firebase Phone Auth chủ yếu chạy ở client-side
- Backend chỉ validate OTP code
- Free và không giới hạn

### 4.5 Development Mode (Log Only)

Cho môi trường development, OTP được log vào console/file:

```env
SMS_PROVIDER=log
```

**Output:**
```
=== OTP Code ===
Phone: +84987654321
OTP: 123456
Purpose: login
================
```

---

## 5. Security Features

### 5.1 Email Verification
- Token hết hạn sau 24 giờ
- Token chỉ sử dụng được 1 lần
- Tài khoản inactive cho đến khi verify

### 5.2 OTP Security
- OTP code 6 số ngẫu nhiên
- Hết hạn sau 5 phút
- Tự động xóa sau khi verify thành công
- Rate limiting để tránh spam

### 5.3 Device Management
- Track devices cho mỗi user
- Hỗ trợ trusted devices
- Token expires theo cấu hình Passport

### 5.4 Social Login Security
- Verify token với provider trước khi tạo session
- Auto-link accounts nếu email trùng
- Tự động verify email cho social users

---

## 6. Database Schema

### Users Table
```sql
- id
- name
- email (unique)
- phone (unique, nullable)
- password
- email_verified_at
- google_id
- facebook_id
- tiktok_id
- avatar_url
- role (user/admin)
- status (0=inactive, 1=active)
- ...
```

### User Verifies Table
```sql
- id
- user_id
- token (64 chars)
- expires_at
- created_at
```

### OTP Verifications Table
```sql
- id
- phone
- otp_code (6 digits)
- purpose (login/register/etc)
- is_used (boolean)
- expires_at
- verified_at
- created_at
```

### User Devices Table
```sql
- id
- user_id
- device_id (unique)
- device_name
- device_type
- is_trusted
- is_active
- last_used_at
- ...
```

---

## 7. Testing

### Test Email Verification
```bash
# Send test email
curl -X POST http://localhost/api/auth/register \
  -H "Content-Type: application/json" \
  -d '{"name":"Test","email":"test@example.com","password":"123456"}'
```

### Test Google Login
```bash
curl -X POST http://localhost/api/auth/social/google \
  -H "Content-Type: application/json" \
  -d '{"token":"google_id_token_here"}'
```

### Test Phone OTP
```bash
# Send OTP
curl -X POST http://localhost/api/auth/phone/send-otp \
  -H "Content-Type: application/json" \
  -d '{"phone":"0987654321","purpose":"login"}'

# Verify OTP
curl -X POST http://localhost/api/auth/phone/verify-otp \
  -H "Content-Type: application/json" \
  -d '{"phone":"0987654321","otp_code":"123456","purpose":"login"}'
```

---

## 8. Frontend Integration Examples

### React Example - Phone OTP

```javascript
// Send OTP
const sendOTP = async (phone) => {
    const response = await fetch('/api/auth/phone/send-otp', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ 
            phone: phone,
            purpose: 'login' 
        })
    });
    const data = await response.json();
    return data;
};

// Verify OTP
const verifyOTP = async (phone, otpCode) => {
    const response = await fetch('/api/auth/phone/verify-otp', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ 
            phone: phone,
            otp_code: otpCode,
            purpose: 'login'
        })
    });
    const data = await response.json();
    
    if (data.success) {
        // Save token
        localStorage.setItem('token', data.data.token);
        localStorage.setItem('user', JSON.stringify(data.data.user));
    }
    
    return data;
};
```

### Vue Example - Social Login

```javascript
// Google Login
const loginWithGoogle = async (googleToken) => {
    const response = await fetch('/api/auth/social/google', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ token: googleToken })
    });
    const data = await response.json();
    
    if (data.success) {
        // Save authentication
        store.commit('setAuth', {
            token: data.data.token,
            user: data.data.user
        });
    }
};
```

---

## 9. Error Handling

### Common Error Responses

**Email Already Exists:**
```json
{
    "success": false,
    "message": "Validation error",
    "errors": {
        "email": ["The email has already been taken."]
    }
}
```

**Invalid OTP:**
```json
{
    "success": false,
    "message": "OTP không hợp lệ hoặc đã hết hạn"
}
```

**Invalid Social Token:**
```json
{
    "success": false,
    "message": "Invalid Google token"
}
```

**Account Not Active:**
```json
{
    "success": false,
    "message": "Account is not active"
}
```

---

## 10. Best Practices

### Security
1. Always use HTTPS in production
2. Implement rate limiting on all auth endpoints
3. Use strong password policies
4. Enable 2FA for admin accounts
5. Monitor suspicious login activities

### UX Considerations
1. Provide clear error messages
2. Show loading states during auth
3. Auto-focus OTP input fields
4. Support paste for OTP codes
5. Show OTP expiration countdown

### Performance
1. Use queue for sending emails
2. Cache social provider responses
3. Implement session management
4. Clean up expired tokens regularly

---

## Support

Để biết thêm chi tiết, xem:
- [API Documentation](./api-documentation.md)
- [Security Guide](./security.md)
- [Deployment Guide](./deployment.md)

