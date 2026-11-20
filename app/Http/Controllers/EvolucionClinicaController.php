<?php

namespace App\Http\Controllers;

use App\Models\Atencion;
use App\Models\EvolucionClinica;
use App\Models\Urgencia;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;

class EvolucionClinicaController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request): Response
    {
        $evolutions = EvolucionClinica::query()
            ->with(['atencion.urgencia.mascota.propietario', 'veterinario:id,name'])
            ->when($request->user()->rol === User::ROLE_VETERINARIO, fn ($query) => $query->where('veterinario_id', $request->user()->id))
            ->latest('fecha_evolucion')
            ->paginate(15)
            ->withQueryString();

        return Inertia::render('PetAlert', [
            'screen' => 'evolutions.index',
            'evolutions' => $evolutions,
        ]);
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create(Request $request): Response
    {
        $attentions = Atencion::query()
            ->where('estado', 'en_proceso')
            ->whereHas('urgencia', fn ($query) => $query
                ->whereIn('estado', ['pendiente', 'en_atencion', 'en_observacion'])
                ->whereNull('fecha_egreso'));

        if ($request->user()->rol === User::ROLE_VETERINARIO) {
            $attentions->where('veterinario_id', $request->user()->id);
        }

        return Inertia::render('PetAlert', [
            'screen' => 'evolutions.create',
            'attentions' => $attentions
                ->with('urgencia.mascota:id,nombre')
                ->orderByDesc('fecha_atencion')
                ->get(['id', 'urgencia_id', 'diagnostico', 'fecha_atencion']),
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
            'atencion_id' => [
                'required',
                Rule::exists('atenciones', 'id')->where(fn ($query) => $query->where('estado', 'en_proceso')),
            ],
            'veterinario_id' => [
                'nullable',
                Rule::exists('users', 'id')->where(fn ($query) => $query
                    ->where('rol', User::ROLE_VETERINARIO)
                    ->where('estado', 'activo')),
            ],
            'descripcion' => ['required', 'string', 'max:10000'],
            'tratamiento_aplicado' => ['nullable', 'string', 'max:10000'],
            'estado_mascota' => ['nullable', 'string', 'max:50'],
            'peso' => ['nullable', 'numeric', 'gt:0', 'max:999999.99'],
            'fecha_evolucion' => ['required', 'date'],
            'observaciones' => ['nullable', 'string', 'max:10000'],
        ], [
            'atencion_id.exists' => 'Solo puedes registrar evoluciones en una atención abierta de una urgencia activa.',
        ]);

        $validated['veterinario_id'] = $request->user()->rol === User::ROLE_VETERINARIO
            ? $request->user()->id
            : $validated['veterinario_id'];

        if (! $validated['veterinario_id']) {
            return back()->withErrors(['veterinario_id' => 'Selecciona un veterinario activo.']);
        }

        $evolution = DB::transaction(function () use ($request, $validated): EvolucionClinica {
            $urgencyId = Atencion::query()
                ->whereKey($validated['atencion_id'])
                ->value('urgencia_id');

            if ($urgencyId === null) {
                throw ValidationException::withMessages([
                    'atencion_id' => 'La atención seleccionada ya no está disponible.',
                ]);
            }

            $urgency = Urgencia::query()
                ->lockForUpdate()
                ->findOrFail($urgencyId);
            $attention = Atencion::query()
                ->lockForUpdate()
                ->findOrFail($validated['atencion_id']);

            if ($request->user()->rol === User::ROLE_VETERINARIO) {
                abort_unless($attention->veterinario_id === $request->user()->id, 404);
            }

            if ($attention->estado !== 'en_proceso') {
                throw ValidationException::withMessages([
                    'atencion_id' => 'Solo puedes registrar evoluciones en una atención abierta.',
                ]);
            }

            if (
                in_array($urgency->estado, ['finalizada', 'cancelada'], true)
                || $urgency->fecha_egreso !== null
            ) {
                throw ValidationException::withMessages([
                    'atencion_id' => 'No puedes registrar evoluciones después del cierre de la urgencia o del egreso físico.',
                ]);
            }

            return EvolucionClinica::create($validated);
        });

        return redirect()->route('evolutions.index')->with('success', 'Evolución clínica registrada.');
    }

    /**
     * Display the specified resource.
     */
    public function show(Request $request, EvolucionClinica $evolution): Response
    {
        if ($request->user()->rol === User::ROLE_VETERINARIO) {
            abort_unless($evolution->veterinario_id === $request->user()->id, 404);
        }

        return Inertia::render('PetAlert', [
            'screen' => 'evolutions.show',
            'evolution' => $evolution->load(['atencion.urgencia.mascota.propietario', 'veterinario:id,name']),
        ]);
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(EvolucionClinica $evolution)
    {
        abort(405, 'Las evoluciones clínicas son registros históricos y no se editan.');
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, EvolucionClinica $evolution)
    {
        abort(405, 'Las evoluciones clínicas son registros históricos y no se editan.');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(EvolucionClinica $evolution)
    {
        abort(405, 'Las evoluciones clínicas son registros históricos y no se eliminan.');
    }
}
