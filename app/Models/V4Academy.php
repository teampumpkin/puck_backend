<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class V4Academy extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'v4_academies';

    protected $fillable = [
        'academy_name',
        'profile_photo',
        'administrator_first_name',
        'administrator_last_name',
        'email',
        'phone',
        'leagues',
        'website',
        'address',
        'city',
        'state',
        'zipcode',
        'country',
        'academy_years_running',
    ];

    protected $appends = ['members_count'];

    protected $casts = [
        'teams' => 'array',
    ];

    public function members()
    {
        return $this->hasMany(AcademyMember::class, 'academy_id');
    }

    public function admins()
    {
        return $this->hasMany(V4AcademyAdmin::class, 'academy_id');
    }

    public function isMember($userId): bool
    {
        return $this->members()
            ->where('team_id', $userId)
            ->exists();
    }

    public function getMembersCountAttribute()
    {
        return $this->members()->count();
    }
}
