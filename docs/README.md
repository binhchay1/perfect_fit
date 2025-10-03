# 📚 Perfect Fit API Documentation

## 📖 Danh Sách Tài Liệu

### Authentication & Security
1. 🔐 [**Authentication Guide**](./authentication.md) - Complete authentication system
   - Email/Password with custom verification template
   - Social Login (Google, Facebook, TikTok)
   - Phone OTP (5 SMS providers: Twilio, Firebase, eSMS, SpeedSMS, Log)
2. ⚙️ [**Setup Guide**](./SETUP_AUTHENTICATION.md) - Step-by-step provider configuration
3. 🚀 [**Quick Start**](./AUTHENTICATION_QUICKSTART.md) - 5-minute setup guide
4. 🔑 [**Environment Reference**](./ENV_REFERENCE.md) - All environment variables

### Core Features
5. [Device Management](./DEVICE_MANAGEMENT_API_DOCUMENTATION.md) - Quản lý thiết bị và session
6. [Product Reviews](./PRODUCT_REVIEWS_API_DOCUMENTATION.md) - Đánh giá sản phẩm
7. [Perfect Fit AI](./PERFECT_FIT_AI_API_DOCUMENTATION.md) - Gợi ý size bằng AI
8. [Order Returns](./ORDER_RETURNS_API_DOCUMENTATION.md) - Trả hàng/Hoàn tiền
9. [Payment Accounts](./PAYMENT_ACCOUNTS_API_DOCUMENTATION.md) - Quản lý tài khoản thanh toán
10. [Social Auth & OTP](./SOCIAL_AUTH_OTP_API_DOCUMENTATION.md) - API reference (deprecated, see authentication.md)

### E-Commerce Features
11. [Products](./PRODUCT_API_DOCUMENTATION.md) - Quản lý sản phẩm
12. [Brands](./BRAND_API_DOCUMENTATION.md) - Quản lý thương hiệu
13. [Cart](./CART_API_DOCUMENTATION.md) - Giỏ hàng
14. [Wishlist](./WISHLIST_API_DOCUMENTATION.md) - Danh sách yêu thích
15. [Orders](./ORDER_API_DOCUMENTATION.md) - Quản lý đơn hàng
16. [Payment](./PAYMENT_API_DOCUMENTATION.md) - Thanh toán

### Admin Features
17. [Dashboard](./DASHBOARD_API_DOCUMENTATION.md) - Thống kê và báo cáo
18. [Shipping Settings](./SHIPPING_SETTINGS_API_DOCUMENTATION.md) - Cài đặt vận chuyển
19. [Shipping Carriers](./SHIPPING_CARRIERS_API_DOCUMENTATION.md) - Đơn vị vận chuyển

### Summary & Implementation
20. [API Complete Summary](./API_COMPLETE_SUMMARY.md) - Tổng hợp 143 endpoints
21. [Authentication Summary](../AUTHENTICATION_SUMMARY.md) - Implementation summary

---

## 🚀 Quick Start

### 1. Access Swagger Documentation

**URL:** http://localhost:8000/docs

Swagger UI cung cấp:
- Interactive testing cho tất cả 143 endpoints
- Complete request/response schemas
- Authentication testing
- Try it out functionality

### 2. Authentication

Hầu hết endpoints yêu cầu Bearer token:

```bash
Authorization: Bearer your_access_token_here
```

### 3. Get Access Token

Perfect Fit supports **3 authentication methods**:

**Option 1: Email/Password** (with custom email verification)
```bash
POST /api/auth/login
{
  "email": "user@example.com",
  "password": "password"
}
```

**Option 2: Social Login** (Google/Facebook/TikTok)
```bash
# Google OAuth
POST /api/auth/social/google
{
  "token": "google_id_token"
}

# Facebook OAuth
POST /api/auth/social/facebook
{
  "token": "facebook_access_token"
}

# TikTok OAuth
POST /api/auth/social/tiktok
{
  "token": "tiktok_access_token"
}
```

**Option 3: Phone OTP** (SMS verification)
```bash
# Send OTP
POST /api/auth/phone/send-otp
{
  "phone": "0987654321",
  "purpose": "login"
}

# Verify OTP
POST /api/auth/phone/verify-otp
{
  "phone": "0987654321",
  "otp_code": "123456",
  "purpose": "login"
}
```

**Option 3: Phone OTP**
```bash
# Step 1: Send OTP
POST /api/auth/phone/send-otp
{
  "phone": "0987654321",
  "purpose": "login"
}

# Step 2: Verify OTP
POST /api/auth/phone/verify-otp
{
  "phone": "0987654321",
  "otp_code": "123456"
}
```

---

## 📊 API Coverage

| Module | User Endpoints | Admin Endpoints | Total |
|--------|---------------|-----------------|-------|
| Authentication | 12 | 0 | 12 |
| User Management | 5 | 6 | 11 |
| Devices | 6 | 0 | 6 |
| Products | 7 | 7 | 14 |
| Reviews | 4 | 0 | 4 |
| Perfect Fit AI | 3 | 0 | 3 |
| Brands | 4 | 5 | 9 |
| Cart | 6 | 0 | 6 |
| Wishlist | 7 | 0 | 7 |
| Orders | 6 | 7 | 13 |
| Returns | 4 | 2 | 6 |
| Payment | 4 | 0 | 4 |
| Payment Accounts | 0 | 6 | 6 |
| Shipping | 0 | 7 | 7 |
| Dashboard | 0 | 6 | 6 |
| **TOTAL** | **68** | **46** | **114** |

*(Plus 29 Horizon monitoring endpoints = 143 total)*

---

## ✨ Key Features

### 🔐 **Multi-Channel Authentication**
- Email/Password với email verification
- Google, Facebook, Tiktok OAuth
- Phone OTP authentication
- Multi-device session management

### 🤖 **AI-Powered Features**
- Perfect Fit AI size recommendation
- Image-based body analysis
- Measurement-based suggestions
- Fit preference personalization

### 💬 **Customer Engagement**
- Product reviews với ratings
- Review reactions (like/dislike)
- Verified purchase badges
- Image uploads in reviews

### 🔄 **Returns & Refunds**
- Flexible return requests
- Multiple return types
- Image upload for proof
- Admin approval workflow
- Refund tracking

### 💰 **Complete Payment System**
- Multiple payment methods
- VNPay gateway integration
- Payment account management
- Transaction tracking
- Session-based security

### 📦 **Full E-Commerce**
- Product catalog với variants
- Shopping cart
- Wishlist
- Order processing
- Shipping calculation
- Inventory tracking

---

## 🏗️ Architecture

```
┌─────────────┐
│   Client    │
└──────┬──────┘
       │
       ▼
┌─────────────┐
│  Controller │ ◄─── HTTP Requests
└──────┬──────┘
       │
       ▼
┌─────────────┐
│   Service   │ ◄─── Business Logic
└──────┬──────┘
       │
       ▼
┌─────────────┐
│ Repository  │ ◄─── Data Access
└──────┬──────┘
       │
       ▼
┌─────────────┐
│    Model    │ ◄─── Database
└─────────────┘
```

**Benefits:**
- ✅ Separation of Concerns
- ✅ Testability
- ✅ Maintainability
- ✅ Scalability
- ✅ Reusability

---

## 📱 Figma Design Coverage

**Status: 100% Complete** ✅

All screens from Figma design have corresponding APIs:
- ✅ Authentication flows
- ✅ Product browsing
- ✅ Checkout process
- ✅ Order tracking
- ✅ Return requests
- ✅ Reviews system
- ✅ Perfect Fit AI
- ✅ Admin dashboard
- ✅ Payment accounts setup
- ✅ Shipping configuration

---

**For detailed information, refer to individual documentation files listed above.** 📖

