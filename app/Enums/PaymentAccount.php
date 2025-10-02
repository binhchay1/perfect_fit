<?php

namespace App\Enums;

final class PaymentAccount
{
    public const ACCOUNT_TYPE_SAVINGS = 'savings';
    public const ACCOUNT_TYPE_CHECKING = 'checking';
    public const ACCOUNT_TYPE_BUSINESS = 'business';

    public const STATUS_ACTIVE = true;
    public const STATUS_INACTIVE = false;

    public const DEFAULT = true;
    public const NOT_DEFAULT = false;

    public const ACCOUNT_TYPES = [
        self::ACCOUNT_TYPE_SAVINGS,
        self::ACCOUNT_TYPE_CHECKING,
        self::ACCOUNT_TYPE_BUSINESS,
    ];

    public const VIETNAM_BANKS = [
        'Vietcombank' => 'Ngân hàng TMCP Ngoại Thương Việt Nam',
        'BIDV' => 'Ngân hàng TMCP Đầu tư và Phát triển Việt Nam',
        'VietinBank' => 'Ngân hàng TMCP Công Thương Việt Nam',
        'Agribank' => 'Ngân hàng Nông nghiệp và Phát triển Nông thôn Việt Nam',
        'Techcombank' => 'Ngân hàng TMCP Kỹ Thương Việt Nam',
        'ACB' => 'Ngân hàng TMCP Á Châu',
        'MB' => 'Ngân hàng TMCP Quân Đội',
        'VPBank' => 'Ngân hàng TMCP Việt Nam Thịnh Vượng',
        'TPBank' => 'Ngân hàng TMCP Tiên Phong',
        'Sacombank' => 'Ngân hàng TMCP Sài Gòn Thương Tín',
        'HDBank' => 'Ngân hàng TMCP Phát triển TP.HCM',
        'VIB' => 'Ngân hàng TMCP Quốc Tế',
        'SHB' => 'Ngân hàng TMCP Sài Gòn - Hà Nội',
        'OCB' => 'Ngân hàng TMCP Phương Đông',
        'MSB' => 'Ngân hàng TMCP Hàng Hải',
    ];
}

