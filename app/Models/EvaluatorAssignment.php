<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

/**
 * Assignment of a submission to an evaluator.
 *
 * Fields: id, submission_id, evaluator_id, status [pending, completed, rejected], assigned_at, completed_at, notes
 */
class EvaluatorAssignment extends Model
{
    use HasFactory;

    const STATUS_PENDING = 'pending';
    const STATUS_IN_PROGRESS = 'in_progress';
    const STATUS_COMPLETED = 'completed';
    const STATUS_REJECTED = 'rejected';

    protected $fillable = [
        'submission_id',
        'evaluator_id',
        'status',
        'assigned_at',
        'completed_at',
        'notes',
        'meta',
    ];

    protected $casts = [
        'meta' => 'array',
        'assigned_at' => 'datetime',
        'completed_at' => 'datetime',
    ];

    protected $attributes = [
        'status' => self::STATUS_PENDING,
    ];

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($model) {
            if (!$model->assigned_at) {
                $model->assigned_at = now();
            }
        });
    }

    /* --------------------
     | Relationships
     --------------------*/
    public function submission()
    {
        return $this->belongsTo(EvaluationSubmission::class, 'submission_id');
    }

    public function evaluator()
    {
        return $this->belongsTo(V4User::class, 'evaluator_id');
    }

    public function evaluation()
    {
        return $this->hasOne(Evaluation::class, 'assignment_id');
    }

    /* --------------------
     | Scopes
     --------------------*/
    public function scopePending($q)
    {
        return $q->where('status', self::STATUS_PENDING);
    }

    public function scopeCompleted($q)
    {
        return $q->where('status', self::STATUS_COMPLETED);
    }

    public function scopeRejected($q)
    {
        return $q->where('status', self::STATUS_REJECTED);
    }

    public function scopeForEvaluator($q, $evaluatorId)
    {
        return $q->where('evaluator_id', $evaluatorId);
    }

    public function scopeForSubmission($q, $submissionId)
    {
        return $q->where('submission_id', $submissionId);
    }

    /* --------------------
     | Helper Methods
     --------------------*/

    public function markCompleted(array $resultReport = [])
    {
        $this->status = self::STATUS_COMPLETED;
        $this->completed_at = now();
        $this->save();

        // Update submission status
        if ($this->submission) {
            $this->submission->status = EvaluationSubmission::STATUS_COMPLETED;
            if (!empty($resultReport)) {
                $this->submission->result_report_meta = $resultReport;
            }
            $this->submission->save();
        }

        return $this;
    }

    public function markRejected(string $reason = null)
    {
        $this->status = self::STATUS_REJECTED;

        if ($reason) {
            $this->notes = trim(($this->notes ?? '') . "\nRejection reason: {$reason}");
        }

        $this->save();

        // Update submission status
        if ($this->submission) {
            $this->submission->status = EvaluationSubmission::STATUS_REJECTED;
            $this->submission->save();
        }

        return $this;
    }

    public function reassign(int $newEvaluatorId, string $reason = null)
    {
        // Mark current assignment as rejected
        $this->markRejected($reason ?? 'Reassigned to another evaluator');

        // Create new assignment
        $newAssignment = self::create([
            'submission_id' => $this->submission_id,
            'evaluator_id' => $newEvaluatorId,
            'status' => self::STATUS_PENDING,
            'notes' => $reason ? "Reassigned: {$reason}" : 'Reassigned from another evaluator',
        ]);

        // Update submission status back to assigned
        if ($this->submission) {
            $this->submission->status = EvaluationSubmission::STATUS_ASSIGNED;
            $this->submission->save();
        }

        return $newAssignment;
    }

    /* --------------------
     | Status Checkers
     --------------------*/
    public function isPending()
    {
        return $this->status === self::STATUS_PENDING;
    }

    public function markInProgress()
    {
        $this->status = self::STATUS_IN_PROGRESS;
        $this->save();
        return $this;
    }

    public function isInProgress()
    {
        return $this->status === self::STATUS_IN_PROGRESS;
    }

    public function scopeInProgress($query)
    {
        return $query->where('status', self::STATUS_IN_PROGRESS);
    }

    public function isCompleted()
    {
        return $this->status === self::STATUS_COMPLETED;
    }

    public function isRejected()
    {
        return $this->status === self::STATUS_REJECTED;
    }

    /* --------------------
     | Accessors
     --------------------*/
    public function getDurationAttribute()
    {
        if (!$this->completed_at || !$this->assigned_at) {
            return null;
        }

        return $this->assigned_at->diffInHours($this->completed_at);
    }

    public function getFormattedDurationAttribute()
    {
        $duration = $this->duration;
        if (!$duration)
            return null;

        if ($duration < 24) {
            return $duration . ' hours';
        }

        $days = floor($duration / 24);
        $hours = $duration % 24;

        return $days . ' days' . ($hours > 0 ? ', ' . $hours . ' hours' : '');
    }
}
