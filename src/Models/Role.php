<?php

namespace Bausch\LaravelFortress\Models;

use Illuminate\Database\Eloquent\Model;

class Role extends Model
{
    /**
     * The database table used by the model.
     *
     * @var string
     */
    protected $table = 'fortress_roles';

    /**
     * The attributes that should be casted to native types.
     *
     * @var array
     */
    protected $casts = [
        'id' => 'int',
    ];

    /**
     * Get id.
     *
     * @return int
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * Get Model type.
     *
     * @return string
     */
    public function getModelType()
    {
        return $this->model_type;
    }

    /**
     * Get Model id.
     *
     * @return mixed
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
    public function getRoleName()
    {
        return $this->role_name;
    }

    /**
     * Get Resource type.
     *
     * @return string
     */
    public function getResourceType()
    {
        return $this->resource_type;
    }

    /**
     * Get Resource id.
     *
     * @return mixed
     */
    public function getResourceId()
    {
        return $this->resource_id;
    }

    /**
     * Get created at.
     *
     * @return \Carbon\Carbon
     */
    public function getCreatedAt()
    {
        return $this->created_at;
    }

    /**
     * Get updated at.
     *
     * @return \Carbon\Carbon
     */
    public function getUpdatedAt()
    {
        return $this->updated_at;
    }

    /**
     * Set Model.
     *
     * @param \Illuminate\Database\Eloquent\Model $model
     *
     * @throws \Exception
     */
    public function setModel($model)
    {
        if (!is_object($model)) {
            throw new \Exception('Invalid Model');
        }

        $this->setModelType(get_class($model));
        $this->setModelId($model->getKey());
    }

    /**
     * Set Model type.
     *
     * @param string $type
     */
    public function setModelType($type)
    {
        $this->model_type = $type;
    }

    /**
     * Set Model id.
     *
     * @param int|string $id
     */
    public function setModelId($id)
    {
        $this->model_id = $id;
    }

    /**
     * Set Role name.
     *
     * @param string $name
     */
    public function setRoleName($name)
    {
        $this->role_name = $name;
    }

    /**
     * Set Resource.
     *
     * @param \Illuminate\Database\Eloquent\Model $resource
     *
     * @throws \Exception
     */
    public function setResource($resource)
    {
        if (!is_object($resource)) {
            throw new \Exception('Invalid Resource');
        }

        $this->setResourceType(get_class($resource));
        $this->setResourceId($resource->getKey());
    }

    /**
     * Set Resource type.
     *
     * @param string $type
     */
    public function setResourceType($type)
    {
        $this->resource_type = $type;
    }

    /**
     * Set Resource id.
     *
     * @param int|string $id
     */
    public function setResourceId($id)
    {
        $this->resource_id = $id;
    }
}
