<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class V4PlayerPortfolioSub extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'portfolio_id',
        'subable_id',
        'subable_type',
    ];

    public function portfolio()
    {
        return $this->belongsTo(V4PlayerPortfolio::class, 'portfolio_id');
    }

    public function subable()
    {
        return $this->morphTo();
    }
}
