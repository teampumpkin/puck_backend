<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class V4TeamAdmin extends Model
{
    use SoftDeletes;

    protected $table = 'v4_team_admins';

    protected $fillable = [
        'team_id',
        'admin_id',
        'designation',
        'name',
        'email',
        'phone',
        'location',
    ];

    public function team()
    {
        return $this->belongsTo(V4Team::class, 'team_id');
    }

    public function admin()
    {
        return $this->belongsTo(V4User::class, 'admin_id');
    }
}
