<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PrcPlayable extends Model
{
    use HasFactory;

    /**
     * @var string
     */
    protected $table = "prc_playables";

    /**
     * @var string[]
     */
    protected $guarded = [
        'id'
    ];

    protected $casts = [
        'value' => 'float',
    ];
}
