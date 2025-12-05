<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class V4SuspendReason extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = ['title', 'description', 'active'];
}
