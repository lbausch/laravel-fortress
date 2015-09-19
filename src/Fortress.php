<?php

namespace Bausch\LaravelFortress;

use Closure;
use Illuminate\Contracts\Auth\Access\Gate as GateContract;
use Bausch\LaravelFortress\Models\Role;
use Illuminate\Support\Collection;

class Fortress implements Contracts\Fortress
{
    /**
     * Gate.
     *
     * @var GateContract
     */
    protected $gate;

    /**
     * Fortress constructor.
     *
     * @param GateContract $gate
     */
    public function __construct(GateContract $gate)
    {
        $this->gate = $gate;
    }

    /**
     * Which Models have the Permission for a resource?
     *
     * @param string       $permission_name
     * @param object       $resource
     * @param Closure|null $resolver
     *
     * @return Collection
     */
    public function allowedModels($permission_name, $resource, Closure $resolver = null)
    {
        $policy = $this->gate->getPolicyFor($resource);

        $policy_roles = $policy->fortress_roles();

        $check_roles = [];

        foreach ($policy_roles as $role_name => $permissions) {
            if (in_array($permission_name, $permissions)) {
                $check_roles[] = $role_name;
            }
        }

        $roles = Role::whereIn('role', $check_roles)
            ->where('resource_type', get_class($resource))
            ->where('resource_id', $resource->getKey())
            ->get();

        if ($resolver) {
            return $resolver($roles);
        }

        $collection = collect();

        foreach ($roles as $role) {
            $tmp_instance = app($role->getModelType());
            $tmp_instance->find($role->getModelId());

            $collection->push($tmp_instance);
        }

        return $collection;
    }

    /**
     * Get all Models for a specific Resource.
     *
     * @param object      $resource
     * @param string|null $filter_class
     */
    public function modelsForResource($resource, $filter_class = null)
    {
        $roles = Role::where('resource_type', get_class($resource))
            ->where('resource_id', $resource->getKey());

        if (!is_null($filter_class)) {
            $roles->where('model_type', $filter_class);
        }

        return $roles->get();
    }

    /**
     * Destroy Role.
     *
     * @param int $id
     *
     * @return bool
     */
    public function destroyRole($id)
    {
        return Role::findOrFail($id)
            ->delete();
    }
}
