<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasOne;

class PrcReport extends Model
{
    use HasFactory;

    protected $guarded = ["id"];

    /**
     * @return HasOne
     */
    public function player()
    {
        return $this->hasOne(User::class, 'id', 'player_user_id');
    }

    /**
     * @return HasOne
     */
    public function scout()
    {
        return $this->hasOne(User::class, 'id', 'scout_user_id');
    }

    /**
     * @return HasOne
     */
    public function scout_request()
    {
        return $this->hasOne(PrcScoutRequest::class, 'id', 'scout_request_id');
    }
}
