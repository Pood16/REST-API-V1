<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;
use Illuminate\Support\Facades\DB;

class RolesController extends Controller
{
    public function index()
    {
        if (!auth()->user()->can('view_users')) {
            return response()->json([
                'message' => 'You do not have permission to view roles'
            ], 403);
        }

        $roles = Role::with('permissions')->get();

        return response()->json([
            'message' => 'Success',
            'data' => $roles
        ], 200);
    }

    // function to create a costumized role with spesefic permissions can be applies to it in other function..

    public function store(Request $request)
    {
        if (!auth()->user()->can('create_users')) {
            return response()->json([
                'message' => 'You do not have permission to create roles'
            ], 403);
        }

        $data = $request->validate([
            'name' => 'required|string|unique:roles,name',
            'permissions' => 'sometimes|array',
            'permissions.*' => 'exists:permissions,name'
            ]);

        $role = Role::create(['name' => $data['name']]);

        if (!empty($data['permissions'])) {
            $role->givePermissionTo($data['permissions']);
        }

        return response()->json([
            'message' => 'Role created successfully',
            'data' => $role->load('permissions')
        ], 201);
    }

    public function show($id)
    {
        if (!auth()->user()->can('view_users')) {
            return response()->json([
                'message' => 'You do not have permission to view roles'
            ], 403);
        }

        $role = Role::with('permissions')->findOrFail($id);

        return response()->json([
            'message' => 'Success',
            'data' => $role
        ], 200);
    }

    public function update(Request $request, $id)
    {
        if (!auth()->user()->can('edit_users')) {
            return response()->json([
                'message' => 'You do not have permission to update roles'
            ], 403);
        }

        $role = Role::findOrFail($id);

        // Prevent updates to default roles
        if (in_array($role->name, ['super_admin', 'product_manager', 'user_manager', 'guest'])) {
            return response()->json([
                'message' => 'Cannot modify default roles'
            ], 403);
        }

        $data = $request->validate([
            'name' => 'sometimes|string|unique:roles,name,' . $id,
            'permissions' => 'sometimes|array',
            'permissions.*' => 'exists:permissions,name'
        ]);

        if (isset($data['name'])) {
            $role->name = $data['name'];
            $role->save();
        }

        if (isset($data['permissions'])) {
            $role->syncPermissions($data['permissions']);
        }

        return response()->json([
            'message' => 'Role updated successfully',
            'data' => $role->load('permissions')
        ], 200);
    }



    public function destroy($id)
    {
        if (!auth()->user()->can('delete_users')) {
            return response()->json([
                'message' => 'You do not have permission to delete roles'
            ], 403);
        }

        try {
            // Use the Spatie model explicitly with the correct namespace
            $role = Role::findOrFail($id);

            // Prevent deletion of default roles
            if (in_array($role->name, ['super_admin', 'product_manager', 'user_manager', 'guest'])) {
                return response()->json([
                    'message' => 'Cannot delete default roles'
                ], 403);
            }

            // Remove all relationships first
            $role->syncPermissions([]);

            dd($role);

            // Detach this role from all users
            $role->users()->detach();

            // Now delete the role
            $role->delete();

            return response()->json([
                'message' => 'Role deleted successfully'
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Error deleting role: ' . $e->getMessage()
            ], 500);
        }
    }





    public function assignRoleToUser(Request $request)
    {
        if (!auth()->user()->can('edit_users')) {
            return response()->json([
                'message' => 'You do not have permission to assign roles'
            ], 403);
        }

        $data = $request->validate([
            'user_id' => 'required|exists:users,id',
            'roles' => 'required',
            'roles.*' => 'exists:roles,name'
        ]);

        $user = User::findOrFail($data['user_id']);

        // Check if trying to modify super_admin (user 1)
        if ($user->id == 1 && auth()->id() != 1) {
            return response()->json([
                'message' => 'Only super admin can modify their own roles'
            ], 403);
        }

        $user->syncRoles($data['roles']);

        return response()->json([
            'message' => 'Roles assigned successfully',
            'data' => $user->load('roles')
        ], 200);
    }

    public function getPermissions()
    {
        if (!auth()->user()->can('view_users')) {
            return response()->json([
                'message' => 'You do not have permission to view permissions'
            ], 403);
        }

        $permissions = Permission::all();

        return response()->json([
            'message' => 'Success',
            'data' => $permissions
        ], 200);
    }

    public function getUserRoles($userId)
    {
        if (!auth()->user()->can('view_users')) {
            return response()->json([
                'message' => 'You do not have permission to view user roles'
            ], 403);
        }

        $user = User::with('roles.permissions')->findOrFail($userId);

        return response()->json([
            'message' => 'Success',
            'data' => [
                'user' => [
                    'id' => $user->id,
                    'name' => $user->name,
                    'email' => $user->email
                ],
                'roles' => $user->roles
            ]
        ], 200);
    }
}
