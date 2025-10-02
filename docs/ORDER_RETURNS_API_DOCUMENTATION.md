# Order Returns API Documentation

## Tổng quan

API Order Returns cho phép khách hàng yêu cầu trả hàng/hoàn tiền cho đơn hàng đã mua.

## Base URL

```
http://localhost:8000/api
```

## Authentication

Endpoints yêu cầu Bearer Token:
```
Authorization: Bearer {token}
```

## API Endpoints

### User Endpoints

#### 1. Lấy danh sách yêu cầu trả hàng

**GET** `/returns`

#### Response

```json
{
    "success": true,
    "message": "Returns retrieved successfully",
    "data": [
        {
            "id": 1,
            "return_code": "RET-ABC12345",
            "order_id": 123,
            "return_type": "refund",
            "reason": "wrong_size",
            "description": "Sản phẩm quá nhỏ",
            "status": "pending",
            "refund_amount": 250000,
            "created_at": "2025-10-02T10:00:00.000000Z"
        }
    ]
}
```

#### 2. Tạo yêu cầu trả hàng

**POST** `/orders/{orderId}/return`

#### Request Body

```json
{
    "return_type": "refund",
    "reason": "wrong_size",
    "description": "Sản phẩm không vừa kích cỡ, tôi muốn đổi size khác",
    "images": ["image1.jpg", "image2.jpg"]
}
```

**Return Types:**
- `return`: Trả hàng
- `refund`: Hoàn tiền
- `exchange`: Đổi hàng

**Reasons:**
- `damaged`: Sản phẩm bị hư hại
- `wrong_item`: Giao sai sản phẩm
- `wrong_size`: Không vừa kích cỡ
- `not_as_described`: Không như mô tả
- `quality_issue`: Vấn đề chất lượng
- `changed_mind`: Không như mong đợi
- `other`: Lý do khác

#### Response

```json
{
    "success": true,
    "message": "Return request created successfully",
    "data": {
        "id": 1,
        "return_code": "RET-ABC12345",
        "status": "pending"
    }
}
```

#### 3. Xem chi tiết yêu cầu trả hàng

**GET** `/returns/{returnCode}`

#### Response

```json
{
    "success": true,
    "data": {
        "id": 1,
        "return_code": "RET-ABC12345",
        "order_id": 123,
        "return_type": "refund",
        "reason": "wrong_size",
        "description": "Sản phẩm quá nhỏ",
        "images": ["image1.jpg"],
        "status": "approved",
        "admin_notes": "Đã duyệt yêu cầu hoàn tiền",
        "refund_amount": 250000,
        "created_at": "2025-10-02T10:00:00.000000Z",
        "approved_at": "2025-10-02T11:00:00.000000Z",
        "completed_at": null
    }
}
```

#### 4. Hủy yêu cầu trả hàng

**POST** `/returns/{id}/cancel`

**Lưu ý:** Chỉ hủy được khi status = `pending`

### Admin Endpoints

#### 1. Lấy tất cả yêu cầu trả hàng (Admin)

**GET** `/admin/returns`

#### 2. Cập nhật trạng thái yêu cầu (Admin)

**PUT** `/admin/returns/{id}/status`

#### Request Body

```json
{
    "status": "approved",
    "admin_notes": "Đã xác nhận, sẽ hoàn tiền trong 3-5 ngày",
    "refund_amount": 250000
}
```

**Statuses:**
- `pending`: Chờ xử lý
- `approved`: Đã duyệt
- `rejected`: Từ chối
- `processing`: Đang xử lý
- `completed`: Hoàn tất
- `cancelled`: Đã hủy

## Workflow

1. **Khách hàng** tạo yêu cầu trả hàng (status: pending)
2. **Admin** xem và duyệt/từ chối (status: approved/rejected)
3. **Admin** xử lý trả hàng (status: processing)
4. **Admin** hoàn tất (status: completed)

## Features

- ✅ Multiple return types (return/refund/exchange)
- ✅ Upload hình ảnh chứng minh
- ✅ Tracking với return code
- ✅ Admin approval workflow
- ✅ Refund amount tracking
- ✅ Cannot cancel after approval

---

**Order Returns API đã sẵn sàng!** 🔄

