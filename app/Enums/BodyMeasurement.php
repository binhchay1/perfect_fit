<?php

namespace App\Enums;

final class BodyMeasurement
{
    public const GENDER_MALE = 'male';
    public const GENDER_FEMALE = 'female';
    public const GENDER_UNISEX = 'unisex';

    public const UNIT_CM = 'cm';
    public const UNIT_IN = 'in';
    public const UNIT_KG = 'kg';
    public const UNIT_LBS = 'lbs';

    public const FIT_TYPE_TIGHT = 'tight';
    public const FIT_TYPE_REGULAR = 'regular';
    public const FIT_TYPE_LOOSE = 'loose';

    public const GENDERS = [
        self::GENDER_MALE,
        self::GENDER_FEMALE,
        self::GENDER_UNISEX,
    ];

    public const FIT_TYPES = [
        self::FIT_TYPE_TIGHT => 'Fit Your Body',
        self::FIT_TYPE_REGULAR => 'Comfortable',
        self::FIT_TYPE_LOOSE => 'Oversized',
    ];

    public const SIZE_CHART = [
        'XS' => ['chest' => [80, 85], 'waist' => [60, 65], 'hips' => [85, 90]],
        'S' => ['chest' => [85, 90], 'waist' => [65, 70], 'hips' => [90, 95]],
        'M' => ['chest' => [90, 95], 'waist' => [70, 75], 'hips' => [95, 100]],
        'L' => ['chest' => [95, 100], 'waist' => [75, 80], 'hips' => [100, 105]],
        'XL' => ['chest' => [100, 105], 'waist' => [80, 85], 'hips' => [105, 110]],
        '2XL' => ['chest' => [105, 112], 'waist' => [85, 92], 'hips' => [110, 117]],
    ];
}

