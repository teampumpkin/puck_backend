<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class V4UploadedVideos extends Model
{
    use SoftDeletes;

    protected $table = 'v4_uploaded_videos';

    protected $fillable = [
        'user_id',
        'file_path',
        'meta',
    ];

    protected $casts = [
        'meta' => 'array',
    ];

    public function user()
    {
        return $this->belongsTo(V4User::class, 'user_id');
    }

    public function portfolioSubs()
    {
        return $this->morphMany(V4PlayerPortfolioSub::class, 'subable');
    }

}
