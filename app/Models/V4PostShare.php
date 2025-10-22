<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class V4PostShare extends Model
{
    use HasFactory, SoftDeletes;


    public function user()
    {
        return $this->belongsTo(V4User::class);
    }

    public function post()
    {
        return $this->belongsTo(V4Post::class);
    }
}
