# Shipping Carriers API Documentation

## Overview

This document describes the Shipping Carriers API endpoints for managing shipping carriers in the PerfectFit admin system. This is a separate controller from Shipping Settings.

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

### 1. Get Carriers by Shipping Type

**GET** `/api/admin/shipping/carriers?shipping_type=domestic`

Get carriers filtered by shipping type (domestic or inter_province).

#### Query Parameters

-   `shipping_type`: required|in:domestic,inter_province

#### Response

```json
{
    "success": true,
    "data": {
        "all_carriers": [
            {
                "id": 1,
                "name": "Ahamove",
                "logo": "https://example.com/logos/ahamove.png",
                "shipping_type": "domestic",
                "is_default": true,
                "is_active": true,
                "information": {
                    "first_km": "2km đầu - 15.709₫",
                    "second_km": "1km tiếp - 19.636₫",
                    "additional_km": "Km tiếp theo - 5.400₫/km",
                    "description": "Giao hàng nhanh trong 30-60 phút, dưới 6km",
                    "delivery_time": "30-60 phút",
                    "max_distance": 6
                },
                "cod": {
                    "free_threshold": "Miễn phí dưới 500.000₫",
                    "rate_1": "Từ 500.000₫-5.000.000₫: 0.6%",
                    "rate_2": "Từ 5.000.000₫-10.000.000₫: 0.88%"
                },
                "created_at": "2025-01-15T00:00:00.000000Z",
                "updated_at": "2025-01-15T00:00:00.000000Z"
            }
        ],
        "active_carriers": [...],
        "default_carrier": {...},
        "shipping_type": "domestic"
    },
    "message": "Carriers retrieved successfully"
}
```

---

### 2. Create Carrier

**POST** `/api/admin/shipping/carrier`

Create a new shipping carrier.

#### Request Body

```json
{
    "name": "Grab Express",
    "logo": "https://example.com/logos/grab.png",
    "shipping_type": "domestic",
    "is_default": false,
    "is_active": true,
    "information": {
        "first_km": "2km đầu - 15.000₫",
        "second_km": "1km tiếp - 18.000₫",
        "additional_km": "Km tiếp theo - 5.000₫/km",
        "description": "Giao hàng nhanh với Grab",
        "delivery_time": "30-45 phút",
        "max_distance": 10
    },
    "cod": {
        "free_threshold": "Miễn phí dưới 500.000₫",
        "rate_1": "Từ 500.000₫-5.000.000₫: 0.5%",
        "rate_2": "Từ 5.000.000₫-10.000.000₫: 0.8%"
    }
}
```

#### Validation Rules

-   `name`: required|string|max:255
-   `logo`: nullable|string|max:255
-   `shipping_type`: required|in:domestic,inter_province
-   `is_default`: boolean
-   `is_active`: boolean
-   `information`: required|array
-   `information.first_km`: required|string|max:255
-   `information.second_km`: required|string|max:255
-   `information.additional_km`: required|string|max:255
-   `information.description`: nullable|string|max:500
-   `information.delivery_time`: nullable|string|max:100
-   `information.max_distance`: nullable|integer|min:1
-   `information.working_hours`: nullable|string|max:100
-   `information.weight_limit`: nullable|integer|min:1
-   `cod`: required|array
-   `cod.free_threshold`: required|string|max:255
-   `cod.rate_1`: required|string|max:255
-   `cod.rate_2`: required|string|max:255

#### Custom Error Messages

All validation rules include Vietnamese error messages for better user experience.

#### Response

```json
{
    "success": true,
    "data": {
        "id": 2,
        "name": "Grab Express",
        "logo": "https://example.com/logos/grab.png",
        "shipping_type": "domestic",
        "is_default": false,
        "is_active": true,
        "information": {...},
        "cod": {...},
        "created_at": "2025-01-15T00:00:00.000000Z",
        "updated_at": "2025-01-15T00:00:00.000000Z"
    },
    "message": "Carrier created successfully"
}
```

---

### 3. Update Carrier

**POST** `/api/admin/shipping/carrier/{id}`

Update an existing carrier.

#### Request Body

```json
{
    "name": "Grab Express Updated",
    "is_default": true,
    "information": {
        "first_km": "2km đầu - 16.000₫"
    }
}
```

#### Validation Rules

Same as create carrier, but all fields are optional (using `sometimes`). Uses `UpdateShippingCarrierRequest` Form Request with Vietnamese error messages.

#### Response

```json
{
    "success": true,
    "data": {
        "id": 2,
        "name": "Grab Express Updated",
        "logo": "https://example.com/logos/grab.png",
        "shipping_type": "domestic",
        "is_default": true,
        "is_active": true,
        "information": {...},
        "cod": {...},
        "created_at": "2025-01-15T00:00:00.000000Z",
        "updated_at": "2025-01-15T00:00:00.000000Z"
    },
    "message": "Carrier updated successfully"
}
```

---

### 4. Set Carrier as Default

**POST** `/api/admin/shipping/carrier/{id}/set-default`

Set a carrier as the default for its shipping type.

#### Response

```json
{
    "success": true,
    "data": {
        "id": 2,
        "name": "Grab Express",
        "logo": "https://example.com/logos/grab.png",
        "shipping_type": "domestic",
        "is_default": true,
        "is_active": true,
        "information": {...},
        "cod": {...},
        "created_at": "2025-01-15T00:00:00.000000Z",
        "updated_at": "2025-01-15T00:00:00.000000Z"
    },
    "message": "Carrier set as default successfully"
}
```

---

### 5. Toggle Carrier Status

**POST** `/api/admin/shipping/carrier/{id}/toggle-status`

Toggle the active status of a carrier.

#### Response

```json
{
    "success": true,
    "data": {
        "id": 2,
        "name": "Grab Express",
        "logo": "https://example.com/logos/grab.png",
        "shipping_type": "domestic",
        "is_default": false,
        "is_active": false,
        "information": {...},
        "cod": {...},
        "created_at": "2025-01-15T00:00:00.000000Z",
        "updated_at": "2025-01-15T00:00:00.000000Z"
    },
    "message": "Carrier status updated successfully"
}
```

---

### 6. Toggle Carrier for Shipping Type

**POST** `/api/admin/shipping/carrier/toggle-for-type`

Toggle carrier active status for a specific shipping type.

#### Request Body

```json
{
    "carrier_id": 2,
    "shipping_type": "domestic",
    "is_active": true
}
```

#### Validation Rules

-   `carrier_id`: required|exists:shipping_carriers,id
-   `shipping_type`: required|in:domestic,inter_province
-   `is_active`: required|boolean

Uses `ToggleCarrierRequest` Form Request with Vietnamese error messages.

#### Response

```json
{
    "success": true,
    "data": {
        "id": 2,
        "name": "Grab Express",
        "logo": "https://example.com/logos/grab.png",
        "shipping_type": "domestic",
        "is_default": false,
        "is_active": true,
        "information": {...},
        "cod": {...},
        "created_at": "2025-01-15T00:00:00.000000Z",
        "updated_at": "2025-01-15T00:00:00.000000Z"
    },
    "message": "Carrier status updated successfully"
}
```

---

### 7. Delete Carrier

**DELETE** `/api/admin/shipping/carrier/{id}`

Delete a carrier.

#### Response

```json
{
    "success": true,
    "data": null,
    "message": "Carrier deleted successfully"
}
```

---

### 8. Calculate Shipping Cost

**POST** `/api/admin/shipping/calculate-cost`

Calculate shipping cost and COD fee for a given distance and order amount.

#### Request Body

```json
{
    "carrier_id": 1,
    "distance": 5.5,
    "order_amount": 1000000
}
```

#### Validation Rules

-   `carrier_id`: required|exists:shipping_carriers,id
-   `distance`: required|numeric|min:0
-   `order_amount`: required|numeric|min:0

Uses `CalculateShippingCostRequest` Form Request with Vietnamese error messages.

#### Response

```json
{
    "success": true,
    "data": {
        "shipping_cost": 64554.00,
        "cod_fee": 6000.00,
        "total_cost": 70554.00,
        "breakdown": {
            "first_km": 31418.00,
            "second_km": 19636.00,
            "additional_km": 13500.00
        },
        "carrier": {
            "id": 1,
            "name": "Ahamove",
            "shipping_type": "domestic",
            "is_default": true,
            "is_active": true,
            "information": {...},
            "cod": {...}
        }
    },
    "message": "Shipping cost calculated successfully"
}
```

---

## Error Responses

### Validation Error (422)

```json
{
    "success": false,
    "message": "Validation failed",
    "errors": {
        "name": ["The name field is required."],
        "information.first_km.distance": [
            "The information.first km.distance field is required."
        ]
    }
}
```

### Not Found Error (404)

```json
{
    "success": false,
    "message": "Failed to update carrier: No query results for model [App\\Models\\ShippingCarrier] 999"
}
```

### Server Error (500)

```json
{
    "success": false,
    "message": "Failed to create carrier: Database connection error"
}
```

---

## Data Structure

### Information JSON Structure

```json
{
    "first_km": "2km đầu - 15.709₫",
    "second_km": "1km tiếp - 19.636₫",
    "additional_km": "Km tiếp theo - 5.400₫/km",
    "description": "Giao hàng nhanh trong 30-60 phút, dưới 6km",
    "delivery_time": "30-60 phút",
    "max_distance": 6,
    "working_hours": "7:00-22:00",
    "weight_limit": 50
}
```

### COD JSON Structure

```json
{
    "free_threshold": "Miễn phí dưới 500.000₫",
    "rate_1": "Từ 500.000₫-5.000.000₫: 0.6%",
    "rate_2": "Từ 5.000.000₫-10.000.000₫: 0.88%"
}
```

---

## Example Usage

### Creating a Domestic Carrier

```bash
curl -X POST "https://api.perfectfit.com/api/admin/shipping/carrier" \
  -H "Authorization: Bearer {token}" \
  -H "Content-Type: application/json" \
  -d '{
    "name": "Ahamove",
    "logo": "https://example.com/logos/ahamove.png",
    "shipping_type": "domestic",
    "is_default": true,
    "is_active": true,
     "information": {
       "first_km": "2km đầu - 15.709₫",
       "second_km": "1km tiếp - 19.636₫",
       "additional_km": "Km tiếp theo - 5.400₫/km",
       "description": "Giao hàng nhanh trong 30-60 phút",
       "delivery_time": "30-60 phút",
       "max_distance": 6
     },
     "cod": {
       "free_threshold": "Miễn phí dưới 500.000₫",
       "rate_1": "Từ 500.000₫-5.000.000₫: 0.6%",
       "rate_2": "Từ 5.000.000₫-10.000.000₫: 0.88%"
     }
  }'
```

### Calculating Shipping Cost

```bash
curl -X POST "https://api.perfectfit.com/api/admin/shipping/calculate-cost" \
  -H "Authorization: Bearer {token}" \
  -H "Content-Type: application/json" \
  -d '{
    "carrier_id": 1,
    "distance": 5.5,
    "order_amount": 1000000
  }'
```

### Getting Domestic Carriers

```bash
curl -X GET "https://api.perfectfit.com/api/admin/shipping/carriers?shipping_type=domestic" \
  -H "Authorization: Bearer {token}"
```

---

## Form Request Classes

The API uses dedicated Form Request classes for validation:

### CreateShippingCarrierRequest

-   Validates carrier creation data
-   Includes comprehensive validation rules for all fields
-   Provides Vietnamese error messages

### UpdateShippingCarrierRequest

-   Validates carrier update data
-   All fields are optional (using `sometimes`)
-   Maintains data integrity during updates

### ToggleCarrierRequest

-   Validates carrier toggle operations
-   Ensures carrier exists and shipping type matches
-   Simple boolean validation for status changes

### CalculateShippingCostRequest

-   Validates cost calculation parameters
-   Ensures carrier exists and numeric values are valid
-   Prevents calculation errors

## Business Logic

### Default Carrier Management

-   Only one carrier can be default per shipping type
-   Setting a carrier as default automatically unsets other defaults of the same type
-   Default carriers are used when customers don't specify a preference

### Carrier Status

-   `is_active`: Controls whether carrier appears in customer options
-   `is_default`: Determines which carrier is selected by default
-   Inactive carriers are hidden from customer selection

### Pricing Calculation

1. **First KM**: Fixed price for first X kilometers
2. **Second KM**: Fixed price for next Y kilometers
3. **Additional KM**: Per-kilometer price for remaining distance

### COD Fee Calculation

1. **Free**: Orders below free threshold
2. **Rate 1**: Percentage fee for orders between thresholds
3. **Rate 2**: Higher percentage fee for larger orders
