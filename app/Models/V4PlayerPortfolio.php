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
}
