<?php

declare(strict_types=1);

namespace Tests;

use Bausch\Fortress\Models\Role;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Models\Post;
use Tests\Models\User;

final class RolesTest extends BaseTestCase
{
    use RefreshDatabase;

    public function testRoleIsAssigned()
    {
        $role_name = 'posts';

        $user = factory(User::class)->create();

        $this->assertTrue($user->assignRole($role_name));

        $this->assertTrue($user->hasRole($role_name));

        $roles = Role::all();

        $this->assertTrue(1 === $roles->count());

        $role = $roles->first();

        $this->assertEquals(User::class, $role->model_class);
        $this->assertEquals($user->getKey(), $role->model_id);
        $this->assertEquals($role_name, $role->name);
        $this->assertNull($role->resource_class);
        $this->assertNull($role->resource_id);
    }

    public function testRoleIsAssignedOnResource()
    {
        $role_name = 'post.read';

        $user = factory(User::class)->create();
        $post = factory(Post::class)->create();

        $this->assertTrue($user->assignRole($role_name, $post));

        $this->assertTrue($user->hasRole($role_name, $post));
        $this->assertFalse($user->hasRole($role_name));

        $roles = Role::all();

        $this->assertTrue(1 === $roles->count());

        $role = $roles->first();

        $this->assertEquals(User::class, $role->model_class);
        $this->assertEquals($user->getKey(), $role->model_id);
        $this->assertEquals($role_name, $role->name);
        $this->assertEquals(Post::class, $role->resource_class);
        $this->assertEquals($post->getKey(), $role->resource_id);
    }

    public function testRoleIsNotAssignedTwice()
    {
        $role_name = 'posts.read';

        $user = factory(User::class)->create();

        $this->assertFalse($user->hasRole($role_name));
        $this->assertEquals(0, Role::all()->count());

        $this->assertTrue($user->assignRole($role_name));

        $this->assertTrue($user->hasRole($role_name));
        $this->assertEquals(1, Role::all()->count());

        $this->assertTrue($user->assignRole($role_name));

        $this->assertTrue($user->hasRole($role_name));
        $this->assertEquals(1, Role::all()->count());
    }

    public function testRoleIsRevoked()
    {
        $role_name = 'admin';

        $user = factory(User::class)->create();

        $this->assertTrue($user->assignRole($role_name));

        $this->assertTrue($user->hasRole($role_name));

        $user->revokeRole($role_name);

        $this->assertFalse($user->hasRole($role_name));

        $roles = Role::all();

        $this->assertEquals(0, $roles->count());
    }

    public function testRoleIsRevokedOnResource()
    {
        $role_name = 'admin';

        $user = factory(User::class)->create();
        $post = factory(Post::class)->create();

        $this->assertTrue($user->assignRole($role_name));
        $this->assertTrue($user->assignRole($role_name, $post));

        $this->assertTrue($user->hasRole($role_name));
        $this->assertTrue($user->hasRole($role_name, $post));

        $user->revokeRole($role_name, $post);

        $this->assertTrue($user->hasRole($role_name));
        $this->assertFalse($user->hasRole($role_name, $post));

        $roles = Role::all();

        $this->assertEquals(1, $roles->count());
    }
}
