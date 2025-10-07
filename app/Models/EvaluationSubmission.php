<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * Submission: represents a player's evaluation submission request.
 *
 * Fields:
 *  id, player_id, payment_request_id (nullable), current_version_id (points to submission_versions),
 *  status [uploaded, assigned, evaluating, rejected, accepted, completed], evaluator_assignment_id (nullable),
 *  result_report_meta (json), meta
 */
class EvaluationSubmission extends Model
{
    use HasFactory, SoftDeletes;

    const STATUS_UPLOADED = 'uploaded';
    const STATUS_ASSIGNED = 'assigned';
    const STATUS_REJECTED = 'rejected';
    const STATUS_COMPLETED = 'completed';

    protected $fillable = [
        'player_id',
        'payment_request_id',
        'current_version_id',
        'status',
        'result_report_meta',
        'meta',
    ];

    protected $casts = [
        'result_report_meta' => 'array',
        'meta' => 'array',
        'deleted_at' => 'datetime',
    ];

    protected $attributes = [
        'status' => self::STATUS_UPLOADED,
    ];

    /* --------------------
     | Relationships
     --------------------*/
    public function player()
    {
        return $this->belongsTo(V4User::class, 'player_id');
    }

    public function paymentRequest()
    {
        return $this->belongsTo(V4PaymentRequest::class, 'payment_request_id');
    }

    public function versions()
    {
        return $this->hasMany(EvaluationSubmissionVersion::class, 'submission_id');
    }

    public function currentVersion()
    {
        return $this->belongsTo(EvaluationSubmissionVersion::class, 'current_version_id');
    }

    public function evaluatorAssignment()
    {
        return $this->hasOne(EvaluatorAssignment::class, 'submission_id');
    }

    public function evaluations()
    {
        return $this->hasMany(Evaluation::class, 'submission_id');
    }

    /* --------------------
     | Scopes
     --------------------*/
    public function scopePendingAssignment($q)
    {
        return $q->where('status', self::STATUS_UPLOADED)->whereDoesntHave('evaluatorAssignment');
    }

    public function scopeByStatus($q, $status)
    {
        return $q->where('status', $status);
    }

    public function scopeForPlayer($q, $playerId)
    {
        return $q->where('player_id', $playerId);
    }

    public function scopeAssigned($q)
    {
        return $q->where('status', self::STATUS_ASSIGNED);
    }

    public function scopeEvaluating($q)
    {
        return $q->where('status', self::STATUS_EVALUATING);
    }

    public function scopeCompleted($q)
    {
        return $q->where('status', self::STATUS_COMPLETED);
    }

    public function scopeRejected($q)
    {
        return $q->where('status', self::STATUS_REJECTED);
    }

    /* --------------------
     | Helper Methods
     --------------------*/
    public function markAssigned(int $evaluatorId)
    {
        $this->status = self::STATUS_ASSIGNED;
        $this->save();

        $assignment = EvaluatorAssignment::create([
            'submission_id' => $this->id,
            'evaluator_id' => $evaluatorId,
            'status' => EvaluatorAssignment::STATUS_PENDING,
            'assigned_at' => now(),
        ]);

        return $assignment;
    }

    public function markEvaluating()
    {
        $this->status = self::STATUS_EVALUATING;
        $this->save();

        // Update assignment status if exists
        if ($this->evaluatorAssignment) {
            $this->evaluatorAssignment->update(['status' => EvaluatorAssignment::STATUS_IN_PROGRESS]);
        }

        return $this;
    }

    public function markRejected(int $evaluatorId, int $reasonId = null, string $notes = null)
    {
        $this->status = self::STATUS_REJECTED;

        // Store last rejection info in meta
        $meta = $this->meta ?? [];
        $meta['last_rejection'] = [
            'by_evaluator' => $evaluatorId,
            'reason_id' => $reasonId,
            'notes' => $notes,
            'at' => now()->toDateTimeString(),
        ];
        $this->meta = $meta;
        $this->save();

        // Update assignment status if exists
        if ($this->evaluatorAssignment) {
            $this->evaluatorAssignment->update(['status' => EvaluatorAssignment::STATUS_REJECTED]);
        }

        return $this;
    }

    public function markAccepted(array $reportMeta = [])
    {
        $this->status = self::STATUS_ACCEPTED;
        $this->result_report_meta = $reportMeta;
        $this->save();

        // Update assignment status if exists
        if ($this->evaluatorAssignment) {
            $this->evaluatorAssignment->update(['status' => EvaluatorAssignment::STATUS_ACCEPTED]);
        }

        return $this;
    }

    public function markCompleted(array $reportMeta = [])
    {
        $this->status = self::STATUS_COMPLETED;
        if (!empty($reportMeta)) {
            $this->result_report_meta = array_merge($this->result_report_meta ?? [], $reportMeta);
        }
        $this->save();

        // Update assignment status if exists
        if ($this->evaluatorAssignment) {
            $this->evaluatorAssignment->update([
                'status' => EvaluatorAssignment::STATUS_COMPLETED,
                'completed_at' => now()
            ]);
        }

        return $this;
    }

    /* --------------------
     | Status Checkers
     --------------------*/
    public function isUploaded()
    {
        return $this->status === self::STATUS_UPLOADED;
    }

    public function isAssigned()
    {
        return $this->status === self::STATUS_ASSIGNED;
    }

    public function isEvaluating()
    {
        return $this->status === self::STATUS_EVALUATING;
    }

    public function isRejected()
    {
        return $this->status === self::STATUS_REJECTED;
    }

    public function isAccepted()
    {
        return $this->status === self::STATUS_ACCEPTED;
    }

    public function isCompleted()
    {
        return $this->status === self::STATUS_COMPLETED;
    }

    public function canBeAssigned()
    {
        return $this->status === self::STATUS_UPLOADED && !$this->evaluatorAssignment;
    }

    public function canBeEvaluated()
    {
        return in_array($this->status, [self::STATUS_ASSIGNED, self::STATUS_EVALUATING]);
    }

    /* --------------------
     | Accessors
     --------------------*/
    public function getLastRejectionAttribute()
    {
        return $this->meta['last_rejection'] ?? null;
    }

    public function getRejectionCountAttribute()
    {
        return collect($this->meta['rejection_history'] ?? [])->count();
    }
}
