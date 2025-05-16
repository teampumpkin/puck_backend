<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class PrcModule extends Model
{
    use HasFactory;

    protected $guarded = ['id'];

    /**
     * @return BelongsToMany
     */
    public function allowToTypes()
    {
        return $this->belongsToMany(PrcUserType::class, 'prc_user_type_modules', 'module_id', 'user_type_id');
    }
}
