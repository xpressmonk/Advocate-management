<?php

namespace App\Support;

use App\Models\Role;

class Roles
{
    public const ALL = [
        'admin',
        'manager',
        'staff',
        'viewer',
    ];

    public static function can(string $role, string $permission): bool
    {
        $record = Role::where('slug', $role)->first();
        if ($record) {
            return $record->allows($permission);
        }

        $permissions = [
            'admin' => ['*'],
            'manager' => ['view', 'create', 'update', 'delete', 'manage_users', 'manage_settings', 'view_audit'],
            'staff' => ['view', 'create', 'update'],
            'viewer' => ['view'],
        ];

        $allowed = $permissions[$role] ?? [];

        return in_array('*', $allowed, true) || in_array($permission, $allowed, true);
    }

    public static function labels(): array
    {
        return [
            'admin' => 'Administrator',
            'manager' => 'Manager',
            'staff' => 'Staff',
            'viewer' => 'Viewer',
        ];
    }
}
