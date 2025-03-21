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

        app()[PermissionRegistrar::class]->forgetCachedPermissions();


        $permissions = [
            'view_dashboard',
            'view_products',
            'create_products',
            'edit_products',
            'delete_products',
            'restore_products',
            'view_categories',
            'create_categories',
            'edit_categories',
            'delete_categories',
            'view_users',
            'create_users',
            'edit_users',
            'delete_users'
        ];


        foreach ($permissions as $permission) {
            Permission::create(['name' => $permission]);
        }


        $role_1 = Role::create(['name' => 'super_admin']);
        $role_2 = Role::create(['name' => 'product_manager']);
        $role_3 = Role::create(['name' => 'user_manager']);
        $role_4 = Role::create(['name' => 'client']);


        $role_1->givePermissionTo(Permission::all());
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
