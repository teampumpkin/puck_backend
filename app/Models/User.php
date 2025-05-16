<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

/**
 * Class User
 * @package App\Models
 */
class User extends Authenticatable
{
    use HasFactory, Notifiable;

    /**
     * @var string
     */
    protected $table = "prc_users";

    /**
     * @var string[]
     */
    protected $guarded = [
        'id'
    ];

    /**
     * The attributes that should be hidden for arrays.
     *
     * @var array
     */
    protected $hidden = [
        'token',
        'password',
        'password_reset_pin',
        'status',
        'setting',
        'profile_picture'
    ];

    protected $casts = [
        'notification_preferences' => 'array'
    ];

    /**
     * @return HasMany
     */
    public function medias()
    {
        return $this->hasMany(PrcMedia::class, 'user_id', 'id');
    }

    /**
     * @return HasOne
     */
    public function player_league()
    {
        return $this->hasOne(PrcLeague::class, 'id', 'league');
    }

    /**
     * @return HasOne
     */
    public function player_position()
    {
        return $this->hasOne(PrcPosition::class, 'id', 'position');
    }

    /**
     * @return HasMany
     */
    public function team_managers()
    {
        return $this->hasMany(PrcTeamMember::class, 'team_id', 'id')->where('type', 'manager')->orderBy('id');
    }

    /**
     * @return HasMany
     */
    public function coaches()
    {
        return $this->hasMany(PrcTeamMember::class, 'team_id', 'id')->where('type', 'coach')->orderBy('id');
    }

    /**
     * @return HasMany
     */
    public function team_players()
    {
        return $this->hasMany(PrcTeamMember::class, 'team_id', 'id')->where('type', 'player')->orderBy('id');
    }

    /**
     * @return HasOne
     */
    public function current_subscription()
    {
        return $this->hasOne(PrcSubscription::class, 'user_id', 'id')
            ->where('renew_on', '>', Carbon::now()->format('Y-m-d'))
            ->orderBy('id', 'DESC');
    }

    /**
     * Get the country that owns the User
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function countryR(): BelongsTo
    {
        return $this->belongsTo(Country::class, "country_id", "id");
    }

    /**
     * Get the state that owns the User
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function stateR(): BelongsTo
    {
        return $this->belongsTo(State::class, "state_id", "id");
    }

    /**
     * Get the country that owns the User
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function cityR(): BelongsTo
    {
        return $this->belongsTo(City::class, "city_id", "id");
    }

    /**
     * Get number of purchases of User
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function nPurchases(): HasMany
    {
        return $this->hasMany(PrcScoutRequest::class, "source_user_id", "id");
    }

}
