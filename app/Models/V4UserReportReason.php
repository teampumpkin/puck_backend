<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class V4UserReportReason extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'reason',
        'slug',
        'description',
        'active',
        'sort_order',
    ];

    protected $casts = [
        'active' => 'boolean',
        'meta'   => 'array',
    ];

    public function reports()
    {
        return $this->hasMany(V4UserReport::class);
    }
}
