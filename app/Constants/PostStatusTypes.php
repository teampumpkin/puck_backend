<?php

namespace App\Constants;

class PostStatusTypes
{


    const DRAFT = 'draft';
    const PUBLISHED = 'published';
    const ARCHIVED = 'archived';


    public static function all(): array
    {
        return [
            self::DRAFT,
            self::ARCHIVED,
            self::PUBLISHED
        ];
    }
}
