<?php

namespace App\Constants;

class HockeyListingConditions
{
    const NEW = 'new';
    const USED_LIKE_NEW = 'Used - Like New';
    const USED_GOOD = 'Used - Good';
    const USED_FAIR = 'Used - Fair';

    public static function all(): array
    {
        return [
            self::NEW,
            self::USED_LIKE_NEW,
            self::USED_GOOD,
            self::USED_FAIR,
        ];
    }
}
