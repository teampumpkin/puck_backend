<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class OrganizerProfile extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id', 'business_name', 'business_phone', 'website',
        'address', 'leagues', 'number_years_organizing',
        'link_of_previous_events', 'number_of_events_organized'
    ];

    protected $casts = [
        'leagues' => 'array',
        'link_of_previous_events' => 'array',
    ];

    public function user()
    {
        return $this->belongsTo(V4User::class);
    }
}
