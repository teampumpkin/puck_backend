<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class V4ConsultationRequest extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'submission_version_id',
        'evaluator_id',
        'status',
        'admin_notes',
        'evaluator_notes',
    ];

    const STATUS_PENDING = 'pending';
    const STATUS_ACCEPTED = 'accepted';
    const STATUS_REJECTED = 'rejected';
    const STATUS_COMPLETED = 'completed';


    public function submissionVersion()
    {
        return $this->belongsTo(EvaluationSubmissionVersion::class);
    }

    public function evaluator()
    {
        return $this->belongsTo(V4User::class, 'evaluator_id');
    }

    public function feedback()
    {
        return $this->hasOne(V4ConsultationFeedback::class);
    }
}
