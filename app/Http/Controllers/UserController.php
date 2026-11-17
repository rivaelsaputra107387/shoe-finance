<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules;
use Inertia\Inertia;
use Spatie\Permission\Models\Role;

class UserController extends Controller
{
    public function index(Request $request)
    {
        $users = User::with('roles')
            ->when($request->search, function ($query, $search) {
                $query->where('name', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%");
            })
            ->when($request->role, function ($query, $role) {
                $query->whereHas('roles', function ($q) use ($role) {
                    $q->where('name', $role);
                });
            })
            ->orderBy('name')
            ->paginate(10)
            ->withQueryString();

        $roles = Role::pluck('name');

        return Inertia::render('Settings/Users', [
            'users' => $users,
            'roles' => $roles,
            'filters' => $request->only(['search', 'role'])
        ]);
    }

    public function store(Request $request)
    {
        $authUser = auth()->user();
        $isFinanceOnly = $authUser->hasRole('finance') && !$authUser->hasRole('owner');

        if ($isFinanceOnly && strtolower($request->role) !== 'staff') {
            return back()->with('error', 'Akses ditolak: User Finance hanya dapat menambahkan akun dengan role Staff.');
        }

        $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users',
            'phone' => 'nullable|string|max:20',
            'password' => ['required', 'confirmed', Rules\Password::defaults()],
            'role' => 'required|string|exists:roles,name|not_in:owner',
        ], [
            'role.not_in' => 'Anda tidak dapat menambahkan user dengan role Owner.',
        ]);

        $user = User::create([
            'name' => $request->name,
            'email' => $request->email,
            'phone' => $request->phone,
            'password' => Hash::make($request->password),
        ]);

        $user->assignRole($request->role);

        return back()->with('success', 'User berhasil ditambahkan.');
    }

    public function update(Request $request, User $user)
    {
        $authUser = auth()->user();
        $isFinanceOnly = $authUser->hasRole('finance') && !$authUser->hasRole('owner');

        if ($isFinanceOnly && ($user->hasRole('owner') || $user->hasRole('finance') || strtolower($request->role) !== 'staff')) {
            return back()->with('error', 'Akses ditolak: User Finance hanya diperbolehkan mengelola/mengedit akun dengan role Staff.');
        }

        if ($user->id === auth()->id()) {
            return back()->with('error', 'Anda tidak dapat mengedit akun Anda sendiri melalui halaman ini. Silakan gunakan menu Edit Profil.');
        }

        $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users,email,'.$user->id,
            'phone' => 'nullable|string|max:20',
            'role' => 'required|string|exists:roles,name|not_in:owner',
            'password' => ['nullable', 'confirmed', Rules\Password::defaults()],
        ], [
            'role.not_in' => 'Anda tidak dapat mengubah role menjadi Owner.',
        ]);

        $user->update([
            'name' => $request->name,
            'email' => $request->email,
            'phone' => $request->phone,
        ]);

        if ($request->filled('password')) {
            $user->update([
                'password' => Hash::make($request->password),
            ]);
        }

        $user->syncRoles([$request->role]);

        return back()->with('success', 'User berhasil diperbarui.');
    }

    public function destroy(User $user)
    {
        $authUser = auth()->user();
        $isFinanceOnly = $authUser->hasRole('finance') && !$authUser->hasRole('owner');

        if ($isFinanceOnly && ($user->hasRole('owner') || $user->hasRole('finance'))) {
            return back()->with('error', 'Akses ditolak: User Finance hanya diperbolehkan menghapus akun dengan role Staff.');
        }

        if ($user->id === auth()->id()) {
            return back()->with('error', 'Anda tidak dapat menghapus akun Anda sendiri.');
        }

        $user->delete();

        return back()->with('success', 'User berhasil dihapus.');
    }
}
