<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class V4HockeyListingImage extends Model
{
    use HasFactory;

    protected $fillable = [
        'listing_id',
        'image_url',
        'sort_order',
    ];

    protected $casts = [
        'sort_order' => 'integer',
    ];

    public function listing()
    {
        return $this->belongsTo(V4HockeyListing::class, 'listing_id');
    }
}
