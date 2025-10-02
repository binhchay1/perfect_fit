<?php

namespace App\Enums;

final class OrderReturn
{
    public const TYPE_RETURN = 'return';
    public const TYPE_REFUND = 'refund';
    public const TYPE_EXCHANGE = 'exchange';

    public const REASON_DAMAGED = 'damaged';
    public const REASON_WRONG_ITEM = 'wrong_item';
    public const REASON_WRONG_SIZE = 'wrong_size';
    public const REASON_NOT_AS_DESCRIBED = 'not_as_described';
    public const REASON_QUALITY_ISSUE = 'quality_issue';
    public const REASON_CHANGED_MIND = 'changed_mind';
    public const REASON_OTHER = 'other';

    public const STATUS_PENDING = 'pending';
    public const STATUS_APPROVED = 'approved';
    public const STATUS_REJECTED = 'rejected';
    public const STATUS_PROCESSING = 'processing';
    public const STATUS_COMPLETED = 'completed';
    public const STATUS_CANCELLED = 'cancelled';

    public const RETURN_TYPES = [
        self::TYPE_RETURN,
        self::TYPE_REFUND,
        self::TYPE_EXCHANGE,
    ];

    public const RETURN_REASONS = [
        self::REASON_DAMAGED => 'Sản phẩm bị hư hại',
        self::REASON_WRONG_ITEM => 'Giao sai sản phẩm',
        self::REASON_WRONG_SIZE => 'Không vừa kích cỡ',
        self::REASON_NOT_AS_DESCRIBED => 'Không như mô tả',
        self::REASON_QUALITY_ISSUE => 'Vấn đề chất lượng',
        self::REASON_CHANGED_MIND => 'Không như mong đợi',
        self::REASON_OTHER => 'Lý do khác',
    ];

    public const STATUSES = [
        self::STATUS_PENDING,
        self::STATUS_APPROVED,
        self::STATUS_REJECTED,
        self::STATUS_PROCESSING,
        self::STATUS_COMPLETED,
        self::STATUS_CANCELLED,
    ];
}

