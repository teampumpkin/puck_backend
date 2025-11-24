<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class TeamMember extends Model
{
    use SoftDeletes;

    protected $table = 'team_members';

    protected $fillable = [
        'team_id',
        'player_id',
        'added_by'
    ];

    public function team()
    {
        return $this->belongsTo(V4Team::class, 'team_id');
    }

    public function player()
    {
        return $this->belongsTo(V4User::class, 'player_id');
    }

    public function addedBy()
    {
        return $this->belongsTo(V4User::class, 'added_by');
    }
}
