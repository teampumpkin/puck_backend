<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class V4TeamInvite extends Model
{
    use SoftDeletes;

    protected $table = 'v4_team_invites';

    protected $fillable = [
        'team_id',
        'email_id',
        'phone_no',
    ];

    public function team()
    {
        return $this->belongsTo(V4Team::class, 'team_id');
    }
}
