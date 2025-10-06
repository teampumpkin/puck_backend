<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

/**
 * Question that belongs to a Category.
 *
 * Fields: id, category_id, title, question, required (bool), sort_order, active (bool), meta
 */
class EvaluationQuestion extends Model
{
    use HasFactory;

    protected $fillable = [
        'category_id',
        'title',
        'question',
        'required',
        'sort_order',
        'active',
        'meta',
    ];

    protected $casts = [
        'required' => 'boolean',
        'active' => 'boolean',
        'meta' => 'array',
    ];

    public function category()
    {
        return $this->belongsTo(EvaluationCategory::class, 'category_id');
    }

    public function options()
    {
        return $this->hasMany(EvaluationQuestionOption::class, 'question_id')->orderBy('sort_order');
    }

    public function scopeActive($q)
    {
        return $q->where('active', true)->orderBy('sort_order');
    }

    public function scopeRequired($q)
    {
        return $q->where('required', true);
    }
}
