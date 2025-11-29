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
        'conversation_id',
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

    public static function adminAcademiesTeamsWithMember($adminId, $playerId)
    {
        $academyIds = V4AcademyAdmin::where('admin_id', $adminId)
            ->pluck('academy_id');

        return V4Team::whereIn('academy_id', $academyIds)
            ->get()
            ->map(fn($team) => [
                'team' => $team,
                'is_member' => TeamMember::where('player_id', $playerId)
                    ->where('team_id', $team->id)
                    ->exists(),
            ]);
    }

    public static function adminAcademiesMembers($adminId, $playerId)
    {
        $academyIds = V4AcademyAdmin::where('admin_id', $adminId)
            ->pluck('academy_id');


        return V4Academy::whereIn('id', $academyIds)
            ->get()
            ->map(fn($academy) => [
                'academy' => $academy,
                'is_member' => AcademyMember::where('academy_id', $academy->id)
                    ->where('player_id', $playerId)
                    ->exists(),
            ]);
    }
}
