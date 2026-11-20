<?php

namespace App\Http\Controllers;

use App\Models\Mascota;
use App\Models\Urgencia;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;

class UrgenciaController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request): Response
    {
        $search = trim((string) $request->query('q', ''));
        $status = (string) $request->query('estado', '');

        $urgencies = Urgencia::query()
            ->with(['mascota.propietario', 'registradoPor:id,name', 'veterinario:id,name'])
            ->when($request->user()->rol === User::ROLE_VETERINARIO, fn ($query) => $query->where('veterinario_id', $request->user()->id))
            ->when($search !== '', function ($query) use ($search) {
                $query->where(function ($query) use ($search) {
                    $query->where('tipo_urgencia', 'like', "%{$search}%")
                        ->orWhere('descripcion', 'like', "%{$search}%")
                        ->orWhereHas('mascota', fn ($petQuery) => $petQuery->where('nombre', 'like', "%{$search}%"));
                });
            })
            ->when(in_array($status, ['pendiente', 'en_atencion', 'en_observacion', 'finalizada', 'cancelada'], true), fn ($query) => $query->where('estado', $status))
            ->latest('fecha_ingreso')
            ->paginate(15)
            ->withQueryString();

        return Inertia::render('PetAlert', [
            'screen' => 'urgencies.index',
            'urgencies' => $urgencies,
            'filters' => ['q' => $search, 'estado' => $status],
        ]);
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create(): Response
    {
        return Inertia::render('PetAlert', [
            'screen' => 'urgencies.create',
            'pets' => Mascota::query()->where('estado', 'activo')
                ->with('propietario:id,nombres,apellidos')
                ->orderBy('nombre')
                ->get(['id', 'propietario_id', 'nombre', 'especie']),
            'veterinarians' => User::query()->where('rol', User::ROLE_VETERINARIO)
                ->where('estado', 'activo')
                ->orderBy('name')
                ->get(['id', 'name']),
        ]);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'mascota_id' => ['required', 'exists:mascotas,id'],
            'tipo_urgencia' => ['required', 'string', 'max:100'],
            'descripcion' => ['required', 'string', 'max:10000'],
            'nivel_prioridad' => ['required', Rule::in(['baja', 'media', 'alta', 'critica'])],
            'fecha_ingreso' => ['required', 'date'],
            'veterinario_id' => [
                'nullable',
                Rule::exists('users', 'id')->where(fn ($query) => $query
                    ->where('rol', User::ROLE_VETERINARIO)
                    ->where('estado', 'activo')),
            ],
        ]);

        $validated['registrado_por'] = $request->user()->id;
        $validated['estado'] = 'pendiente';

        if ($request->user()->rol === User::ROLE_VETERINARIO) {
            $validated['veterinario_id'] = $request->user()->id;
        }

        $urgency = Urgencia::create($validated);

        return redirect()->route('urgencies.show', $urgency)->with('success', 'Urgencia registrada correctamente.');
    }

    /**
     * Display the specified resource.
     */
    public function show(Request $request, Urgencia $urgency): Response
    {
        if ($request->user()->rol === User::ROLE_VETERINARIO) {
            abort_unless($urgency->veterinario_id === $request->user()->id, 404);
        }

        $relationships = [
            'mascota.propietario',
            'registradoPor:id,name',
            'veterinario:id,name',
            'egresoRegistradoPor:id,name',
        ];
        $props = [];

        if (in_array($request->user()->rol, [User::ROLE_ADMINISTRADOR, User::ROLE_VETERINARIO], true)) {
            $relationships[] = 'atenciones.veterinario:id,name';
            $relationships[] = 'atenciones.evolucionesClinicas.veterinario:id,name';
        }

        if (in_array($request->user()->rol, [User::ROLE_ADMINISTRADOR, User::ROLE_RECEPCION], true)) {
            $props['veterinarians'] = User::query()
                ->where('rol', User::ROLE_VETERINARIO)
                ->where('estado', 'activo')
                ->orderBy('name')
                ->get(['id', 'name']);
        }

        $urgency->load($relationships);

        return Inertia::render('PetAlert', [
            'screen' => 'urgencies.show',
            'urgency' => $urgency,
            ...$props,
        ]);
    }

    public function assign(Request $request, Urgencia $urgency): RedirectResponse
    {
        $validated = $request->validate([
            'veterinario_id' => [
                'required',
                Rule::exists('users', 'id')->where(fn ($query) => $query
                    ->where('rol', User::ROLE_VETERINARIO)
                    ->where('estado', 'activo')),
            ],
        ]);

        $urgency->update(['veterinario_id' => $validated['veterinario_id']]);

        return redirect()->route('urgencies.show', $urgency)->with('success', 'Veterinario asignado correctamente.');
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Urgencia $urgency): Response
    {
        return Inertia::render('PetAlert', [
            'screen' => 'urgencies.edit',
            'urgency' => $urgency,
            'veterinarians' => User::query()->where('rol', User::ROLE_VETERINARIO)
                ->where('estado', 'activo')
                ->orderBy('name')
                ->get(['id', 'name']),
        ]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Urgencia $urgency): RedirectResponse
    {
        if ($request->user()->rol === User::ROLE_VETERINARIO) {
            abort_unless($urgency->veterinario_id === $request->user()->id, 404);
        }

        $validated = $request->validate([
            'estado' => ['required', Rule::in(['pendiente', 'en_atencion', 'en_observacion', 'finalizada', 'cancelada'])],
            'veterinario_id' => [
                'nullable',
                Rule::exists('users', 'id')->where(fn ($query) => $query
                    ->where('rol', User::ROLE_VETERINARIO)
                    ->where('estado', 'activo')),
            ],
        ]);

        if ($request->user()->rol === User::ROLE_VETERINARIO) {
            $validated['veterinario_id'] = $request->user()->id;
        }

        return DB::transaction(function () use ($request, $urgency, $validated): RedirectResponse {
            $lockedUrgency = Urgencia::query()
                ->lockForUpdate()
                ->findOrFail($urgency->id);

            if ($request->user()->rol === User::ROLE_VETERINARIO) {
                abort_unless($lockedUrgency->veterinario_id === $request->user()->id, 404);
            }

            if ($lockedUrgency->fecha_egreso !== null && $validated['estado'] !== 'finalizada') {
                return back()->withErrors([
                    'estado' => 'No puedes cambiar el estado de una urgencia después de registrar el egreso físico.',
                ]);
            }

            if (
                in_array($lockedUrgency->estado, ['finalizada', 'cancelada'], true)
                && $validated['estado'] !== $lockedUrgency->estado
            ) {
                return back()->withErrors([
                    'estado' => 'No puedes cambiar el estado de una urgencia finalizada o cancelada.',
                ]);
            }

            $isClosingUrgency = in_array($validated['estado'], ['finalizada', 'cancelada'], true)
                && $validated['estado'] !== $lockedUrgency->estado;

            if ($isClosingUrgency) {
                $attentionStates = $lockedUrgency->atenciones()
                    ->lockForUpdate()
                    ->pluck('estado');

                if ($validated['estado'] === 'finalizada' && $attentionStates->isEmpty()) {
                    return back()->withErrors([
                        'estado' => 'Registra una atención antes de finalizar la urgencia.',
                    ]);
                }

                if ($attentionStates->contains(fn (string $status): bool => $status !== 'finalizada')) {
                    return back()->withErrors([
                        'estado' => 'Finaliza todas las atenciones antes de cerrar la urgencia.',
                    ]);
                }
            }

            $lockedUrgency->update($validated);

            return redirect()->route('urgencies.show', $lockedUrgency)->with('success', 'Estado de urgencia actualizado.');
        });
    }

    public function discharge(Request $request, Urgencia $urgency): RedirectResponse
    {
        if ($request->user()->rol === User::ROLE_VETERINARIO) {
            abort_unless($urgency->veterinario_id === $request->user()->id, 404);
        }

        $validated = $request->validate([
            'fecha_egreso' => [
                'required',
                'date',
                'after_or_equal:'.$urgency->fecha_ingreso->format('Y-m-d H:i:s'),
                'before_or_equal:'.now()->format('Y-m-d H:i:s'),
            ],
            'estado_final_mascota' => ['required', 'string', 'max:50'],
        ], [
            'fecha_egreso.after_or_equal' => 'La fecha y hora de egreso debe ser igual o posterior al ingreso.',
            'fecha_egreso.before_or_equal' => 'La fecha y hora de egreso no puede ser futura.',
        ]);

        return DB::transaction(function () use ($request, $urgency, $validated): RedirectResponse {
            $lockedUrgency = Urgencia::query()
                ->lockForUpdate()
                ->findOrFail($urgency->id);

            if ($request->user()->rol === User::ROLE_VETERINARIO) {
                abort_unless($lockedUrgency->veterinario_id === $request->user()->id, 404);
            }

            if ($lockedUrgency->estado !== 'finalizada') {
                return back()->withErrors([
                    'fecha_egreso' => 'Solo puedes registrar el egreso físico de una urgencia finalizada.',
                ]);
            }

            if ($lockedUrgency->fecha_egreso !== null) {
                return back()->withErrors([
                    'fecha_egreso' => 'El egreso físico ya fue registrado.',
                ]);
            }

            $attentionStates = $lockedUrgency->atenciones()
                ->lockForUpdate()
                ->pluck('estado');

            if (
                $attentionStates->isEmpty()
                || $attentionStates->contains(fn (string $status): bool => $status !== 'finalizada')
            ) {
                return back()->withErrors([
                    'fecha_egreso' => 'Finaliza todas las atenciones antes de registrar el egreso físico.',
                ]);
            }

            $lockedUrgency->update([
                'fecha_egreso' => $validated['fecha_egreso'],
                'egreso_registrado_por' => $request->user()->id,
                'estado_final_mascota' => $validated['estado_final_mascota'],
            ]);

            return redirect()->route('urgencies.show', $lockedUrgency)
                ->with('success', 'Egreso físico registrado correctamente.');
        });
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Urgencia $urgency)
    {
        abort(405, 'Las urgencias son parte del historial clínico y no se pueden eliminar.');
    }
}
