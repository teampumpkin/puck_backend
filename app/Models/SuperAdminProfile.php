<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SuperAdminProfile extends Model
{
    use HasFactory;

    protected $fillable = [
        'v4_user_id',
    ];

    public function user()
    {
        return $this->belongsTo(V4User::class, 'v4_user_id');
    }
}
