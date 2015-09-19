<?php

namespace Bausch\LaravelFortress\Contracts;

use Closure;

interface FortressGuardContract
{
    /**
     * Get all resources on which the Model has the requested ability.
     *
     * @param string       $permission_name
     * @param string       $model_class_name
     * @param Closure|null $resolver
     *
     * @return Collection
     */
    public function myAllowedResources($permission_name, $model_class_name, Closure $resolver = null);

    /**
     * Has Role.
     *
     * @param string      $role_name
     * @param object|null $resource
     *
     * @return bool
     */
    public function hasRole($role_name, $resource = null);

    /**
     * Has Permission.
     *
     * @param string      $permission_name
     * @param object|null $resource
     *
     * @return bool
     */
    public function hasPermission($permission_name, $resource = null);

    /**
     * Assign Role.
     *
     * @param string $role_name
     * @param object $resource
     *
     * @return bool
     */
    public function assignRole($role_name, $resource = null);

    /***
     * Revoke role.
     *
     * @param string      $role_name
     * @param object|null $resource
     *
     * @return bool
     */
    public function revokeRole($role_name, $resource = null);

    /**
     * Destroy all Roles of the Model.
     *
     * @return bool
     */
    public function destroyRoles();
}
