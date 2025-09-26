# 💳 Payment API Documentation

## 📋 Overview

Payment API provides a comprehensive transaction management system supporting multiple payment methods including VNPay, Cash on Delivery, Bank Transfer, and more. The system includes robust transaction tracking, audit trails, and support for refunds and adjustments.

---

## 🔧 Configuration

### Environment Variables

```env
# VNPay Configuration
VNPAY_TMN_CODE="YOUR_TMN_CODE"
VNPAY_HASH_SECRET="YOUR_HASH_SECRET"
VNPAY_URL=https://sandbox.vnpayment.vn/paymentv2/vpcpay.html
VNPAY_RETURN_URL=http://localhost:8000/api/payment/vnpay/callback
VNPAY_API_URL=https://sandbox.vnpayment.vn/merchant_webapi/api/transaction
VNPAY_FRONTEND_REDIRECT_URL=http://localhost:3000/payment/result

### Supported Payment Methods

The system supports multiple payment methods:

| Method | Identifier | Description | Requires Gateway |
|--------|------------|-------------|------------------|
| Cash | `cash` | Cash payment upon delivery | No |
| VNPay | `vnpay` | Online payment gateway | Yes |
| MoMo | `momo` | MoMo e-wallet | Yes |
| Bank Transfer | `bank_transfer` | Bank transfer payment | No |
| Credit Card | `credit_card` | Credit card payment | Yes |
| Debit Card | `debit_card` | Debit card payment | Yes |

### Database Structure

The payment system uses the following database structure:

**Tables:**
- `users` - Customer information
- `orders` - Order data
- `payments` - Payment method and status (includes session management)
- `transactions` - Money flow tracking (payments, refunds, adjustments)
- `payment_callbacks` - Gateway callback logs for auditing
- `payment_logs` - Detailed payment activities

**Key Relationships:**
- User can have many orders
- Order can have many payments
- Payment can have many transactions
- Transactions track money flow (payment, refund, adjustment)

**Payment Session Management:**
- Each payment for online gateways has a session_token for security
- session_expires_at tracks when payment link expires (30 minutes by default)
- session_used tracks if payment session has been used
- System handles session renewal for interrupted payments

### Frontend Integration

After payment processing, users are redirected to the frontend URL with the following parameters:

**Query Parameters:**
- `status` - Payment status: `success`, `failed`, or `error`
- `message` - Human-readable payment message
- `payment_id` - Unique payment identifier
- `order_id` - Order identifier (extracted from VNPay order info)
- `transaction_id` - VNPay transaction number (for successful payments)
- `amount` - Payment amount in VND (for successful payments)

**Frontend Handling Example:**
```javascript
// Handle payment result redirect
const urlParams = new URLSearchParams(window.location.search);
const paymentStatus = urlParams.get('status');
const paymentMessage = urlParams.get('message');
const paymentId = urlParams.get('payment_id');
const orderId = urlParams.get('order_id');

if (paymentStatus === 'success') {
    // Payment successful - show success page
    console.log('Payment successful:', paymentMessage);
    console.log('Payment ID:', paymentId);
    console.log('Order ID:', orderId);
} else {
    // Payment failed - show error page
    console.log('Payment failed:', paymentMessage);
}
```
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

### 1. Create Payment Request

**Endpoint:** `POST /api/payment/create`

**Description:** Creates a payment request for the specified payment method and returns appropriate response.

**Authentication:** Required (Bearer Token)

**Request Body:**

```json
{
    "order_id": 17,
    "payment_method": "vnpay"
}
```

**Parameters:**
| Parameter | Type | Required | Description |
|-----------|------|----------|-------------|
| order_id | integer | Yes | ID of the order to be paid |
| payment_method | string | Yes | Payment method (cash, vnpay, momo, bank_transfer, credit_card, debit_card) |

**Response Examples:**

**Success (200) - Online Gateway (VNPay):**

```json
{
    "success": true,
    "message": "Payment request created successfully",
    "data": {
        "payment_id": 1,
        "transaction_id": 1,
        "payment_url": "https://sandbox.vnpayment.vn/paymentv2/vpcpay.html?vnp_Amount=17160000&vnp_Command=pay&vnp_CreateDate=20250912161530&vnp_CurrCode=VND&vnp_IpAddr=127.0.0.1&vnp_Locale=vn&vnp_OrderInfo=TEST-1757668551&vnp_ReturnUrl=http://localhost:8000/api/payment/vnpay/callback&vnp_TmnCode=1QUQF2FW&vnp_TxnRef=1&vnp_Version=2.1.0&vnp_SecureHash=abc123...",
        "order_id": 17,
        "amount": 17160000,
        "order_number": "TEST-1757668551",
        "payment_method": "vnpay"
    }
}
```

**Success (200) - Cash Payment:**

```json
{
    "success": true,
    "message": "Payment completed successfully",
    "data": {
        "payment_id": 2,
        "transaction_id": 2,
        "order_id": 18,
        "amount": 250000,
        "order_number": "TEST-1757668552",
        "payment_method": "cash",
        "message": "Payment completed successfully"
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

### 2. Get Payment Link (Recover Lost Payment)

**Endpoint:** `GET /api/orders/{order_id}/payment-link`

**Description:** Recovers payment link for interrupted payment flows. Handles cases where user closes app or loses payment URL.

**Authentication:** Required (Bearer Token)

**Path Parameters:**
| Parameter | Type | Required | Description |
|-----------|------|----------|-------------|
| order_id | integer | Yes | ID of the order to recover payment link |

**Response Examples:**

**Success (200) - Valid Payment Link:**

```json
{
    "success": true,
    "message": "Payment link is still valid",
    "data": {
        "status": "valid",
        "payment_id": 1,
        "payment_url": "https://sandbox.vnpayment.vn/paymentv2/vpcpay.html?vnp_Amount=17160000&vnp_Command=pay&vnp_CreateDate=20250912161530&vnp_CurrCode=VND&vnp_IpAddr=127.0.0.1&vnp_Locale=vn&vnp_OrderInfo=TEST-1757668551&vnp_ReturnUrl=http://localhost:8000/api/payment/vnpay/callback&vnp_TmnCode=1QUQF2FW&vnp_TxnRef=1&vnp_Version=2.1.0&vnp_SecureHash=abc123...",
        "order_id": 17,
        "amount": 17160000,
        "order_number": "TEST-1757668551",
        "payment_method": "vnpay",
        "expires_at": "2025-09-12T16:45:30.000000Z"
    }
}
```

**Success (200) - Renewed Payment Link (Expired):**

```json
{
    "success": true,
    "message": "Payment link has been renewed due to expiration",
    "data": {
        "status": "renewed",
        "payment_id": 2,
        "payment_url": "https://sandbox.vnpayment.vn/paymentv2/vpcpay.html?vnp_Amount=17160000&vnp_Command=pay&vnp_CreateDate=20250912162000&vnp_CurrCode=VND&vnp_IpAddr=127.0.0.1&vnp_Locale=vn&vnp_OrderInfo=TEST-1757668551&vnp_ReturnUrl=http://localhost:8000/api/payment/vnpay/callback&vnp_TmnCode=1QUQF2FW&vnp_TxnRef=2&vnp_Version=2.1.0&vnp_SecureHash=xyz456...",
        "order_id": 17,
        "amount": 17160000,
        "order_number": "TEST-1757668551",
        "payment_method": "vnpay",
        "expires_at": "2025-09-12T16:50:00.000000Z"
    }
}
```

**Success (200) - Payment Already Completed:**

```json
{
    "success": true,
    "message": "Payment already completed",
    "data": {
        "status": "paid",
        "payment_id": 1,
        "order_id": 17,
        "order_number": "TEST-1757668551",
        "amount": 17160000,
        "payment_method": "vnpay"
    }
}
```

**Error (404) - Order Not Found:**

```json
{
    "success": false,
    "message": "Order not found or access denied",
    "data": null
}
```

**Error (400) - No Pending Payment:**

```json
{
    "success": false,
    "message": "No pending payment found for this order",
    "data": null
}
```

**Error (400) - Payment Method Not Supported:**

```json
{
    "success": false,
    "message": "Payment method does not support link recovery",
    "data": null
}
```

---

### 3. Get Payment Status

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
        "order_number": "TEST-1757668551",
        "amount": 17160000,
        "payment_method": "vnpay",
        "payment_provider": "vnpay",
        "status": "paid",
        "transaction_id": "VNP1234567890",
        "external_payment_id": "VNP1234567890",
        "paid_at": "2025-09-12T16:15:30.000000Z",
        "order_status": "confirmed",
        "total_paid": 17160000,
        "total_refunded": 0,
        "transactions": [
            {
                "id": 1,
                "type": "payment",
                "status": "completed",
                "amount": 17160000,
                "gateway_transaction_id": "VNP1234567890",
                "processed_at": "2025-09-12T16:15:30.000000Z",
                "description": "Giao dịch thành công"
            }
        ]
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

**Description:** Handles VNPay payment callback. This endpoint is called by VNPay after payment processing and redirects to the frontend with payment result.

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

**Behavior:**
- Verifies VNPay signature for security
- Processes payment result (success/failure)
- Updates payment and order status in database
- Redirects to frontend URL with payment result parameters

**Frontend Redirect Parameters:**
| Parameter | Type | Description |
|-----------|------|-------------|
| status | string | Payment status (success, failed, error) |
| message | string | Payment message |
| payment_id | string | Payment ID |
| order_id | string | Order ID extracted from order info |
| transaction_id | string | VNPay transaction number |
| amount | string | Payment amount |

**Example Redirect URL:**
```
http://localhost:3000/payment/result?status=success&message=Giao+dịch+thành+công&payment_id=1&order_id=TEST-1757668551&transaction_id=VNP1234567890&amount=17160000
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

// Recover payment link for interrupted payments
const recoverPaymentLink = async (orderId) => {
    const response = await fetch(`/api/orders/${orderId}/payment-link`, {
        headers: {
            Authorization: `Bearer ${token}`,
        },
    });

    const data = await response.json();
    if (data.success) {
        if (data.data.status === 'valid') {
            // Payment link is still valid, redirect to it
            window.location.href = data.data.payment_url;
        } else if (data.data.status === 'renewed') {
            // Payment link renewed, redirect to new URL
            window.location.href = data.data.payment_url;
        } else if (data.data.status === 'paid') {
            // Payment already completed
            console.log('Payment already completed');
            // Handle success case
        } else {
            // Handle other cases (failed, cancelled, etc.)
            console.log('Payment status:', data.data.status);
        }
    } else {
        console.error('Failed to recover payment link:', data.message);
    }
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

-   `users` - Customer information
-   `orders` - Order information
-   `order_items` - Order items
-   `payments` - Payment records (method and status)
-   `transactions` - Money flow tracking (payments, refunds, adjustments)
-   `payment_callbacks` - Gateway callback logs for auditing
-   `payment_logs` - Payment activity logs

---

## 🔄 Payment Flow

### Complete Payment Flow

```
1. User creates order
2. User requests payment (specifies payment method)
3. System creates payment record
4. System creates transaction record
5. System generates payment session with token and expiration (for online gateways)
6. If online gateway (VNPay, MoMo, etc.):
   - System generates gateway URL
   - User redirected to gateway
   - User completes payment
   - Gateway calls callback
   - System verifies signature
   - System updates payment and transaction status
   - System updates order status
   - System deducts stock
   - System redirects to frontend with result
7. If offline payment (Cash, Bank Transfer):
   - System marks as pending or completed
   - Admin processes payment manually
   - System updates status when confirmed
```

### Payment Link Recovery Flow (Interrupted Payments)

```
1. User creates order and starts payment process
2. User gets redirected to payment gateway but closes app/loses link
3. User returns to app and requests payment link recovery
4. System checks payment session status:
   - If session valid: Returns existing payment URL
   - If session expired: Creates new payment session and returns new URL
   - If already paid: Returns payment completion info
   - If failed/cancelled: Returns status and guidance
5. User can continue with payment using recovered/renewed link
```

### Frontend Integration Flow

```
1. User completes payment on gateway
2. Gateway processes payment and calls callback
3. System updates database and redirects to frontend
4. Frontend receives redirect with payment result
5. Frontend displays success/failure page
6. User can check payment status via API if needed
```

### Transaction Tracking

```
1. Every payment creates a transaction record
2. Transactions track money flow (payment, refund, adjustment)
3. Multiple transactions can be linked to one payment
4. Transactions provide audit trail for all money movement
5. Callbacks are logged separately for debugging
```

### Key Benefits of New Structure

- **Separation of Concerns**: Payments vs Transactions
- **Multiple Payment Methods**: Easy to add new gateways
- **Audit Trail**: Complete money flow tracking
- **Flexibility**: Support for refunds, adjustments, chargebacks
- **Scalability**: Can handle complex payment scenarios

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
