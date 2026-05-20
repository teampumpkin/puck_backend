<?php

namespace App\Constants;

class HockeyListingConditions
{
    const NEW = 'new';
    const USED_LIKE_NEW = 'used_like_new';
    const USED_GOOD = 'used_good';
    const USED_FAIR = 'used_fair';

    public static function all(): array
    {
        return [
            self::NEW ,
            self::USED_LIKE_NEW,
            self::USED_GOOD,
            self::USED_FAIR,
        ];
    }
}
