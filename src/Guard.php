<?php

namespace Bausch\LaravelFortress;

use Closure;
use Illuminate\Contracts\Auth\Access\Gate as GateContract;
use Bausch\LaravelFortress\Contracts\FortressGuardContract;
use Bausch\LaravelFortress\Models\Grant;

class Guard implements FortressGuardContract
{
    /**
     * Gate instance.
     *
     * @var GateContract
     */
    protected $gate;

    /**
     * All Grants the guarded Model has.
     *
     * @var \Illuminate\Support\Collection
     */
    protected $grants;

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
    public function __construct($model, GateContract $gate)
    {
        $this->model = $model;
        $this->gate = $gate;

        // If the model has relations, also fetch the related Grants
        if (method_exists($this->model, 'fortress_relations')) {
            $fortress_relations = $this->model->fortress_relations();

            if ($fortress_relations->count() > 0) {
                $this->relations = Grant::where(function ($query) use ($fortress_relations) {

                    foreach ($fortress_relations as $relation) {
                        $query->orWhere(function ($query_model) use ($relation) {

                            $model_type = get_class($relation);
                            $model_id = $relation->getKey();

                            $query_model->where('model_type', $model_type)
                                ->where('model_id', $model_id);
                        });
                    }

                })->get();
            }
        }

        // Fallback: Initialize Model relations with empty Collection
        if (!$this->relations) {
            $this->relations = collect();
        }

        // Get all Grants for the Model
        $model_type = get_class($this->model);
        $model_id = $this->model->getKey();

        $this->grants = Grant::where('model_type', $model_type)
            ->where('model_id', $model_id)
            ->get();
    }

    /**
     * Get all resources on which the model has the requested ability.
     *
     * @param string       $ability
     * @param string       $model_class_name
     * @param Closure|null $resolver
     *
     * @return Collection
     */
    public function myAllowedResources($ability, $model_class_name, Closure $resolver = null)
    {
        $policy_instance = $this->gate->getPolicyFor($model_class_name);

        $policy_roles = $policy_instance->fortress_roles();

        // Try to find Roles
        $roles = [];

        foreach ($policy_roles as $role_name => $abilities) {
            if (in_array($ability, $abilities)) {
                $roles[] = $role_name;
            }
        }

        if (empty($roles)) {
            return collect();
        }

        $grants = $this->grants->merge($this->relations)->filter(function ($grant) use ($roles, $model_class_name) {
            if (in_array($grant->getRole(), $roles) && $grant->getResourceType() === $model_class_name) {
                return true;
            }

            return false;
        });

        if ($resolver) {
            return $resolver($grants);
        }

        $return = collect();

        foreach ($grants as $grant) {
            $tmp = app($grant->resource_type);
            $tmp = $tmp->find($grant->resource_id);

            if ($tmp->getKey()) {
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

        // Check all Grants the Model has
        foreach ($this->grants as $grant) {
            if ($this->checkGrant($grant, $role_name, $resource)) {
                return true;
            }
        }

        // Also check relations
        foreach ($this->relations as $grant) {
            if ($this->checkGrant($grant, $role_name, $resource)) {
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

        $roles = [];

        if (is_string($permission_name) && is_null($resource)) {
            $roles_tmp = config('laravel-fortress', []);
        } else {
            $policy = $this->gate->getPolicyFor($resource);

            if (!method_exists($policy, 'fortress_roles')) {
                return false;
            }

            $roles_tmp = $policy->fortress_roles();
        }

        foreach ($roles_tmp as $role_name => $permissions) {
            if (in_array($permission_name, $permissions)) {
                $roles[] = $role_name;
            }
        }

        foreach ($roles as $role_name) {
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

        $grant = new Grant();
        $grant->model_type = get_class($this->model);
        $grant->model_id = $this->model->getKey();
        $grant->role = $role_name;

        if (is_object($resource)) {
            $grant->resource_type = get_class($resource);
            $grant->resource_id = $resource->getKey();
        }

        return $grant->save();
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
        // Search Grants in Cache
        $delete_grants = $this->grants->filter(function ($grant) use ($role_name, $resource) {
            return $this->checkGrant($grant, $role_name, $resource);
        });

        if (!$delete_grants->count()) {
            return true;
        }

        $model_type = get_class($this->model);
        $model_id = $this->model->getKey();

        $delete = Grant::where('model_type', $model_type)
            ->where('model_id', $model_id)
            ->where('role', $role_name);

        if (is_object($resource)) {
            $resource_type = get_class($resource);
            $resource_id = $resource->getKey();

            $delete->where('resource_type', $resource_type)
                ->where('resource_id', $resource_id);
        }

        $delete->delete();

        foreach ($delete_grants->pluck('id') as $id) {
            $this->grants->forget($id);
        }

        return $delete;
    }

    /**
     * Destroy all Grants.
     *
     * @return bool
     */
    public function destroyGrants()
    {
        $model_type = get_class($this->model);
        $model_id = $this->model->getKey();

        return Grant::where('model_type', $model_type)
            ->where('model_id', $model_id)
            ->delete();
    }

    /**
     * Check Grant.
     *
     * @param Grant       $grant
     * @param string      $role_name
     * @param object|null $resource
     *
     * @return bool
     */
    private function checkGrant(Grant $grant, $role_name, $resource = null)
    {
        if (is_null($resource)) {
            return $this->checkGlobalRole($grant, $role_name);
        }

        return $this->checkRole($grant, $role_name, $resource);
    }

    /**
     * Check Role.
     *
     * @param Grant  $grant
     * @param string $role_name
     * @param object $resource
     *
     * @return bool
     */
    private function checkRole(Grant $grant, $role_name, $resource)
    {
        $resource_type = get_class($resource);
        $resource_id = $resource->getKey();

        if ($grant->getRole() === $role_name && $grant->getResourceType() === $resource_type && $grant->getResourceId() == $resource_id) {
            return true;
        }

        return false;
    }

    /**
     * Check global Role.
     *
     * @param Grant  $grant
     * @param string $role_name
     *
     * @return bool
     */
    private function checkGlobalRole(Grant $grant, $role_name)
    {
        if ($grant->getRole() === $role_name && is_null($grant->getResourceType()) && is_null($grant->getResourceId())) {
            return true;
        }

        return false;
    }
}
