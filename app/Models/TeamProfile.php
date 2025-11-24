<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TeamProfile extends Model
{
    protected $table = 'team_profiles';

    protected $fillable = [
        'v4_user_id',
        'team_id',
        'team_name',
        'administrator_first_name',
        'administrator_last_name',
        'email',
        'leagues',
        'website',
        'address',
        'team_years_running',
    ];

    protected $casts = [
        'leagues' => 'array',
    ];

    protected $appends = [
        'administrator_name',
        // 'age'
    ];

    public function user()
    {
        return $this->belongsTo(V4User::class, 'v4_user_id');
    }

    public function getAdministratorNameAttribute(): ?string
    {
        $first = trim($this->administrator_first_name ?? '');
        $last  = trim($this->administrator_last_name ?? '');

        $fullName = trim("$first $last");

        return $fullName !== '' ? $fullName : null;
    }

    public function team()
    {
        return $this->belongsTo(V4Team::class, 'team_id');
    }
}
