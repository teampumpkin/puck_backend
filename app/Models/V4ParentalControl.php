<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class V4ParentalControl extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = ['parent_id', 'child_id', 'enabled'];

    public function parent()
    {
        return $this->belongsTo(V4User::class, 'parent_id');  // Reference to the parent
    }

    public function child()
    {
        return $this->belongsTo(V4User::class, 'child_id');  // Reference to the child
    }
}
