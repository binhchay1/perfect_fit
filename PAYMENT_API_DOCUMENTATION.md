# 💳 Payment API Documentation

## 📋 Overview

Payment API provides endpoints for handling VNPay payment integration, including payment creation, callback processing, and status checking.

---

## 🔧 Configuration

### Environment Variables

```env
# VNPay Configuration
VNPAY_TMN_CODE=your_sandbox_tmn_code
VNPAY_HASH_SECRET=your_sandbox_hash_secret
VNPAY_URL=https://sandbox.vnpayment.vn/paymentv2/vpcpay.html
VNPAY_RETURN_URL=http://localhost:8000/api/payment/vnpay/callback
VNPAY_API_URL=https://sandbox.vnpayment.vn/merchant_webapi/api/transaction
```

### Test Card Information

```
Card Number: 9704198526191432198
CVV: 123
Expire Date: 12/25
OTP: 123456
```

---

## 🚀 API Endpoints

### 1. Create VNPay Payment

**Endpoint:** `POST /api/payment/vnpay/create`

**Description:** Creates a VNPay payment request and returns payment URL for user to complete payment.

**Authentication:** Required (Bearer Token)

**Request Body:**

```json
{
    "order_id": 17
}
```

**Parameters:**
| Parameter | Type | Required | Description |
|-----------|------|----------|-------------|
| order_id | integer | Yes | ID of the order to be paid |

**Response Examples:**

**Success (200):**

```json
{
    "success": true,
    "message": "Payment request created successfully",
    "data": {
        "payment_id": 1,
        "payment_url": "https://sandbox.vnpayment.vn/paymentv2/vpcpay.html?vnp_Amount=17160000&vnp_Command=pay&vnp_CreateDate=20250912161530&vnp_CurrCode=VND&vnp_IpAddr=127.0.0.1&vnp_Locale=vn&vnp_OrderInfo=TEST-1757668551&vnp_ReturnUrl=http://localhost:8000/api/payment/vnpay/callback&vnp_TmnCode=1QUQF2FW&vnp_TxnRef=1&vnp_Version=2.1.0&vnp_SecureHash=abc123...",
        "order_id": 17,
        "amount": 17160000,
        "order_number": "TEST-1757668551"
    }
}
```

**Error (400):**

```json
{
    "success": false,
    "message": "Order is not in pending status",
    "data": null
}
```

**Error (404):**

```json
{
    "success": false,
    "message": "Order not found or access denied",
    "data": null
}
```

---

### 2. Get Payment Status

**Endpoint:** `GET /api/payment/status`

**Description:** Retrieves the current status of a payment.

**Authentication:** Required (Bearer Token)

**Query Parameters:**
| Parameter | Type | Required | Description |
|-----------|------|----------|-------------|
| payment_id | integer | Yes | ID of the payment to check |

**Response Examples:**

**Success (200):**

```json
{
    "success": true,
    "message": "Payment status retrieved successfully",
    "data": {
        "payment_id": 1,
        "order_id": 17,
        "amount": 17160000,
        "payment_method": "vnpay",
        "status": "paid",
        "transaction_id": "VNP1234567890",
        "paid_at": "2025-09-12T16:15:30.000000Z",
        "order_status": "confirmed",
        "order_number": "TEST-1757668551"
    }
}
```

**Error (404):**

```json
{
    "success": false,
    "message": "Payment not found or access denied",
    "data": null
}
```

---

### 3. VNPay Callback (Internal)

**Endpoint:** `GET /api/payment/vnpay/callback`

**Description:** Handles VNPay payment callback. This endpoint is called by VNPay after payment processing.

**Authentication:** Not required (Public endpoint)

**Query Parameters:**
| Parameter | Type | Description |
|-----------|------|-------------|
| vnp_Amount | string | Payment amount in VND |
| vnp_BankCode | string | Bank code |
| vnp_BankTranNo | string | Bank transaction number |
| vnp_CardType | string | Card type |
| vnp_OrderInfo | string | Order information |
| vnp_PayDate | string | Payment date (YYYYMMDDHHMMSS) |
| vnp_ResponseCode | string | Response code (00 = success) |
| vnp_TmnCode | string | Merchant code |
| vnp_TransactionNo | string | VNPay transaction number |
| vnp_TxnRef | string | Payment reference (Payment ID) |
| vnp_SecureHash | string | Security hash |

**Response Examples:**

**Success (200):**

```json
{
    "success": true,
    "message": "Payment callback processed successfully",
    "data": {
        "payment_id": 1,
        "order_id": 17,
        "status": "paid",
        "message": "Giao dịch thành công",
        "transaction_id": "VNP1234567890"
    }
}
```

**Error (400):**

```json
{
    "success": false,
    "message": "Invalid signature",
    "data": null
}
```

---

## 📊 Payment Statuses

### Payment Status

| Status   | Description                                       |
| -------- | ------------------------------------------------- |
| pending  | Payment request created, waiting for user payment |
| paid     | Payment completed successfully                    |
| failed   | Payment failed or cancelled                       |
| refunded | Payment has been refunded                         |

### Order Status

| Status     | Description                         |
| ---------- | ----------------------------------- |
| pending    | Order created, waiting for payment  |
| confirmed  | Payment successful, order confirmed |
| processing | Order being processed               |
| shipped    | Order shipped                       |
| delivered  | Order delivered                     |
| cancelled  | Order cancelled                     |

---

## 🔒 Security

### Signature Verification

All VNPay callbacks are verified using HMAC-SHA512 signature to ensure data integrity and authenticity.

### Amount Validation

Payment amounts are validated to prevent tampering and ensure consistency.

### Access Control

-   Payment creation and status checking require user authentication
-   Callback endpoint is public but protected by signature verification

---

## 🧪 Testing

### Test Page

Access the test page at: `http://localhost:8000/payment/test`

### Test Flow

1. **Create Order**: Use order creation API
2. **Create Payment**: Call payment creation API
3. **Test Payment**: Use VNPay sandbox with test card
4. **Check Status**: Verify payment status update

### Test Card

```
Card Number: 9704198526191432198
CVV: 123
Expire Date: 12/25
OTP: 123456
```

---

## 📱 Integration Examples

### JavaScript (Frontend)

```javascript
// Create payment
const createPayment = async (orderId) => {
    const response = await fetch("/api/payment/vnpay/create", {
        method: "POST",
        headers: {
            Authorization: `Bearer ${token}`,
            "Content-Type": "application/json",
        },
        body: JSON.stringify({ order_id: orderId }),
    });

    const data = await response.json();
    if (data.success) {
        // Redirect to VNPay
        window.location.href = data.data.payment_url;
    }
};

// Check payment status
const checkPaymentStatus = async (paymentId) => {
    const response = await fetch(
        `/api/payment/status?payment_id=${paymentId}`,
        {
            headers: {
                Authorization: `Bearer ${token}`,
            },
        }
    );

    const data = await response.json();
    return data.data;
};
```

### PHP (Backend)

```php
// Create payment
$response = Http::withHeaders([
    'Authorization' => 'Bearer ' . $token,
    'Content-Type' => 'application/json'
])->post('/api/payment/vnpay/create', [
    'order_id' => $orderId
]);

$data = $response->json();
if ($data['success']) {
    $paymentUrl = $data['data']['payment_url'];
    // Redirect user to payment URL
}
```

---

## 🚨 Error Handling

### Common Error Codes

| Code | Description      | Solution                   |
| ---- | ---------------- | -------------------------- |
| 400  | Bad Request      | Check request parameters   |
| 401  | Unauthorized     | Provide valid Bearer token |
| 404  | Not Found        | Check order/payment ID     |
| 422  | Validation Error | Check request validation   |
| 500  | Server Error     | Check server logs          |

### VNPay Response Codes

| Code | Description                                                      |
| ---- | ---------------------------------------------------------------- |
| 00   | Giao dịch thành công                                             |
| 07   | Trừ tiền thành công, giao dịch bị nghi ngờ                       |
| 09   | Giao dịch không thành công do thẻ/tài khoản chưa đăng ký dịch vụ |
| 10   | Xác thực thông tin thẻ/tài khoản không đúng                      |
| 11   | Đã hết hạn chờ thanh toán                                        |
| 12   | Giao dịch bị hủy                                                 |
| 24   | Giao dịch không thành công do hết hạn chờ thanh toán             |

---

## 📈 Monitoring

### Logs

Payment activities are logged in:

-   `storage/logs/laravel.log` - General application logs
-   `payments` table - Payment records
-   `payment_logs` table - Detailed payment activities

### Database Tables

-   `payments` - Payment records
-   `payment_logs` - Payment activity logs
-   `orders` - Order information
-   `order_items` - Order items

---

## 🔄 Payment Flow

```
1. User creates order
2. User requests payment
3. System creates payment record
4. System generates VNPay URL
5. User redirected to VNPay
6. User completes payment
7. VNPay calls callback
8. System verifies signature
9. System updates payment status
10. System updates order status
11. System deducts stock (if successful)
12. System sends notifications
```

---

## 📞 Support

For technical support or questions about Payment API:

-   Check application logs for detailed error information
-   Verify VNPay configuration and credentials
-   Test with sandbox environment first
-   Contact development team for assistance

---

**Last Updated:** September 12, 2025  
**Version:** 1.0.0
