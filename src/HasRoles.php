<?php

namespace Bausch\Fortress;

use Bausch\Fortress\Models\Role;
use Illuminate\Database\Eloquent\Model;

trait HasRoles
{
    /**
     * Assign Role.
     *
     * @param Model $resource
     *
     * @return bool
     */
    public function assignRole(string $name, Model $resource = null)
    {
        if ($this->hasRole($name, $resource)) {
            return true;
        }

        $role = new Role([
            'model_class' => get_class($this),
            'model_id' => $this->getKey(),
            'name' => $name,
            'resource_class' => $resource ? get_class($resource) : null,
            'resource_id' => $resource ? $resource->getKey() : null,
        ]);

        return $role->save();
    }

    /**
     * Has Role?
     *
     * @param Model $resource
     *
     * @return bool
     */
    public function hasRole(string $name, Model $resource = null)
    {
        $role = Role::where('model_class', get_class($this))
            ->where('model_id', $this->getKey())
            ->where('name', $name)
            ->where('resource_class', $resource ? get_class($resource) : null)
            ->where('resource_id', $resource ? $resource->getKey() : null);

        $role = $role->first();

        return $role ? true : false;
    }

    /**
     * Revoke Role.
     *
     * @param Model $resource
     *
     * @return void
     */
    public function revokeRole(string $name, Model $resource = null)
    {
        Role::where('model_class', get_class($this))
            ->where('model_id', $this->getKey())
            ->where('name', $name)
            ->where('resource_class', $resource ? get_class($resource) : null)
            ->where('resource_id', $resource ? $resource->getKey() : null)
            ->delete();
    }
}
