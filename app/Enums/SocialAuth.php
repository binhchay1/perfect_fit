<?php

namespace App\Enums;

final class SocialAuth
{
    public const PROVIDER_GOOGLE = 'google';
    public const PROVIDER_FACEBOOK = 'facebook';
    public const PROVIDER_TIKTOK = 'tiktok';

    public const PROVIDERS = [
        self::PROVIDER_GOOGLE,
        self::PROVIDER_FACEBOOK,
        self::PROVIDER_TIKTOK,
    ];
}

