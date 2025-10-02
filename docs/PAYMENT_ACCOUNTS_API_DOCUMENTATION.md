# Payment Accounts API Documentation

## Tổng quan

API Payment Accounts cho phép admin/shop quản lý các tài khoản ngân hàng để nhận thanh toán từ khách hàng.

## Base URL

```
http://localhost:8000/api
```

## Authentication

Tất cả endpoints yêu cầu Bearer Token và quyền admin:
```
Authorization: Bearer {token}
```

## API Endpoints

### 1. Lấy danh sách tài khoản thanh toán

**GET** `/admin/payment-accounts`

#### Response

```json
{
    "success": true,
    "message": "Payment accounts retrieved successfully",
    "data": [
        {
            "id": 1,
            "bank_name": "Vietcombank",
            "account_number": "**** **** 6557",
            "account_number_full": "1234567890",
            "account_holder_name": "Nguyễn Văn A",
            "bank_branch": "Chi nhánh HCM",
            "account_type": "savings",
            "is_default": true,
            "is_active": true,
            "created_at": "2025-10-02T10:00:00.000000Z"
        }
    ]
}
```

### 2. Thêm tài khoản thanh toán

**POST** `/admin/payment-accounts`

#### Request Body

```json
{
    "bank_name": "Vietcombank",
    "account_number": "1234567890",
    "account_holder_name": "Nguyễn Văn A",
    "bank_branch": "Chi nhánh HCM",
    "swift_code": "BFTVVNVX",
    "account_type": "savings",
    "is_default": false,
    "notes": "Tài khoản chính"
}
```

#### Response

```json
{
    "success": true,
    "message": "Payment account created successfully",
    "data": {
        "id": 1,
        "bank_name": "Vietcombank",
        "account_number": "**** **** 6557",
        "account_holder_name": "Nguyễn Văn A",
        "is_default": false
    }
}
```

### 3. Cập nhật tài khoản thanh toán

**PUT** `/admin/payment-accounts/{id}`

#### Request Body

```json
{
    "bank_name": "BIDV",
    "account_number": "9876543210",
    "account_holder_name": "Nguyễn Văn B"
}
```

### 4. Xóa tài khoản thanh toán

**DELETE** `/admin/payment-accounts/{id}`

**Lưu ý:** Không thể xóa tài khoản mặc định

### 5. Đặt làm tài khoản mặc định

**POST** `/admin/payment-accounts/{id}/set-default`

### 6. Bật/Tắt tài khoản

**POST** `/admin/payment-accounts/{id}/toggle-status`

## Danh sách ngân hàng Việt Nam

- Vietcombank
- BIDV
- VietinBank
- Agribank
- Techcombank
- ACB
- MB
- VPBank
- TPBank
- Sacombank
- HDBank
- VIB
- SHB
- OCB
- MSB

---

**Payment Accounts API đã sẵn sàng!** 🏦

