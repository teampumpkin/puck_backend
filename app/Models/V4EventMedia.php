<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class V4EventMedia extends Model
{
    public const MEDIA_IMAGE = 'image';
    public const MEDIA_VIDEO = 'video';

    protected $table = 'v4_event_media';

    protected $fillable = ['event_id', 'media_type', 'url', 'thumbnail_url', 'sort_order'];

    protected $casts = ['sort_order' => 'integer'];

    public function event()
    {
        return $this->belongsTo(V4Event::class, 'event_id');
    }
}
