<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 *
 */
class PrcAdvanceAssessmentValue extends Model
{
    use HasFactory;

    /**
     * @var string[]
     */
    protected $guarded = ['id'];


    /**
     * @return HasMany
     */
    public function assessment_statements()
    {
        return $this->hasMany(PrcAdvanceAssessmentValueStatement::class, 'assessment_value_id', 'id');
    }

    /**
     * @return BelongsTo
     */
    public function skill()
    {
        return $this->belongsTo(PrcAdvanceAssessmentSkill::class, 'skill_id', 'id');
    }
}
