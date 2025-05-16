<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasOne;

/**
 *
 */
class Chat extends Model
{
    use HasFactory;

    /**
     * @var string[]
     */
    protected $guarded = ['id'];

    /**
     * @return HasOne
     */
    public function user_1()
    {
        return $this->hasOne(User::class, 'id', 'user1');
    }

    /**
     * @return HasOne
     */
    public function user_2()
    {
        return $this->hasOne(User::class, 'id', 'user2');
    }
}
