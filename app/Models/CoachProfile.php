<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class CoachProfile extends Model
{
    use HasFactory;
    protected $fillable = [
        'user_id', 'leagues', 'teams'
    ];

    protected $casts = [
        'leagues' => 'array',
        'teams' => 'array',
    ];

    public function user()
    {
        return $this->belongsTo(V4User::class);
    }
}
