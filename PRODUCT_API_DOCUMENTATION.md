# Product API Documentation

## Tổng quan

API Product cung cấp các chức năng quản lý sản phẩm bao gồm tạo, đọc, cập nhật, xóa và tìm kiếm sản phẩm. Hệ thống hỗ trợ quản lý sản phẩm với nhiều màu sắc, kích thước và hình ảnh.

## Cấu trúc Database

### Bảng `products`

-   `id`: Primary key
-   `name`: Tên sản phẩm
-   `slug`: URL slug (tự động tạo từ name)
-   `description`: Mô tả sản phẩm
-   `brand_id`: Foreign key đến bảng brands
-   `material`: Chất liệu
-   `images`: JSON array chứa đường dẫn hình ảnh chính
-   `price`: Giá sản phẩm (decimal 10,2)
-   `product_type`: Loại sản phẩm
-   `product_link`: Link sản phẩm
-   `gender`: Giới tính (male/female/unisex)
-   `is_active`: Trạng thái hoạt động (boolean)
-   `created_at`, `updated_at`: Timestamps

### Bảng `product_colors`

-   `id`: Primary key
-   `product_id`: Foreign key đến bảng products
-   `color_name`: Tên màu sắc
-   `images`: JSON array chứa đường dẫn hình ảnh màu
-   `created_at`, `updated_at`: Timestamps

### Bảng `product_sizes`

-   `id`: Primary key
-   `product_color_id`: Foreign key đến bảng product_colors
-   `size_name`: Tên kích thước
-   `quantity`: Số lượng tồn kho
-   `sku`: Mã SKU (tự động tạo)
-   `created_at`, `updated_at`: Timestamps

### Bảng `product_tags`

-   `id`: Primary key
-   `product_id`: Foreign key đến bảng products
-   `tag`: Tên tag
-   `created_at`, `updated_at`: Timestamps

## API Endpoints

### 1. Lấy danh sách sản phẩm (Public)

```
GET /api/products
```

**Response:**

```json
{
    "success": true,
    "message": "Products retrieved successfully",
    "data": [
        {
            "id": 1,
            "name": "Áo thun nam Nike",
            "slug": "ao-thun-nam-nike",
            "description": "Áo thun chất lượng cao",
            "brand_id": 1,
            "material": "Cotton 100%",
            "images": [
                "/images/upload/product/image1.jpg",
                "/images/upload/product/image2.jpg"
            ],
            "price": "299.00",
            "product_type": "T-shirt",
            "product_link": "https://example.com",
            "gender": "male",
            "is_active": true,
            "brand": {
                "id": 1,
                "name": "Nike"
            },
            "tags": [
                { "id": 1, "tag": "sport" },
                { "id": 2, "tag": "casual" }
            ],
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
    ]
}
```

### 2. Lấy tất cả sản phẩm (Admin - Protected)

```
GET /api/admin/products
```

**Headers:**

```
Authorization: Bearer {token}
```

**Response:** Tương tự endpoint trên nhưng bao gồm cả sản phẩm inactive.

### 3. Tạo sản phẩm mới (Admin - Protected)

```
POST /api/admin/product
```

**Headers:**

```
Authorization: Bearer {token}
Content-Type: multipart/form-data
```

**Request Body:**

```json
{
    "name": "Áo thun nam Nike",
    "slug": "ao-thun-nam-nike",
    "description": "Áo thun chất lượng cao",
    "brand_id": 1,
    "material": "Cotton 100%",
    "price": 299.00,
    "product_type": "T-shirt",
    "product_link": "https://example.com",
    "gender": "male",
    "is_active": true,
    "images": [file1, file2],
    "tags": ["sport", "casual"],
    "colors": [
        {
            "color_name": "Đỏ",
            "images": [file3, file4],
            "sizes": [
                {
                    "size_name": "M",
                    "quantity": 10
                },
                {
                    "size_name": "L",
                    "quantity": 15
                }
            ]
        },
        {
            "color_name": "Xanh",
            "images": [file5],
            "sizes": [
                {
                    "size_name": "M",
                    "quantity": 8
                }
            ]
        }
    ]
}
```

**Validation Rules:**

-   `name`: required, string, max:255
-   `slug`: nullable, string, max:255, unique:products
-   `description`: nullable, string
-   `brand_id`: required, exists:brands,id
-   `material`: nullable, string, max:255
-   `price`: required, numeric, min:0
-   `product_type`: nullable, string, max:100
-   `product_link`: nullable, url
-   `gender`: required, in:male,female,unisex
-   `is_active`: boolean
-   `images`: array of image files
-   `tags`: array of strings
-   `colors`: array of color objects
-   `colors.*.color_name`: required, string
-   `colors.*.images`: array of image files
-   `colors.*.sizes`: array of size objects
-   `colors.*.sizes.*.size_name`: required, string
-   `colors.*.sizes.*.quantity`: required, integer, min:0

**Response:**

```json
{
    "success": true,
    "message": "Product created successfully",
    "data": {
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
        "tags": [
            { "id": 1, "tag": "sport" },
            { "id": 2, "tag": "casual" }
        ],
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
```

### 4. Lấy sản phẩm theo ID (Admin - Protected)

```
GET /api/admin/product/{id}
```

**Headers:**

```
Authorization: Bearer {token}
```

### 5. Cập nhật sản phẩm (Admin - Protected)

```
POST /api/admin/product/{id}
```

**Headers:**

```
Authorization: Bearer {token}
Content-Type: multipart/form-data
```

**Request Body:** Tương tự như tạo sản phẩm mới.

**Lưu ý:**

-   Khi cập nhật, tất cả tags, colors và sizes cũ sẽ bị xóa và tạo mới
-   Nếu không gửi `colors`, colors cũ sẽ được giữ nguyên
-   Nếu không gửi `tags`, tags cũ sẽ được giữ nguyên

### 6. Xóa sản phẩm (Admin - Protected)

```
DELETE /api/admin/product/{id}
```

**Headers:**

```
Authorization: Bearer {token}
```

**Response:**

```json
{
    "success": true,
    "message": "Product deleted successfully",
    "data": null
}
```

### 7. Toggle trạng thái sản phẩm (Admin - Protected)

```
POST /api/admin/product/{id}/toggle-status
```

**Headers:**

```
Authorization: Bearer {token}
```

**Response:**

```json
{
    "success": true,
    "message": "Product status updated successfully",
    "data": {
        "id": 1,
        "name": "Áo thun nam Nike",
        "is_active": false
    }
}
```

### 8. Tìm kiếm sản phẩm (Public)

```
GET /api/products/search?keyword={keyword}
```

**Query Parameters:**

-   `keyword`: Từ khóa tìm kiếm (required)

**Response:** Tương tự endpoint lấy danh sách sản phẩm.

### 9. Lấy sản phẩm theo thương hiệu (Public)

```
GET /api/product/brand/{brandId}
```

**Response:** Tương tự endpoint lấy danh sách sản phẩm.

### 10. Lấy sản phẩm theo giới tính (Public)

```
GET /api/product/gender/{gender}
```

**Path Parameters:**

-   `gender`: male, female, hoặc unisex

**Response:** Tương tự endpoint lấy danh sách sản phẩm.

### 11. Lấy sản phẩm với bộ lọc (Public)

```
GET /api/products/filters
```

**Query Parameters:**

-   `brand_id`: ID thương hiệu
-   `gender`: Giới tính (male/female/unisex)
-   `product_type`: Loại sản phẩm
-   `min_price`: Giá tối thiểu
-   `max_price`: Giá tối đa
-   `search`: Từ khóa tìm kiếm

**Example:**

```
GET /api/products/filters?brand_id=1&gender=male&min_price=100&max_price=500
```

**Response:** Tương tự endpoint lấy danh sách sản phẩm.

### 12. Lấy sản phẩm nổi bật (Public)

```
GET /api/product/featured?limit={limit}
```

**Query Parameters:**

-   `limit`: Số lượng sản phẩm (default: 10)

**Response:** Tương tự endpoint lấy danh sách sản phẩm.

### 13. Lấy sản phẩm theo slug (Public)

```
GET /api/product/{slug}
```

**Path Parameters:**

-   `slug`: URL slug của sản phẩm

**Response:** Tương tự endpoint lấy sản phẩm theo ID.

## Models và Relationships

### Product Model

```php
// Relationships
public function brand(): BelongsTo
public function tags(): HasMany
public function colors(): HasMany

// Scopes
public function scopeActive($query)
public function scopeByGender($query, $gender)
public function scopeByBrand($query, $brandId)

// Methods
public function getAvailableColors()
public function getAvailableSizes()
public function getTotalStock()
public function hasStock(): bool
```

### ProductColor Model

```php
// Relationships
public function product(): BelongsTo
public function sizes(): HasMany

// Methods
public function availableSizes(): HasMany
public function getTotalStockAttribute()
public function hasStock(): bool
public function getImageUrlsAttribute()
public function getFirstImageUrlAttribute()
```

### ProductSize Model

```php
// Relationships
public function productColor(): BelongsTo
public function product(): BelongsTo

// Scopes
public function scopeInStock($query)
public function scopeBySize($query, $size)

// Methods
public function isInStock(): bool
public static function generateSku($productId, $colorName, $sizeName): string
```

### ProductTag Model

```php
// Relationships
public function product(): BelongsTo
```

## Xử lý File Upload

### Hình ảnh sản phẩm chính

-   Đường dẫn lưu trữ: `/public/images/upload/product/`
-   Tên file: `{timestamp}_{uniqid}.{extension}`
-   Format hỗ trợ: jpg, jpeg, png, gif
-   Kích thước tối đa: 5MB per file

### Hình ảnh màu sắc

-   Đường dẫn lưu trữ: `/public/images/upload/product/colors/`
-   Tên file: `{timestamp}_{uniqid}.{extension}`
-   Format hỗ trợ: jpg, jpeg, png, gif
-   Kích thước tối đa: 5MB per file

## SKU Generation

SKU được tự động tạo theo format: `PRD{productId}{colorCode}{sizeCode}`

**Ví dụ:**

-   Product ID: 1
-   Color: "Đỏ" → "RED"
-   Size: "M" → "M"
-   SKU: "PRD0001REDM"

## Error Handling

Hệ thống trả về các lỗi phổ biến:

-   `400 Bad Request`: Dữ liệu không hợp lệ
-   `401 Unauthorized`: Chưa đăng nhập (cho admin endpoints)
-   `404 Not Found`: Sản phẩm không tồn tại
-   `422 Unprocessable Entity`: Validation errors
-   `500 Internal Server Error`: Lỗi server

## Lưu ý

1. **File Upload**: Sử dụng `multipart/form-data` cho các request có upload file
2. **Transaction**: Tất cả operations tạo/cập nhật sản phẩm đều sử dụng database transaction
3. **Slug Generation**: Slug được tự động tạo từ name nếu không được cung cấp
4. **Image Management**: Hình ảnh được lưu trữ local, có thể tích hợp cloud storage sau
5. **Stock Management**: Quantity được quản lý ở level ProductSize
6. **Soft Delete**: Không có soft delete, sản phẩm bị xóa hoàn toàn
7. **Cascade Delete**: Khi xóa sản phẩm, tất cả tags, colors, sizes liên quan cũng bị xóa
