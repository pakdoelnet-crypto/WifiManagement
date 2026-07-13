<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;
use Inertia\Inertia;

class RoleController extends Controller
{

    public function index()
    {
        // Load roles with count of users and permissions
        $roles = Role::withCount(['users', 'permissions'])->get();
        
        // Get all permissions and group them by module name (first part before dot)
        $allPermissions = Permission::all()->map(function ($p) {
            $parts = explode('.', $p->name);
            $module = $parts[0] ?? 'other';
            return [
                'id' => $p->id,
                'name' => $p->name,
                'module' => $module,
            ];
        })->groupBy('module');

        return Inertia::render('Roles/Index', [
            'roles' => $roles,
            'allPermissions' => $allPermissions,
        ]);
    }

    public function store(Request $request)
    {
        $request->validate([
            'name' => ['required', 'string', 'unique:roles,name', 'max:255'],
            'permissions' => ['nullable', 'array'],
        ]);

        $role = Role::create(['name' => $request->name]);
        
        if ($request->filled('permissions')) {
            $role->syncPermissions($request->permissions);
        }

        return redirect()->route('roles.index')->with('success', "Role {$role->name} berhasil ditambahkan.");
    }

    public function update(Request $request, $id)
    {
        $role = Role::findOrFail($id);

        if ($role->name === 'Super Admin') {
            abort(403, 'Role Super Admin tidak boleh diubah.');
        }

        $request->validate([
            'name' => ['required', 'string', 'max:255', 'unique:roles,name,' . $role->id],
            'permissions' => ['nullable', 'array'],
        ]);

        $role->update(['name' => $request->name]);
        $role->syncPermissions($request->permissions ?? []);

        return redirect()->route('roles.index')->with('success', "Role {$role->name} berhasil diperbarui.");
    }

    public function destroy($id)
    {
        $role = Role::findOrFail($id);

        if ($role->name === 'Super Admin') {
            abort(403, 'Role Super Admin tidak boleh dihapus.');
        }

        $role->delete();

        return redirect()->route('roles.index')->with('success', "Role {$role->name} berhasil dihapus.");
    }

    public function getRolePermissions($id)
    {
        $role = Role::findOrFail($id);
        return response()->json($role->permissions->pluck('name'));
    }
}
