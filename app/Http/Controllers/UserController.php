<?php

namespace App\Http\Controllers;

use App\Support\AccessAudit;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules;
use Inertia\Inertia;
use App\Models\Role;

class UserController extends Controller
{
    public function index(Request $request)
    {
        $users = User::with(['roles', 'units:units.id,units.name'])
            ->whereNull('deactivated_at')
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
            'units' => auth()->user()->accessibleUnits()->map->only(['id', 'name'])->values(),
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
            'email' => 'required|string|email|max:255|unique:central.users',
            'phone' => 'nullable|string|max:20',
            'password' => ['required', 'confirmed', Rules\Password::defaults()],
            'role' => 'required|string|exists:central.roles,name|not_in:owner',
            'unit_ids' => 'required|array|min:1',
            'unit_ids.*' => 'integer|exists:central.units,id',
        ], [
            'role.not_in' => 'Anda tidak dapat menambahkan user dengan role Owner.',
            'unit_ids.required' => 'Pilih minimal satu unit.',
        ]);

        $unitIds = $this->allowedUnitIds($authUser, $request->input('unit_ids'));

        $user = User::create([
            'name' => $request->name,
            'email' => $request->email,
            'phone' => $request->phone,
            'password' => Hash::make($request->password),
        ]);

        $user->assignRole($request->role);
        $user->units()->syncWithPivotValues($unitIds, ['granted_by' => $authUser->id]);

        AccessAudit::record('user.created', 'user', $user->id, null, [
            'email' => $user->email,
            'role' => $request->role,
            'unit_ids' => $unitIds,
        ]);

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
            'email' => 'required|string|email|max:255|unique:central.users,email,'.$user->id,
            'phone' => 'nullable|string|max:20',
            'role' => 'required|string|exists:central.roles,name|not_in:owner',
            'password' => ['nullable', 'confirmed', Rules\Password::defaults()],
            'unit_ids' => 'required|array|min:1',
            'unit_ids.*' => 'integer|exists:central.units,id',
        ], [
            'role.not_in' => 'Anda tidak dapat mengubah role menjadi Owner.',
            'unit_ids.required' => 'Pilih minimal satu unit.',
        ]);

        $old = [
            'email' => $user->email,
            'role' => $user->getRoleNames()->first(),
            'unit_ids' => $user->units()->pluck('units.id')->all(),
        ];

        $unitIds = $this->allowedUnitIds($authUser, $request->input('unit_ids'));

        // Finance hanya mengatur unit yang dimilikinya, jadi akses ke unit lain tetap utuh.
        $untouched = collect($old['unit_ids'])->diff($authUser->accessibleUnits()->pluck('id'));
        $unitIds = $untouched->merge($unitIds)->unique()->values()->all();

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
        $user->units()->syncWithPivotValues($unitIds, ['granted_by' => $authUser->id]);

        AccessAudit::record('user.updated', 'user', $user->id, $old, [
            'email' => $user->email,
            'role' => $request->role,
            'unit_ids' => $unitIds,
            'password_changed' => $request->filled('password'),
        ]);

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

        // Dinonaktifkan, bukan dihapus: jurnal di semua unit masih menyimpan ID pengguna ini.
        $user->forceFill(['deactivated_at' => now()])->save();
        $user->units()->detach();

        AccessAudit::record('user.deactivated', 'user', $user->id, ['email' => $user->email]);

        return back()->with('success', 'User berhasil dinonaktifkan.');
    }

    /**
     * Pengguna hanya boleh memberikan unit yang juga dia miliki.
     */
    private function allowedUnitIds(User $authUser, array $requested): array
    {
        $requested = collect($requested)->map(fn ($id) => (int) $id)->unique();

        abort_if(
            $requested->diff($authUser->accessibleUnits()->pluck('id'))->isNotEmpty(),
            403,
            'Anda hanya dapat memberikan akses ke unit yang Anda miliki.'
        );

        return $requested->values()->all();
    }
}
