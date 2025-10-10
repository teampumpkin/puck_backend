<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

/**
 * Evaluation: an evaluator's review of a submission.
 *
 * Fields: id, submission_id, assignment_id, evaluator_id, overall_rating (float), notes, status, meta
 *
 * status: submitted | rejected
 */
class Evaluation extends Model
{
    use HasFactory;

    const STATUS_SUBMITTED = 'submitted';
    const STATUS_REJECTED = 'rejected';

    protected $fillable = [
        'submission_id',
        'assignment_id',
        'evaluator_id',
        'overall_rating',
        'notes',
        'status',
        'meta',
    ];

    protected $casts = [
        'overall_rating' => 'float',
        'meta' => 'array',
    ];

    /* --------------------
     | Relationships
     --------------------*/
    public function submission()
    {
        return $this->belongsTo(EvaluationSubmission::class, 'submission_id');
    }

    public function assignment()
    {
        return $this->belongsTo(EvaluatorAssignment::class, 'assignment_id');
    }

    public function evaluator()
    {
        return $this->belongsTo(V4User::class, 'evaluator_id');
    }

    public function answers()
    {
        return $this->hasMany(EvaluationAnswer::class, 'evaluation_id');
    }

    /* --------------------
     | Scopes
     --------------------*/

    public function scopeSubmitted($q)
    {
        return $q->where('status', self::STATUS_SUBMITTED);
    }

    public function scopeRejected($q)
    {
        return $q->where('status', self::STATUS_REJECTED);
    }

    public function scopeByEvaluator($q, $evaluatorId)
    {
        return $q->where('evaluator_id', $evaluatorId);
    }

    public function scopeForSubmission($q, $submissionId)
    {
        return $q->where('submission_id', $submissionId);
    }

    public function scopeCompleted($q)
    {
        return $q->where('status', self::STATUS_SUBMITTED);
    }

    /* --------------------
     | Helper Methods
     --------------------*/
    public function markSubmitted()
    {
        $this->status = self::STATUS_SUBMITTED;
        $this->overall_rating = $this->computeAggregatedRating();
        $this->save();

        // Update assignment and submission status
        if ($this->assignment) {
            $this->assignment->markCompleted([
                'overall_rating' => $this->overall_rating,
                'evaluation_id' => $this->id,
                'completed_at' => now()->toISOString(),
            ]);
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

        // Update assignment status
        if ($this->assignment) {
            $this->assignment->markRejected($reason);
        }

        return $this;
    }

    /**
     * Calculate aggregated rating from answers (weighted average if needed).
     */
    public function computeAggregatedRating(): float
    {
        $answers = $this->answers()->get();

        if ($answers->isEmpty()) {
            return 0.0;
        }

        $totalWeight = 0;
        $weightedSum = 0;

        foreach ($answers as $answer) {
            $weight = $answer->question->meta['weight'] ?? 1;
            $weightedSum += $answer->rating * $weight;
            $totalWeight += $weight;
        }

        if ($totalWeight === 0) {
            return 0.0;
        }

        return round($weightedSum / $totalWeight, 2);
    }

    /**
     * Get evaluation progress percentage.
     */
    public function getProgressPercentage(): int
    {
        $totalQuestions = $this->submission->currentVersion
            ? $this->submission->currentVersion->submission->evaluations()->count()
            : 0;

        if ($totalQuestions === 0) {
            return 0;
        }

        $answeredQuestions = $this->answers()->count();
        return round(($answeredQuestions / $totalQuestions) * 100);
    }

    /**
     * Check if evaluation is complete (all required questions answered).
     */
    public function isComplete(): bool
    {
        // Get all required questions for this evaluation
        $requiredQuestions = EvaluationQuestion::active()
            ->required()
            ->get()
            ->pluck('id');

        if ($requiredQuestions->isEmpty()) {
            return true;
        }

        // Check if all required questions have answers
        $answeredQuestions = $this->answers()
            ->whereIn('question_id', $requiredQuestions)
            ->pluck('question_id');

        return $requiredQuestions->diff($answeredQuestions)->isEmpty();
    }

    /* --------------------
     | Status Checkers
     --------------------*/

    public function isSubmitted()
    {
        return $this->status === self::STATUS_SUBMITTED;
    }

    public function isRejected()
    {
        return $this->status === self::STATUS_REJECTED;
    }

    /* --------------------
     | Accessors
     --------------------*/
    public function getFormattedRatingAttribute()
    {
        return number_format($this->overall_rating, 1) . '/5.0';
    }

    public function getRatingPercentageAttribute()
    {
        return round(($this->overall_rating / 5.0) * 100);
    }

    public function getAnswerCountAttribute()
    {
        return $this->answers()->count();
    }

    public function getCategoryBreakdownAttribute()
    {
        $breakdown = [];

        $answers = $this->answers()->with('question.category')->get();

        foreach ($answers as $answer) {
            $categoryName = $answer->question->category->name ?? 'Uncategorized';

            if (!isset($breakdown[$categoryName])) {
                $breakdown[$categoryName] = [
                    'total_rating' => 0,
                    'count' => 0,
                    'average' => 0,
                ];
            }

            $breakdown[$categoryName]['total_rating'] += $answer->rating;
            $breakdown[$categoryName]['count']++;
        }

        // Calculate averages
        foreach ($breakdown as $category => &$data) {
            $data['average'] = round($data['total_rating'] / $data['count'], 2);
        }

        return $breakdown;
    }
}
