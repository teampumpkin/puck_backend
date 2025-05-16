<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Class PrcAssessmentStatementLog
 * @package App\Models
 */
class PrcAssessmentStatementLog extends Model
{
    use HasFactory;

    /**
     * @var string[]
     */
    protected $guarded = ['id'];

    /**
     * @return BelongsTo
     */
    public function statement()
    {
        return $this->belongsTo(PrcAdvanceAssessmentValueStatement::class, 'statement_id', 'id');
    }
}
