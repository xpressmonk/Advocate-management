<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('roles', function (Blueprint $table): void {
            $table->id();
            $table->string('name', 100);
            $table->string('slug', 100)->unique();
            $table->text('description')->nullable();
            $table->json('permissions')->nullable();
            $table->boolean('system')->default(false);
            $table->timestamps();
        });

        Schema::table('users', function (Blueprint $table): void {
            $table->foreignId('role_id')->nullable()->after('role')->constrained('roles')->nullOnDelete();
        });

        $defaults = [
            'admin' => ['name' => 'Administrator', 'permissions' => ['*']],
            'manager' => ['name' => 'Manager', 'permissions' => ['view', 'create', 'update', 'delete', 'manage_users', 'manage_settings', 'view_audit']],
            'staff' => ['name' => 'Staff', 'permissions' => ['view', 'create', 'update']],
            'viewer' => ['name' => 'Viewer', 'permissions' => ['view']],
        ];

        foreach ($defaults as $slug => $role) {
            $id = DB::table('roles')->insertGetId([
                'name' => $role['name'],
                'slug' => $slug,
                'permissions' => json_encode($role['permissions']),
                'system' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
            DB::table('users')->where('role', $slug)->update(['role_id' => $id]);
        }
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table): void {
            $table->dropConstrainedForeignId('role_id');
        });
        Schema::dropIfExists('roles');
    }
};
