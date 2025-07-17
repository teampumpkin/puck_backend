<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ScoutProfile extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id', 'scouting_years', 'level_hockey_played',
        'current_involvement_level', 'current_sport_role',
        'leagues', 'teams', 'resume', 'references'
    ];

    protected $casts = [
        'leagues' => 'array',
        'teams' => 'array',
        'references' => 'array',
    ];

    public function user()
    {
        return $this->belongsTo(V4User::class);
    }
}
