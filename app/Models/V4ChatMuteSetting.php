<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class V4ChatMuteSetting extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = ['user_id', 'chat_id', 'duration', 'muted_until', 'active'];

    // Define the relationship with the User and Chat models
    public function user()
    {
        return $this->belongsTo(V4User::class, 'user_id');
    }
}
