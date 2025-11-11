<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class V4PlayerAchievement extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'v4_player_achievements';

    protected $fillable = [
        'player_id',
        'title',
        'file_path',
        'details',
        'meta',
    ];

    protected $casts = [
        'meta' => 'array',
        'deleted_at' => 'datetime',
    ];

    /**
     * Relationships
     */
    public function player()
    {
        return $this->belongsTo(V4User::class, 'player_id');
    }

    public function portfolioSubs()
    {
        return $this->morphMany(V4PlayerPortfolioSub::class, 'subable');
    }

}