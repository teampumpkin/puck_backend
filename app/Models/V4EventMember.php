<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class V4EventMember extends Model
{
    public const ACTION_JOIN = 'join';
    public const ACTION_LEAVE = 'leave';

    protected $table = 'v4_event_members';

    protected $fillable = ['event_id', 'user_id', 'action'];

    public function event()
    {
        return $this->belongsTo(V4Event::class, 'event_id');
    }

    public function user()
    {
        return $this->belongsTo(V4User::class, 'user_id');
    }
}
