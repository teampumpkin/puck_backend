<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 *
 */
class PrcAdvanceAssessmentSkill extends Model
{
    use HasFactory;

    /**
     * @var string[]
     */
    protected $guarded = ['id'];

    /**
     * @return HasMany
     */
    public function skill_values()
    {
        return $this->hasMany(PrcAdvanceAssessmentValue::class, 'skill_id', 'id')->orderBy('rating');
    }


    /**
     * @return BelongsTo
     */
    public function assessment_category()
    {
        return $this->belongsTo(PrcAdvanceAssessmentCategory::class, 'category_id', 'id');
    }
}
