<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class FavouriteUser extends Model
{
    use SoftDeletes;

    protected $table = 'favourite_users';

    protected $fillable = [
        'user_id',
        'favourite_id',
        'conversation_id',
    ];

    public function user()
    {
        return $this->belongsTo(V4User::class, 'user_id');
    }

    public function favourite()
    {
        return $this->belongsTo(V4User::class, 'favourite_id')->withTrashed();
    }

    public static function isFavourite($ownerId, $targetUserId): bool
    {
        return self::where('user_id', $ownerId)
            ->where('favourite_id', $targetUserId)
            ->exists();
    }
}
