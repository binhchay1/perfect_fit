# Wishlist API Documentation

## Tổng quan

API Wishlist cung cấp các chức năng quản lý danh sách sản phẩm yêu thích của người dùng. Hệ thống cho phép user thêm, xóa, và quản lý các sản phẩm trong wishlist của mình.

## Cấu trúc Database

### Bảng `wishlists`

-   `id`: Primary key
-   `user_id`: Foreign key đến bảng users
-   `product_id`: Foreign key đến bảng products
-   `created_at`, `updated_at`: Timestamps

**Unique constraint**: `['user_id', 'product_id']` - Mỗi user chỉ có thể thêm 1 sản phẩm vào wishlist 1 lần

## API Endpoints

Tất cả endpoints đều yêu cầu authentication (`auth:api` middleware).

### 1. Lấy danh sách sản phẩm trong wishlist

```
GET /api/wishlist
```

**Query Parameters:**

-   `per_page`: Số lượng items per page (optional, default: 15)

**Response:**

```json
{
    "success": true,
    "message": "Wishlist items retrieved successfully",
    "data": [
        {
            "id": 1,
            "user_id": 1,
            "product_id": 1,
            "created_at": "2025-09-10T10:00:00.000000Z",
            "updated_at": "2025-09-10T10:00:00.000000Z",
            "product": {
                "id": 1,
                "name": "Áo thun nam Nike",
                "slug": "ao-thun-nam-nike",
                "description": "Áo thun chất lượng cao",
                "brand_id": 1,
                "material": "Cotton 100%",
                "images": ["/images/upload/product/image1.jpg"],
                "price": "299.00",
                "product_type": "T-shirt",
                "product_link": "https://example.com",
                "gender": "male",
                "is_active": true,
                "brand": {
                    "id": 1,
                    "name": "Nike"
                },
                "colors": [
                    {
                        "id": 1,
                        "color_name": "Đỏ",
                        "images": ["/images/upload/product/colors/red1.jpg"],
                        "sizes": [
                            {
                                "id": 1,
                                "size_name": "M",
                                "quantity": 10,
                                "sku": "PRD0001REDM"
                            }
                        ]
                    }
                ]
            }
        }
    ]
}
```

**Với pagination:**

```json
{
    "success": true,
    "message": "Wishlist items retrieved successfully",
    "data": {
        "current_page": 1,
        "data": [...],
        "first_page_url": "http://localhost/api/wishlist?page=1",
        "from": 1,
        "last_page": 2,
        "last_page_url": "http://localhost/api/wishlist?page=2",
        "links": [...],
        "next_page_url": "http://localhost/api/wishlist?page=2",
        "path": "http://localhost/api/wishlist",
        "per_page": 15,
        "prev_page_url": null,
        "to": 15,
        "total": 25
    }
}
```

### 2. Thêm sản phẩm vào wishlist

```
POST /api/wishlist
```

**Request Body:**

```json
{
    "product_id": 1
}
```

**Validation:**

-   `product_id`: required, exists in products table

**Business Logic:**

-   Kiểm tra product có active không
-   Kiểm tra product đã có trong wishlist chưa
-   Nếu chưa có, thêm vào wishlist

**Response:**

```json
{
    "success": true,
    "message": "Product added to wishlist successfully",
    "data": {
        "id": 1,
        "user_id": 1,
        "product_id": 1,
        "created_at": "2025-09-10T10:00:00.000000Z",
        "updated_at": "2025-09-10T10:00:00.000000Z",
        "product": {
            "id": 1,
            "name": "Áo thun nam Nike",
            "brand": {
                "id": 1,
                "name": "Nike"
            },
            "colors": [...]
        }
    }
}
```

### 3. Xóa sản phẩm khỏi wishlist (theo wishlist ID)

```
DELETE /api/wishlist/{id}
```

**Path Parameters:**

-   `id`: ID của wishlist item

**Response:**

```json
{
    "success": true,
    "message": "Product removed from wishlist successfully",
    "data": null
}
```

### 4. Xóa sản phẩm khỏi wishlist (theo product ID)

```
POST /api/wishlist/remove-by-product
```

**Request Body:**

```json
{
    "product_id": 1
}
```

**Validation:**

-   `product_id`: required, exists in products table

**Response:**

```json
{
    "success": true,
    "message": "Product removed from wishlist successfully",
    "data": null
}
```

### 5. Xóa toàn bộ wishlist

```
DELETE /api/wishlist
```

**Response:**

```json
{
    "success": true,
    "message": "Wishlist cleared successfully",
    "data": null
}
```

### 6. Lấy số lượng sản phẩm trong wishlist

```
GET /api/wishlist/count
```

**Response:**

```json
{
    "success": true,
    "message": "Wishlist count retrieved successfully",
    "data": {
        "count": 5
    }
}
```

### 7. Kiểm tra sản phẩm có trong wishlist không

```
POST /api/wishlist/check
```

**Request Body:**

```json
{
    "product_id": 1
}
```

**Validation:**

-   `product_id`: required, exists in products table

**Response:**

```json
{
    "success": true,
    "message": "Wishlist status checked successfully",
    "data": {
        "is_in_wishlist": true
    }
}
```

## Models và Relationships

### Wishlist Model

```php
// Relationships
public function user(): BelongsTo
public function product(): BelongsTo

// Fillable fields
protected $fillable = ['user_id', 'product_id'];
```

### User Model

```php
// Relationships
public function wishlistItems(): HasMany
```

### Product Model

```php
// Relationships (existing)
public function brand(): BelongsTo
public function colors(): HasMany
```

## Repository Pattern

### WishlistRepository

```php
// Core methods
public function getForUserWithRelations($userId)
public function getForUserPaginated($userId, $perPage = 15)
public function isInWishlist($userId, $productId): bool
public function addToWishlist($userId, $productId): Wishlist
public function removeFromWishlist($userId, $productId): bool
public function getForUser($wishlistId, $userId)
public function deleteForUser($wishlistId, $userId): bool
public function clearForUser($userId): bool
public function getCountForUser($userId): int
```

## Tính năng bảo mật

1. **Authentication Required**: Tất cả endpoints đều yêu cầu user đăng nhập
2. **User Isolation**: Mỗi user chỉ có thể truy cập wishlist của chính mình
3. **Product Validation**: Kiểm tra product có active không trước khi thêm
4. **Duplicate Prevention**: Unique constraint ngăn thêm sản phẩm trùng lặp
5. **Security Checks**: Tất cả operations đều kiểm tra ownership

## Business Logic

### Thêm vào wishlist:

1. Validate product_id
2. Kiểm tra product có active không
3. Kiểm tra product đã có trong wishlist chưa
4. Nếu chưa có, thêm vào wishlist
5. Load relationships và trả về response

### Xóa khỏi wishlist:

1. Validate input (ID hoặc product_id)
2. Kiểm tra ownership (user chỉ có thể xóa wishlist của mình)
3. Xóa item khỏi wishlist
4. Trả về response

## Error Handling

Hệ thống trả về các lỗi phổ biến:

-   `401 Unauthorized`: Chưa đăng nhập
-   `400 Bad Request`: Product không active, đã có trong wishlist
-   `404 Not Found`: Wishlist item không tồn tại
-   `422 Unprocessable Entity`: Validation errors

## Lưu ý

1. **Unique Constraint**: Mỗi user chỉ có thể thêm 1 sản phẩm vào wishlist 1 lần
2. **Product Status**: Chỉ cho phép thêm sản phẩm active vào wishlist
3. **Pagination**: Hỗ trợ pagination với parameter `per_page`
4. **Relationships**: Tự động load product với brand, colors, sizes
5. **Security**: Tất cả operations đều kiểm tra ownership
6. **Performance**: Sử dụng eager loading để tối ưu queries
7. **Repository Pattern**: Logic xử lý data được tách biệt khỏi controller

## Ví dụ sử dụng

### Frontend Integration:

```javascript
// Thêm vào wishlist
const addToWishlist = async (productId) => {
    const response = await fetch("/api/wishlist", {
        method: "POST",
        headers: {
            Authorization: `Bearer ${token}`,
            "Content-Type": "application/json",
        },
        body: JSON.stringify({ product_id: productId }),
    });
    return response.json();
};

// Kiểm tra trạng thái wishlist
const checkWishlistStatus = async (productId) => {
    const response = await fetch("/api/wishlist/check", {
        method: "POST",
        headers: {
            Authorization: `Bearer ${token}`,
            "Content-Type": "application/json",
        },
        body: JSON.stringify({ product_id: productId }),
    });
    return response.json();
};

// Lấy danh sách wishlist
const getWishlist = async () => {
    const response = await fetch("/api/wishlist", {
        headers: {
            Authorization: `Bearer ${token}`,
        },
    });
    return response.json();
};
```
