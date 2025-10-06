<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

/**
 * Option for question: stores text + rating value (float).
 *
 * Example option structure from your sample:
 *  title: "SKATING MECHANICS" -> option text string with option -> rating: 1 | 1.5 | 2 ... 5
 *
 * Fields: id, question_id, title, option, rating (float), sort_order, meta
 */
class EvaluationQuestionOption extends Model
{
    use HasFactory;

    protected $fillable = [
        'question_id',
        'title',
        'option',
        'rating',
        'sort_order',
        'meta',
    ];

    protected $casts = [
        'rating' => 'float',
        'meta' => 'array',
    ];

    public function question()
    {
        return $this->belongsTo(EvaluationQuestion::class, 'question_id');
    }

    public function scopeOrdered($query)
    {
        return $query->orderBy('sort_order');
    }
}
