<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class V4Team extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'v4_teams';

    protected $fillable = [
        'team_name',
        'profile_photo',
        'administrator_first_name',
        'administrator_last_name',
        'email',
        'leagues',
        'website',
        'address',
        'team_years_running',
        'phone',
        'city',
        'state',
        'zipcode',
        'country',
    ];

    protected $casts = [
        'leagues' => 'array',
    ];

    public function members()
    {
        return $this->hasMany(TeamMember::class, 'team_id');
    }

    public function isMember($userId): bool
    {
        return $this->members()
            ->where('player_id', $userId)
            ->exists();
    }
}
