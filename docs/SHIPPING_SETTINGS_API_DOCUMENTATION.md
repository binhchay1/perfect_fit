# Shipping Settings API Documentation

## Overview

This document describes the Shipping Settings API endpoints for managing general shipping configuration in the PerfectFit admin system.

## Base URL

```
/api/admin/shipping
```

## Authentication

All endpoints require admin authentication. Include the Bearer token in the Authorization header:

```
Authorization: Bearer {your_token}
```

---

## Endpoints

### 1. Get Shipping Settings

**GET** `/api/admin/shipping/settings`

Get current shipping settings configuration.

#### Response

```json
{
    "success": true,
    "data": {
        "id": 1,
        "shop_name": "PerfectFit Store",
        "shipping_location": {
            "address": "123 Đường Nguyễn Huệ",
            "ward": "Phường Bến Nghé",
            "district": "Quận 1",
            "city": "Thành phố Hồ Chí Minh",
            "province": "Hồ Chí Minh",
            "postal_code": "700000",
            "country": "Việt Nam",
            "coordinates": {
                "latitude": 10.7769,
                "longitude": 106.7009
            },
            "full_address": "123 Đường Nguyễn Huệ, Phường Bến Nghé, Quận 1, TP.HCM, Việt Nam"
        },
        "phone": "0245354050",
        "enable_domestic_shipping": true,
        "enable_inter_province_shipping": true,
        "created_at": "2025-01-15T00:00:00.000000Z",
        "updated_at": "2025-01-15T00:00:00.000000Z"
    },
    "message": "Shipping settings retrieved successfully"
}
```

---

### 2. Update Shipping Settings

**POST** `/api/admin/shipping/settings`

Update shipping settings configuration.

#### Request Body

```json
{
    "shop_name": "PerfectFit Store",
    "shipping_location": {
        "address": "123 Đường Nguyễn Huệ",
        "ward": "Phường Bến Nghé",
        "district": "Quận 1",
        "city": "Thành phố Hồ Chí Minh",
        "province": "Hồ Chí Minh",
        "postal_code": "700000",
        "country": "Việt Nam",
        "coordinates": {
            "latitude": 10.7769,
            "longitude": 106.7009
        },
        "full_address": "123 Đường Nguyễn Huệ, Phường Bến Nghé, Quận 1, TP.HCM, Việt Nam"
    },
    "phone": "0245354050",
    "enable_domestic_shipping": true,
    "enable_inter_province_shipping": true
}
```

#### Validation Rules

-   `shop_name`: required|string|max:255
-   `shipping_location`: required|array
-   `shipping_location.address`: required|string|max:255
-   `shipping_location.ward`: required|string|max:255
-   `shipping_location.district`: required|string|max:255
-   `shipping_location.city`: required|string|max:255
-   `shipping_location.province`: required|string|max:255
-   `shipping_location.country`: required|string|max:255
-   `shipping_location.coordinates.latitude`: nullable|numeric
-   `shipping_location.coordinates.longitude`: nullable|numeric
-   `phone`: required|string|max:20
-   `enable_domestic_shipping`: required|boolean
-   `enable_inter_province_shipping`: required|boolean

#### Response

```json
{
    "success": true,
    "data": {
        "id": 1,
        "shop_name": "PerfectFit Store",
        "shipping_location": {...},
        "phone": "0245354050",
        "enable_domestic_shipping": true,
        "enable_inter_province_shipping": true,
        "created_at": "2025-01-15T00:00:00.000000Z",
        "updated_at": "2025-01-15T00:00:00.000000Z"
    },
    "message": "Shipping settings updated successfully"
}
```

---

## Error Responses

### Not Found Error (404)

```json
{
    "success": false,
    "message": "Shipping settings not found"
}
```

### Validation Error (422)

```json
{
    "success": false,
    "message": "Validation failed",
    "errors": {
        "shop_name": ["The shop name field is required."],
        "shipping_location.address": [
            "The shipping location.address field is required."
        ]
    }
}
```

### Server Error (500)

```json
{
    "success": false,
    "message": "Failed to update shipping settings: Database connection error"
}
```

---

## Data Structure

### Shipping Location JSON Structure

```json
{
    "address": "123 Đường Nguyễn Huệ",
    "ward": "Phường Bến Nghé",
    "district": "Quận 1",
    "city": "Thành phố Hồ Chí Minh",
    "province": "Hồ Chí Minh",
    "postal_code": "700000",
    "country": "Việt Nam",
    "coordinates": {
        "latitude": 10.7769,
        "longitude": 106.7009
    },
    "full_address": "123 Đường Nguyễn Huệ, Phường Bến Nghé, Quận 1, TP.HCM, Việt Nam"
}
```

---

## Example Usage

### Getting Shipping Settings

```bash
curl -X GET "https://api.perfectfit.com/api/admin/shipping/settings" \
  -H "Authorization: Bearer {token}"
```

### Updating Shipping Settings

```bash
curl -X POST "https://api.perfectfit.com/api/admin/shipping/settings" \
  -H "Authorization: Bearer {token}" \
  -H "Content-Type: application/json" \
  -d '{
    "shop_name": "PerfectFit Store",
    "shipping_location": {
      "address": "123 Đường Nguyễn Huệ",
      "ward": "Phường Bến Nghé",
      "district": "Quận 1",
      "city": "Thành phố Hồ Chí Minh",
      "province": "Hồ Chí Minh",
      "country": "Việt Nam",
      "coordinates": {
        "latitude": 10.7769,
        "longitude": 106.7009
      }
    },
    "phone": "0245354050",
    "enable_domestic_shipping": true,
    "enable_inter_province_shipping": true
  }'
```

---

## Business Logic

### Settings Management

-   Only one shipping settings record exists in the system
-   If no settings exist, a new record is created
-   If settings exist, they are updated
-   Settings control which shipping types are enabled

### Location Data

-   `shipping_location` is stored as JSON for flexibility
-   Coordinates are optional but recommended for distance calculations
-   Full address is auto-generated from components

### Shipping Types

-   `enable_domestic_shipping`: Controls nội thành shipping
-   `enable_inter_province_shipping`: Controls liên tỉnh shipping
-   Both can be enabled/disabled independently

---

---

## Data Structure

### Field Descriptions

-   `shop_name`: Name of the shop
-   `shipping_location`: Detailed address information
-   `phone`: Contact phone number
-   `enable_domestic_shipping`: Enable/disable domestic shipping
-   `enable_inter_province_shipping`: Enable/disable inter-province shipping

### Business Rules

-   Only one shipping settings record can exist
-   Domestic and inter-province shipping can be enabled independently
-   Shipping location is required for all shipping types
