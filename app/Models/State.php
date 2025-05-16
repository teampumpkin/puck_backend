<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Class State
 * @package App\Models
 */
class State extends Model
{
    use HasFactory;

    /**
     * @var string[]
     */
    protected $guarded = ['id'];

    /**
     * @return HasMany
     */
    public function cities()
    {
        return $this->hasMany(City::class, 'state_id', 'id');
    }

    protected $fillable = [
        'id',
        'country_id',
        'state_name',
        'state_code',
        'status'
    ];

    /**
     * Get all of the users for the Stete
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function users(): HasMany
    {
        return $this->hasMany(User::class, 'state_id', 'id');
    }
}
