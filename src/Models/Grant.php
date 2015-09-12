<?php

namespace Bausch\LaravelFortress\Models;

use Illuminate\Database\Eloquent\Model;

class Grant extends Model
{
    /**
     * The database table used by the model.
     *
     * @var string
     */
    protected $table = 'fortress_grants';

    /**
     * casts.
     *
     * @var array
     */
    protected $casts = [
        'id' => 'int',
    ];

    /**
     * get id.
     *
     * @return int
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * Get Model Type.
     *
     * @return string
     */
    public function getModelType()
    {
        return $this->model_type;
    }

    /**
     * Get Model Id.
     *
     * @return int
     */
    public function getModelId()
    {
        return $this->model_id;
    }

    /**
     * Get Role name.
     *
     * @return string
     */
    public function getRole()
    {
        return $this->role;
    }
}
