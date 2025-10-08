<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TeamProfile extends Model
{
    use HasFactory;

    protected $fillable = [
        'v4_user_id',
        'team_name',
        'administrator_first_name',
        'administrator_last_name',
        'email',
        'leagues',
        'website',
        'address',
        'team_years_running'
    ];

    protected $casts = [
        'leagues' => 'array'
    ];

    public function user()
    {
        return $this->belongsTo(V4User::class, 'v4_user_id');
    }
}
