<?php

namespace App\Constants;

class MarketplaceTypes
{
    const PERSONALIZED_VIDEO_EVALUATION = 'Personalized Video Evaluation';
    const CONSULTATION_VIDEO_CALL = '1on1 Consultation Video Call';
    const MENTORSHIP_PROGRAM = '12-Week Mentorship Program';

    /**
     * Return all available types (for validation, dropdowns, etc.)
     */
    public static function all(): array
    {
        return [
            self::PERSONALIZED_VIDEO_EVALUATION,
            self::CONSULTATION_VIDEO_CALL,
            self::MENTORSHIP_PROGRAM,
        ];
    }
}
