<?php

namespace Tests\Feature;

use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class RoleCrudTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_create_edit_and_delete_a_custom_role(): void
    {
        $admin = $this->createUser('role-admin@example.com', 'admin');

        $this->actingAs($admin)->post(route('roles.store'), [
            'name' => 'Billing Clerk',
            'slug' => 'billing_clerk',
            'description' => 'Manages billing records',
            'permissions' => ['view', 'create', 'update'],
        ])->assertRedirect();

        $role = Role::where('slug', 'billing_clerk')->firstOrFail();
        $this->assertSame(['view', 'create', 'update'], $role->permissions);

        $this->actingAs($admin)->put(route('roles.update', $role), [
            'name' => 'Billing Manager',
            'slug' => 'billing_clerk',
            'description' => 'Manages billing',
            'permissions' => ['view', 'create', 'update', 'delete'],
        ])->assertRedirect();

        $role->refresh();
        $this->assertSame('Billing Manager', $role->name);
        $this->assertContains('delete', $role->permissions);

        $this->actingAs($admin)->delete(route('roles.destroy', $role))
            ->assertRedirect();
        $this->assertDatabaseMissing('roles', ['id' => $role->id]);
    }

    public function test_admin_can_create_and_update_users_with_role_assignment(): void
    {
        $admin = $this->createUser('user-admin@example.com', 'admin');
        $role = Role::where('slug', 'staff')->firstOrFail();

        $this->actingAs($admin)->post(route('users.store'), [
            'name' => 'New Staff',
            'email' => 'new-staff@example.com',
            'password' => 'secure-password',
            'role_id' => $role->id,
        ])->assertRedirect();

        $user = User::where('email', 'new-staff@example.com')->firstOrFail();
        $this->assertSame('staff', $user->role);
        $this->assertSame($role->id, $user->role_id);
        $originalPasswordHash = $user->password;

        $this->actingAs($admin)->get(route('users.index'))
            ->assertOk()
            ->assertSee('name="role_id"', false)
            ->assertSee('name="password"', false);

        $manager = Role::where('slug', 'manager')->firstOrFail();
        $this->actingAs($admin)->put(route('users.update', $user), [
            'name' => 'Updated Staff',
            'email' => 'new-staff@example.com',
            'role_id' => $manager->id,
            'password' => '',
        ])->assertRedirect();

        $user->refresh();
        $this->assertSame('manager', $user->role);
        $this->assertSame($manager->id, $user->role_id);
        $this->assertSame($originalPasswordHash, $user->password);

        $this->actingAs($admin)->put(route('users.update', $user), [
            'name' => 'Updated Staff',
            'email' => 'new-staff@example.com',
            'role_id' => $manager->id,
            'password' => 'replacement-password',
        ])->assertRedirect();

        $this->assertTrue(Hash::check('replacement-password', $user->fresh()->password));
    }

    public function test_viewer_cannot_manage_roles_or_create_users(): void
    {
        $viewer = $this->createUser('role-viewer@example.com', 'viewer');

        $this->actingAs($viewer)->get(route('roles.index'))->assertForbidden();
        $this->actingAs($viewer)->post(route('users.store'), [
            'name' => 'Blocked User',
            'email' => 'blocked@example.com',
            'password' => 'secure-password',
            'role_id' => Role::where('slug', 'staff')->value('id'),
        ])->assertForbidden();
    }

    public function test_builtin_role_cannot_be_deleted(): void
    {
        $admin = $this->createUser('builtin-admin@example.com', 'admin');
        $adminRole = Role::where('slug', 'admin')->firstOrFail();

        $this->actingAs($admin)->delete(route('roles.destroy', $adminRole))
            ->assertRedirect()
            ->assertSessionHasErrors('role');
        $this->assertDatabaseHas('roles', ['id' => $adminRole->id]);
    }

    private function createUser(string $email, string $role): User
    {
        $roleRecord = Role::where('slug', $role)->first();

        return User::create([
            'name' => ucfirst($role).' User',
            'email' => $email,
            'password' => 'test-password',
            'role' => $role,
            'role_id' => $roleRecord?->id,
        ]);
    }
}
