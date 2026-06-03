<?php

namespace App\Constants;

class HockeyListingCategories
{
    const HOCKEY_CAMPS = 'hockey_camps';
    const HELMET = 'helmet';
    const GEAR = 'gear';
    const TICKETS_FOR_GAMES = 'tickets_for_games';

    public static function all(): array
    {
        return [
            self::HOCKEY_CAMPS,
            self::HELMET,
            self::GEAR,
            self::TICKETS_FOR_GAMES,
        ];
    }
}
