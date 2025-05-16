<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Class Country
 * @package App\Models
 */
class Country extends Model
{
    use HasFactory;

    /**
     * @var string[]
     */
    protected $guarded = ['id'];

    /**
     * @return HasMany
     */
    public function states()
    {
        return $this->hasMany(State::class, 'country_id', 'id');
    }

    /**
     * Get all of the users for the Country
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function users(): HasMany
    {
        return $this->hasMany(User::class, 'country_id', 'id');
    }

    protected $fillable = [
        'id',
        'country_name',
        'short_name_3_digit',
        'short_name_2_digit',
        'phone_code',
        'country_flag',
        'region',
        'emoji',
        'emoji_code',
        'status'
    ];
}
