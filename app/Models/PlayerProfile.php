<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class PlayerProfile extends Model
{

    use HasFactory;

    protected $fillable = [
        'v4_user_id', 'teams', 'leagues', 'handedness',
        'weight', 'height', 'position', 'gender', 'permissions'
    ];

    protected $casts = [
        'teams' => 'array',
        'leagues' => 'array',
        'permissions' => 'array',
    ];

    public function user()
    {
        return $this->belongsTo(V4User::class, 'v4_user_id');
    }
}
