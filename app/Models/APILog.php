<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 *
 */
class APILog extends Model
{
    use HasFactory;

    /**
     * @var string
     */
    protected $table = 'api_logs';
    /**
     * @var string[]
     */
    protected $guarded = ['id'];
}
