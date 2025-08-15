# Hướng dẫn sử dụng API Perfect Fit

## Tổng quan

API này được xây dựng với Laravel 11 và sử dụng Sanctum cho authentication. Tất cả responses đều theo format chuẩn để dễ dàng xử lý ở frontend.

## Base URL

```
https://your-domain.com/api
```

## Response Format

Tất cả API responses đều theo format sau:

### Success Response

```json
{
    "success": true,
    "message": "Thông báo thành công",
    "type": "success_type",
    "data": {
        // Dữ liệu trả về
    }
}
```

### Error Response

```json
{
    "success": false,
    "message": "Thông báo lỗi",
    "type": "error_type",
    "errors": {
        // Chi tiết lỗi validation (nếu có)
    }
}
```

## Authentication

### 1. Đăng ký tài khoản

**POST** `/api/register`

```json
{
    "name": "Nguyễn Văn A",
    "email": "user@example.com",
    "password": "password123",
    "password_confirmation": "password123"
}
```

**Response:**

```json
{
    "success": true,
    "message": "Đăng ký thành công",
    "type": "register_success",
    "data": {
        "user": {
            "id": 1,
            "name": "Nguyễn Văn A",
            "email": "user@example.com"
            // ... other fields
        },
        "token": "1|abc123..."
    }
}
```

### 2. Đăng nhập

**POST** `/api/login`

```json
{
    "email": "user@example.com",
    "password": "password123"
}
```

**Response:**

```json
{
    "success": true,
    "message": "Đăng nhập thành công",
    "type": "login_success",
    "data": {
        "user": {
            "id": 1,
            "name": "Nguyễn Văn A",
            "email": "user@example.com"
        },
        "token": "1|abc123..."
    }
}
```

### 3. Đăng xuất

**POST** `/api/logout`

**Headers:**

```
Authorization: Bearer 1|abc123...
```

## Password Reset

### 1. Gửi email reset password

**POST** `/api/password/forget`

```json
{
    "email": "user@example.com"
}
```

### 2. Reset password

**POST** `/api/password/reset/{token}`

```json
{
    "password": "newpassword123",
    "password_confirmation": "newpassword123"
}
```

## User Management

### 1. Lấy thông tin user hiện tại

**GET** `/api/user/me`

**Headers:**

```
Authorization: Bearer 1|abc123...
```

### 2. Cập nhật thông tin user

**POST** `/api/user/update`

**Headers:**

```
Authorization: Bearer 1|abc123...
```

```json
{
    "name": "Tên mới",
    "phone": "0123456789",
    "country": "Vietnam",
    "province": "Ho Chi Minh",
    "district": "District 1",
    "ward": "Ward 1",
    "address": "123 Street Name"
}
```

### 3. Đổi mật khẩu

**POST** `/api/user/change-password`

**Headers:**

```
Authorization: Bearer 1|abc123...
```

```json
{
    "current_password": "oldpassword",
    "new_password": "newpassword123",
    "new_password_confirmation": "newpassword123"
}
```

## Error Handling

### HTTP Status Codes

-   `200` - Success
-   `201` - Created
-   `400` - Bad Request
-   `401` - Unauthorized
-   `403` - Forbidden
-   `404` - Not Found
-   `422` - Validation Error
-   `429` - Too Many Requests
-   `500` - Internal Server Error

### Common Error Types

-   `validation_error` - Lỗi validation
-   `unauthorized` - Chưa đăng nhập
-   `forbidden` - Không có quyền
-   `not_found` - Không tìm thấy
-   `rate_limit_exceeded` - Vượt quá giới hạn request

## Best Practices

### 1. Error Handling ở Frontend

```javascript
// Axios interceptor
axios.interceptors.response.use(
    (response) => response,
    (error) => {
        if (error.response?.data) {
            const { success, message, type, errors } = error.response.data;

            switch (type) {
                case "unauthorized":
                    // Redirect to login
                    break;
                case "validation_error":
                    // Show validation errors
                    break;
                case "rate_limit_exceeded":
                    // Show retry message
                    break;
                default:
                // Show generic error
            }
        }
        return Promise.reject(error);
    }
);
```

### 2. Token Management

```javascript
// Lưu token
localStorage.setItem("token", response.data.data.token);

// Sử dụng token
axios.defaults.headers.common["Authorization"] = `Bearer ${token}`;

// Xóa token khi logout
localStorage.removeItem("token");
```

### 3. Request/Response Logging

```javascript
// Log requests
axios.interceptors.request.use((request) => {
    console.log("Request:", request);
    return request;
});

// Log responses
axios.interceptors.response.use((response) => {
    console.log("Response:", response);
    return response;
});
```

## Testing

### 1. Test với Postman

1. Import collection từ `/api/docs`
2. Set environment variables
3. Test từng endpoint

### 2. Test với cURL

```bash
# Login
curl -X POST http://localhost:8000/api/auth/login \
  -H "Content-Type: application/json" \
  -d '{"email":"user@example.com","password":"password123"}'

# Get user info
curl -X GET http://localhost:8000/api/user/me \
  -H "Authorization: Bearer YOUR_TOKEN"
```

## Security Considerations

1. **HTTPS**: Luôn sử dụng HTTPS trong production
2. **Token Expiration**: Tokens có thời hạn, cần refresh khi cần
3. **Rate Limiting**: API có rate limiting để tránh spam
4. **Input Validation**: Tất cả inputs đều được validate
5. **CORS**: Cấu hình CORS phù hợp cho frontend

## Development

### 1. Local Development

```bash
# Start server
php artisan serve

# Run migrations
php artisan migrate

# Seed data
php artisan db:seed
```

### 2. API Documentation

Truy cập `/api/docs` để xem documentation đầy đủ.

### 3. Testing

```bash
# Run tests
php artisan test

# Run specific test
php artisan test --filter=AuthTest
```

## Support

Nếu có vấn đề gì, vui lòng liên hệ:

-   Email: support@perfectfit.com
-   Documentation: `/api/docs`
-   GitHub Issues: [Repository URL]
