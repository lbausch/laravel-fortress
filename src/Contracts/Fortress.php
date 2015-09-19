<?php

namespace Bausch\LaravelFortress\Contracts;

use Closure;

interface Fortress
{
    /**
     * Which Models have the Permission for a resource?
     *
     * @param string       $permission_name
     * @param object       $resource
     * @param Closure|null $resolver
     *
     * @return Collection
     */
    public function allowedModels($permission_name, $resource, Closure $resolver = null);

    /**
     * Get all Models for a specific Resource.
     *
     * @param object      $resource
     * @param string|null $filter_class
     */
    public function modelsForResource($resource, $filter_class = null);

    /**
     * Destroy Grant.
     *
     * @param int $id
     *
     * @return bool
     */
    public function destroyRole($id);
}
