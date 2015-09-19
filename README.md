# Laravel Fortress

## Roles and Permissions for Laravel's Authorization
[Laravel](http://laravel.com/) 5.1.11 already ships with a great [Authorization](http://laravel.com/docs/5.1/authorization) system. Fortress aims to add the funcionality of Roles and Permissions in an easy to use, non-invasive manner.

## Installation in 3 easy steps
**Require the package with composer**:
```
composer require lbausch/laravel-fortress
```

**Add the Service Provider in `config/app.php`**:
```
Bausch\LaravelFortress\ServiceProvider::class,
```

**Publish database migration and config file**:
```
php artisan vendor:publish --provider="Bausch\LaravelFortress\ServiceProvider"
```
Run `php artisan migrate` to finish installation (the Facade `Fortress` is added automagically).


## Using Laravel Fortress

### Guard your Models
Every Model (User, Group, ...) that should be protected by Fortress needs to implement the Contract `FortressGuardContract` and may use the Trait `FortressGuardTrait` (in addition to `AuthorizableContract` and `Authorizable`).

```php
...
use Bausch\LaravelFortress\Contracts\FortressGuardContract;
use Bausch\LaravelFortress\Traits\FortressGuardTrait as FortressGuard;

class User extends Model implements AuthenticatableContract,
                                    AuthorizableContract,
                                    CanResetPasswordContract,
                                    FortressGuardContract
{
    use Authenticatable, Authorizable, CanResetPassword, FortressGuard;
...					
```

After that the following methods are available on the Model:
+ `assignRole($role_name, $resource = null)`: Assign a Role (for a Resource) to the Model
+ `revokeRole($role_name, $resource = null)`: Revoke a Role (for a Resource) from the Model
+ `hasRole($role_name, $resource = null)`: Check for a Role (for a Resource)
+ `hasPermission($permission_name, $resource = null)`: Check for a Permission (for a Resource)
+ `destroyRoles()`: Destroy all Roles a Model has

You also can define a method called `fortress_relations()` on your model and return a `\Illuminate\Support\Collection` of additional Models which should be checked (e.g. Groups):

```php
...
    /**
     * Fortress Relations.
     *
     * @return \Illuminate\Support\Collection
     */
    public function fortress_relations()
    {
        return $this->groups;
    }

    /**
     * Groups.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsToMany
     */
    public function groups()
    {
        return $this->belongsToMany(Group::class, 'group_members');
    }
...
```

### Global Roles
As you may have noticed the `$resource` parameter is optional. You can assign Roles which only apply to a specific Resource or - if you omit the `$resource` parameter - assign so called "global Roles". Global Roles do not apply to a specific Resource (Post, Blog, ...) and are defined in the config file `config/laravel-fortress.php`:

```php
<?php

/*
 * Define Roles here which do not apply to a specific Resource.
 *
 * role_name => [permissions]
 */
return [
    'admin' => [
        'broadcast',
        'manageUsers',
    ],
];
```
Global Roles can be verified like normal Roles - simply use the `can()` method:  `$user->can('broadcast');`. Of course `$user->hasRole($global_role_name)` and `$user->hasPermission($global_permission_name)` works as well.


### Policies
To be able to assign Roles for a Resource you need to have a Policy for the Resource. All you need to do is to implement the `fortress_roles()` method as required by the `FortressPolicy`. Inside the `fortress_roles()` method you can specify Roles and Permissions which ONLY apply to the Resource the Policy belongs to. Make sure to name your Permissions as you would name  your Policy methods (e.g. do not use whitespaces).

```php
...
use Bausch\LaravelFortress\Contracts\FortressPolicy;

class BlogPolicy implements FortressPolicy
{
    /**
     * Fortress Roles.
     *
     * @return array
     */
    public function fortress_roles()
    {
        return [
            'owner' => [
                'edit',
                'destroy',
            ],
        ];
    }
...
```


### Middleware

To use the provided Middleware you need to add it your Kernel (`app/Http/Kernel.php`):

```php
...
    /**
     * The application's route middleware.
     *
     * @var array
     */
    protected $routeMiddleware = [
        ...
        'role' => \Bausch\LaravelFortress\Http\Middleware\VerifyGlobalRole::class,
        'permission' => \Bausch\LaravelFortress\Http\Middleware\VerifyGlobalPermission::class,
        ...
    ];
...
```

The Middleware can be used as shown in the following snippet (`app/Http/routes.php`):
```php
Route::group(['middleware' => 'role:admin'], function () {
});

Route::group(['middleware' => 'permission:broadcast'], function () {
});
```
Note that the Middleware right now is only capable of verifying global Roles and Permissions.

### Retrieving all permitted Resources
Often you may want to retrieve all Resources for which your User/Group has a certain Permission. Fortress has you covered:

```php
$readable_blogs = $user->myAllowedResources('read', Blog::class);
```
This method will return all Blogs where the User has the "read" Permission.

**Important:** The `myAllowedResources` method is quite limited when it comes to resolving the requested Resources as it produdes *a lot* of queries (one for every found Resource). Luckily you can pass in a Closure as the third argument and come up with your own resolving logic: 

```php
$readable_blogs = $user->myAllowedResources('read', Blog::class, function($resources) {
    // $resources contains a Collection of all found Resources
    return Blogs::whereIn('id', $resources->pluck('resource_id'));
});
```

### Retrieving all Models whith specific Permission
In case you want to find all Models with a specifc Permission on a given Resource just call the `allowedModels()` method on the `Fortress` Facade:

```php
$users_who_can_read_this_blog = \Fortress::allowedModels('read', $blog_instance');
```

Of course `allowedModels()` also accepts a Closure as the third argument allowing you to use your own resolving logic.

### A Note on deleting Models
Fortress hooks into the deleting process of a Model. This means if you delete a Model from your database which uses the `FortressGuardTrait` all assigned Roles will be deleted from the database. In case your Model makes use of the Soft Deletes feature all assigned Roles are kept until your Model is deleted permanently.