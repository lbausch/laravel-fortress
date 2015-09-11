<?php

namespace Bausch\LaravelFortress;

use Closure;
use Illuminate\Contracts\Auth\Access\Gate as GateContract;
use Bausch\LaravelFortress\Models\Grant;
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

        $roles = [];

        foreach ($policy_roles as $role_name => $permissions) {
            if (in_array($permission_name, $permissions)) {
                $roles[] = $role_name;
            }
        }

        $grants = Grant::whereIn('role', $roles)
            ->where('resource_type', get_class($resource))
            ->where('resource_id', $resource->getKey())
            ->get();

        if ($resolver) {
            return $resolver($grants);
        }

        $return = collect();

        foreach ($grants as $grant) {
            $tmp = app($grant->model_type);
            $tmp->find($grant->model_id);

            $return->push($tmp);
        }

        return $return;
    }
}