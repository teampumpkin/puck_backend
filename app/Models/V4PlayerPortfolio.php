<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class V4PlayerPortfolio extends Model
{
    use SoftDeletes;

    protected $table = 'v4_player_portfolios';

    protected $fillable = [
        'player_id',
        'submission_id',
        'title',
        'description',
        'thumbnail_path',
        'is_public',
        'meta',
    ];

    protected $casts = [
        'is_public' => 'boolean',
        'meta' => 'array',
    ];

    // Relationship: Portfolio belongs to a user
    public function player()
    {
        return $this->belongsTo(V4User::class, 'player_id');
    }

    public function submission()
    {
        return $this->belongsTo(EvaluationSubmission::class, 'submission_id');
    }

    public function subs()
    {
        return $this->hasMany(V4PlayerPortfolioSub::class, 'portfolio_id');
    }

    // Optionally: eager-load subs when needed, or cascade soft-deletes/restores
    protected static function booted()
    {
        static::deleting(function ($model) {
            if (method_exists($model, 'subs')) {
                if ($model->isForceDeleting()) {
                    $model->subs()->forceDelete();
                } else {
                    $model->subs()->delete();
                }
            }
        });

        static::restoring(function ($model) {
            if (method_exists($model, 'subs')) {
                $model->subs()->withTrashed()->restore();
            }
        });
    }
}
