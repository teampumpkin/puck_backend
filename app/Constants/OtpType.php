<?php

namespace App\Constants;

class OtpType
{
    const EMAIL = 'email';
    const PHONE = 'phone';

    public static function all()
    {
        return [
            self::EMAIL,
            self::PHONE,
        ];
    }
}
