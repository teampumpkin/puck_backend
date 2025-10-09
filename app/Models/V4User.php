<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Tymon\JWTAuth\Contracts\JWTSubject;

use Carbon\Carbon;

use Illuminate\Database\Eloquent\Casts\Attribute;

class V4User extends Authenticatable implements JWTSubject
{
    use HasFactory, Notifiable;

    protected $fillable = [
        'email',
        'phone',
        'password',
        'first_name',
        'last_name',
        'date_of_birth',
        'country',
        'state',
        'city',
        'zip',
        'is_child',
        'parent_id',
        'username',
        'enable_private_account',
        'receive_news_offers',
        'terms_accepted',
        'role',
        'is_onboarded',
        'otp',
        'otp_expiry',
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

    protected $appends = ['block_status', 'name', 'age'];

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

    public function evaluatorProfile()
    {
        return $this->hasOne(EvaluatorProfile::class, 'v4_user_id');
    }

    public function superAdminProfile()
    {
        return $this->hasOne(SuperAdminProfile::class, 'v4_user_id');
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
    /**
     * Get users blocked by this user
     */
    public function blockedUsers(): HasMany
    {
        return $this->hasMany(BlockedUser::class, 'blocker_id');
    }

    /**
     * Get users who blocked this user
     */
    public function blockedByUsers(): HasMany
    {
        return $this->hasMany(BlockedUser::class, 'blocked_id');
    }

    /**
     * Check if this user has blocked another user
     */
    public function hasBlocked($userId): bool
    {
        return $this->blockedUsers()
            ->where('blocked_id', $userId)
            ->active()
            ->exists();
    }

    /**
     * Check if this user is blocked by another user
     */
    public function isBlockedBy($userId): bool
    {
        return $this->blockedByUsers()
            ->where('blocker_id', $userId)
            ->active()
            ->exists();
    }

    /**
     * Get active blocked users
     */
    public function getActiveBlockedUsers()
    {
        return $this->blockedUsers()->active()->get();
    }

    /**
     * Get block history
     */
    public function getBlockHistory()
    {
        return BlockedUser::where('blocker_id', $this->id)
            ->orWhere('blocked_id', $this->id)
            ->with(['blocker', 'blocked'])
            ->orderBy('created_at', 'desc')
            ->get();
    }


    // Traditional accessor for Laravel 8
    public function getBlockStatusAttribute()
    {
        $currentUserId = auth()->id();
        return [
            'you_blocked_them' => $this->hasBlocked($currentUserId),
            'they_blocked_you' => $this->isBlockedBy($currentUserId),
            'is_blocked' => $this->hasBlocked($currentUserId) || $this->isBlockedBy($currentUserId),
        ];
    }

    public function getNameAttribute(): string
    {
        return $this->first_name . ' ' . $this->last_name;
    }


    public function getAgeAttribute(): ?int
    {
        if (!$this->date_of_birth) {
            return null;
        }
        return Carbon::now()->diffInYears($this->date_of_birth);
    }

    public function getProfileDataAttribute()
    {
        $profileRelations = [
            'playerProfile',
            'coachProfile',
            'teamProfile',
            'scoutProfile',
            'academyProfile',
            'organizerProfile',
            'adviserProfile',
            'parentProfile',
            'fanProfile',
            'superAdminProfile',
        ];

        foreach ($profileRelations as $relation) {
            if ($this->relationLoaded($relation) && $this->$relation) {
                return $this->$relation;
            }
        }

        return null;
    }


    /**
     * Eager load block relationships for current user
     */
    public function scopeWithBlockStatus($query)
    {
        $currentUserId = auth()->id();
        return $query->with([
            'blockedUsers' => function ($query) use ($currentUserId) {
                $query->where('blocked_id', $currentUserId)->active();
            },
            'blockedByUsers' => function ($query) use ($currentUserId) {
                $query->where('blocker_id', $currentUserId)->active();
            }
        ]);
    }
}
