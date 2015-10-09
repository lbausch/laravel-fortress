<?php

namespace Bausch\LaravelFortress;

use Illuminate\Contracts\Auth\Access\Gate as GateContract;
use Bausch\LaravelFortress\Models\Role;
use Bausch\LaravelFortress\Contracts\FortressGuardContract;
use Bausch\LaravelFortress\Contracts\Fortress as FortressContract;
use Illuminate\Support\Collection;
use Closure;

class Guard implements FortressGuardContract
{
    /**
     * Gate instance.
     *
     * @var GateContract
     */
    protected $gate;

    /**
     * Fortress.
     *
     * @var FortressContract
     */
    protected $fortress;

    /**
     * All Roles the guarded Model has.
     *
     * @var \Illuminate\Support\Collection
     */
    protected $roles;

    /**
     * The Model which the Guard protects.
     *
     * @var \Illuminate\Database\Eloquent\Model
     */
    protected $model;

    /**
     * Relations of the guarded Model (e.g. Groups).
     *
     * @var \Illuminate\Support\Collection
     */
    protected $relations;

    /**
     * FortressGuard constructor.
     *
     * @param object       $model
     * @param GateContract $gate
     */
    public function __construct($model, GateContract $gate, FortressContract $fortress)
    {
        $this->gate = $gate;
        $this->model = $model;
        $this->fortress = $fortress;

        // Initialize Roles for the Model
        $this->roles = $this->initRoles();

        // Initialize Model relations
        $this->relations = $this->initRelations();
    }

    /**
     * Get all resources on which the model has the requested permission.
     *
     * @param string       $permission_name
     * @param string       $resource_class_name
     * @param Closure|null $resolver
     *
     * @return Collection
     */
    public function myAllowedResources($permission_name, $resource_class_name, Closure $resolver = null)
    {
        $policy_instance = $this->gate->getPolicyFor($resource_class_name);

        $policy_roles = $policy_instance->fortress_roles();

        // Try to find Roles
        $check_roles = [];

        foreach ($policy_roles as $role_name => $abilities) {
            if (in_array($permission_name, $abilities)) {
                $check_roles[] = $role_name;
            }
        }

        if (empty($check_roles)) {
            return collect();
        }

        $roles = $this->roles->merge($this->relations)->filter(function ($role) use ($check_roles, $resource_class_name) {
            if (in_array($role->getRoleName(), $check_roles) && $role->getResourceType() === $resource_class_name) {
                return true;
            }

            return false;
        });

        if ($resolver) {
            return $resolver($roles);
        }

        $return = collect();

        foreach ($roles as $role) {
            $tmp = app($role->getResourceType());
            $tmp = $tmp->find($role->getResourceId());

            if ($tmp && $tmp->getKey()) {
                $return->push($tmp);
            }
        }

        return $return;
    }

    /**
     * Has Role.
     *
     * @param string $role_name
     * @param object $resource
     *
     * @throws \Exception
     *
     * @return bool
     */
    public function hasRole($role_name, $resource = null)
    {
        if (!is_null($resource) && !is_object($resource)) {
            throw new \Exception('Invalid Resource');
        }

        // Check all Roles the Model has
        foreach ($this->roles as $role) {
            if ($this->checkRole($role, $role_name, $resource)) {
                return true;
            }
        }

        // Also check relations
        foreach ($this->relations as $role) {
            if ($this->checkRole($role, $role_name, $resource)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Has Permission.
     *
     * @param string|object|null $permission_name
     * @param object|null        $resource
     *
     * @return bool
     */
    public function hasPermission($permission_name = null, $resource = null)
    {
        if (is_object($permission_name) && is_null($resource)) {
            $resource = $permission_name;
            $permission_name = debug_backtrace(false, 3)[2]['function'];
        }

        $check_roles = [];

        if (is_string($permission_name) && is_null($resource)) {
            $roles_tmp = $this->fortress->getGlobalRoles();
        } else {
            $policy = $this->gate->getPolicyFor($resource);

            if (!method_exists($policy, 'fortress_roles')) {
                return false;
            }

            $roles_tmp = $policy->fortress_roles();
        }

        foreach ($roles_tmp as $role_name => $permissions) {
            if (in_array($permission_name, $permissions)) {
                $check_roles[] = $role_name;
            }
        }

        foreach ($check_roles as $role_name) {
            if ($this->hasRole($role_name, $resource)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Assign Role.
     *
     * @param string $role_name
     * @param object $resource
     *
     * @return bool
     */
    public function assignRole($role_name, $resource = null)
    {
        if ($this->hasRole($role_name, $resource)) {
            return true;
        }

        $role = app(Role::class);
        $role->setModel($this->model);
        $role->setRoleName($role_name);

        if (is_object($resource)) {
            $role->setResource($resource);
        }

        return $role->save();
    }

    /**
     * Revoke role.
     *
     * @param string      $role_name
     * @param object|null $resource
     *
     * @return bool
     */
    public function revokeRole($role_name, $resource = null)
    {
        // Search Roles in Cache
        $delete_roles = $this->roles->filter(function ($role) use ($role_name, $resource) {
            return $this->checkRole($role, $role_name, $resource);
        });

        if (!$delete_roles->count()) {
            return true;
        }

        $model_type = get_class($this->model);
        $model_id = $this->model->getKey();

        $delete = Role::where('model_type', $model_type)
            ->where('model_id', $model_id)
            ->where('role_name', $role_name);

        if (is_object($resource)) {
            $resource_type = get_class($resource);
            $resource_id = $resource->getKey();

            $delete->where('resource_type', $resource_type)
                ->where('resource_id', $resource_id);
        }

        $delete->delete();

        foreach ($delete_roles->pluck('id') as $id) {
            $this->roles->forget($id);
        }

        return $delete;
    }

    /**
     * Destroy all Model Roles.
     *
     * @return bool
     */
    public function destroyRoles()
    {
        $model_type = get_class($this->model);
        $model_id = $this->model->getKey();

        return Role::where('model_type', $model_type)
            ->where('model_id', $model_id)
            ->delete();
    }

    /**
     * Initialize Roles.
     *
     * @return \Illuminate\Support\Collection
     */
    private function initRoles()
    {
        $model_type = get_class($this->model);
        $model_id = $this->model->getKey();

        return Role::where('model_type', $model_type)
            ->where('model_id', $model_id)
            ->get();
    }

    /**
     * Initialize Model relations.
     *
     * @return \Illuminate\Support\Collection
     */
    private function initRelations()
    {
        // If the model has relations, also fetch the related Roles
        if (!method_exists($this->model, 'fortress_relations')) {
            return collect();
        }

        $fortress_relations = $this->model->fortress_relations();

        if (!$fortress_relations->count()) {
            return collect();
        }

        $relations = Role::where(function ($query) use ($fortress_relations) {

            foreach ($fortress_relations as $relation) {
                $query->orWhere(function ($query_model) use ($relation) {

                    $model_type = get_class($relation);
                    $model_id = $relation->getKey();

                    $query_model->where('model_type', $model_type)
                        ->where('model_id', $model_id);
                });
            }

        })->get();

        // Fallback: Initialize Model relations with empty Collection
        if (!$relations) {
            return  collect();
        }

        return $relations;
    }

    /**
     * Check Role.
     *
     * @param Role        $role
     * @param string      $role_name
     * @param object|null $resource
     *
     * @return bool
     */
    private function checkRole(Role $role, $role_name, $resource = null)
    {
        if (is_null($resource)) {
            return $this->checkGlobalRole($role, $role_name);
        }

        return $this->checkResourceRole($role, $role_name, $resource);
    }

    /**
     * Check Role.
     *
     * @param Role   $role
     * @param string $role_name
     * @param object $resource
     *
     * @return bool
     */
    private function checkResourceRole(Role $role, $role_name, $resource)
    {
        $resource_type = get_class($resource);
        $resource_id = $resource->getKey();

        if ($role->getRoleName() === $role_name && $role->getResourceType() === $resource_type && $role->getResourceId() == $resource_id) {
            return true;
        }

        return false;
    }

    /**
     * Check global Role.
     *
     * @param Role   $role
     * @param string $role_name
     *
     * @return bool
     */
    private function checkGlobalRole(Role $role, $role_name)
    {
        if ($role->getRoleName() === $role_name && is_null($role->getResourceType()) && is_null($role->getResourceId())) {
            return true;
        }

        return false;
    }
}
