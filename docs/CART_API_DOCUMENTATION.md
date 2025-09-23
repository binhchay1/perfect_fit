# Cart API Documentation

## Tổng quan

Hệ thống Cart được thiết kế với yêu cầu user phải đăng nhập (authentication) mới có thể thêm sản phẩm vào giỏ hàng. Hệ thống sử dụng cấu trúc 2 bảng: `carts` và `cart_items` để quản lý giỏ hàng.

## Cấu trúc Database

### Bảng `carts`

-   `id`: Primary key
-   `user_id`: Foreign key đến bảng users (unique - mỗi user chỉ có 1 cart)
-   `created_at`, `updated_at`: Timestamps

**Lưu ý**: `total_amount` và `total_items` được tính toán động từ `cart_items` thông qua accessors

### Bảng `cart_items`

-   `id`: Primary key
-   `cart_id`: Foreign key đến bảng carts
-   `product_id`: Foreign key đến bảng products
-   `product_color_id`: Foreign key đến bảng product_colors (nullable)
-   `product_size_id`: Foreign key đến bảng product_sizes (nullable)
-   `quantity`: Số lượng sản phẩm (integer, min: 1, max: 10)
-   `price`: Giá sản phẩm tại thời điểm thêm vào giỏ (decimal 10,2)
-   `size`: Tên size (string, nullable - để backward compatibility)
-   `color`: Tên màu (string, nullable - để backward compatibility)
-   `created_at`, `updated_at`: Timestamps

**Unique constraint**: `['cart_id', 'product_id', 'product_color_id', 'product_size_id']`

## API Endpoints

Tất cả endpoints đều yêu cầu authentication (`auth:api` middleware).

### 1. Lấy danh sách sản phẩm trong giỏ hàng

```
GET /api/cart
```

**Response:**

```json
{
    "success": true,
    "message": "Cart items retrieved successfully",
    "data": {
        "cart_items": [
            {
                "id": 1,
                "cart_id": 1,
                "product_id": 1,
                "product_color_id": 1,
                "product_size_id": 1,
                "quantity": 2,
                "price": "299.00",
                "size": "M",
                "color": "Đỏ",
                "product": {
                    "id": 1,
                    "name": "Áo thun nam",
                    "price": "299.00",
                    "brand": {
                        "id": 1,
                        "name": "Nike"
                    }
                },
                "product_color": {
                    "id": 1,
                    "color_name": "Đỏ"
                },
                "product_size": {
                    "id": 1,
                    "size_name": "M",
                    "quantity": 10
                }
            }
        ],
        "total_items": 2,
        "total_amount": "598.00"
    }
}
```

### 2. Thêm sản phẩm vào giỏ hàng

```
POST /api/cart
```

**Request Body:**

```json
{
    "product_id": 1,
    "product_color_id": 1,
    "product_size_id": 1,
    "quantity": 2
}
```

**Validation:**

-   `product_id`: required, exists in products table
-   `product_color_id`: required, exists in product_colors table
-   `product_size_id`: required, exists in product_sizes table
-   `quantity`: required, integer, min: 1, max: 10

**Business Logic:**

-   Kiểm tra product có active không
-   Kiểm tra color có thuộc product không
-   Kiểm tra size có thuộc color không
-   Kiểm tra stock availability
-   Nếu item đã tồn tại, cộng dồn quantity (max 10)
-   Nếu item chưa tồn tại, tạo mới

**Response:**

```json
{
    "success": true,
    "message": "Product added to cart successfully",
    "data": {
        "id": 1,
        "cart_id": 1,
        "product_id": 1,
        "product_color_id": 1,
        "product_size_id": 1,
        "quantity": 2,
        "price": "299.00",
        "size": "M",
        "color": "Đỏ",
        "product": {
            "id": 1,
            "name": "Áo thun nam",
            "brand": {
                "id": 1,
                "name": "Nike"
            }
        },
        "product_color": {
            "id": 1,
            "color_name": "Đỏ"
        },
        "product_size": {
            "id": 1,
            "size_name": "M",
            "quantity": 10
        }
    }
}
```

### 3. Cập nhật số lượng sản phẩm

```
PUT /api/cart/{id}
```

**Request Body:**

```json
{
    "quantity": 3
}
```

**Validation:**

-   `quantity`: required, integer, min: 1, max: 10

### 4. Xóa sản phẩm khỏi giỏ hàng

```
DELETE /api/cart/{id}
```

### 5. Xóa toàn bộ giỏ hàng

```
DELETE /api/cart
```

### 6. Lấy tóm tắt giỏ hàng

```
GET /api/cart/summary
```

**Response:**

```json
{
    "success": true,
    "message": "Cart summary retrieved successfully",
    "data": {
        "total_items": 5,
        "total_amount": "1495.00"
    }
}
```

## Architecture

### Repository Pattern

Hệ thống sử dụng Repository Pattern để tách biệt logic xử lý dữ liệu khỏi Controller:

-   **ProductRepository**: Xử lý logic liên quan đến Product
-   **ProductColorRepository**: Xử lý logic liên quan đến ProductColor
-   **ProductSizeRepository**: Xử lý logic liên quan đến ProductSize
-   **CartRepository**: Xử lý logic liên quan đến Cart
-   **CartItemRepository**: Xử lý logic liên quan đến CartItem

### Dependency Injection

CartController sử dụng Dependency Injection để inject các repository cần thiết.

## Models và Relationships

### Cart Model

```php
// Relationships
public function user(): BelongsTo
public function cartItems(): HasMany

// Accessors (tính toán động)
public function getTotalItemsAttribute(): int
public function getTotalAmountAttribute(): float
```

### CartItem Model

```php
// Relationships
public function cart(): BelongsTo
public function product(): BelongsTo
public function productColor(): BelongsTo
public function productSize(): BelongsTo

// Accessors
public function getTotalPriceAttribute(): float
public function getColorNameAttribute(): ?string
public function getSizeNameAttribute(): ?string
```

### User Model

```php
// Relationships
public function cart(): HasOne
```

## Tính năng bảo mật

1. **Authentication Required**: Tất cả endpoints đều yêu cầu user đăng nhập
2. **User Isolation**: Mỗi user chỉ có thể truy cập cart của chính mình
3. **Stock Validation**: Kiểm tra stock trước khi thêm/cập nhật
4. **Data Integrity**: Kiểm tra color thuộc product, size thuộc color
5. **Quantity Limits**: Giới hạn tối đa 10 sản phẩm cùng loại

## Error Handling

Hệ thống trả về các lỗi phổ biến:

-   `401 Unauthorized`: Chưa đăng nhập
-   `404 Not Found`: Cart item không tồn tại
-   `400 Bad Request`: Stock không đủ, product không active
-   `422 Unprocessable Entity`: Validation errors

## Lưu ý

1. Giá sản phẩm được lưu tại thời điểm thêm vào giỏ hàng
2. `total_amount` và `total_items` được tính toán động thông qua accessors (không lưu trong DB)
3. Unique constraint đảm bảo không có duplicate items
4. Backward compatibility với các trường `size` và `color` dạng string
5. Bảng `carts` chỉ lưu `user_id` để đảm bảo mỗi user có 1 cart duy nhất
