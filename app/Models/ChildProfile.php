<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ChildProfile extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'teams',
        'leagues',
        'position',
        'gender',
        'username',
        'password'
    ];
    protected $casts = [
        'teams' => 'array',
        'leagues' => 'array',
    ];


    public function user()
    {
        return $this->belongsTo(V4User::class);
    }
}
