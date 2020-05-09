<?php

namespace Bausch\Fortress\Models;

use Illuminate\Database\Eloquent\Model;

class Role extends Model
{
    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'fortress_roles';

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'model_class',
        'model_id',
        'name',
        'resource_class',
        'resource_id',
    ];
}
