<?php

namespace Bausch\LaravelFortress;

use Closure;
use Illuminate\Contracts\Auth\Access\Gate as GateContract;
use Bausch\LaravelFortress\Contracts\FortressGuard as GuardContract;
use Bausch\LaravelFortress\Models\Grant;
use Symfony\Component\HttpKernel\Exception\HttpException;

class Guard implements GuardContract
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
     * @param object $model
     */
    public function __construct($model)
    {
        $this->model = $model;

        // If the model has relations, also fetch their Grants
        if (method_exists($this->model, 'fortress_relations')) {
            $model_relations = $this->model->fortress_relations();

            if ($model_relations->count() > 0) {
                $this->relations = Grant::where(function ($query) use ($model_relations) {
                    foreach ($model_relations as $relation) {
                        $query->orWhere(function ($q2) use ($relation) {
                            $q2->where('model_type', get_class($relation))
                                ->where('model_id', $relation->getKey());
                        });
                    }
                })->get();
            }
        }

        // Fallback: Initialize model relations with empty collection
        if (!$this->relations) {
            $this->relations = collect();
        }

        // Get a new Gate instance
        $this->gate = app(GateContract::class);

        // Get all Grants for the model
        $this->grants = Grant::where('model_type', get_class($this->model))
            ->where('model_id', $this->model->getKey())
            ->get();
    }

    /**
     * Authorize global.
     *
     * @param string $ability
     *
     * @throws HttpException
     */
    public function authorizeGlobal($ability)
    {
        if (!$this->canGlobal($ability)) {
            throw new HttpException(403, 'This action is unauthorized.');
        }
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

        $policy = $this->gate->getPolicyFor($resource);

        if (!method_exists($policy, 'fortress_roles')) {
            return false;
        }

        $roles = [];

        foreach ($policy->fortress_roles() as $role_name => $permissions) {
            if (in_array($permission_name, $permissions)) {
                $roles[] = $role_name;
            }
        }

        foreach ($this->grants as $grant) {
            if (in_array($grant->role, $roles) && $grant->resource_type === get_class($resource) && $grant->resource_id == $resource->getKey()) {
                return true;
            }
        }

        foreach ($this->relations as $grant) {
            if (in_array($grant->role, $roles) && $grant->resource_type === get_class($resource) && $grant->resource_id == $resource->getKey()) {
                return true;
            }
        }

        return false;
    }

    /**
     * Can global.
     *
     * @param string $ability
     *
     * @return bool
     */
    public function canGlobal($ability)
    {
        $global_roles = config('fortress.global_roles', []);

        $roles = [];

        foreach ($global_roles as $role_name => $abilities) {
            if (in_array($ability, $abilities)) {
                $roles[] = $role_name;
            }
        }

        $filtered = $this->grants->filter(function ($grant) use ($roles) {
            if (is_null($grant->resouce_type) && is_null($grant->resource_id) && in_array($grant->role, $roles)) {
                return true;
            }

            return false;
        });

        return $filtered->count() > 0 ? true : false;
    }

    /**
     * Assign Role.
     *
     * @param string $role_name
     * @param object $resource
     *
     * @return bool
     */
    public function assignRole($role_name, $resource)
    {
        if ($this->hasRole($role_name, $resource)) {
            return true;
        }

        $grant = new Grant();
        $grant->model_type = get_class($this->model);
        $grant->model_id = $this->model->getKey();
        $grant->role = $role_name;
        $grant->resource_type = get_class($resource);
        $grant->resource_id = $resource->getKey();

        return $grant->save();
    }

    /**
     * Assign global Role.
     *
     * @param string $role_name
     *
     * @return bool
     */
    public function assignGlobalRole($role_name)
    {
        if ($this->hasGlobalRole($role_name)) {
            return true;
        }

        $grant = new Grant();
        $grant->model_type = get_class($this->model);
        $grant->model_id = $this->model->getKey();
        $grant->role = $role_name;

        return $grant->save();
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
            if (in_array($grant->role, $roles) && $grant->resource_type === $model_class_name) {
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
     * Revoke role.
     *
     * @param string $role_name
     * @param object $resource
     *
     * @return bool
     */
    public function revokeRole($role_name, $resource)
    {
        // Search Grants in Cache
        $delete_grants = $this->grants->filter(function ($grant) use ($role_name, $resource) {
            if ($grant->role === $role_name && $grant->resource_type === get_class($resource) && $grant->resource_id == $resource->getKey()) {
                return true;
            }

            return false;
        });

        if (!$delete_grants->count()) {
            return true;
        }

        $delete = Grant::where('model_type', get_class($this->model))
            ->where('model_id', $this->model->getKey())
            ->where('role', $role_name)
            ->where('resource_type', get_class($resource))
            ->where('resource_id', $resource->getKey())
            ->delete();

        foreach ($delete_grants->pluck('id') as $id) {
            $this->grants->forget($id);
        }

        return $delete;
    }

    /**
     * Has Role.
     *
     * @param string $role_name
     * @param object $resource
     *
     * @return bool
     */
    public function hasRole($role_name, $resource)
    {
        $filtered = $this->grants->filter(function ($grant) use ($role_name, $resource) {
            if ($grant->role === $role_name && $grant->resource_type === get_class($resource) && $grant->resource_id == $resource->getKey()) {
                return true;
            }

            return false;
        });

        return $filtered->count() > 0 ? true : false;
    }

    /**
     * Has global Role.
     *
     * @param string $role_name
     *
     * @return bool
     */
    public function hasGlobalRole($role_name)
    {
        $filtered = $this->grants->filter(function ($grant) use ($role_name) {
            if (is_null($grant->resource_type) && is_null($grant->resource_id) && $grant->role === $role_name) {
                return true;
            }

            return false;
        });

        return $filtered->count() > 0 ? true : false;
    }

    /**
     * Destroy all Grants.
     *
     * @return bool
     */
    public function destroyGrants()
    {
        return Grant::where('model_type', get_class($this->model))
            ->where('model_id', $this->model->getKey())
            ->delete();
    }
}
