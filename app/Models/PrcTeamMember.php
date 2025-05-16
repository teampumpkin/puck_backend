<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasOne;

/**
 *
 */
class PrcTeamMember extends Model
{
    use HasFactory;

    /**
     * @var string[]
     */
    protected $guarded = ['id'];

    /**
     * @return HasOne
     */
    public function current_subscription()
    {
        return $this->hasOne(PrcSubscription::class, 'user_id', 'user_id')
            ->where('renew_on', '>', Carbon::now()->format('Y-m-d'))
            ->orderBy('id', 'DESC');
    }
}
