<?php

namespace App\Constants;

class HockeyListingCategories
{
    const HOCKEY_CAMPS = 'Hockey Camps';
    const HELMET = 'Helmet';
    const GEAR = 'Gear';

    public static function all(): array
    {
        return [
            self::HOCKEY_CAMPS,
            self::HELMET,
            self::GEAR,
        ];
    }
}
