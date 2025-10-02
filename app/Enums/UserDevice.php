<?php

namespace App\Enums;

final class UserDevice
{
    public const DEVICE_TYPE_IOS = 'ios';
    public const DEVICE_TYPE_ANDROID = 'android';
    public const DEVICE_TYPE_WEB = 'web';
    public const DEVICE_TYPE_DESKTOP = 'desktop';
    public const DEVICE_TYPE_TABLET = 'tablet';
    public const DEVICE_TYPE_UNKNOWN = 'unknown';

    public const STATUS_ACTIVE = true;
    public const STATUS_INACTIVE = false;

    public const TRUSTED = true;
    public const UNTRUSTED = false;

    public const RECENTLY_USED_DAYS = 30;

    public const DEVICE_TYPES = [
        self::DEVICE_TYPE_IOS,
        self::DEVICE_TYPE_ANDROID,
        self::DEVICE_TYPE_WEB,
        self::DEVICE_TYPE_DESKTOP,
        self::DEVICE_TYPE_TABLET,
        self::DEVICE_TYPE_UNKNOWN,
    ];
}
