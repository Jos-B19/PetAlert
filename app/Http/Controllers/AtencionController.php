<?php

namespace App\Http\Controllers;

use App\Models\Atencion;
use App\Models\Medicamento;
use App\Models\Urgencia;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;

class AtencionController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request): Response
    {
        $search = trim((string) $request->query('q', ''));

        $attentions = Atencion::query()
            ->with(['urgencia.mascota.propietario', 'veterinario:id,name'])
            ->when($request->user()->rol === User::ROLE_VETERINARIO, fn ($query) => $query->where('veterinario_id', $request->user()->id))
            ->when($search !== '', fn ($query) => $query->where(function ($query) use ($search) {
                $query->where('diagnostico', 'like', "%{$search}%")
                    ->orWhereHas('urgencia.mascota', fn ($petQuery) => $petQuery->where('nombre', 'like', "%{$search}%"));
            }))
            ->latest('fecha_atencion')
            ->paginate(15)
            ->withQueryString();

        return Inertia::render('PetAlert', [
            'screen' => 'attentions.index',
            'attentions' => $attentions,
            'filters' => ['q' => $search],
        ]);
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create(Request $request): Response
    {
        $urgencyQuery = Urgencia::query()
            ->whereIn('estado', ['pendiente', 'en_atencion', 'en_observacion'])
            ->whereNull('fecha_egreso');

        if ($request->user()->rol === User::ROLE_VETERINARIO) {
            $urgencyQuery->where('veterinario_id', $request->user()->id);
        }

        return Inertia::render('PetAlert', [
            'screen' => 'attentions.create',
            'urgencies' => $urgencyQuery
                ->with('mascota:id,nombre,propietario_id', 'mascota.propietario:id,nombres,apellidos')
                ->orderByDesc('fecha_ingreso')
                ->get(['id', 'mascota_id', 'tipo_urgencia', 'fecha_ingreso', 'estado']),
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
            'urgencia_id' => [
                'required',
                Rule::exists('urgencias', 'id')->where(fn ($query) => $query
                    ->whereIn('estado', ['pendiente', 'en_atencion', 'en_observacion'])
                    ->whereNull('fecha_egreso')),
            ],
            'veterinario_id' => [
                'nullable',
                Rule::exists('users', 'id')->where(fn ($query) => $query
                    ->where('rol', User::ROLE_VETERINARIO)
                    ->where('estado', 'activo')),
            ],
            'diagnostico' => ['required', 'string', 'max:10000'],
            'tratamiento' => ['nullable', 'string', 'max:10000'],
            'procedimientos' => ['nullable', 'string', 'max:10000'],
            'observaciones' => ['nullable', 'string', 'max:10000'],
            'fecha_atencion' => ['required', 'date'],
        ], [
            'urgencia_id.exists' => 'No puedes crear una atención para una urgencia cerrada o con egreso físico.',
        ]);

        $validated['veterinario_id'] = $request->user()->rol === User::ROLE_VETERINARIO
            ? $request->user()->id
            : $validated['veterinario_id'];

        if (! $validated['veterinario_id']) {
            return back()->withErrors(['veterinario_id' => 'Selecciona un veterinario activo.']);
        }

        $attention = DB::transaction(function () use ($request, $validated): Atencion {
            $urgency = Urgencia::query()
                ->lockForUpdate()
                ->findOrFail($validated['urgencia_id']);

            if (
                ! in_array($urgency->estado, ['pendiente', 'en_atencion', 'en_observacion'], true)
                || $urgency->fecha_egreso !== null
            ) {
                throw ValidationException::withMessages([
                    'urgencia_id' => 'No puedes crear una atención para una urgencia cerrada o con egreso físico.',
                ]);
            }

            if ($request->user()->rol === User::ROLE_VETERINARIO) {
                abort_unless($urgency->veterinario_id === $request->user()->id, 404);
            }

            $attention = Atencion::create([
                ...$validated,
                'estado' => 'en_proceso',
            ]);

            $urgency->update([
                'estado' => 'en_atencion',
                'veterinario_id' => $attention->veterinario_id,
            ]);

            return $attention;
        });

        return redirect()->route('attentions.show', $attention)->with('success', 'Atención veterinaria registrada.');
    }

    /**
     * Display the specified resource.
     */
    public function show(Request $request, Atencion $attention): Response
    {
        $this->ensureVeterinarianOwnsAttention($request, $attention);
        $attention->load([
            'urgencia.mascota.propietario',
            'veterinario:id,name',
            'medicamentosIndicados.medicamento',
            'medicamentosIndicados.veterinario:id,name',
            'medicamentosIndicados.administraciones.veterinario:id,name',
            'evolucionesClinicas' => fn ($query) => $query
                ->when($request->user()->rol === User::ROLE_VETERINARIO, fn ($query) => $query->where('veterinario_id', $request->user()->id))
                ->with('veterinario:id,name'),
        ]);

        return Inertia::render('PetAlert', [
            'screen' => 'attentions.show',
            'attention' => $attention,
            'availableMedications' => Medicamento::query()
                ->where('activo', true)
                ->orderBy('nombre')
                ->get(['id', 'nombre', 'presentacion']),
            'veterinarians' => User::query()
                ->where('rol', User::ROLE_VETERINARIO)
                ->where('estado', 'activo')
                ->orderBy('name')
                ->get(['id', 'name']),
        ]);
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Request $request, Atencion $attention): Response
    {
        $this->ensureVeterinarianOwnsAttention($request, $attention);

        return Inertia::render('PetAlert', [
            'screen' => 'attentions.edit',
            'attention' => $attention->load(['urgencia.mascota', 'veterinario:id,name']),
        ]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Atencion $attention): RedirectResponse
    {
        $this->ensureVeterinarianOwnsAttention($request, $attention);
        $validated = $request->validate([
            'diagnostico' => ['required', 'string', 'max:10000'],
            'tratamiento' => ['nullable', 'string', 'max:10000'],
            'procedimientos' => ['nullable', 'string', 'max:10000'],
            'observaciones' => ['nullable', 'string', 'max:10000'],
            'estado' => ['required', Rule::in(['en_proceso', 'finalizada'])],
        ]);

        return DB::transaction(function () use ($request, $attention, $validated): RedirectResponse {
            $urgency = Urgencia::query()
                ->lockForUpdate()
                ->findOrFail($attention->urgencia_id);
            $lockedAttention = Atencion::query()
                ->lockForUpdate()
                ->findOrFail($attention->id);

            $this->ensureVeterinarianOwnsAttention($request, $lockedAttention);

            if (
                $validated['estado'] === 'en_proceso'
                && $urgency->fecha_egreso !== null
            ) {
                return back()->withErrors([
                    'estado' => 'No puedes reabrir una atención después del egreso físico de la mascota.',
                ]);
            }

            if ($lockedAttention->estado === 'finalizada') {
                return back()->withErrors([
                    'estado' => 'No puedes modificar una atención finalizada.',
                ]);
            }

            if ($urgency->fecha_egreso !== null) {
                return back()->withErrors([
                    'estado' => 'No puedes modificar una atención después del egreso físico de la mascota.',
                ]);
            }

            if (in_array($urgency->estado, ['finalizada', 'cancelada'], true)) {
                return back()->withErrors([
                    'estado' => 'No puedes modificar una atención después del cierre de la urgencia.',
                ]);
            }

            $lockedAttention->update($validated);

            if ($validated['estado'] === 'finalizada') {
                $attentionStates = $urgency->atenciones()
                    ->lockForUpdate()
                    ->pluck('estado');

                if (
                    $attentionStates->isNotEmpty()
                    && $attentionStates->every(fn (string $status): bool => $status === 'finalizada')
                ) {
                    $urgency->update(['estado' => 'finalizada']);
                }
            }

            return redirect()->route('attentions.show', $lockedAttention)
                ->with('success', 'Atención actualizada.');
        });
    }

    private function ensureVeterinarianOwnsAttention(Request $request, Atencion $attention): void
    {
        if ($request->user()->rol === User::ROLE_VETERINARIO) {
            abort_unless($attention->veterinario_id === $request->user()->id, 404);
        }
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Atencion $attention)
    {
        abort(405, 'Las atenciones hacen parte del historial clínico y no se pueden eliminar.');
    }
}
