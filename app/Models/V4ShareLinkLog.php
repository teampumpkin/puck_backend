<?php
// app/Models/V4ShareLinkLog.php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class V4ShareLinkLog extends Model
{
    use SoftDeletes;

    protected $table = 'v4_share_link_logs';

    public $timestamps = false;

    protected $fillable = ['share_link_id', 'user_id', 'action', 'ref_code', 'created_at'];

    protected $casts = ['created_at' => 'datetime', 'deleted_at' => 'datetime'];

    public function shareLink()
    {
        return $this->belongsTo(V4ShareLink::class, 'share_link_id');
    }
}
