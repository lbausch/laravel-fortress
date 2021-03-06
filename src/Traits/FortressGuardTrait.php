<?php

namespace Bausch\LaravelFortress\Traits;

use Closure;
use Bausch\LaravelFortress\Contracts\FortressGuardContract;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Collection;

trait FortressGuardTrait
{
    /**
     * Fortress Guard instance.
     *
     * @var FortressGuardContract
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
     * @return FortressGuardContract
     */
    public function callFortressGuard()
    {
        // Simple singleton
        if (!$this->fortress_guard) {
            $this->fortress_guard = app()->makeWith(FortressGuardContract::class, [
                'model' => $this,
            ]);
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
     * Get allowed Resources.
     *
     * @param string       $permission_name
     * @param string       $resource_class_name
     * @param Closure|null $resolver
     *
     * @return Collection
     */
    public function myAllowedResources($permission_name, $resource_class_name, Closure $resolver = null)
    {
        return $this->callFortressGuard()
            ->myAllowedResources($permission_name, $resource_class_name, $resolver);
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
     * Remove Role (alias).
     *
     * @param string $role_name
     * @param object $resource
     *
     * @return bool
     */
    public function removeRole($role_name, $resource = null)
    {
        return $this->revokeRole($role_name, $resource);
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
