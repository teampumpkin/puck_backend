<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

/**
 * Stores answer for a single question inside an evaluation.
 *
 * Fields: id, evaluation_id, question_id, question_option_id (nullable), rating (float), comment, meta
 *
 * Notes:
 *  - Option stores predefined rating values (e.g. 1, 1.5, ... 5)
 *  - Rating is stored as float to allow half steps
 */
class EvaluationAnswer extends Model
{
    use HasFactory;

    protected $fillable = [
        'evaluation_id',
        'question_id',
        'question_option_id',
        'rating',
        'comment',
        'meta',
    ];

    protected $casts = [
        'rating' => 'float',
        'meta' => 'array',
    ];

    protected static function boot()
    {
        parent::boot();

        // Auto-set rating from option when option is selected
        static::saving(function ($model) {
            if ($model->question_option_id && !$model->rating) {
                $option = $model->option;
                if ($option) {
                    $model->rating = $option->rating;
                }
            }
        });
    }

    /* --------------------
     | Relationships
     --------------------*/
    public function evaluation()
    {
        return $this->belongsTo(Evaluation::class, 'evaluation_id');
    }

    public function question()
    {
        return $this->belongsTo(EvaluationQuestion::class, 'question_id');
    }

    public function option()
    {
        return $this->belongsTo(EvaluationQuestionOption::class, 'question_option_id');
    }

    /* --------------------
     | Scopes
     --------------------*/
    public function scopeForEvaluation($q, $evaluationId)
    {
        return $q->where('evaluation_id', $evaluationId);
    }

    public function scopeForQuestion($q, $questionId)
    {
        return $q->where('question_id', $questionId);
    }

    public function scopeWithComment($q)
    {
        return $q->whereNotNull('comment');
    }

    public function scopeByRatingRange($q, $minRating, $maxRating)
    {
        return $q->whereBetween('rating', [$minRating, $maxRating]);
    }

    public function scopeHighRating($q, $threshold = 4.0)
    {
        return $q->where('rating', '>=', $threshold);
    }

    public function scopeLowRating($q, $threshold = 2.0)
    {
        return $q->where('rating', '<=', $threshold);
    }

    public function scopeByCategory($q, $categoryId)
    {
        return $q->whereHas('question', function ($query) use ($categoryId) {
            $query->where('category_id', $categoryId);
        });
    }

    /* --------------------
     | Helper Methods
     --------------------*/
    public function setFromOption(int $optionId)
    {
        $option = EvaluationQuestionOption::find($optionId);
        if ($option) {
            $this->question_option_id = $optionId;
            $this->rating = $option->rating;
            $this->save();
        }
        return $this;
    }

    public function updateRating(float $rating, string $comment = null)
    {
        $this->rating = $rating;
        if ($comment !== null) {
            $this->comment = $comment;
        }
        $this->save();
        return $this;
    }

    public function addComment(string $comment)
    {
        $this->comment = $comment;
        $this->save();
        return $this;
    }

    /* --------------------
     | Accessors
     --------------------*/
    public function getFormattedRatingAttribute()
    {
        return number_format($this->rating, 1) . '/5.0';
    }

    public function getRatingPercentageAttribute()
    {
        return round(($this->rating / 5.0) * 100);
    }

    public function getSelectedOptionTextAttribute()
    {
        return $this->option ? $this->option->option : null;
    }

    public function getCategoryNameAttribute()
    {
        return $this->question && $this->question->category
            ? $this->question->category->name
            : null;
    }

    public function getQuestionTitleAttribute()
    {
        return $this->question ? $this->question->title : null;
    }

    public function getWeightedRatingAttribute()
    {
        $weight = $this->question->meta['weight'] ?? 1;
        return $this->rating * $weight;
    }

    /* --------------------
     | Status Checkers
     --------------------*/
    public function hasComment()
    {
        return !empty($this->comment);
    }

    public function hasOption()
    {
        return !is_null($this->question_option_id);
    }

    public function isHighRating($threshold = 4.0)
    {
        return $this->rating >= $threshold;
    }

    public function isLowRating($threshold = 2.0)
    {
        return $this->rating <= $threshold;
    }

    public function isComplete()
    {
        return $this->rating > 0;
    }

    /* --------------------
     | Validation
     --------------------*/
    public function isValidRating()
    {
        return $this->rating >= 1.0 && $this->rating <= 5.0;
    }

    public function requiresComment()
    {
        // Check if the selected option requires a comment
        if ($this->option && isset($this->option->meta['requires_comment'])) {
            return $this->option->meta['requires_comment'];
        }

        // Check if low ratings require comments
        return $this->rating <= 2.0;
    }

    public function isValidAnswer()
    {
        if (!$this->isValidRating()) {
            return false;
        }

        if ($this->requiresComment() && empty($this->comment)) {
            return false;
        }

        return true;
    }
}
