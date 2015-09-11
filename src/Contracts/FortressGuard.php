<?php

namespace Bausch\LaravelFortress\Contracts;

interface FortressGuard
{
    /**
     * Has Role.
     *
     * @param string $role_name
     * @param object $resource
     *
     * @return bool
     */
    public function hasRole($role_name, $resource);

    /**
     * Has global Role.
     *
     * @param string $role_name
     *
     * @return bool
     */
    public function hasGlobalRole($role_name);

    /**
     * Destroy all Grants of the Model.
     *
     * @return bool
     */
    public function destroyGrants();
}
