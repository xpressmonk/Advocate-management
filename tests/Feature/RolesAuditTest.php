<?php

namespace Tests\Feature;

use App\Models\AuditLog;
use App\Models\Role;
use App\Models\User;
use App\Support\Roles;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RolesAuditTest extends TestCase
{
    use RefreshDatabase;

    public function test_role_permission_matrix_and_helpers(): void
    {
        $this->assertTrue(Roles::can('admin', 'delete'));
        $this->assertTrue(Roles::can('manager', 'manage_users'));
        $this->assertTrue(Roles::can('staff', 'create'));
        $this->assertTrue(Roles::can('staff', 'update'));
        $this->assertFalse(Roles::can('staff', 'delete'));
        $this->assertFalse(Roles::can('viewer', 'create'));
        $this->assertTrue(Roles::can('viewer', 'view'));
    }

    public function test_admin_can_manage_users_and_mutations_are_audited(): void
    {
        $admin = $this->createUser('admin-test@example.com', 'admin');
        $staff = $this->createUser('staff-test@example.com', 'staff');

        $this->actingAs($admin)->put(route('users.update', $staff), [
            'name' => 'Updated Staff',
            'email' => 'staff-test@example.com',
            'role_id' => Role::where('slug', 'manager')->value('id'),
            'password' => '',
        ])->assertRedirect();

        $staff->refresh();
        $this->assertSame('manager', $staff->role);
        $this->assertDatabaseHas('audit_logs', [
            'user_id' => $admin->id,
            'action' => 'updated',
            'route_name' => 'users.update',
        ]);
    }

    public function test_viewer_cannot_create_or_delete_records(): void
    {
        $viewer = $this->createUser('viewer-test@example.com', 'viewer');

        $this->actingAs($viewer)->post(route('tasks.store'), [
            'title' => 'Forbidden task',
            'priority' => 'normal',
            'status' => 'pending',
        ])->assertForbidden();

        $this->assertDatabaseMissing('tasks', ['title' => 'Forbidden task']);
    }

    public function test_staff_cannot_delete_records(): void
    {
        $staff = $this->createUser('staff-delete@example.com', 'staff');
        $target = $this->createUser('delete-target@example.com', 'viewer');

        $this->actingAs($staff)
            ->delete(route('users.destroy', $target))
            ->assertForbidden();
    }

    public function test_manager_can_view_audit_log_but_viewer_cannot(): void
    {
        $manager = $this->createUser('manager-audit@example.com', 'manager');
        $viewer = $this->createUser('viewer-audit@example.com', 'viewer');

        AuditLog::create([
            'user_id' => $manager->id,
            'action' => 'created',
            'route_name' => 'test.create',
            'changes' => ['title' => 'Test'],
        ]);

        $this->actingAs($manager)->get(route('audit.index'))
            ->assertOk()
            ->assertSee('Audit log');

        $this->actingAs($viewer)->get(route('audit.index'))
            ->assertForbidden();
    }

    private function createUser(string $email, string $role): User
    {
        return User::create([
            'name' => ucfirst($role).' User',
            'email' => $email,
            'password' => 'test-password',
            'role' => $role,
        ]);
    }
}
