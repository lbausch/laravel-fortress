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
}
