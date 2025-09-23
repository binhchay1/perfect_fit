# Brand API Documentation

## Tổng quan

API quản lý thương hiệu (Brand) với đầy đủ CRUD operations và các tính năng nâng cao.

## Base URL

```
http://localhost:8000/api
```

## Authentication

-   **Public Routes**: Không cần authentication
-   **Admin Routes**: Cần Bearer Token trong header

## Endpoints

### 1. Lấy danh sách thương hiệu (Public)

```http
GET /brands
```

**Response:**

```json
{
    "success": true,
    "message": "Brands retrieved successfully",
    "data": [
        {
            "id": 1,
            "name": "Nike",
            "slug": "nike",
            "description": "Just Do It - Thương hiệu thể thao hàng đầu thế giới",
            "logo": null,
            "website": "https://www.nike.com",
            "country": "USA",
            "founded_year": 1964,
            "is_active": true,
            "created_at": "2025-01-08T12:00:00.000000Z",
            "updated_at": "2025-01-08T12:00:00.000000Z",
            "products_count": 0,
            "active_products_count": 0
        }
    ]
}
```

### 2. Lấy thương hiệu theo slug (Public)

```http
GET /brands/{slug}
```

**Example:**

```http
GET /brands/nike
```

### 3. Tìm kiếm thương hiệu (Public)

```http
GET /brands/search?keyword=nike
```

### 4. Lấy thương hiệu theo quốc gia (Public)

```http
GET /brands/country/{country}
```

**Example:**

```http
GET /brands/country/USA
```

### 5. Lấy thương hiệu phổ biến (Public)

```http
GET /brands/popular?limit=10
```

### 6. Lấy thương hiệu với sản phẩm (Public)

```http
GET /brands/with-products?limit=10
```

### 7. Tạo thương hiệu mới (Admin)

```http
POST /admin/brands
Authorization: Bearer {token}
Content-Type: multipart/form-data
```

**Request Body:**

```json
{
    "name": "New Brand",
    "slug": "new-brand",
    "description": "Description of the brand",
    "logo": "file",
    "website": "https://www.newbrand.com",
    "country": "Vietnam",
    "founded_year": 2020,
    "is_active": true
}
```

### 8. Cập nhật thương hiệu (Admin)

```http
PUT /admin/brands/{id}
Authorization: Bearer {token}
Content-Type: multipart/form-data
```

### 9. Xóa thương hiệu (Admin)

```http
DELETE /admin/brands/{id}
Authorization: Bearer {token}
```

### 10. Toggle trạng thái thương hiệu (Admin)

```http
PATCH /admin/brands/{id}/toggle-status
Authorization: Bearer {token}
```

### 11. Lấy tất cả thương hiệu (Admin)

```http
GET /admin/brands
Authorization: Bearer {token}
```

## Validation Rules

### BrandRequest

-   `name`: required, string, max:255, unique
-   `slug`: nullable, string, max:255, unique, regex:/^[a-z0-9-]+$/
-   `description`: nullable, string, max:1000
-   `logo`: nullable, image, mimes:jpeg,png,jpg,gif,svg, max:2048
-   `website`: nullable, url, max:255
-   `country`: nullable, string, max:100
-   `founded_year`: nullable, integer, min:1800, max:current_year
-   `is_active`: boolean

## Error Responses

### 400 Bad Request

```json
{
    "success": false,
    "message": "Validation failed",
    "errors": {
        "name": ["Tên thương hiệu là bắt buộc."]
    }
}
```

### 404 Not Found

```json
{
    "success": false,
    "message": "Brand not found"
}
```

### 500 Server Error

```json
{
    "success": false,
    "message": "Failed to retrieve brands",
    "error": "Error details"
}
```

## Features

### 1. Auto Slug Generation

-   Tự động tạo slug từ name nếu không cung cấp
-   Slug unique và SEO-friendly

### 2. Logo Upload

-   Hỗ trợ upload logo với validation
-   Tự động xóa logo cũ khi update
-   Lưu trữ trong `storage/app/public/brands/logos/`

### 3. Product Count

-   Tự động đếm số sản phẩm của thương hiệu
-   Phân biệt tổng sản phẩm và sản phẩm active

### 4. Search & Filter

-   Tìm kiếm theo name, description, country
-   Lọc theo quốc gia
-   Sắp xếp theo độ phổ biến

### 5. Soft Delete

-   Không xóa thực sự, chỉ đánh dấu inactive
-   Toggle status để bật/tắt thương hiệu

## Database Schema

```sql
CREATE TABLE brands (
    id BIGINT PRIMARY KEY AUTO_INCREMENT,
    name VARCHAR(255) NOT NULL,
    slug VARCHAR(255) UNIQUE NOT NULL,
    description TEXT NULL,
    logo VARCHAR(255) NULL,
    website VARCHAR(255) NULL,
    country VARCHAR(100) NULL,
    founded_year INT NULL,
    is_active BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP NULL,
    updated_at TIMESTAMP NULL,

    INDEX idx_brands_active (is_active),
    INDEX idx_brands_slug (slug),
    INDEX idx_brands_name (name)
);
```

## Usage Examples

### Frontend Integration

```javascript
// Get all brands
const brands = await fetch("/api/brands").then((r) => r.json());

// Search brands
const searchResults = await fetch("/api/brands/search?keyword=nike").then((r) =>
    r.json()
);

// Get brand by slug
const brand = await fetch("/api/brands/nike").then((r) => r.json());

// Create new brand (Admin)
const formData = new FormData();
formData.append("name", "New Brand");
formData.append("logo", logoFile);

const newBrand = await fetch("/api/admin/brands", {
    method: "POST",
    headers: {
        Authorization: `Bearer ${token}`,
    },
    body: formData,
}).then((r) => r.json());
```

## Testing

### Run Migration

```bash
php artisan migrate
```

### Run Seeder

```bash
php artisan db:seed --class=BrandSeeder
```

### Test API

```bash
# Get all brands
curl -X GET http://localhost:8000/api/brands

# Create brand
curl -X POST http://localhost:8000/api/admin/brands \
  -H "Authorization: Bearer {token}" \
  -F "name=Test Brand" \
  -F "description=Test Description"
```
