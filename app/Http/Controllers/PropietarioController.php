<?php

namespace App\Http\Controllers;

use App\Models\Atencion;
use App\Models\Propietario;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;

class PropietarioController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request): Response
    {
        $search = trim((string) $request->query('q', ''));
        $status = (string) $request->query('estado', '');

        $owners = Propietario::query()
            ->withCount(['mascotas', 'urgencias'])
            ->when($search !== '', function ($query) use ($search) {
                $query->where(function ($query) use ($search) {
                    $query->where('nombres', 'like', "%{$search}%")
                        ->orWhere('apellidos', 'like', "%{$search}%")
                        ->orWhere('numero_documento', 'like', "%{$search}%")
                        ->orWhere('telefono', 'like', "%{$search}%")
                        ->orWhere('correo', 'like', "%{$search}%")
                        ->orWhereHas('user', fn ($userQuery) => $userQuery->where('email', 'like', "%{$search}%"));
                });
            })
            ->when(in_array($status, ['activo', 'inactivo'], true), fn ($query) => $query->where('estado', $status))
            ->orderBy('apellidos')
            ->orderBy('nombres')
            ->paginate(15)
            ->withQueryString();

        return Inertia::render('PetAlert', [
            'screen' => 'owners.index',
            'owners' => $owners,
            'filters' => ['q' => $search, 'estado' => $status],
        ]);
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create(): Response
    {
        return Inertia::render('PetAlert', ['screen' => 'owners.create']);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'nombres' => ['required', 'string', 'max:100'],
            'apellidos' => ['required', 'string', 'max:100'],
            'tipo_documento' => ['required', 'string', 'max:20'],
            'numero_documento' => ['required', 'string', 'max:30', 'unique:propietarios,numero_documento'],
            'telefono' => ['required', 'string', 'max:20'],
            'correo' => ['nullable', 'email', 'max:150', 'unique:propietarios,correo'],
            'direccion' => ['nullable', 'string', 'max:255'],
        ]);

        Propietario::create($validated);

        return redirect()->route('owners.index')->with('success', 'Propietario registrado correctamente.');
    }

    /**
     * Display the specified resource.
     */
    public function show(Request $request, Propietario $owner): Response
    {
        $user = $request->user();
        $role = $user->rol;
        $petColumns = $this->petColumnsForRole($role);

        $owner->load([
            'user:id,name,email,estado',
            'mascotas' => function ($query) use ($role, $user, $petColumns): void {
                if ($role === User::ROLE_VETERINARIO) {
                    $query->whereHas('urgencias', fn ($urgencyQuery) => $urgencyQuery->where('veterinario_id', $user->id));
                }

                $query->select($petColumns)->orderBy('nombre');
            },
        ]);

        if ($role === User::ROLE_VETERINARIO) {
            $owner->loadCount([
                'mascotas' => fn ($query) => $query->whereHas(
                    'urgencias',
                    fn ($urgencyQuery) => $urgencyQuery->where('veterinario_id', $user->id),
                ),
                'urgencias' => fn ($query) => $query->where('veterinario_id', $user->id),
            ]);
        } else {
            $owner->loadCount(['mascotas', 'urgencias']);
        }

        $urgenciesQuery = $owner->urgencias();

        if ($role === User::ROLE_VETERINARIO) {
            $urgenciesQuery->where('veterinario_id', $user->id);
        }

        $recentUrgencies = $urgenciesQuery
            ->with(['mascota:id,nombre', 'veterinario:id,name'])
            ->latest('fecha_ingreso')
            ->limit(10)
            ->get();
        $recentAttentions = collect();

        if (in_array($role, [User::ROLE_ADMINISTRADOR, User::ROLE_VETERINARIO], true)) {
            $attentionQuery = Atencion::query()
                ->whereHas('urgencia.mascota', fn ($query) => $query->where('propietario_id', $owner->id));

            if ($role === User::ROLE_VETERINARIO) {
                $attentionQuery->whereHas('urgencia', fn ($query) => $query->where('veterinario_id', $user->id));
            }

            $recentAttentions = $attentionQuery
                ->with([
                    'urgencia:id,mascota_id,tipo_urgencia',
                    'urgencia.mascota:id,nombre',
                    'veterinario:id,name',
                ])
                ->latest('fecha_atencion')
                ->limit(10)
                ->get();
        }

        return Inertia::render('PetAlert', [
            'screen' => 'owners.show',
            'owner' => $owner,
            'recentUrgencies' => $recentUrgencies,
            'recentAttentions' => $recentAttentions,
        ]);
    }

    /**
     * @return list<string>
     */
    private function petColumnsForRole(string $role): array
    {
        $columns = [
            'id',
            'propietario_id',
            'nombre',
            'especie',
            'raza',
            'color',
            'estado',
        ];

        if (in_array($role, [User::ROLE_ADMINISTRADOR, User::ROLE_VETERINARIO], true)) {
            array_push($columns, 'sexo', 'fecha_nacimiento', 'peso', 'observaciones');
        }

        return $columns;
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Propietario $owner): Response
    {
        return Inertia::render('PetAlert', [
            'screen' => 'owners.edit',
            'owner' => $owner,
        ]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Propietario $owner): RedirectResponse
    {
        $validated = $request->validate([
            'nombres' => ['required', 'string', 'max:100'],
            'apellidos' => ['required', 'string', 'max:100'],
            'tipo_documento' => ['required', 'string', 'max:20'],
            'numero_documento' => ['required', 'string', 'max:30', Rule::unique('propietarios', 'numero_documento')->ignore($owner)],
            'telefono' => ['required', 'string', 'max:20'],
            'correo' => ['nullable', 'email', 'max:150', Rule::unique('propietarios', 'correo')->ignore($owner)],
            'direccion' => ['nullable', 'string', 'max:255'],
        ]);

        $owner->update($validated);

        return redirect()->route('owners.show', $owner)->with('success', 'Información del propietario actualizada.');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Propietario $owner): RedirectResponse
    {
        if ($owner->mascotas()->exists()) {
            return back()->with('error', 'No se puede eliminar un propietario con mascotas asociadas.');
        }

        $owner->delete();

        return redirect()->route('owners.index')->with('success', 'Propietario eliminado.');
    }

    public function toggleStatus(Propietario $owner): RedirectResponse
    {
        $newStatus = $owner->estado === 'activo' ? 'inactivo' : 'activo';

        DB::transaction(function () use ($owner, $newStatus): void {
            $owner->update(['estado' => $newStatus]);
            $owner->user?->update(['estado' => $newStatus]);
        });

        return back()->with('success', 'Estado del propietario actualizado.');
    }
}
