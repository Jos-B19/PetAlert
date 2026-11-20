<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;

class UserController extends Controller
{
    public function index(Request $request): Response
    {
        $search = trim((string) $request->query('q', ''));
        $role = (string) $request->query('rol', '');
        $status = (string) $request->query('estado', '');

        $users = User::query()
            ->whereIn('rol', User::STAFF_ROLES)
            ->select(['id', 'name', 'email', 'telefono', 'tipo_documento', 'numero_documento', 'direccion', 'cargo', 'rol', 'estado', 'created_at'])
            ->when($search !== '', fn ($query) => $query->where(function ($query) use ($search) {
                $query->where('name', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%")
                    ->orWhere('numero_documento', 'like', "%{$search}%")
                    ->orWhere('telefono', 'like', "%{$search}%")
                    ->orWhere('cargo', 'like', "%{$search}%");
            }))
            ->when(in_array($role, User::STAFF_ROLES, true), fn ($query) => $query->where('rol', $role))
            ->when(in_array($status, ['activo', 'inactivo'], true), fn ($query) => $query->where('estado', $status))
            ->orderBy('name')
            ->paginate(15)
            ->withQueryString();

        return Inertia::render('PetAlert', [
            'screen' => 'users.index',
            'users' => $users,
            'filters' => ['q' => $search, 'rol' => $role, 'estado' => $status],
            'roles' => User::STAFF_ROLES,
        ]);
    }

    public function create(): Response
    {
        return Inertia::render('PetAlert', [
            'screen' => 'users.create',
            'roles' => User::STAFF_ROLES,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', 'unique:users,email'],
            'telefono' => ['required', 'string', 'max:20'],
            'tipo_documento' => ['required', 'string', 'max:20'],
            'numero_documento' => ['required', 'string', 'max:30', 'unique:users,numero_documento'],
            'direccion' => ['nullable', 'string', 'max:255'],
            'cargo' => ['required', 'string', 'max:100'],
            'rol' => ['required', Rule::in(User::STAFF_ROLES)],
            'estado' => ['required', Rule::in(['activo', 'inactivo'])],
            'password' => ['required', 'string', 'confirmed', 'min:12'],
        ]);

        $validated['password'] = Hash::make($validated['password']);
        User::create($validated);

        return redirect()->route('users.index')->with('success', 'Usuario creado correctamente.');
    }

    public function edit(User $user): Response
    {
        $this->ensureStaff($user);

        return Inertia::render('PetAlert', [
            'screen' => 'users.edit',
            'managedUser' => $user->only('id', 'name', 'email', 'telefono', 'tipo_documento', 'numero_documento', 'direccion', 'cargo', 'rol', 'estado'),
            'roles' => User::STAFF_ROLES,
        ]);
    }

    public function update(Request $request, User $user): RedirectResponse
    {
        $this->ensureStaff($user);

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', Rule::unique('users', 'email')->ignore($user)],
            'telefono' => ['required', 'string', 'max:20'],
            'tipo_documento' => ['required', 'string', 'max:20'],
            'numero_documento' => ['required', 'string', 'max:30', Rule::unique('users', 'numero_documento')->ignore($user)],
            'direccion' => ['nullable', 'string', 'max:255'],
            'cargo' => ['required', 'string', 'max:100'],
            'rol' => ['required', Rule::in(User::STAFF_ROLES)],
            'estado' => ['required', Rule::in(['activo', 'inactivo'])],
            'password' => ['nullable', 'string', 'confirmed', 'min:12'],
        ]);

        if ($request->user()->is($user)
            && ($validated['rol'] !== $user->rol || $validated['estado'] !== $user->estado)) {
            throw ValidationException::withMessages([
                'rol' => 'No puedes modificar tu propio rol o estado.',
            ]);
        }

        DB::transaction(function () use ($validated, $user) {
            $lockedUser = User::query()->lockForUpdate()->findOrFail($user->id);
            $this->ensureAnotherActiveAdministratorExists($lockedUser, $validated['rol'], $validated['estado']);

            if (blank($validated['password'] ?? null)) {
                unset($validated['password']);
            } else {
                $validated['password'] = Hash::make($validated['password']);
            }

            $lockedUser->update($validated);
        });

        return redirect()->route('users.index')->with('success', 'Usuario actualizado correctamente.');
    }

    public function toggleStatus(User $user): RedirectResponse
    {
        $this->ensureStaff($user);

        if (request()->user()->is($user)) {
            return back()->with('error', 'No puedes desactivar tu propia cuenta.');
        }

        DB::transaction(function () use ($user) {
            $lockedUser = User::query()->lockForUpdate()->findOrFail($user->id);
            $newStatus = $lockedUser->estado === 'activo' ? 'inactivo' : 'activo';
            $this->ensureAnotherActiveAdministratorExists($lockedUser, $lockedUser->rol, $newStatus);
            $lockedUser->update(['estado' => $newStatus]);
        });

        return back()->with('success', 'Estado de usuario actualizado.');
    }

    public function destroy(User $user): RedirectResponse
    {
        $this->ensureStaff($user);

        if (request()->user()->is($user)) {
            return back()->with('error', 'No puedes eliminar tu propia cuenta.');
        }

        $wasDeactivated = DB::transaction(function () use ($user): bool {
            $lockedUser = User::query()->lockForUpdate()->findOrFail($user->id);
            $hasClinicalActivity = $lockedUser->urgenciasRegistradas()->exists()
                || $lockedUser->urgenciasAsignadas()->exists()
                || $lockedUser->atenciones()->exists()
                || $lockedUser->evolucionesClinicas()->exists();

            if ($hasClinicalActivity) {
                $this->ensureAnotherActiveAdministratorExists($lockedUser, $lockedUser->rol, 'inactivo');
                $lockedUser->update(['estado' => 'inactivo']);
                return true;
            }

            $this->ensureAnotherActiveAdministratorExists($lockedUser, null, null);
            $lockedUser->delete();
            return false;
        });

        if ($wasDeactivated) {
            return redirect()->route('users.index')->with('success', 'El usuario tiene actividad relacionada y fue desactivado para conservar el historial.');
        }

        return redirect()->route('users.index')->with('success', 'Usuario eliminado correctamente.');
    }

    private function ensureStaff(User $user): void
    {
        abort_unless($user->isStaff(), 404);
    }

    private function ensureAnotherActiveAdministratorExists(User $user, ?string $role, ?string $status): void
    {
        $isRemovingActiveAdmin = $user->rol === User::ROLE_ADMINISTRADOR
            && $user->estado === 'activo'
            && ($role === null || $status === null || $role !== User::ROLE_ADMINISTRADOR || $status !== 'activo');

        if (! $isRemovingActiveAdmin) {
            return;
        }

        $anotherActiveAdminExists = User::query()
            ->where('rol', User::ROLE_ADMINISTRADOR)
            ->where('estado', 'activo')
            ->where('id', '!=', $user->id)
            ->lockForUpdate()
            ->exists();

        if (! $anotherActiveAdminExists) {
            throw ValidationException::withMessages([
                'rol' => 'Debe permanecer al menos un administrador activo.',
            ]);
        }
    }
}
