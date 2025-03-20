<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Spatie\Permission\PermissionRegistrar;
use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class RolesPermissionsSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // create permissions
        Permission::create(['name' => 'view_dashboard']);
        Permission::create(['name' => 'view_products']);
        Permission::create(['name' => 'create_products']);
        Permission::create(['name' => 'edit_products']);
        Permission::create(['name' => 'delete_products']);
        Permission::create(['name' => 'restore_products']);
        Permission::create(['name' => 'view_categories']);
        Permission::create(['name' => 'create_categories']);
        Permission::create(['name' => 'edit_categories']);
        Permission::create(['name' => 'delete_categories']);
        Permission::create(['name' => 'view_users']);
        Permission::create(['name' => 'create_users']);
        Permission::create(['name' => 'edit_users']);
        Permission::create(['name' => 'delete_users']);

        // create roles
        $role_1 = Role::create(['name' => 'super_admin']);
        $role_2 = Role::create(['name' => 'product_manager']);
        $role_3 = Role::create(['name' => 'user_manager']);
        $role_4 = Role::create(['name' => 'guest']);
        
        // assign permissions to roles
        $role_1->givePermissionTo(Permission::where('guard_name')->get());
        $role_2->givePermissionTo([
            'view_dashboard',
            'view_products',
            'create_products',
            'edit_products',
            'delete_products',
            'restore_products',
        ]);
        $role_3->givePermissionTo([
            'view_dashboard',
            'view_users',
            'create_users',
            'edit_users',
            'delete_users',
        ]);
    }
}
