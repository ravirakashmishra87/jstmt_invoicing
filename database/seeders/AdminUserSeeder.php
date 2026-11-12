<?php

namespace Database\Seeders;

use App\Models\Permission;
use App\Models\Role;
use App\Models\User;
use Illuminate\Database\Seeder;

class AdminUserSeeder extends Seeder
{
    public function run(): void
    {
        $modules = ['dashboard', 'customers', 'suppliers', 'products', 'quotations', 'proformas', 'invoices', 'stock', 'receipts', 'reports', 'communication', 'settings', 'users', 'masters'];
        $actions = ['view', 'create', 'edit', 'delete', 'approve', 'export', 'print'];

        foreach ($modules as $module) {
            foreach ($actions as $action) {
                Permission::updateOrCreate(
                    ['slug' => $module.'.'.$action],
                    [
                        'module' => $module,
                        'action' => $action,
                        'name' => ucfirst($module).' '.str_replace('_', ' ', $action),
                    ]
                );
            }
        }

        $role = Role::updateOrCreate(
            ['slug' => 'super-admin'],
            [
                'name' => 'Super Admin',
                'description' => 'Full access',
            ]
        );

        $role->permissions()->sync(Permission::query()->pluck('id')->all());

        User::updateOrCreate(
            ['username' => 'admin'],
            [
                'name' => 'System Administrator',
                'email' => 'admin@jstmt.com',
                'password' => 'Admin@123',
                'role_id' => $role->id,
                'is_active' => true,
                'failed_login_attempts' => 0,
                'locked_until' => null,
            ]
        );
    }
}
