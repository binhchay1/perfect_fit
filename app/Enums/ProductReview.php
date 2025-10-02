<?php

namespace App\Enums;

final class ProductReview
{
    public const RATING_MIN = 1;
    public const RATING_MAX = 5;

    public const REACTION_LIKE = 'like';
    public const REACTION_DISLIKE = 'dislike';

    public const APPROVED = true;
    public const NOT_APPROVED = false;

    public const VERIFIED_PURCHASE = true;
    public const NOT_VERIFIED_PURCHASE = false;

    public const REACTIONS = [
        self::REACTION_LIKE,
        self::REACTION_DISLIKE,
    ];
}

