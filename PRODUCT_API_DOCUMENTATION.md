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

---

## 🗑️ Bulk Delete Products API

### Tổng quan

API để admin xóa nhiều sản phẩm cùng lúc với validation và error handling.

### 6. Xóa nhiều sản phẩm cùng lúc (Admin)

```http
DELETE /api/admin/products/bulk-delete
Authorization: Bearer {admin_access_token}
Content-Type: application/json
```

#### Request Body

```json
{
    "product_ids": [1, 2, 3, 4, 5]
}
```

#### Parameters

| Parameter       | Type    | Required | Description                    |
| --------------- | ------- | -------- | ------------------------------ |
| `product_ids`   | array   | Yes      | Array of product IDs to delete |
| `product_ids.*` | integer | Yes      | Each product ID must exist     |

#### Validation Rules

-   `product_ids`: Required, array, minimum 1 item
-   `product_ids.*`: Integer, must exist in products table

#### Response Examples

##### Success Response

```json
{
    "success": true,
    "message": "Successfully deleted 3 product(s), 2 failed",
    "data": {
        "deleted_count": 3,
        "total_requested": 5,
        "failed_deletions": [
            {
                "id": 2,
                "name": "Nike Air Max",
                "reason": "Product has existing orders"
            },
            {
                "id": 4,
                "reason": "Failed to delete: Product not found"
            }
        ]
    }
}
```

##### Partial Success Response

```json
{
    "success": true,
    "message": "Successfully deleted 2 product(s)",
    "data": {
        "deleted_count": 2,
        "total_requested": 3,
        "failed_deletions": [
            {
                "id": 1,
                "name": "Adidas Ultraboost",
                "reason": "Product has existing orders"
            }
        ]
    }
}
```

##### Complete Failure Response

```json
{
    "success": false,
    "message": "No products were deleted",
    "data": {
        "deleted_count": 0,
        "total_requested": 2,
        "failed_deletions": [
            {
                "id": 1,
                "name": "Nike Air Max",
                "reason": "Product has existing orders"
            },
            {
                "id": 2,
                "name": "Adidas Ultraboost",
                "reason": "Product has existing orders"
            }
        ]
    }
}
```

##### Validation Error Response

```json
{
    "success": false,
    "message": "Validation error",
    "errors": {
        "product_ids": ["The product ids field is required."],
        "product_ids.0": ["The product_ids.0 must be an integer."]
    }
}
```

#### Authentication & Authorization

-   **Required**: Bearer Token (Admin only)
-   **Middleware**: `auth:api`, `admin`
-   **Access**: Only admin users can bulk delete products

#### Business Logic

##### Deletion Rules

1. **Order Check**: Products with existing orders cannot be deleted
2. **Soft Delete**: Uses Laravel soft delete (deleted_at timestamp)
3. **Transaction**: All deletions are wrapped in database transaction
4. **Partial Success**: Some products can be deleted even if others fail

##### Error Handling

1. **Validation Errors**: Invalid product IDs or missing data
2. **Business Logic Errors**: Products with existing orders
3. **System Errors**: Database connection issues, etc.

#### Response Fields

| Field              | Type    | Description                                     |
| ------------------ | ------- | ----------------------------------------------- |
| `deleted_count`    | integer | Number of successfully deleted products         |
| `total_requested`  | integer | Total number of products requested for deletion |
| `failed_deletions` | array   | Array of failed deletion details                |

##### Failed Deletion Object

| Field    | Type    | Description                      |
| -------- | ------- | -------------------------------- |
| `id`     | integer | Product ID that failed to delete |
| `name`   | string  | Product name (if available)      |
| `reason` | string  | Reason for deletion failure      |

#### Use Cases

##### 1. Admin Bulk Delete Interface

```javascript
// Frontend: Admin selects multiple products and deletes them
const bulkDeleteProducts = async (productIds) => {
    const response = await fetch("/api/admin/products/bulk-delete", {
        method: "DELETE",
        headers: {
            Authorization: `Bearer ${adminToken}`,
            "Content-Type": "application/json",
        },
        body: JSON.stringify({
            product_ids: productIds,
        }),
    });

    const result = await response.json();

    if (result.success) {
        console.log(`Deleted ${result.data.deleted_count} products`);
        if (result.data.failed_deletions.length > 0) {
            console.log("Failed deletions:", result.data.failed_deletions);
        }
    }
};
```

##### 2. Error Handling

```javascript
// Handle different response scenarios
const handleBulkDeleteResponse = (response) => {
    if (response.success) {
        if (response.data.deleted_count === response.data.total_requested) {
            // All products deleted successfully
            showSuccess("All products deleted successfully");
        } else {
            // Partial success
            showWarning(
                `Deleted ${response.data.deleted_count} products. Some failed.`
            );
            displayFailedDeletions(response.data.failed_deletions);
        }
    } else {
        // Complete failure
        showError("No products were deleted");
        displayFailedDeletions(response.data.failed_deletions);
    }
};
```

##### 3. Validation

```javascript
// Frontend validation before API call
const validateBulkDelete = (productIds) => {
    if (!productIds || productIds.length === 0) {
        throw new Error("Please select at least one product");
    }

    if (productIds.length > 100) {
        throw new Error("Cannot delete more than 100 products at once");
    }

    return true;
};
```

#### Integration Notes

##### Database Transaction

-   All deletions are wrapped in a database transaction
-   If any deletion fails, the transaction is rolled back
-   Individual product deletions are handled separately within the transaction

##### Soft Delete

-   Products are soft deleted (deleted_at timestamp set)
-   Related data (colors, sizes, images) may need separate handling
-   Consider implementing cascade soft delete for related models

##### Performance Considerations

-   Large bulk operations may take time
-   Consider implementing background job for very large deletions
-   Monitor database performance during bulk operations

#### Testing Examples

##### cURL Example

```bash
curl -X DELETE "http://localhost:8000/api/admin/products/bulk-delete" \
  -H "Authorization: Bearer YOUR_ADMIN_TOKEN" \
  -H "Content-Type: application/json" \
  -d '{
    "product_ids": [1, 2, 3, 4, 5]
  }'
```

##### Postman Collection

```json
{
    "name": "Bulk Delete Products",
    "request": {
        "method": "DELETE",
        "header": [
            {
                "key": "Authorization",
                "value": "Bearer {{admin_token}}"
            },
            {
                "key": "Content-Type",
                "value": "application/json"
            }
        ],
        "body": {
            "mode": "raw",
            "raw": "{\n    \"product_ids\": [1, 2, 3]\n}"
        },
        "url": {
            "raw": "{{base_url}}/api/admin/products/bulk-delete",
            "host": ["{{base_url}}"],
            "path": ["api", "admin", "products", "bulk-delete"]
        }
    }
}
```
