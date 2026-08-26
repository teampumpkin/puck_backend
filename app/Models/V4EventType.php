<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Cache;

class V4EventType extends Model
{
    use HasFactory, SoftDeletes;

    public const CACHE_KEY = 'v4_event_types_active';

    protected $fillable = ['name', 'active', 'sort_order'];

    protected $casts = [
        'active' => 'boolean',
        'sort_order' => 'integer',
    ];

    /** Active type names, ordered. Cached; auto-busted on any write. */
    public static function activeNames(): array
    {
        return Cache::rememberForever(self::CACHE_KEY, fn () => self::query()
            ->where('active', true)
            ->orderBy('sort_order')
            ->orderBy('name')
            ->pluck('name')
            ->all());
    }

    protected static function booted(): void
    {
        $bust = fn () => Cache::forget(self::CACHE_KEY);
        static::saved($bust);
        static::deleted($bust);
        static::restored($bust);
    }
}
