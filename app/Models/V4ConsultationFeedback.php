<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class V4ConsultationFeedback extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'submission_version_id',
        'evaluator_id',
        'remarks',
        'urls',
    ];

    protected $casts = [
        'urls' => 'array',
    ];

    public function submissionVersion()
    {
        return $this->belongsTo(EvaluationSubmissionVersion::class);
    }

    public function evaluator()
    {
        return $this->belongsTo(V4User::class, 'evaluator_id');
    }
}
