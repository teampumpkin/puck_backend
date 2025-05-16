<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

/**
 *
 */
class PrcUserType extends Model
{
    use HasFactory;

    /**
     * @var string[]
     */
    protected $guarded = ['id'];

    /**
     * @return BelongsToMany
     */
    public function allowModules()
    {
        return $this->belongsToMany(PrcModule::class, 'prc_user_type_modules', 'user_type_id', 'module_id');
    }

    public function parentType()
    {
        return $this->hasOne(PrcUserType::class, 'id', 'parent_id');
    }
}
