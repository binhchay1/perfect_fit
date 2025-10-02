# Social Authentication & OTP API Documentation

## Tổng quan

API hỗ trợ đăng nhập qua mạng xã hội (Google, Facebook, Tiktok) và xác thực OTP qua số điện thoại.

## Base URL

```
http://localhost:8000/api
```

## Social Authentication

### 1. Đăng nhập với Google

**POST** `/auth/social/google`

#### Request Body

```json
{
    "token": "google_id_token_here",
    "device_id": "unique-device-id",
    "device_name": "My iPhone",
    "device_type": "ios"
}
```

#### Response

```json
{
    "success": true,
    "message": "Login successful",
    "data": {
        "user": {
            "id": 1,
            "name": "John Doe",
            "email": "john@gmail.com",
            "role": "user",
            "avatar": "https://avatar-url.com/image.jpg"
        },
        "token": "access_token_here",
        "token_type": "Bearer",
        "expires_at": "2025-10-17T10:00:00.000000Z"
    }
}
```

### 2. Đăng nhập với Facebook

**POST** `/auth/social/facebook`

#### Request Body

```json
{
    "token": "facebook_access_token_here",
    "device_id": "unique-device-id"
}
```

### 3. Đăng nhập với Tiktok

**POST** `/auth/social/tiktok`

#### Request Body

```json
{
    "token": "tiktok_access_token_here",
    "device_id": "unique-device-id"
}
```

## OTP Authentication

### 1. Gửi OTP đến số điện thoại

**POST** `/auth/phone/send-otp`

#### Request Body

```json
{
    "phone": "0987654321",
    "purpose": "login"
}
```

**Purposes:**
- `login`: Đăng nhập
- `register`: Đăng ký
- `verify_phone`: Xác thực số điện thoại
- `order_confirm`: Xác nhận đơn hàng
- `password_reset`: Đặt lại mật khẩu

#### Response

```json
{
    "success": true,
    "message": "OTP sent successfully",
    "data": {
        "expires_at": "2025-10-02T10:05:00.000000Z"
    }
}
```

### 2. Xác thực OTP và đăng nhập

**POST** `/auth/phone/verify-otp`

#### Request Body

```json
{
    "phone": "0987654321",
    "otp_code": "123456",
    "purpose": "login",
    "device_id": "unique-device-id",
    "device_name": "My Phone"
}
```

#### Response

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
        "token": "access_token_here",
        "token_type": "Bearer",
        "expires_at": "2025-10-17T10:00:00.000000Z"
    }
}
```

### 3. Gửi lại OTP

**POST** `/auth/phone/resend-otp`

#### Request Body

```json
{
    "phone": "0987654321",
    "purpose": "login"
}
```

## Configuration

### Environment Variables

```env
# Google OAuth
GOOGLE_CLIENT_ID=your_google_client_id
GOOGLE_CLIENT_SECRET=your_google_client_secret
GOOGLE_REDIRECT_URL=http://localhost:8000/auth/google/callback

# Facebook OAuth
FACEBOOK_CLIENT_ID=your_facebook_app_id
FACEBOOK_CLIENT_SECRET=your_facebook_app_secret
FACEBOOK_REDIRECT_URL=http://localhost:8000/auth/facebook/callback

# Tiktok OAuth
TIKTOK_CLIENT_KEY=your_tiktok_client_key
TIKTOK_CLIENT_SECRET=your_tiktok_client_secret
TIKTOK_REDIRECT_URL=http://localhost:8000/auth/tiktok/callback

# SMS/OTP Service
SMS_API_URL=https://sms-api.com
SMS_API_KEY=your_sms_api_key
SMS_SENDER=PerfectFit
```

## Features

### Social Auth
- ✅ Google OAuth 2.0
- ✅ Facebook Login
- ✅ Tiktok Login
- ✅ Auto-create account if not exists
- ✅ Link social account to existing email
- ✅ Avatar sync from social

### OTP System
- ✅ 6-digit OTP code
- ✅ 5 minutes expiry
- ✅ Max 3 attempts
- ✅ Multiple purposes
- ✅ Auto-create user from phone
- ✅ Resend OTP functionality

## Security

- OTP expires after 5 minutes
- Maximum 3 verification attempts
- One-time use only
- Secure token validation for social providers
- Device tracking support

---

**Social Auth & OTP APIs đã sẵn sàng!** 🔐📱

