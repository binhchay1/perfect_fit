# Product Reviews API Documentation

## Tổng quan

API Product Reviews cho phép khách hàng đánh giá và bình luận về sản phẩm đã mua.

## Base URL

```
http://localhost:8000/api
```

## API Endpoints

### 1. Lấy đánh giá của sản phẩm (Public)

**GET** `/products/{productId}/reviews`

#### Response

```json
{
    "success": true,
    "message": "Reviews retrieved successfully",
    "data": {
        "reviews": [
            {
                "id": 1,
                "user": {
                    "id": 1,
                    "name": "Floyd Miles",
                    "avatar": null
                },
                "rating": 5,
                "comment": "Great product! Highly recommended.",
                "images": ["image1.jpg", "image2.jpg"],
                "likes_count": 11,
                "dislikes_count": 2,
                "is_verified_purchase": true,
                "user_reaction": "like",
                "created_at": "2025-10-02T10:00:00.000000Z"
            }
        ],
        "stats": {
            "average_rating": 4.5,
            "total_reviews": 123
        }
    }
}
```

### 2. Thêm đánh giá sản phẩm

**POST** `/products/{productId}/reviews`

**Authentication:** Required

#### Request Body

```json
{
    "rating": 5,
    "comment": "Great product! Very comfortable and fits perfectly.",
    "order_id": 123,
    "images": ["base64_image1", "base64_image2"]
}
```

#### Response

```json
{
    "success": true,
    "message": "Review created successfully",
    "data": {
        "id": 1,
        "rating": 5,
        "comment": "Great product!"
    }
}
```

### 3. Cập nhật đánh giá

**PUT** `/reviews/{id}`

**Authentication:** Required

#### Request Body

```json
{
    "rating": 4,
    "comment": "Updated review text"
}
```

### 4. Xóa đánh giá

**DELETE** `/reviews/{id}`

**Authentication:** Required

### 5. Like/Dislike đánh giá

**POST** `/reviews/{id}/react`

**Authentication:** Required

#### Request Body

```json
{
    "reaction_type": "like"
}
```

Reaction types: `like` hoặc `dislike`

#### Response

```json
{
    "success": true,
    "message": "Reaction added",
    "data": {
        "likes_count": 12,
        "dislikes_count": 2
    }
}
```

## Features

- ✅ Đánh giá với rating 1-5 sao
- ✅ Bình luận chi tiết
- ✅ Upload hình ảnh (tối đa 5 ảnh)
- ✅ Like/Dislike reviews
- ✅ Verified purchase badge
- ✅ Thống kê rating trung bình
- ✅ Chỉ review 1 lần/sản phẩm

---

**Product Reviews API đã sẵn sàng!** ⭐

