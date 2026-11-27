<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Role extends Model
{
    protected $fillable = [
        'name',
        'slug',
        'description',
        'permissions',
        'system',
    ];

    protected function casts(): array
    {
        return [
            'permissions' => 'array',
            'system' => 'boolean',
        ];
    }

    public function users()
    {
        return $this->hasMany(User::class);
    }

    public function allows(string $permission): bool
    {
        $permissions = $this->permissions ?: [];

        return in_array('*', $permissions, true) || in_array($permission, $permissions, true);
    }
}
