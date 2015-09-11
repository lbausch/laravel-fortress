<?php

namespace Bausch\LaravelFortress\Contracts;

interface FortressPolicy
{
    /**
     * Roles and their Permissions.
     *
     * @return array
     */
    public function fortress_roles();
}
