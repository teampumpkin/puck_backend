<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class V4PostMedia extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'post_id',
        'type',
        'url',
        'mime_type',
        'order',
        'meta',
    ];

    protected $casts = [
        'meta' => 'array', // optional, helpful to auto-cast json
    ];

    public function post()
    {
        return $this->belongsTo(V4Post::class);
    }

    /**
     * Public media columns for feed/detail responses: display dimensions
     * from meta, without internal keys (storage_path, original_name).
     */
    public function scopeWithDimensions($query)
    {
        return $query->select('id', 'post_id', 'type', 'url')
            ->selectRaw("(meta->>'width')::int as width, (meta->>'height')::int as height, (meta->>'duration_ms')::int as duration_ms");
    }
}
