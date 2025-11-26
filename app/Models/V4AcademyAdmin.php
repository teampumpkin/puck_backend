<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class V4AcademyAdmin extends Model
{
    use SoftDeletes;

    protected $table = 'v4_academy_admins';

    protected $fillable = [
        'academy_id',
        'admin_id',
    ];

    public function academy()
    {
        return $this->belongsTo(V4Academy::class, 'academy_id');
    }

    public function admin()
    {
        return $this->belongsTo(V4User::class, 'admin_id');
    }
}
