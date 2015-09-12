<?php

namespace Bausch\LaravelFortress\Traits;

use Bausch\LaravelFortress\Contracts\FortressGuard;
use Closure;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Collection;

trait FortressGuardTrait
{
    /**
     * Fortress Guard instance.
     *
     * @var FortressGuard
     */
    private $fortress_guard;

    /**
     * Boot trait.
     */
    public static function bootFortressGuardTrait()
    {
        // Check if model uses Soft Deleting
        if (in_array(SoftDeletes::class, class_uses(self::class))) {
            static::deleted(function ($model) {
                if (!$model->trashed()) {
                    $model->callFortressGuard()->destroyGrants();
                }
            });
        } else {
            static::deleted(function ($model) {
                $model->callFortressGuard()->destroyGrants();
            });
        }
    }

    /**
     * Call Fortress Guard.
     *
     * @return FortressGuard
     */
    public function callFortressGuard()
    {
        // Simple singleton
        if (!$this->fortress_guard) {
            $this->fortress_guard = app(FortressGuard::class, [$this]);
        }

        return $this->fortress_guard;
    }

    /**
     * Get granted resources.
     *
     * @param string       $ability
     * @param string       $model_class_name
     * @param Closure|null $resolver
     *
     * @return Collection
     */
    public function myAllowedResources($ability, $model_class_name, Closure $resolver = null)
    {
        return $this->callFortressGuard()
            ->myAllowedResources($ability, $model_class_name, $resolver);
    }

    /**
     * Has Permission.
     *
     * @param string|object|null $name
     * @param object|null        $resource
     *
     * @return bool
     */
    public function hasPermission($name = null, $resource = null)
    {
        return $this->callFortressGuard()
            ->hasPermission($name, $resource);
    }

    /**
     * Has global Permission.
     *
     * @param string $permission_name
     *
     * @return bool
     */
    public function hasGlobalPermission($permission_name)
    {
        return $this->callFortressGuard()
            ->canGlobal($permission_name);
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
        return $this->callFortressGuard()
            ->canGlobal($ability);
    }

    /**
     * Can not global.
     *
     * @param $ability
     *
     * @return bool
     */
    public function cannotGlobal($ability)
    {
        return !$this->canGlobal($ability);
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
        return $this->callFortressGuard()
            ->assignRole($role_name, $resource);
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
        return $this->callFortressGuard()
            ->assignGlobalRole($role_name);
    }

    /**
     * Revoke Role.
     *
     * @param string $role_name
     * @param object $resource
     *
     * @return bool
     */
    public function revokeRole($role_name, $resource)
    {
        return $this->callFortressGuard()
            ->revokeRole($role_name, $resource);
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
        return $this->callFortressGuard()
            ->hasRole($role_name, $resource);
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
        return $this->callFortressGuard()
            ->hasGlobalRole($role_name);
    }
}
