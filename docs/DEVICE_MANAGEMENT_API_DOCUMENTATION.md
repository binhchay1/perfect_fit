# Device Management API Documentation

## Tổng quan

API Device Management cung cấp các chức năng quản lý thiết bị cho mobile app, bao gồm đăng ký thiết bị, quản lý session, và bảo mật đa thiết bị.

## Base URL

```
http://localhost:8000/api
```

## Authentication

Tất cả endpoints đều yêu cầu Bearer Token trong header:
```
Authorization: Bearer {token}
```

## Swagger Documentation

Để xem và test API trực tiếp qua Swagger UI, truy cập:

**🔗 http://localhost:8000/docs**

Tại đây bạn có thể:
- Xem chi tiết tất cả endpoints
- Test API trực tiếp
- Xem request/response examples
- Authenticate với Bearer token

## API Endpoints

### 1. Đăng nhập với Device Information

**POST** `/auth/login`

#### Request Body

```json
{
    "email": "user@example.com",
    "password": "password123",
    "device_id": "unique-device-id-123",
    "device_name": "My iPhone",
    "device_type": "iOS",
    "device_model": "iPhone 14",
    "os_version": "iOS 17.0",
    "app_version": "1.0.0",
    "fcm_token": "firebase-token-here",
    "remember_device": true
}
```

#### Response

```json
{
    "success": true,
    "message": "Login successful",
    "data": {
        "user": {
            "id": 1,
            "name": "John Doe",
            "email": "user@example.com",
            "role": "user",
            "status": 1
        },
        "token": "access-token-here",
        "token_type": "Bearer",
        "expires_at": "2025-01-30T10:00:00.000000Z",
        "device": {
            "id": 1,
            "device_name": "My iPhone",
            "is_trusted": true
        }
    }
}
```

### 2. Lấy danh sách thiết bị

**GET** `/devices`

#### Headers

```
Authorization: Bearer {token}
X-Device-ID: {device_id}
```

#### Response

```json
{
    "success": true,
    "message": "Devices retrieved successfully",
    "data": [
        {
            "id": 1,
            "device_name": "My iPhone",
            "device_type": "iOS",
            "device_model": "iPhone 14",
            "os_version": "iOS 17.0",
            "app_version": "1.0.0",
            "is_active": true,
            "is_trusted": true,
            "last_used_at": "2025-01-15T10:00:00.000000Z",
            "is_current": true
        },
        {
            "id": 2,
            "device_name": "Samsung Galaxy",
            "device_type": "Android",
            "device_model": "Galaxy S23",
            "os_version": "Android 14",
            "app_version": "1.0.0",
            "is_active": true,
            "is_trusted": false,
            "last_used_at": "2025-01-10T15:30:00.000000Z",
            "is_current": false
        }
    ]
}
```

### 3. Cập nhật tên thiết bị

**PUT** `/devices/{deviceId}/name`

#### Request Body

```json
{
    "device_name": "My New Device Name"
}
```

#### Response

```json
{
    "success": true,
    "message": "Device name updated successfully",
    "data": {
        "id": 1,
        "device_name": "My New Device Name"
    }
}
```

### 4. Toggle trạng thái trusted của thiết bị

**POST** `/devices/{deviceId}/trust`

#### Response

```json
{
    "success": true,
    "message": "Device marked as trusted",
    "data": {
        "id": 1,
        "is_trusted": true
    }
}
```

### 5. Thu hồi thiết bị

**DELETE** `/devices/{deviceId}`

#### Response

```json
{
    "success": true,
    "message": "Device revoked successfully"
}
```

### 6. Thu hồi tất cả thiết bị khác (giữ lại thiết bị hiện tại)

**POST** `/devices/revoke-others`

#### Headers

```
Authorization: Bearer {token}
X-Device-ID: {current_device_id}
```

#### Response

```json
{
    "success": true,
    "message": "All other devices revoked successfully"
}
```

### 7. Cập nhật FCM Token

**PUT** `/devices/fcm-token`

#### Request Body

```json
{
    "fcm_token": "new-firebase-token",
    "device_id": "device-id-123"
}
```

#### Response

```json
{
    "success": true,
    "message": "FCM token updated successfully"
}
```

## Tính năng Session Management

### 1. **Auto-Login cho Trusted Devices**

- Thiết bị đầu tiên hoặc thiết bị được đánh dấu `remember_device: true` sẽ tự động được trusted
- Trusted devices không cần login lại trong vòng 30 ngày
- Token có thời hạn 15 ngày, tự động refresh khi cần

### 2. **Device Isolation**

- Mỗi device có unique `device_id`
- Token được tạo với tên device để dễ quản lý
- Có thể revoke token của specific device

### 3. **Security Features**

- **Device Tracking**: Lưu IP, User Agent, last used time
- **Trusted Device**: Thiết bị tin cậy không cần login lại
- **Remote Revoke**: Có thể thu hồi thiết bị từ xa
- **Current Device Protection**: Không thể revoke thiết bị hiện tại

### 4. **Push Notification Support**

- Lưu FCM token cho mỗi device
- Hỗ trợ gửi notification đến specific device
- Cập nhật FCM token khi cần

## Business Logic

### 1. **Device Registration Flow**

1. User login với device information
2. System check device_id đã tồn tại chưa
3. Nếu chưa có: tạo mới device
4. Nếu đã có: update thông tin device
5. Auto-trust nếu là device đầu tiên hoặc `remember_device: true`
6. Tạo token với device name
7. Update last_used_at

### 2. **Session Persistence**

- **Trusted Device**: Không cần login lại trong 30 ngày
- **Regular Device**: Cần login lại sau 15 ngày (token expiration)
- **Token Refresh**: Có thể refresh token mà không cần login lại

### 3. **Multi-Device Management**

- User có thể đăng nhập trên nhiều device
- Có thể xem danh sách tất cả devices
- Có thể revoke specific device hoặc tất cả devices khác
- Current device được bảo vệ khỏi việc revoke

## Error Handling

### 400 Bad Request

```json
{
    "success": false,
    "message": "Cannot revoke current device"
}
```

### 404 Not Found

```json
{
    "success": false,
    "message": "Device not found"
}
```

### 422 Validation Error

```json
{
    "success": false,
    "message": "Validation error",
    "errors": {
        "device_name": ["Device name is required."]
    }
}
```

## Frontend Integration

### 1. **Login với Device Info**

```javascript
const login = async (credentials) => {
    const deviceInfo = await getDeviceInfo(); // Get device info from mobile
    
    const response = await fetch('/api/auth/login', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
        },
        body: JSON.stringify({
            ...credentials,
            device_id: deviceInfo.deviceId,
            device_name: deviceInfo.deviceName,
            device_type: deviceInfo.platform,
            device_model: deviceInfo.model,
            os_version: deviceInfo.osVersion,
            app_version: deviceInfo.appVersion,
            fcm_token: deviceInfo.fcmToken,
            remember_device: true, // User choice
        }),
    });
    
    return response.json();
};
```

### 2. **Auto-Login Check**

```javascript
const checkAutoLogin = async () => {
    const token = await getStoredToken();
    if (!token) return false;
    
    try {
        const response = await fetch('/api/me', {
            headers: {
                'Authorization': `Bearer ${token}`,
                'X-Device-ID': getDeviceId(),
            },
        });
        
        return response.ok;
    } catch (error) {
        return false;
    }
};
```

### 3. **Device Management UI**

```javascript
const getDevices = async () => {
    const response = await fetch('/api/devices', {
        headers: {
            'Authorization': `Bearer ${token}`,
            'X-Device-ID': getDeviceId(),
        },
    });
    
    return response.json();
};

const revokeDevice = async (deviceId) => {
    const response = await fetch(`/api/devices/${deviceId}`, {
        method: 'DELETE',
        headers: {
            'Authorization': `Bearer ${token}`,
        },
    });
    
    return response.json();
};
```

## Architecture

Hệ thống Device Management được xây dựng theo kiến trúc sạch và tuân theo các nguyên tắc SOLID:

### 1. **Enum Layer** (`app/Enums/UserDevice.php`)

Định nghĩa các constants và device types:

```php
class UserDevice
{
    public const DEVICE_TYPE_IOS = 'ios';
    public const DEVICE_TYPE_ANDROID = 'android';
    public const DEVICE_TYPE_WEB = 'web';
    public const DEVICE_TYPE_DESKTOP = 'desktop';
    public const DEVICE_TYPE_TABLET = 'tablet';
    
    public const STATUS_ACTIVE = true;
    public const STATUS_INACTIVE = false;
    
    public const TRUSTED = true;
    public const UNTRUSTED = false;
    
    public const RECENTLY_USED_DAYS = 30;
}
```

### 2. **Model Layer** (`app/Models/UserDevice.php`)

Chỉ chứa định nghĩa table structure và relationships:

```php
final class UserDevice extends Model
{
    protected $fillable = [
        'user_id', 'device_id', 'device_name',
        'device_type', 'device_model', 'os_version',
        'app_version', 'fcm_token', 'ip_address',
        'user_agent', 'last_used_at', 'is_active', 'is_trusted',
    ];
    
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
```

### 3. **Repository Layer** (`app/Repositories/UserDeviceRepository.php`)

Xử lý tất cả database queries:

```php
class UserDeviceRepository extends BaseRepository
{
    public function findByUserAndDeviceId(User $user, string $deviceId);
    public function getUserDevices(User $user);
    public function getActiveDevices(User $user);
    public function getTrustedDevices(User $user);
    public function markAsTrusted(UserDevice $device);
    public function deactivateOtherDevices(User $user, string $currentDeviceId);
    // ... other query methods
}
```

### 4. **Service Layer** (`app/Services/UserDeviceService.php`)

Chứa business logic:

```php
final class UserDeviceService
{
    public function registerOrUpdateDevice(User $user, Request $request): UserDevice;
    public function getUserDevices(User $user): Collection;
    public function updateDeviceName(User $user, int $deviceId, string $name): ?UserDevice;
    public function toggleTrust(User $user, int $deviceId): ?array;
    public function revokeDevice(User $user, int $deviceId, ?string $currentDeviceId): ?array;
    public function revokeAllOtherDevices(User $user, string $currentDeviceId): bool;
    public function updateFcmToken(User $user, string $deviceId, string $fcmToken): ?UserDevice;
}
```

### 5. **Controller Layer** (`app/Http/Controllers/API/DeviceController.php`)

Xử lý HTTP requests và responses:

```php
final class DeviceController extends Controller
{
    public function __construct(
        private readonly UserDeviceService $deviceService
    ) {}
    
    public function index(Request $request): JsonResponse;
    public function updateName(Request $request, int $deviceId): JsonResponse;
    public function toggleTrust(Request $request, int $deviceId): JsonResponse;
    public function revoke(Request $request, int $deviceId): JsonResponse;
    public function revokeAllOthers(Request $request): JsonResponse;
    public function updateFcmToken(Request $request): JsonResponse;
}
```

### Architecture Flow

```
Request → Controller → Service → Repository → Model → Database
         ↓             ↓          ↓
      Validation   Business    Query
      Response     Logic       Logic
```

**Ưu điểm:**
- ✅ Separation of Concerns
- ✅ Testability cao
- ✅ Dễ bảo trì và mở rộng
- ✅ Code reusability
- ✅ Type safety với strict typing

## Database Schema

```sql
CREATE TABLE user_devices (
    id BIGINT PRIMARY KEY AUTO_INCREMENT,
    user_id BIGINT NOT NULL,
    device_id VARCHAR(255) UNIQUE NOT NULL,
    device_name VARCHAR(255) NULL,
    device_type VARCHAR(50) NULL,
    device_model VARCHAR(100) NULL,
    os_version VARCHAR(50) NULL,
    app_version VARCHAR(20) NULL,
    fcm_token VARCHAR(255) NULL,
    ip_address VARCHAR(45) NULL,
    user_agent TEXT NULL,
    last_used_at TIMESTAMP NULL,
    is_active BOOLEAN DEFAULT TRUE,
    is_trusted BOOLEAN DEFAULT FALSE,
    created_at TIMESTAMP NULL,
    updated_at TIMESTAMP NULL,
    
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    INDEX idx_user_devices_user_id (user_id),
    INDEX idx_user_devices_device_id (device_id),
    INDEX idx_user_devices_active (is_active),
    INDEX idx_user_devices_trusted (is_trusted)
);
```

## Security Best Practices

1. **Device ID Generation**: Sử dụng UUID hoặc unique identifier
2. **Token Management**: Token có thời hạn và có thể revoke
3. **IP Tracking**: Lưu IP để detect suspicious activity
4. **User Agent**: Track browser/app version
5. **FCM Security**: Encrypt FCM tokens
6. **Rate Limiting**: Limit login attempts per device
7. **Audit Log**: Log all device activities

## Testing

### 1. **Test Device Registration**

```bash
# Login with device info
curl -X POST http://localhost:8000/api/auth/login \
  -H "Content-Type: application/json" \
  -d '{
    "email": "user@example.com",
    "password": "password123",
    "device_id": "test-device-123",
    "device_name": "Test Device",
    "device_type": "iOS",
    "remember_device": true
  }'
```

### 2. **Test Device Management**

```bash
# Get devices
curl -X GET http://localhost:8000/api/devices \
  -H "Authorization: Bearer {token}" \
  -H "X-Device-ID: test-device-123"

# Update device name
curl -X PUT http://localhost:8000/api/devices/1/name \
  -H "Authorization: Bearer {token}" \
  -H "Content-Type: application/json" \
  -d '{"device_name": "Updated Device Name"}'
```

---

**Device Management API đã sẵn sàng để sử dụng!** 📱✨

