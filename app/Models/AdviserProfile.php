<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AdviserProfile extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id', 'business_name', 'business_phone', 'website',
        'address', 'leagues', 'level_hockey_played', 'current_involvement_level',
        'current_sport_role', 'number_of_years_experience', 'resume', 'references'
    ];

    protected $casts = [
        'leagues' => 'array',
        'references' => 'array',
    ];

    public function user()
    {
        return $this->belongsTo(V4User::class);
    }
}
