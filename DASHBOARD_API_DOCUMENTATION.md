# 📊 Dashboard & Analytics API Documentation

## Tổng quan

Dashboard & Analytics API cung cấp các thống kê và phân tích dữ liệu cho admin để quản lý hiệu quả hệ thống e-commerce.

## 🔐 Authentication

Tất cả endpoints đều yêu cầu:

-   **Bearer Token**: `Authorization: Bearer {token}`
-   **Admin Role**: Chỉ admin mới có quyền truy cập

## 📡 API Endpoints

### 1. Dashboard Overview

**GET** `/api/admin/dashboard/overview`

Lấy thống kê tổng quan của hệ thống.

#### Response

```json
{
    "success": true,
    "message": "Dashboard overview retrieved successfully",
    "data": {
        "orders": {
            "total": 150,
            "pending": 10,
            "confirmed": 5,
            "processing": 8,
            "shipped": 12,
            "delivered": 100,
            "cancelled": 10,
            "refunded": 5,
            "today": 5
        },
        "users": {
            "total": 120,
            "active": 100,
            "inactive": 20,
            "admin": 5,
            "regular": 115,
            "verified": 80,
            "unverified": 40
        },
        "products": {
            "total": 50,
            "active": 45,
            "inactive": 5,
            "low_stock": 8
        },
        "revenue": {
            "total": 50000000,
            "today": 2000000,
            "average_order_value": 333333.33
        }
    }
}
```

---

### 2. Revenue Analytics

**GET** `/api/admin/dashboard/revenue-analytics?period=30days`

Lấy dữ liệu doanh thu theo thời gian.

#### Parameters

-   `period` (optional): `7days`, `30days`, `90days`, `1year` (default: `30days`)

#### Response

```json
{
    "success": true,
    "message": "Revenue analytics retrieved successfully",
    "data": {
        "period": "30days",
        "data": [
            {
                "date": "2024-01-01",
                "revenue": 1500000,
                "orders": 5
            },
            {
                "date": "2024-01-02",
                "revenue": 2000000,
                "orders": 7
            }
        ],
        "summary": {
            "total_revenue": 45000000,
            "total_orders": 150,
            "average_daily_revenue": 1500000
        }
    }
}
```

---

### 3. Order Analytics

**GET** `/api/admin/dashboard/order-analytics?period=30days`

Lấy thống kê đơn hàng chi tiết.

#### Parameters

-   `period` (optional): `7days`, `30days`, `90days`, `1year` (default: `30days`)

#### Response

```json
{
    "success": true,
    "message": "Order analytics retrieved successfully",
    "data": {
        "status_breakdown": {
            "pending": 10,
            "confirmed": 5,
            "processing": 8,
            "shipped": 12,
            "delivered": 100,
            "cancelled": 10,
            "refunded": 5
        },
        "conversion_rate": 66.67,
        "average_processing_time": 2.5
    }
}
```

---

### 4. Top Selling Products

**GET** `/api/admin/dashboard/top-products?limit=10&period=30days`

Lấy danh sách sản phẩm bán chạy nhất.

#### Parameters

-   `limit` (optional): Số lượng sản phẩm (default: `10`)
-   `period` (optional): `7days`, `30days`, `90days`, `1year` (default: `30days`)

#### Response

```json
{
    "success": true,
    "message": "Top selling products retrieved successfully",
    "data": [
        {
            "product_id": 1,
            "product_name": "Nike Air Max 270",
            "total_sold": 25,
            "total_revenue": 87500000,
            "average_price": 3500000
        },
        {
            "product_id": 2,
            "product_name": "Adidas Ultraboost 22",
            "total_sold": 20,
            "total_revenue": 84000000,
            "average_price": 4200000
        }
    ]
}
```

---

### 5. Customer Analytics

**GET** `/api/admin/dashboard/customer-analytics?period=30days`

Lấy thống kê khách hàng.

#### Parameters

-   `period` (optional): `7days`, `30days`, `90days`, `1year` (default: `30days`)

#### Response

```json
{
    "success": true,
    "message": "Customer analytics retrieved successfully",
    "data": {
        "new_customers": 15,
        "returning_customers": 25,
        "customer_retention_rate": 75.5,
        "average_customer_value": 416666.67
    }
}
```

---

### 6. Brand Analytics

**GET** `/api/admin/dashboard/brand-analytics?period=30days`

Lấy thống kê hiệu suất thương hiệu.

#### Parameters

-   `period` (optional): `7days`, `30days`, `90days`, `1year` (default: `30days`)

#### Response

```json
{
    "success": true,
    "message": "Brand analytics retrieved successfully",
    "data": [
        {
            "id": 1,
            "name": "Nike",
            "total_orders": 45,
            "total_quantity": 120,
            "total_revenue": 150000000
        },
        {
            "id": 2,
            "name": "Adidas",
            "total_orders": 35,
            "total_quantity": 90,
            "total_revenue": 120000000
        }
    ]
}
```

---

## 📊 Dashboard Features

### 1. **Overview Dashboard**

-   Tổng quan đơn hàng, users, sản phẩm, doanh thu
-   Thống kê real-time
-   Các chỉ số quan trọng

### 2. **Revenue Analytics**

-   Biểu đồ doanh thu theo thời gian
-   So sánh theo ngày/tuần/tháng/năm
-   Tổng doanh thu và trung bình

### 3. **Order Analytics**

-   Phân tích trạng thái đơn hàng
-   Tỷ lệ chuyển đổi
-   Thời gian xử lý trung bình

### 4. **Product Analytics**

-   Sản phẩm bán chạy nhất
-   Doanh thu theo sản phẩm
-   Giá trung bình

### 5. **Customer Analytics**

-   Khách hàng mới vs khách hàng cũ
-   Tỷ lệ giữ chân khách hàng
-   Giá trị khách hàng trung bình

### 6. **Brand Analytics**

-   Hiệu suất theo thương hiệu
-   Doanh thu và số lượng bán
-   So sánh các thương hiệu

---

## 🔧 Technical Details

### Data Sources

-   **Orders**: Từ bảng `orders` và `order_items`
-   **Users**: Từ bảng `users`
-   **Products**: Từ bảng `products`, `product_colors`, `product_sizes`
-   **Brands**: Từ bảng `brands`

### Performance

-   Sử dụng database indexes để tối ưu query
-   Cache kết quả cho các thống kê không thay đổi thường xuyên
-   Pagination cho các danh sách dài

### Error Handling

-   `401 Unauthorized`: Chưa đăng nhập hoặc không phải admin
-   `403 Forbidden`: Không có quyền truy cập
-   `500 Internal Server Error`: Lỗi server

---

## 📈 Use Cases

### 1. **Admin Dashboard**

```javascript
// Lấy overview cho dashboard chính
GET /api/admin/dashboard/overview

// Lấy biểu đồ doanh thu 30 ngày
GET /api/admin/dashboard/revenue-analytics?period=30days

// Lấy top 5 sản phẩm bán chạy
GET /api/admin/dashboard/top-products?limit=5
```

### 2. **Reports & Analytics**

```javascript
// Báo cáo doanh thu theo tháng
GET /api/admin/dashboard/revenue-analytics?period=1year

// Phân tích khách hàng
GET /api/admin/dashboard/customer-analytics?period=90days

// Hiệu suất thương hiệu
GET /api/admin/dashboard/brand-analytics?period=30days
```

---

## 🚀 Future Enhancements

1. **Real-time Updates**: WebSocket cho cập nhật real-time
2. **Export Reports**: Xuất báo cáo ra PDF/Excel
3. **Custom Date Range**: Chọn khoảng thời gian tùy chỉnh
4. **Advanced Filters**: Lọc theo brand, category, region
5. **Predictive Analytics**: Dự đoán xu hướng bán hàng

---

**Dashboard & Analytics API đã sẵn sàng để sử dụng!** 📊✨
