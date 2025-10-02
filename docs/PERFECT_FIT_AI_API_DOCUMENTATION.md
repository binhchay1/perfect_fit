# Perfect Fit AI API Documentation

## Tổng quan

Perfect Fit AI là hệ thống gợi ý size sản phẩm dựa trên AI, giúp khách hàng chọn đúng size ngay lần đầu.

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

### 1. Lấy thông tin số đo cơ thể

**GET** `/user/body-measurements`

#### Response

```json
{
    "success": true,
    "message": "Measurements retrieved successfully",
    "data": {
        "gender": "female",
        "height": 158,
        "weight": 47,
        "chest": 90,
        "waist": 60,
        "hips": 90,
        "height_unit": "cm",
        "weight_unit": "kg",
        "measurement_unit": "cm",
        "preferred_fit": "regular"
    }
}
```

### 2. Lưu số đo cơ thể

**POST** `/user/body-measurements`

#### Request Body

```json
{
    "gender": "female",
    "height": 158,
    "weight": 47,
    "chest": 90,
    "waist": 60,
    "hips": 90,
    "thigh": 50,
    "shoulder": 38,
    "preferred_fit": "regular",
    "height_unit": "cm",
    "weight_unit": "kg",
    "measurement_unit": "cm"
}
```

### 3. Gợi ý size từ ảnh (AI)

**POST** `/products/{productId}/size-recommend-from-image`

#### Request Body

```json
{
    "image": "base64_encoded_image_or_url",
    "gender": "female",
    "height": 158,
    "weight": 47,
    "fit_preference": "regular"
}
```

#### Response

```json
{
    "success": true,
    "message": "Size recommendation retrieved successfully",
    "data": {
        "success": true,
        "recommended_size": "S",
        "confidence": "high",
        "measurements": {
            "chest": 85,
            "waist": 65,
            "hips": 90
        },
        "fit_type": "Fit Your Body",
        "alternative_sizes": ["XS", "M"]
    }
}
```

### 4. Gợi ý size từ số đo đã lưu (AI)

**POST** `/products/{productId}/size-recommend`

Sử dụng số đo cơ thể đã lưu để gọi AI và nhận gợi ý size.

#### Response

```json
{
    "success": true,
    "message": "Size recommendation retrieved successfully",
    "data": {
        "success": true,
        "has_measurements": true,
        "recommended_size": "S",
        "confidence": "high",
        "fit_type": "Comfortable",
        "alternative_sizes": ["XS", "M"],
        "ai_suggestions": "Based on your measurements..."
    }
}
```

### 5. Xóa số đo cơ thể

**DELETE** `/user/body-measurements`

## AI Integration

### External AI Service

API này tích hợp với AI service external để phân tích và gợi ý size:

**AI Endpoint:** Cấu hình trong `.env`

```env
PERFECT_FIT_AI_URL=https://ai.perfectfit.com/api
PERFECT_FIT_AI_KEY=your_api_key_here
```

### AI Request Format

**Endpoint:** `POST {AI_URL}/size-recommendation`

```json
{
    "image": "base64_or_url",
    "gender": "female",
    "height": 158,
    "weight": 47,
    "product_id": 1,
    "fit_preference": "regular"
}
```

**Endpoint:** `POST {AI_URL}/size-recommendation-from-data`

```json
{
    "gender": "female",
    "height": 158,
    "weight": 47,
    "chest": 90,
    "waist": 60,
    "hips": 90,
    "shoulder": 38,
    "product_id": 1,
    "fit_preference": "regular",
    "height_unit": "cm",
    "weight_unit": "kg",
    "measurement_unit": "cm"
}
```

### AI Response Format

```json
{
    "recommended_size": "S",
    "confidence": "high",
    "fit_type": "Fit Your Body",
    "alternative_sizes": ["XS", "M"],
    "measurements": {
        "chest": 85,
        "waist": 65,
        "hips": 90
    },
    "suggestions": "Based on your body type..."
}
```

## Fit Preferences

- **tight** (Fit Your Body): Ôm sát cơ thể
- **regular** (Comfortable): Vừa vặn thoải mái
- **loose** (Oversized): Rộng rãi

## Features

- ✅ AI-powered size recommendation
- ✅ Upload ảnh để nhận gợi ý
- ✅ Lưu số đo để sử dụng lại
- ✅ Multiple fit preferences
- ✅ Alternative sizes suggestion
- ✅ Confidence level indication

---

**Perfect Fit AI đã sẵn sàng!** 🤖✨

