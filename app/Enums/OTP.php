<?php

namespace App\Enums;

final class OTP
{
    public const PURPOSE_LOGIN = 'login';
    public const PURPOSE_REGISTER = 'register';
    public const PURPOSE_VERIFY_PHONE = 'verify_phone';
    public const PURPOSE_ORDER_CONFIRM = 'order_confirm';
    public const PURPOSE_PASSWORD_RESET = 'password_reset';

    public const STATUS_USED = true;
    public const STATUS_UNUSED = false;

    public const EXPIRY_MINUTES = 5;
    public const MAX_ATTEMPTS = 3;
    public const OTP_LENGTH = 6;

    public const PURPOSES = [
        self::PURPOSE_LOGIN,
        self::PURPOSE_REGISTER,
        self::PURPOSE_VERIFY_PHONE,
        self::PURPOSE_ORDER_CONFIRM,
        self::PURPOSE_PASSWORD_RESET,
    ];
}

