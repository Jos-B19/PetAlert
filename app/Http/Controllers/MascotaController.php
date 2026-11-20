<?php

namespace App\Http\Controllers;

use App\Models\Mascota;
use App\Models\Propietario;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class MascotaController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request): Response
    {
        $user = $request->user();
        $search = trim((string) $request->query('q', ''));
        $species = trim((string) $request->query('especie', ''));
        $ownerId = $request->integer('propietario_id');
        $status = (string) $request->query('estado', '');

        $pets = $this->visiblePetsQuery($user)
            ->select($this->petColumnsForRole($user->rol))
            ->with('propietario:id,nombres,apellidos')
            ->when($search !== '', function ($query) use ($search) {
                $query->where(function ($query) use ($search) {
                    $query->where('nombre', 'like', "%{$search}%")
                        ->orWhere('especie', 'like', "%{$search}%")
                        ->orWhere('raza', 'like', "%{$search}%")
                        ->orWhereHas('propietario', function ($ownerQuery) use ($search) {
                            $ownerQuery->where('nombres', 'like', "%{$search}%")
                                ->orWhere('apellidos', 'like', "%{$search}%")
                                ->orWhere('numero_documento', 'like', "%{$search}%");
                        });
                });
            })
            ->when($species !== '', fn ($query) => $query->where('especie', $species))
            ->when($ownerId > 0, fn ($query) => $query->where('propietario_id', $ownerId))
            ->when(in_array($status, ['activo', 'inactivo'], true), fn ($query) => $query->where('estado', $status))
            ->orderBy('nombre')
            ->paginate(15)
            ->withQueryString();

        return Inertia::render('PetAlert', [
            'screen' => 'pets.index',
            'pets' => $pets,
            'filters' => ['q' => $search, 'especie' => $species, 'propietario_id' => $ownerId, 'estado' => $status],
            'filterOptions' => [
                'species' => $this->visiblePetsQuery($user)->distinct()->orderBy('especie')->pluck('especie'),
                'owners' => Propietario::query()
                    ->whereIn('id', $this->visiblePetsQuery($user)->select('propietario_id')->distinct())
                    ->orderBy('apellidos')
                    ->orderBy('nombres')
                    ->get(['id', 'nombres', 'apellidos']),
            ],
        ]);
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create(): Response
    {
        return Inertia::render('PetAlert', [
            'screen' => 'pets.create',
            'owners' => Propietario::query()->orderBy('apellidos')->orderBy('nombres')->get(['id', 'nombres', 'apellidos', 'numero_documento']),
        ]);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'propietario_id' => ['required', 'exists:propietarios,id'],
            'nombre' => ['required', 'string', 'max:100'],
            'especie' => ['required', 'string', 'max:50'],
            'raza' => ['nullable', 'string', 'max:100'],
            'sexo' => ['nullable', 'string', 'max:20'],
            'fecha_nacimiento' => ['nullable', 'date', 'before_or_equal:today'],
            'peso' => ['nullable', 'numeric', 'gt:0', 'max:999999.99'],
            'color' => ['nullable', 'string', 'max:50'],
            'observaciones' => ['nullable', 'string', 'max:5000'],
            'estado' => ['required', 'in:activo,inactivo'],
        ]);

        $mascota = Mascota::create($validated);

        return redirect()->route('pets.show', $mascota)->with('success', 'Mascota registrada correctamente.');
    }

    /**
     * Display the specified resource.
     */
    public function show(Request $request, Mascota $pet): Response
    {
        $user = $request->user();
        $role = $user->rol;
        $pet = $this->visiblePetsQuery($user)
            ->select($this->petColumnsForRole($role))
            ->whereKey($pet->getKey())
            ->firstOrFail();

        $relations = [
            'propietario:id,nombres,apellidos,telefono',
            'urgencias' => function ($query) use ($user, $role): void {
                if ($role === User::ROLE_VETERINARIO) {
                    $query->where('veterinario_id', $user->id);
                }

                $urgencyColumns = $role === User::ROLE_RECEPCION
                    ? ['id', 'mascota_id', 'tipo_urgencia', 'estado', 'fecha_ingreso']
                    : [
                        'id',
                        'mascota_id',
                        'registrado_por',
                        'veterinario_id',
                        'tipo_urgencia',
                        'descripcion',
                        'nivel_prioridad',
                        'estado',
                        'fecha_ingreso',
                        'fecha_egreso',
                        'egreso_registrado_por',
                        'estado_final_mascota',
                    ];

                $query->select($urgencyColumns)->latest('fecha_ingreso')->limit(5);
            },
        ];

        if (in_array($role, [User::ROLE_ADMINISTRADOR, User::ROLE_VETERINARIO], true)) {
            $relations['historialClinico'] = fn ($query) => $query->select([
                'id',
                'mascota_id',
                'antecedentes',
                'alergias',
                'enfermedades_previas',
                'vacunas',
                'observaciones',
            ]);
        }

        $pet->load($relations);

        return Inertia::render('PetAlert', [
            'screen' => 'pets.show',
            'pet' => $pet,
        ]);
    }

    /**
     * Limit veterinarian queries to pets with at least one assigned urgency.
     *
     * @return Builder<Mascota>
     */
    private function visiblePetsQuery(User $user): Builder
    {
        return Mascota::query()->when(
            $user->rol === User::ROLE_VETERINARIO,
            fn (Builder $query) => $query->whereHas(
                'urgencias',
                fn (Builder $urgencyQuery) => $urgencyQuery->where('veterinario_id', $user->id),
            ),
        );
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
    public function edit(Mascota $pet): Response
    {
        return Inertia::render('PetAlert', [
            'screen' => 'pets.edit',
            'pet' => $pet,
            'owners' => Propietario::query()->orderBy('apellidos')->orderBy('nombres')->get(['id', 'nombres', 'apellidos', 'numero_documento']),
        ]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Mascota $pet): RedirectResponse
    {
        $validated = $request->validate([
            'propietario_id' => ['required', 'exists:propietarios,id'],
            'nombre' => ['required', 'string', 'max:100'],
            'especie' => ['required', 'string', 'max:50'],
            'raza' => ['nullable', 'string', 'max:100'],
            'sexo' => ['nullable', 'string', 'max:20'],
            'fecha_nacimiento' => ['nullable', 'date', 'before_or_equal:today'],
            'peso' => ['nullable', 'numeric', 'gt:0', 'max:999999.99'],
            'color' => ['nullable', 'string', 'max:50'],
            'observaciones' => ['nullable', 'string', 'max:5000'],
            'estado' => ['required', 'in:activo,inactivo'],
        ]);

        $pet->update($validated);

        return redirect()->route('pets.show', $pet)->with('success', 'Información de la mascota actualizada.');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Mascota $pet): RedirectResponse
    {
        if ($pet->urgencias()->exists() || $pet->historialClinico()->exists()) {
            return back()->with('error', 'No se puede eliminar una mascota con historial clínico o urgencias asociadas.');
        }

        $pet->delete();

        return redirect()->route('pets.index')->with('success', 'Mascota eliminada.');
    }
}
