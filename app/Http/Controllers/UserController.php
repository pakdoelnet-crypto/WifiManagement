<?php

namespace App\Http\Controllers;

use App\Models\User;
use Spatie\Permission\Models\Role;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules;
use Inertia\Inertia;

class UserController extends Controller
{

    public function index()
    {
        // Load users with their roles mapped
        $users = User::all()->map(function ($u) {
            return array_merge($u->toArray(), [
                'role' => $u->getRoleNames()->first() ?? '-',
            ]);
        });

        $roles = Role::all();

        return Inertia::render('Users/Index', [
            'users' => $users,
            'roles' => $roles,
        ]);
    }

    public function store(Request $request)
    {
        $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255', 'unique:users,email'],
            'password' => ['required', Rules\Password::defaults()],
            'role' => ['required', 'exists:roles,name'],
            'phone' => ['nullable', 'string', 'max:30'],
        ]);

        $user = User::create([
            'name' => $request->name,
            'email' => $request->email,
            'password' => Hash::make($request->password),
            'phone' => $request->phone,
            'is_active' => true,
        ]);

        $user->assignRole($request->role);

        return redirect()->route('users.index')->with('success', "Pengguna {$user->name} berhasil ditambahkan dengan role {$request->role}.");
    }

    public function update(Request $request, $id)
    {
        $user = User::findOrFail($id);

        $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255', 'unique:users,email,' . $user->id],
            'password' => ['nullable', Rules\Password::defaults()],
            'role' => ['required', 'exists:roles,name'],
            'phone' => ['nullable', 'string', 'max:30'],
        ]);

        $data = [
            'name' => $request->name,
            'email' => $request->email,
            'phone' => $request->phone,
        ];

        if ($request->filled('password')) {
            $data['password'] = Hash::make($request->password);
        }

        $user->update($data);
        $user->syncRoles([$request->role]);

        return redirect()->route('users.index')->with('success', "Data pengguna {$user->name} berhasil diperbarui.");
    }

    public function toggleStatus(Request $request, $id)
    {
        $user = User::findOrFail($id);

        if ($user->id === auth()->id()) {
            return redirect()->back()->with('error', 'Anda tidak dapat menonaktifkan akun Anda sendiri.');
        }

        // Check if we are deactivating a Super Admin, only other Super Admins should not be deactivated if they are the only ones
        if ($user->hasRole('Super Admin') && $user->is_active) {
            $activeSuperAdmins = User::role('Super Admin')->where('is_active', true)->count();
            if ($activeSuperAdmins <= 1) {
                return redirect()->back()->with('error', 'Tidak dapat menonaktifkan satu-satunya Super Admin aktif.');
            }
        }

        $user->update([
            'is_active' => !$user->is_active,
        ]);

        $statusText = $user->is_active ? 'diaktifkan' : 'dinonaktifkan';

        return redirect()->route('users.index')->with('success', "Akun pengguna {$user->name} berhasil {$statusText}.");
    }
}
