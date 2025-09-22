<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class V4Media extends Model
{
    use HasFactory;

    protected $fillable = [
        'v4_user_id',
        'caption',
        'media_type',
        'media_format',
        'uploaded_at',
        'media_url'
    ];

    protected $casts = [
        'uploaded_at' => 'datetime'
    ];

    /**
     * Get the user that owns the media.
     */
    public function user()
    {
        return $this->belongsTo(V4User::class, 'v4_user_id');
    }
}
