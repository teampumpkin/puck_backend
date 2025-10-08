<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SuperAdminProfile extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',

    ];

    protected $casts = [
        'leagues' => 'array',
        'references' => 'array',
    ];

    public function user()
    {
        return $this->belongsTo(V4User::class);
    }
}
