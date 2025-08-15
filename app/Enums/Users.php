<?php

namespace App\Enums;

final class Users
{
    const ADMIN = 'admin';
    const USER = 'user';
    const STATUS_ACTIVE_EMAIL = 1;
    const STATUS_NOT_ACTIVE_EMAIL = 0;
    const STATUS_ACTIVE_USER = 1;
    const PUBLISHER = 'publisher';
    const ADVERTISER = 'advertiser';
    const REJECT_PUBLISHER = 2;
    const ACTIVE_PUBLISHER = 1;
}
