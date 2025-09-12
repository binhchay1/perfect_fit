# Order Management API Documentation

## Tổng quan

API Order Management cung cấp các chức năng quản lý đơn hàng trong hệ thống e-commerce. Hệ thống cho phép user tạo đơn hàng từ giỏ hàng, theo dõi trạng thái đơn hàng, và admin quản lý toàn bộ quy trình đơn hàng.

## Cấu trúc Database

### Bảng `orders`

-   `id`: Primary key
-   `user_id`: Foreign key đến bảng users
-   `order_number`: Mã đơn hàng duy nhất (format: ORD-YYYYMMDD-XXXXXX)
-   `status`: Trạng thái đơn hàng (pending, confirmed, processing, shipped, delivered, cancelled, refunded)
-   `payment_status`: Trạng thái thanh toán (pending, paid, failed, refunded)
-   `payment_method`: Phương thức thanh toán (cod, bank_transfer, credit_card, e_wallet)
-   `subtotal`: Tổng tiền sản phẩm
-   `tax_amount`: Thuế VAT (10%)
-   `shipping_fee`: Phí vận chuyển
-   `discount_amount`: Số tiền giảm giá
-   `total_amount`: Tổng tiền cuối cùng
-   `shipping_address`: Địa chỉ giao hàng (JSON)
-   `billing_address`: Địa chỉ thanh toán (JSON)
-   `notes`: Ghi chú đơn hàng
-   `tracking_number`: Mã vận đơn
-   `shipped_at`: Thời gian giao hàng
-   `delivered_at`: Thời gian nhận hàng
-   `created_at`, `updated_at`: Timestamps

### Bảng `order_items`

-   `id`: Primary key
-   `order_id`: Foreign key đến bảng orders
-   `product_id`: Foreign key đến bảng products
-   `product_color_id`: Foreign key đến bảng product_colors
-   `product_size_id`: Foreign key đến bảng product_sizes
-   `product_name`: Tên sản phẩm lúc mua (backup)
-   `product_sku`: SKU lúc mua (backup)
-   `color_name`: Tên màu lúc mua (backup)
-   `size_name`: Tên size lúc mua (backup)
-   `quantity`: Số lượng
-   `unit_price`: Giá đơn vị lúc mua
-   `total_price`: Tổng tiền = quantity \* unit_price
-   `created_at`, `updated_at`: Timestamps

## API Endpoints

Tất cả endpoints đều yêu cầu authentication (`auth:api` middleware).

### 1. Lấy danh sách đơn hàng của user

```
GET /api/orders
```

**Query Parameters:**

-   `per_page`: Số lượng items per page (optional, default: 15)

**Response:**

```json
{
    "success": true,
    "message": "Orders retrieved successfully",
    "data": {
        "current_page": 1,
        "data": [
            {
                "id": 1,
                "user_id": 1,
                "order_number": "ORD-20250910-000001",
                "status": "pending",
                "payment_status": "pending",
                "payment_method": null,
                "subtotal": "299.00",
                "tax_amount": "29.90",
                "shipping_fee": "30.00",
                "discount_amount": "0.00",
                "total_amount": "358.90",
                "shipping_address": {
                    "name": "Nguyễn Văn A",
                    "phone": "0123456789",
                    "address": "123 Đường ABC",
                    "city": "Hồ Chí Minh",
                    "district": "Quận 1",
                    "ward": "Phường Bến Nghé",
                    "postal_code": "700000"
                },
                "billing_address": null,
                "notes": "Giao hàng vào buổi chiều",
                "tracking_number": null,
                "shipped_at": null,
                "delivered_at": null,
                "created_at": "2025-09-10T10:00:00.000000Z",
                "updated_at": "2025-09-10T10:00:00.000000Z",
                "order_items": [
                    {
                        "id": 1,
                        "order_id": 1,
                        "product_id": 1,
                        "product_color_id": 1,
                        "product_size_id": 1,
                        "product_name": "Áo thun nam Nike",
                        "product_sku": "PRD0001REDM",
                        "color_name": "Đỏ",
                        "size_name": "M",
                        "quantity": 2,
                        "unit_price": "149.50",
                        "total_price": "299.00",
                        "product": {
                            "id": 1,
                            "name": "Áo thun nam Nike",
                            "brand": {
                                "id": 1,
                                "name": "Nike"
                            }
                        }
                    }
                ]
            }
        ],
        "first_page_url": "http://localhost/api/orders?page=1",
        "from": 1,
        "last_page": 1,
        "last_page_url": "http://localhost/api/orders?page=1",
        "links": [...],
        "next_page_url": null,
        "path": "http://localhost/api/orders",
        "per_page": 15,
        "prev_page_url": null,
        "to": 1,
        "total": 1
    }
}
```

### 2. Tạo đơn hàng từ giỏ hàng

```
POST /api/orders
```

**Request Body:**

```json
{
    "shipping_address": {
        "name": "Nguyễn Văn A",
        "phone": "0123456789",
        "address": "123 Đường ABC",
        "city": "Hồ Chí Minh",
        "district": "Quận 1",
        "ward": "Phường Bến Nghé",
        "postal_code": "700000"
    },
    "billing_address": {
        "name": "Nguyễn Văn A",
        "phone": "0123456789",
        "address": "123 Đường ABC",
        "city": "Hồ Chí Minh",
        "district": "Quận 1",
        "ward": "Phường Bến Nghé",
        "postal_code": "700000"
    },
    "payment_method": "cod",
    "discount_amount": 0,
    "notes": "Giao hàng vào buổi chiều"
}
```

**Validation (StoreOrderRequest):**

-   `shipping_address`: required, array
-   `shipping_address.name`: required, string, max:255
-   `shipping_address.phone`: required, string, max:20, regex:/^[0-9+\-\s()]+$/
-   `shipping_address.address`: required, string, max:500
-   `shipping_address.city`: required, string, max:100
-   `shipping_address.district`: required, string, max:100
-   `shipping_address.ward`: required, string, max:100
-   `shipping_address.postal_code`: nullable, string, max:10
-   `billing_address`: nullable, array
-   `billing_address.*`: required_with:billing_address (conditional validation)
-   `payment_method`: nullable, in:cod,bank_transfer,credit_card,e_wallet
-   `discount_amount`: nullable, numeric, min:0, max:999999.99
-   `notes`: nullable, string, max:1000

**Custom Messages:** Vietnamese error messages for better UX

**Business Logic:**

1.  Kiểm tra giỏ hàng có items không
2.  Validate địa chỉ giao hàng
3.  Tính toán các khoản phí:
    -   Subtotal: Tổng tiền từ cart
    -   Tax: VAT 10%
    -   Shipping fee: Phí vận chuyển
    -   Discount: Giảm giá (nếu có)
    -   Total: Tổng tiền cuối cùng
4.  Tạo order và order_items
5.  Xóa cart sau khi tạo order thành công

**Response:**

```json
{
    "success": true,
    "message": "Order created successfully",
    "data": {
        "id": 1,
        "user_id": 1,
        "order_number": "ORD-20250910-000001",
        "status": "pending",
        "payment_status": "pending",
        "payment_method": null,
        "subtotal": "299.00",
        "tax_amount": "29.90",
        "shipping_fee": "30.00",
        "discount_amount": "0.00",
        "total_amount": "358.90",
        "shipping_address": {...},
        "billing_address": {...},
        "notes": "Giao hàng vào buổi chiều",
        "created_at": "2025-09-10T10:00:00.000000Z",
        "updated_at": "2025-09-10T10:00:00.000000Z",
        "order_items": [...]
    }
}
```

### 3. Xem chi tiết đơn hàng

```
GET /api/orders/{id}
```

**Path Parameters:**

-   `id`: ID của đơn hàng

**Response:**

```json
{
    "success": true,
    "message": "Order retrieved successfully",
    "data": {
        "id": 1,
        "user_id": 1,
        "order_number": "ORD-20250910-000001",
        "status": "pending",
        "payment_status": "pending",
        "payment_method": null,
        "subtotal": "299.00",
        "tax_amount": "29.90",
        "shipping_fee": "30.00",
        "discount_amount": "0.00",
        "total_amount": "358.90",
        "shipping_address": {...},
        "billing_address": {...},
        "notes": "Giao hàng vào buổi chiều",
        "tracking_number": null,
        "shipped_at": null,
        "delivered_at": null,
        "created_at": "2025-09-10T10:00:00.000000Z",
        "updated_at": "2025-09-10T10:00:00.000000Z",
        "order_items": [...]
    }
}
```

### 4. Hủy đơn hàng

```
PUT /api/orders/{id}/cancel
```

**Path Parameters:**

-   `id`: ID của đơn hàng

**Business Logic:**

-   Chỉ có thể hủy đơn hàng ở trạng thái: pending, confirmed, processing
-   Không thể hủy đơn hàng đã shipped
-   Khi hủy đơn hàng, sẽ restore stock

**Response:**

```json
{
    "success": true,
    "message": "Order cancelled successfully",
    "data": {
        "id": 1,
        "status": "cancelled",
        "updated_at": "2025-09-10T10:30:00.000000Z"
    }
}
```

### 5. Theo dõi đơn hàng

```
GET /api/orders/{id}/tracking
```

**Path Parameters:**

-   `id`: ID của đơn hàng

**Response:**

```json
{
    "success": true,
    "message": "Tracking information retrieved successfully",
    "data": {
        "order_number": "ORD-20250910-000001",
        "status": "shipped",
        "tracking_number": "VN123456789",
        "shipped_at": "2025-09-10T14:00:00.000000Z",
        "delivered_at": null,
        "shipping_address": {
            "name": "Nguyễn Văn A",
            "phone": "0123456789",
            "address": "123 Đường ABC",
            "city": "Hồ Chí Minh",
            "district": "Quận 1",
            "ward": "Phường Bến Nghé",
            "postal_code": "700000"
        }
    }
}
```

## Models và Relationships

### Order Model

```php
// Relationships
public function user(): BelongsTo
public function orderItems(): HasMany

// Methods
public static function generateOrderNumber(): string
public function canBeCancelled(): bool
public function canBeRefunded(): bool

// Fillable fields
protected $fillable = [
    'user_id', 'order_number', 'status', 'payment_status',
    'payment_method', 'subtotal', 'tax_amount', 'shipping_fee',
    'discount_amount', 'total_amount', 'shipping_address',
    'billing_address', 'notes', 'tracking_number',
    'shipped_at', 'delivered_at'
];
```

### OrderItem Model

```php
// Relationships
public function order(): BelongsTo
public function product(): BelongsTo
public function productColor(): BelongsTo
public function productSize(): BelongsTo

// Methods
public function calculateTotalPrice(): float

// Fillable fields
protected $fillable = [
    'order_id', 'product_id', 'product_color_id', 'product_size_id',
    'product_name', 'product_sku', 'color_name', 'size_name',
    'quantity', 'unit_price', 'total_price'
];
```

### User Model

```php
// Relationships
public function orders(): HasMany
```

## Repository Pattern

### OrderRepository

```php
// Core methods
public function createOrder($orderData)
public function getUserCart($userId)
public function clearUserCart($userId)
public function getForUser($userId, $perPage = 15)
public function getByIdForUser($orderId, $userId)
public function updateStatus($orderId, $status, $userId = null)
public function cancelOrder($orderId, $userId)
public function getStatistics($dateRange = null)
public function getPendingOrders()
public function getOrdersByStatus($status, $perPage = 15)
```

### OrderItemRepository

```php
// Core methods
public function getForOrder($orderId)
public function getProductSales($productId, $dateRange = null)
public function getTopSellingProducts($limit = 10, $dateRange = null)
public function getWithProductDetails($orderId)
```

## Order Status Flow

```
PENDING → CONFIRMED → PROCESSING → SHIPPED → DELIVERED
    ↓
CANCELLED (có thể cancel ở bất kỳ trạng thái nào trước SHIPPED)
    ↓
REFUNDED (sau khi DELIVERED)
```

### Status Descriptions

-   **PENDING**: Đơn hàng mới tạo, chờ xác nhận
-   **CONFIRMED**: Admin đã xác nhận, bắt đầu xử lý
-   **PROCESSING**: Đang chuẩn bị hàng
-   **SHIPPED**: Đã giao cho đơn vị vận chuyển
-   **DELIVERED**: Khách hàng đã nhận hàng
-   **CANCELLED**: Đơn hàng bị hủy
-   **REFUNDED**: Đơn hàng đã được hoàn tiền

## Payment Status Flow

```
PENDING → PAID → FAILED → REFUNDED
```

### Payment Status Descriptions

-   **PENDING**: Chưa thanh toán
-   **PAID**: Đã thanh toán thành công
-   **FAILED**: Thanh toán thất bại
-   **REFUNDED**: Đã hoàn tiền

## Business Logic

### Order Creation Flow

1.  **Validation Phase**:

    -   StoreOrderRequest tự động validate input
    -   Kiểm tra user đã login (middleware)
    -   Kiểm tra cart có items không
    -   Custom Vietnamese error messages

2.  **Calculation Phase**:

    -   Tính subtotal từ cart items
    -   Tính tax (VAT 10%)
    -   Tính shipping fee (dựa trên địa chỉ)
    -   Áp dụng discount (nếu có)
    -   Tính total_amount

3.  **Order Creation Phase**:

    -   Controller xử lý business logic (tính toán, preparation)
    -   Repository tạo order record
    -   Controller tạo order_items trực tiếp từ cart_items
    -   Backup product info (tên, giá, SKU)
    -   Clear cart

4.  **Notification Phase**:
    -   Gửi email xác nhận đơn hàng
    -   Gửi thông báo cho admin

### Stock Management Integration

-   **Khi tạo order**: Kiểm tra stock → Trừ stock ngay lập tức
-   **Khi cancel order**: Restore stock (hoàn trả)
-   **Khi refund**: Restore stock (hoàn trả)
-   **Stock validation**: Kiểm tra đủ hàng trước khi tạo order

## Tính năng bảo mật

1.  **Authentication Required**: Tất cả endpoints đều yêu cầu user đăng nhập
2.  **User Isolation**: Mỗi user chỉ có thể truy cập đơn hàng của chính mình
3.  **Order Ownership**: Kiểm tra ownership trước khi thao tác
4.  **Status Validation**: Kiểm tra quyền chuyển trạng thái
5.  **Stock Validation**: Kiểm tra tồn kho trước khi tạo đơn hàng

## Error Handling

Hệ thống trả về các lỗi phổ biến:

-   `401 Unauthorized`: Chưa đăng nhập
-   `400 Bad Request`: Cart trống, không thể hủy đơn hàng
-   `404 Not Found`: Đơn hàng không tồn tại
-   `422 Unprocessable Entity`: Validation errors

## Lưu ý

1.  **Order Number**: Tự động generate theo format ORD-YYYYMMDD-XXXXXX
2.  **Data Backup**: OrderItem lưu thông tin sản phẩm lúc mua
3.  **Cart Integration**: Tự động xóa cart sau khi tạo order
4.  **Stock Management**: Tự động cập nhật stock
5.  **Tax Calculation**: VAT 10% tự động tính
6.  **Shipping Fee**: Có thể customize theo địa chỉ
7.  **Status Flow**: Kiểm tra quyền chuyển trạng thái
8.  **Repository Pattern**: Logic xử lý data được tách biệt khỏi controller

## Ví dụ sử dụng

### Frontend Integration:

```javascript
// Tạo đơn hàng từ giỏ hàng
const createOrder = async (orderData) => {
    const response = await fetch("/api/orders", {
        method: "POST",
        headers: {
            Authorization: `Bearer ${token}`,
            "Content-Type": "application/json",
        },
        body: JSON.stringify(orderData),
    });
    return response.json();
};

// Lấy danh sách đơn hàng
const getOrders = async (page = 1) => {
    const response = await fetch(`/api/orders?page=${page}`, {
        headers: {
            Authorization: `Bearer ${token}`,
        },
    });
    return response.json();
};

// Xem chi tiết đơn hàng
const getOrderDetail = async (orderId) => {
    const response = await fetch(`/api/orders/${orderId}`, {
        headers: {
            Authorization: `Bearer ${token}`,
        },
    });
    return response.json();
};

// Hủy đơn hàng
const cancelOrder = async (orderId) => {
    const response = await fetch(`/api/orders/${orderId}/cancel`, {
        method: "PUT",
        headers: {
            Authorization: `Bearer ${token}`,
        },
    });
    return response.json();
};

// Theo dõi đơn hàng
const trackOrder = async (orderId) => {
    const response = await fetch(`/api/orders/${orderId}/tracking`, {
        headers: {
            Authorization: `Bearer ${token}`,
        },
    });
    return response.json();
};
```

## Tương lai

Hệ thống Order Management có thể được mở rộng với:

1.  **Admin Management**: Quản lý đơn hàng cho admin
2.  **Payment Integration**: Tích hợp cổng thanh toán
3.  **Shipping Integration**: Tích hợp đơn vị vận chuyển
4.  **Notification System**: Hệ thống thông báo
5.  **Analytics**: Phân tích doanh thu và bán hàng
6.  **Return/Refund**: Hệ thống trả hàng và hoàn tiền
7.  **Multi-currency**: Hỗ trợ nhiều loại tiền
8.  **International Shipping**: Giao hàng quốc tế

---

## 📦 Purchased Products API

### Tổng quan

API để lấy danh sách sản phẩm đã mua của user (giống như lịch sử mua hàng trên Shopee).

### 5. Lấy danh sách sản phẩm đã mua

```http
GET /api/purchased-products
Authorization: Bearer {access_token}
```

#### Parameters

| Parameter  | Type    | Default   | Description                                    |
| ---------- | ------- | --------- | ---------------------------------------------- |
| `per_page` | integer | 15        | Số lượng items per page                        |
| `status`   | string  | delivered | Trạng thái đơn hàng (delivered, shipped, etc.) |

#### Example Request

```http
GET /api/purchased-products?per_page=10&status=delivered
Authorization: Bearer eyJ0eXAiOiJKV1QiLCJhbGciOiJSUzI1NiJ9...
```

#### Example Response

```json
{
    "success": true,
    "message": "Purchased products retrieved successfully",
    "data": {
        "current_page": 1,
        "data": [
            {
                "id": 1,
                "product_id": 5,
                "product_color_id": 1,
                "product_size_id": 1,
                "product_name": "Nike Air Max",
                "product_sku": "NIK-NIKE-5-DEN-S",
                "color_name": "Den",
                "size_name": "S",
                "quantity": 1,
                "unit_price": 299.99,
                "total_price": 299.99,
                "order_id": 3,
                "created_at": "2025-01-01T09:00:00.000000Z",
                "product": {
                    "id": 5,
                    "name": "Nike Air Max",
                    "slug": "nike-air-max",
                    "images": "[\"nike-air-max-1.jpg\", \"nike-air-max-2.jpg\"]",
                    "price": 299.99
                },
                "product_color": {
                    "id": 1,
                    "color_name": "Den"
                },
                "product_size": {
                    "id": 1,
                    "size_name": "S"
                },
                "order": {
                    "id": 3,
                    "order_number": "ORD-20250101-ABC123",
                    "status": "delivered",
                    "created_at": "2025-01-01T09:00:00.000000Z"
                }
            }
        ],
        "first_page_url": "http://localhost:8000/api/purchased-products?page=1",
        "from": 1,
        "last_page": 1,
        "last_page_url": "http://localhost:8000/api/purchased-products?page=1",
        "links": [
            {
                "url": null,
                "label": "&laquo; Previous",
                "active": false
            },
            {
                "url": "http://localhost:8000/api/purchased-products?page=1",
                "label": "1",
                "active": true
            },
            {
                "url": null,
                "label": "Next &raquo;",
                "active": false
            }
        ],
        "next_page_url": null,
        "path": "http://localhost:8000/api/purchased-products",
        "per_page": 15,
        "prev_page_url": null,
        "to": 1,
        "total": 1
    }
}
```

#### Response Fields

##### Order Item Fields

| Field              | Type     | Description               |
| ------------------ | -------- | ------------------------- |
| `id`               | integer  | Order item ID             |
| `product_id`       | integer  | Product ID                |
| `product_color_id` | integer  | Product color ID          |
| `product_size_id`  | integer  | Product size ID           |
| `product_name`     | string   | Product name (snapshot)   |
| `product_sku`      | string   | Product SKU               |
| `color_name`       | string   | Color name                |
| `size_name`        | string   | Size name                 |
| `quantity`         | integer  | Quantity purchased        |
| `unit_price`       | decimal  | Price per unit            |
| `total_price`      | decimal  | Total price for this item |
| `order_id`         | integer  | Order ID                  |
| `created_at`       | datetime | Purchase date             |

##### Related Data

| Field           | Type   | Description                                          |
| --------------- | ------ | ---------------------------------------------------- |
| `product`       | object | Product details (id, name, slug, images, price)      |
| `product_color` | object | Color details (id, color_name)                       |
| `product_size`  | object | Size details (id, size_name)                         |
| `order`         | object | Order details (id, order_number, status, created_at) |

#### Use Cases

##### 1. View Purchase History

```javascript
// Frontend: Display user's purchased products
const response = await fetch("/api/purchased-products", {
    headers: {
        Authorization: `Bearer ${token}`,
        Accept: "application/json",
    },
});
const data = await response.json();
```

##### 2. Reorder Products

```javascript
// Frontend: Add product to cart for reorder
const reorderProduct = async (productId, colorId, sizeId, quantity) => {
    await fetch("/api/cart", {
        method: "POST",
        headers: {
            Authorization: `Bearer ${token}`,
            "Content-Type": "application/json",
        },
        body: JSON.stringify({
            product_id: productId,
            product_color_id: colorId,
            product_size_id: sizeId,
            quantity: quantity,
        }),
    });
};
```

##### 3. Filter by Status

```javascript
// Get only delivered products
const deliveredProducts = await fetch(
    "/api/purchased-products?status=delivered"
);
```

#### Notes

1. **Default Status**: Only shows `delivered` orders by default
2. **Pagination**: Supports Laravel pagination
3. **Relationships**: Includes product, color, size, and order data
4. **Security**: Users can only see their own purchased products
5. **Performance**: Optimized queries with select specific fields
