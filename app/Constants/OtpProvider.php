<?php

namespace App\Constants;

class OtpProvider
{
    const SENDX = 'sendx';
    const TEST = 'test';
    const Twilio = 'twilio';

    public static function all()
    {
        return [
            self::SENDX,
        ];
    }
}
