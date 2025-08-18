# Authentication API Documentation

This document describes the Authentication Flow implementation using Laravel Passport.

## API Base URL

```
http://your-domain.com/api
```

## Authentication Flow Endpoints

### 1. Login

**Endpoint:** `POST /auth/login`  
**Description:** Authenticate user and return access token

**Request Body:**

```json
{
    "email": "user@example.com",
    "password": "password123"
}
```

**Response (Success - 200):**

```json
{
    "success": true,
    "message": "Login successful",
    "data": {
        "user": {
            "id": 1,
            "name": "Vu Leo",
            "email": "vu@email.com",
            "role": "user",
            "status": 1
        },
        "token": "eyJ0eXAiOiJKV1QiLCJhbGciOiJSUzI1NiJ9...",
        "token_type": "Bearer",
        "expires_at": "2024-02-15 10:30:00"
    }
}
```

**Response (Validation Error - 422):**

```json
{
    "success": false,
    "message": "Validation error",
    "errors": {
        "password": ["The password must be at least 6 characters."]
    }
}
```

**Response (Unauthorized - 401):**

```json
{
    "success": false,
    "message": "Invalid credentials"
}
```

### 2. Register

**Endpoint:** `POST /auth/register`  
**Description:** Create new user account

**Request Body:**

```json
{
    "name": "John Doe",
    "email": "user@example.com",
    "password": "password123",
    "password_confirmation": "password123",
    "phone": "+1234567890",
    "country": "USA",
    "province": "California",
    "district": "Los Angeles",
    "ward": "Downtown",
    "address": "123 Main St",
    "postal_code": "90210"
}
```

**Response (Success - 201):**

```json
{
    "success": true,
    "message": "User created successfully",
    "data": {
        "token": "eyJ0eXAiOiJKV1QiLCJhbGciOiJSUzI1NiJ9...",
        "token_type": "Bearer",
        "expires_at": "2024-02-15 10:30:00",
        "user": {
            "id": 1,
            "name": "John Doe",
            "email": "user@example.com",
            "role": "user",
            "status": 1
        }
    }
}
```

### 3. Refresh Token

**Endpoint:** `POST /auth/token/refresh`  
**Description:** Get new token using refresh token  
**Authentication:** Required (Bearer Token)

**Headers:**

```
Authorization: Bearer your-access-token
```

**Request Body:**

```json
{
    "refresh_token": "your-refresh-token"
}
```

**Response (Success - 200):**

```json
{
    "success": true,
    "message": "Token refreshed successfully",
    "data": {
        "token": "eyJ0eXAiOiJKV1QiLCJhbGciOiJSUzI1NiJ9...",
        "token_type": "Bearer",
        "expires_at": "2024-02-15 10:30:00"
    }
}
```

### 4. Logout

**Endpoint:** `POST /auth/logout`  
**Description:** Invalidate current token  
**Authentication:** Required (Bearer Token)

**Headers:**

```
Authorization: Bearer your-access-token
```

**Response (Success - 200):**

```json
{
    "success": true,
    "message": "Successfully logged out"
}
```

### 5. Get User Profile

**Endpoint:** `GET /auth/user`  
**Description:** Get authenticated user details  
**Authentication:** Required (Bearer Token)

**Headers:**

```
Authorization: Bearer your-access-token
```

**Response (Success - 200):**

```json
{
    "success": true,
    "message": "User details retrieved successfully",
    "data": {
        "id": 1,
        "name": "John Doe",
        "email": "user@example.com",
        "phone": "+1234567890",
        "country": "USA",
        "province": "California",
        "district": "Los Angeles",
        "ward": "Downtown",
        "address": "123 Main St",
        "postal_code": "90210",
        "role": "user",
        "status": 1,
        "profile_photo_path": null,
        "email_verified_at": null,
        "created_at": "2024-01-15T10:30:00.000000Z",
        "updated_at": "2024-01-15T10:30:00.000000Z"
    }
}
```

## Error Responses

### Validation Error (400)

```json
{
    "success": false,
    "message": "Validation Error",
    "data": {
        "email": ["The email field is required."],
        "password": ["The password field is required."]
    }
}
```

### Unauthorized (401)

```json
{
    "success": false,
    "message": "Unauthenticated."
}
```

### Internal Server Error (500)

```json
{
    "success": false,
    "message": "Failed to refresh token",
    "error": "Error details..."
}
```

## Usage Examples

### Using cURL

#### Login

```bash
curl -X POST http://your-domain.com/api/auth/login \
  -H "Content-Type: application/json" \
  -d '{
    "email": "user@example.com",
    "password": "password123"
  }'
```

#### Register

```bash
curl -X POST http://your-domain.com/api/auth/register \
  -H "Content-Type: application/json" \
  -d '{
    "name": "John Doe",
    "email": "user@example.com",
    "password": "password123",
    "password_confirmation": "password123"
  }'
```

#### Get User Profile

```bash
curl -X GET http://your-domain.com/api/auth/user \
  -H "Authorization: Bearer your-access-token"
```

#### Logout

```bash
curl -X POST http://your-domain.com/api/auth/logout \
  -H "Authorization: Bearer your-access-token"
```

## Authentication Flow Summary

1. **Login:** `POST /auth/login` - Authenticate user and return token
2. **Register:** `POST /auth/register` - Create new user account
3. **Refresh Token:** `POST /auth/token/refresh` - Get new token using refresh token
4. **Logout:** `POST /auth/logout` - Invalidate current token

## Token Configuration

-   **Access Token Expiry:** 15 days
-   **Refresh Token Expiry:** 30 days
-   **Personal Access Token Expiry:** 6 months

## Important Notes

-   All protected routes require the `Authorization: Bearer {token}` header
-   Tokens are JWT tokens generated by Laravel Passport
-   User status must be `1` (active) to login successfully
-   Default user role is `user` for new registrations
-   All responses follow a consistent JSON structure with `success`, `message`, and `data` fields
