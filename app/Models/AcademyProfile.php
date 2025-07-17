<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AcademyProfile extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id', 'academy_name', 'teams', 'administrator_first_name',
        'administrator_last_name', 'email', 'leagues',
        'website', 'address', 'academy_years_running', 'main_team_name'
    ];

    protected $casts = [
        'teams' => 'array',
        'leagues' => 'array',
    ];

    public function user()
    {
        return $this->belongsTo(V4User::class);
    }
}
