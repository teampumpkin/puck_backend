<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class AcademyMember extends Model
{
    use SoftDeletes;

    protected $table = 'academy_member';

    protected $fillable = [
        'academy_id',
        'added_by',
        'player_id',
        'removed_by'
    ];

    public function academy()
    {
        return $this->belongsTo(V4Academy::class, 'academy_id');
    }

    public function team()
    {
        return $this->belongsTo(V4Team::class, 'team_id');
    }

    public function addedBy()
    {
        return $this->belongsTo(V4User::class, 'added_by');
    }

    public function removedBy()
    {
        return $this->belongsTo(V4User::class, 'removed_by');
    }

    public function player()
    {
        return $this->belongsTo(V4User::class, 'player_id');
    }
}
