<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;

/**
 * Class PrcScoutRequest
 * @package App\Models
 */
class PrcScoutRequest extends Model
{
    use HasFactory;

    /**
     * @var string[]
     */
    protected $guarded = ["id"];

    /**
     * @return HasOne
     */
    public function player()
    {
        return $this->hasOne(User::class, 'id', 'source_user_id');
    }

    public function evaluator()
    {
        return $this->hasOne(User::class, 'id', 'scout_user_id');
    }

    /**
     * @return BelongsTo
     */
    public function report()
    {
        return $this->belongsTo(PrcReport::class, 'id', 'scout_request_id');
    }

    /**
     * @return HasOne
     */
    public function media()
    {
        return $this->hasOne(PrcMedia::class, 'id', 'media_id');
    }

    /**
     * @return HasOne
     */
    public function playable()
    {
        return $this->hasOne(PrcPlayable::class, 'id', 'playable_id');
    }
}
