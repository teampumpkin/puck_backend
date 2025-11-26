<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class V4Academy extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'v4_academies';

    protected $fillable = [
        'academy_name',
        'administrator_first_name',
        'administrator_last_name',
        'email',
        'phone',
        'leagues',
        'website',
        'address',
        'city',
        'state',
        'zipcode',
        'country',
        'academy_years_running',
    ];

    protected $casts = [
        'teams' => 'array',
        'leagues' => 'array',
    ];

}
