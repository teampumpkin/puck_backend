<?php

namespace App\Constants;

class EventTypes
{
    public const TYPES = [
        'ID Camp', 'Showcase', 'Tryout', 'Recruiting Event', 'Clinic',
        'Combine', 'Open Skate', 'Junior Camp', 'College Camp', 'Prospect Camp',
        'Evaluation Camp', 'Power Skating', 'Goalie Camp', 'Skills Camp', 'Tournament',
    ];

    public static function all(): array
    {
        return self::TYPES;
    }
}
