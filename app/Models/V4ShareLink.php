<?php
// app/Models/V4ShareLink.php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

class V4ShareLink extends Model
{
    protected $table = 'v4_share_links';

    protected $fillable = [
        'token', 'shareable_type', 'shareable_id', 'created_by', 'revoked_at', 'revoked_by',
    ];

    protected $casts = ['revoked_at' => 'datetime'];

    public function shareable()
    {
        return $this->morphTo();
    }

    public function creator()
    {
        return $this->belongsTo(V4User::class, 'created_by');
    }

    public function logs()
    {
        return $this->hasMany(V4ShareLinkLog::class, 'share_link_id');
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->whereNull('revoked_at');
    }
}
