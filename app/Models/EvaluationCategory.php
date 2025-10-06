<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

/**
 * Category of questions (e.g. SKILLS). Admin can enable/disable categories and reorder them.
 *
 * Fields: id, name, slug, description, active (bool), sort_order, meta
 */
class EvaluationCategory extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'slug',
        'description',
        'active',
        'sort_order',
        'meta',
    ];

    protected $casts = [
        'active' => 'boolean',
        'meta' => 'array',
    ];

    public function questions()
    {
        return $this->hasMany(EvaluationQuestion::class, 'category_id')->orderBy('sort_order');
    }

    public function scopeActive($q)
    {
        return $q->where('active', true)->orderBy('sort_order');
    }
}
