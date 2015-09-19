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
                    $model->destroyRoles();
                }
            });
        } else {
            static::deleted(function ($model) {
                $model->destroyRoles();
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
     * Destroy all Roles.
     *
     * @return bool
     */
    public function destroyRoles()
    {
        return $this->callFortressGuard()
            ->destroyRoles();
    }

    /**
     * Get allowed resources.
     *
     * @param string       $permission_name
     * @param string       $model_class_name
     * @param Closure|null $resolver
     *
     * @return Collection
     */
    public function myAllowedResources($permission_name, $model_class_name, Closure $resolver = null)
    {
        return $this->callFortressGuard()
            ->myAllowedResources($permission_name, $model_class_name, $resolver);
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
     * Assign Role.
     *
     * @param string      $role_name
     * @param object|null $resource
     *
     * @return bool
     */
    public function assignRole($role_name, $resource = null)
    {
        return $this->callFortressGuard()
            ->assignRole($role_name, $resource);
    }

    /**
     * Has Role.
     *
     * @param string      $role_name
     * @param object|null $resource
     *
     * @return bool
     */
    public function hasRole($role_name, $resource = null)
    {
        return $this->callFortressGuard()
            ->hasRole($role_name, $resource);
    }

    /**
     * Revoke Role.
     *
     * @param string $role_name
     * @param object $resource
     *
     * @return bool
     */
    public function revokeRole($role_name, $resource = null)
    {
        return $this->callFortressGuard()
            ->revokeRole($role_name, $resource);
    }
}
