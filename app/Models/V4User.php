<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Tymon\JWTAuth\Contracts\JWTSubject;

class V4User extends Authenticatable implements JWTSubject
{
    use HasFactory, Notifiable;

    protected $fillable = [
        'email', 'phone', 'password', 'first_name', 'last_name',
        'date_of_birth', 'country', 'state', 'city', 'zip',
        'is_child', 'parent_id', 'username', 'enable_private_account',
        'receive_news_offers', 'terms_accepted', 'role', 'is_onboarded', 'otp', 'otp_expiry',
        'profile_photo'
    ];

    protected $hidden = [
        'password'
    ];

    protected $casts = [
        'date_of_birth' => 'date',
        'is_child' => 'boolean',
        'enable_private_account' => 'boolean',
        'receive_news_offers' => 'boolean',
        'terms_accepted' => 'boolean',
        'is_onboarded' => 'boolean'
    ];

    public function getJWTIdentifier()
    {
        return $this->getKey();
    }

    public function getJWTCustomClaims()
    {
        return [];
    }

    // Relationships
    public function playerProfile()
    {
        return $this->hasOne(PlayerProfile::class, 'v4_user_id');
    }

    public function coachProfile()
    {
        return $this->hasOne(CoachProfile::class);
    }

    public function scoutProfile()
    {
        return $this->hasOne(ScoutProfile::class);
    }

    public function teamProfile()
    {
        return $this->hasOne(TeamProfile::class);
    }

    public function academyProfile()
    {
        return $this->hasOne(AcademyProfile::class);
    }

    public function organizerProfile()
    {
        return $this->hasOne(OrganizerProfile::class);
    }

    public function adviserProfile()
    {
        return $this->hasOne(AdviserProfile::class);
    }

    public function parentProfile()
    {
        return $this->hasOne(ParentProfile::class);
    }

    public function fanProfile()
    {
        return $this->hasOne(FanProfile::class);
    }

    public function children()
    {
        return $this->hasMany(V4User::class, 'parent_id');
    }

    public function parent()
    {
        return $this->belongsTo(V4User::class, 'parent_id');
    }

    public function media()
    {
        return $this->hasMany(V4Media::class, 'v4_user_id');
    }
}
