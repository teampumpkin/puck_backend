<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class V4Event extends Model
{
    use HasFactory, SoftDeletes;

    public const STATUS_PENDING_PAYMENT   = 'pending_payment';
    public const STATUS_PAYMENT_REQUESTED = 'payment_requested';
    public const STATUS_PUBLISHED         = 'published';
    public const STATUS_CANCELLED         = 'cancelled';

    protected $fillable = [
        'user_id', 'payment_request_id', 'event_type', 'name', 'description',
        'start_at', 'end_at', 'registration_deadline', 'payment_deadline',
        'country', 'province', 'city', 'venue', 'latitude', 'longitude',
        'age_min', 'age_max', 'age_division', 'cost_person_cents', 'cost_person_currency',
        'special_qualification', 'coordinator_name', 'business_name', 'contact_email',
        'contact_phone', 'website_url', 'social_links', 'scout_leagues', 'positions',
        'birth_years', 'league', 'team', 'status', 'cancel_reason', 'delete_reason',
        'published_at', 'cancelled_at',
    ];

    protected $attributes = [
        'status' => self::STATUS_PENDING_PAYMENT,
        'cost_person_currency' => 'CAD',
    ];

    protected $casts = [
        'start_at' => 'datetime',
        'end_at' => 'datetime',
        'registration_deadline' => 'datetime',
        'payment_deadline' => 'datetime',
        'published_at' => 'datetime',
        'cancelled_at' => 'datetime',
        'latitude' => 'float',
        'longitude' => 'float',
        'age_min' => 'integer',
        'age_max' => 'integer',
        'cost_person_cents' => 'integer',
        'social_links' => 'array',
        'scout_leagues' => 'array',
        'positions' => 'array',
        'birth_years' => 'array',
    ];

    public function creator()
    {
        return $this->belongsTo(V4User::class, 'user_id');
    }

    public function paymentRequest()
    {
        return $this->belongsTo(V4PaymentRequest::class, 'payment_request_id');
    }

    public function media()
    {
        return $this->hasMany(V4EventMedia::class, 'event_id')->orderBy('sort_order');
    }

    public function memberActions()
    {
        return $this->hasMany(V4EventMember::class, 'event_id');
    }

    public function scopePublished($query)
    {
        return $query->where('status', self::STATUS_PUBLISHED);
    }
}
