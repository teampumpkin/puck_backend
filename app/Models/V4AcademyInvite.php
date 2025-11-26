<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class V4AcademyInvite extends Model
{
    use SoftDeletes;

    protected $table = 'v4_academy_invites';

    protected $fillable = [
        'academy_id',
        'email_id',
        'phone_no',
    ];

    public function academy()
    {
        return $this->belongsTo(V4Academy::class, 'academy_id');
    }
}
