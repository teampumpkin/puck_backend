<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Tymon\JWTAuth\Contracts\JWTSubject;
use Illuminate\Support\Facades\Log;

use Carbon\Carbon;

use Illuminate\Database\Eloquent\Casts\Attribute;

class V4User extends Authenticatable implements JWTSubject
{
    use HasFactory, Notifiable, SoftDeletes;

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
        'provider',
        'provider_id',
        'profile_photo',
        'followers_count',
        'followings_count'
    ];
    protected $hidden   = ['password'];

    protected $casts    = [
        'date_of_birth' => 'date',
        'is_child' => 'boolean',
        'enable_private_account' => 'boolean',
        'receive_news_offers' => 'boolean',
        'terms_accepted' => 'boolean',
        'is_onboarded' => 'boolean'
    ];

    protected $appends = [
        'block_status',
        'name',
        'is_suspended',
        'is_banned',
        // 'age'
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

    public function notificationPreferences()
    {
        return $this->hasOne(V4NotificationPreference::class, 'user_id');
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

        if (!$currentUserId) {
            return [
                'you_blocked_them' => false,
                'they_blocked_you' => false,
                'is_blocked' => false,
            ];
        }

        return [
            'you_blocked_them' => $this->hasBlocked($currentUserId),
            'they_blocked_you' => $this->isBlockedBy($currentUserId),
            'is_blocked' => $this->hasBlocked($currentUserId) || $this->isBlockedBy($currentUserId),
        ];
    }

    public function getNameAttribute(): ?string
    {
        $first = trim($this->first_name ?? '');
        $last = trim($this->last_name ?? '');

        $fullName = trim("$first $last");

        return $fullName !== '' ? $fullName : null;
    }


    public function getAgeAttribute(): ?int
    {
        if (!$this->date_of_birth) {
            return null;
        }
        return Carbon::now()->diffInYears($this->date_of_birth) ?? 0;
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
            'evaluatorProfile',
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

    public function evaluatorAssignments()
    {
        return $this->hasMany(EvaluatorAssignment::class, 'evaluator_id');
    }

    // posts
    public function posts()
    {
        return $this->hasMany(V4Post::class, 'user_id');
    }

    public function likes()
    {
        return $this->hasMany(V4PostLike::class);
    }

    public function comments()
    {
        return $this->hasMany(V4PostComment::class);
    }

    /**
     * Users that this user is following.
     */
    public function following()
    {
        return $this->belongsToMany(V4User::class, 'v4_follows', 'follower_id', 'following_id')->wherePivot('status', 'accepted')->withTimestamps();
    }

    /**
     * Users that follow this user.
     */
    public function followers()
    {
        return $this->belongsToMany(V4User::class, 'v4_follows', 'following_id', 'follower_id')->wherePivot('status', 'accepted')->withTimestamps();
    }

    /**
     * Pending Follow Requests — requests awaiting this user's approval
     */
    public function pendingFollowRequests()
    {
        return $this->hasMany(V4Follow::class, 'following_id')->where('status', 'pending');
    }

    /**
     * Follow Requests Sent — requests this user sent to others
     */
    public function sentFollowRequests()
    {
        return $this->hasMany(V4Follow::class, 'follower_id')->where('status', 'pending');
    }

    /**
     * Check if current user follows another user.
     */
    public function isFollowing($userId): bool
    {
        return V4Follow::where('follower_id', $this->id)->where('following_id', $userId)->where('status', 'accepted')->exists();
    }

    /**
     * Check if another user follows this user.
     */
    public function isFollowedBy($userId): bool
    {
        return V4Follow::where('follower_id', $userId)->where('following_id', $this->id)->where('status', 'accepted')->exists();
    }

    /**
     * Check if user has pending request to follow another user.
     */
    public function hasPendingRequest($userId): bool
    {
        return V4Follow::where('follower_id', $this->id)->where('following_id', $userId)->where('status', 'pending')->exists();
    }

    /**
     * Check if user has pending request to follow another user.
     */
    public function hasSendPendingRequest($userId): bool
    {
        return V4Follow::where('follower_id', $userId)->where('following_id', $this->id)->where('status', 'pending')->exists();
    }

    public function v4Notifications()
    {
        return $this->hasMany(Notification::class, 'v4_user_id');
    }

    public function muteSettings()
    {
        return $this->hasMany(V4ChatMuteSetting::class, 'user_id');
    }


    public function getConversationWith($otherUserId): ?string
    {
        $conversation = V4Follow::where(function ($q) use ($otherUserId) {
            $q->where('follower_id', $this->id)
                ->where('following_id', $otherUserId);
        })
            ->orWhere(function ($q) use ($otherUserId) {
                $q->where('follower_id', $otherUserId)
                    ->where('following_id', $this->id);
            })
            ->whereNotNull('conversation_id')
            ->latest('updated_at')
            ->first();

        return $conversation?->conversation_id;
    }

    public function fcmTokens()
    {
        return $this->hasMany(V4UserFcmToken::class, 'user_id');
    }

    public function suspensions()
    {
        return $this->hasMany(V4SuspendedUser::class, 'user_id')->withTrashed();
    }

    public function activeSuspension()
    {
        return $this->hasOne(V4SuspendedUser::class, 'user_id')
            ->whereNull('unsuspended_at')
            ->whereNull('deleted_at');
    }

    public function bans()
    {
        return $this->hasMany(V4BannedUser::class, 'user_id')->withTrashed();
    }

    public function activeBan()
    {
        return $this->hasOne(V4BannedUser::class, 'user_id')
            ->whereNull('unbanned_at')
            ->whereNull('deleted_at');
    }

    public function getIsSuspendedAttribute(): bool
    {
        return $this->activeSuspension()->exists();
    }

    public function getIsBannedAttribute(): bool
    {
        return $this->activeBan()->exists();
    }
}
