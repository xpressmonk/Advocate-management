<?php

namespace App\Http\Controllers;

use App\Models\Role;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class RoleController extends Controller
{
    public function index(): View
    {
        return view('roles.index', [
            'roles' => Role::withCount('users')->orderBy('name')->paginate(20),
            'permissionOptions' => $this->permissionOptions(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:100'],
            'slug' => ['required', 'string', 'max:100', 'regex:/^[a-z0-9_-]+$/', 'unique:roles,slug'],
            'description' => ['nullable', 'string', 'max:1000'],
            'permissions' => ['required', 'array', 'min:1'],
            'permissions.*' => ['string', 'in:'.implode(',', array_keys($this->permissionOptions()))],
        ]);

        Role::create([
            ...$validated,
            'system' => false,
        ]);

        return back()->with('success', 'Role created.');
    }

    public function update(Request $request, Role $role): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:100'],
            'slug' => ['required', 'string', 'max:100', 'regex:/^[a-z0-9_-]+$/', Rule::unique('roles', 'slug')->ignore($role->id)],
            'description' => ['nullable', 'string', 'max:1000'],
            'permissions' => ['required', 'array', 'min:1'],
            'permissions.*' => ['string', 'in:'.implode(',', array_keys($this->permissionOptions()))],
        ]);

        if ($role->system && $validated['slug'] !== $role->slug) {
            return back()->withErrors(['slug' => 'Built-in role slugs cannot be changed.']);
        }

        $role->update($validated);

        return back()->with('success', 'Role updated.');
    }

    public function destroy(Role $role): RedirectResponse
    {
        if ($role->system) {
            return back()->withErrors(['role' => 'Built-in roles cannot be deleted.']);
        }
        if ($role->users()->exists()) {
            return back()->withErrors(['role' => 'Move or delete assigned users before deleting this role.']);
        }
        $role->delete();

        return back()->with('success', 'Role deleted.');
    }

    public function permissionOptions(): array
    {
        return [
            '*' => 'Full access',
            'view' => 'View records',
            'create' => 'Create records',
            'update' => 'Update records',
            'delete' => 'Delete records',
            'manage_users' => 'Manage users and roles',
            'manage_settings' => 'Manage reminder settings',
            'view_audit' => 'View audit logs',
        ];
    }
}
