<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 *
 */
class PrcAdvanceAssessmentCategory extends Model
{
    use HasFactory;

    /**
     * @var string[]
     */
    protected $guarded = ['id'];

    /**
     * @return HasMany
     */
    public function data()
    {
        return $this->skills();
    }

    /**
     * @return HasMany
     */
    public function skills()
    {
        return $this->hasMany(PrcAdvanceAssessmentSkill::class, 'category_id', 'id')->where('status', 1)->orderBy('id', 'ASC');
    }

    /**
     * @return BelongsTo
     */
    public function player_position()
    {
        return $this->belongsTo(PrcPosition::class, 'player_position_id', 'id');
    }
}
