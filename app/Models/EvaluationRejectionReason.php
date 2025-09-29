<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

/**
 * Predefined rejection reasons that evaluator can select (admin can manage).
 *
 * Fields: id, title, description, active, sort_order, meta
 */
class EvaluationRejectionReason extends Model
{
    use HasFactory;

    protected $fillable = [
        'title',
        'description',
        'active',
        'sort_order',
        'meta',
    ];

    protected $casts = [
        'active' => 'boolean',
        'meta' => 'array',
    ];

    public function scopeActive($q)
    {
        return $q->where('active', true)->orderBy('sort_order');
    }
}
