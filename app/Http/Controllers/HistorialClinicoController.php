<?php

namespace App\Http\Controllers;

use App\Models\HistorialClinico;
use App\Models\Mascota;
use Illuminate\Http\Request;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;

class HistorialClinicoController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request): Response
    {
        $search = trim((string) $request->query('q', ''));

        $pets = Mascota::query()
            ->with(['propietario:id,nombres,apellidos', 'historialClinico:id,mascota_id,updated_at'])
            ->when($request->user()->rol === 'veterinario', fn ($query) => $query->whereHas(
                'urgencias',
                fn ($urgencyQuery) => $urgencyQuery->where('veterinario_id', $request->user()->id),
            ))
            ->when($search !== '', fn ($query) => $query->where(function ($query) use ($search) {
                $query->where('nombre', 'like', "%{$search}%")
                    ->orWhereHas('propietario', fn ($ownerQuery) => $ownerQuery
                        ->where('nombres', 'like', "%{$search}%")
                        ->orWhere('apellidos', 'like', "%{$search}%"));
            }))
            ->orderBy('nombre')
            ->paginate(15)
            ->withQueryString();

        return Inertia::render('PetAlert', [
            'screen' => 'histories.index',
            'pets' => $pets,
            'filters' => ['q' => $search],
        ]);
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        abort(405, 'El historial clínico se crea al guardar su primera evolución.');
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        abort(405, 'Actualiza el historial desde la ficha de la mascota.');
    }

    /**
     * Display the specified resource.
     */
    public function show(Request $request, Mascota $pet): Response
    {
        if ($request->user()->rol === 'veterinario') {
            $pet = Mascota::query()
                ->whereKey($pet->id)
                ->whereHas('urgencias', fn ($query) => $query->where('veterinario_id', $request->user()->id))
                ->firstOrFail();
        }

        $pet->load([
            'propietario',
            'historialClinico',
            'urgencias' => function ($query) use ($request): void {
                if ($request->user()->rol === 'veterinario') {
                    $query->where('veterinario_id', $request->user()->id);
                }

                $query->with([
                    'registradoPor:id,name',
                    'veterinario:id,name',
                    'atenciones' => fn ($attentionQuery) => $attentionQuery
                        ->with([
                            'veterinario:id,name',
                            'evolucionesClinicas.veterinario:id,name',
                        ])
                        ->orderBy('fecha_atencion'),
                ])->orderByDesc('fecha_ingreso');
            },
        ]);

        $timeline = collect();

        foreach ($pet->urgencias as $urgency) {
            $timeline->push([
                'type' => 'urgencia',
                'date' => $urgency->fecha_ingreso,
                'record' => $urgency,
            ]);

            foreach ($urgency->atenciones as $attention) {
                $timeline->push([
                    'type' => 'atencion',
                    'date' => $attention->fecha_atencion,
                    'record' => $attention,
                ]);

                foreach ($attention->evolucionesClinicas as $evolution) {
                    $timeline->push([
                        'type' => 'evolucion',
                        'date' => $evolution->fecha_evolucion,
                        'record' => $evolution,
                    ]);
                }
            }
        }

        return Inertia::render('PetAlert', [
            'screen' => 'histories.show',
            'pet' => $pet,
            'history' => $pet->historialClinico,
            'timeline' => $timeline->sortByDesc('date')->values(),
        ]);
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Mascota $pet)
    {
        return $this->show($pet);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Mascota $pet): RedirectResponse
    {
        if ($request->user()->rol === 'veterinario') {
            $pet = Mascota::query()
                ->whereKey($pet->id)
                ->whereHas('urgencias', fn ($query) => $query->where('veterinario_id', $request->user()->id))
                ->firstOrFail();
        }

        $validated = $request->validate([
            'antecedentes' => ['nullable', 'string', 'max:10000'],
            'alergias' => ['nullable', 'string', 'max:10000'],
            'enfermedades_previas' => ['nullable', 'string', 'max:10000'],
            'vacunas' => ['nullable', 'string', 'max:10000'],
            'observaciones' => ['nullable', 'string', 'max:10000'],
        ]);

        $pet->historialClinico()->updateOrCreate([], $validated);

        return redirect()->route('histories.show', $pet)->with('success', 'Historial clínico actualizado.');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Mascota $pet)
    {
        abort(405, 'El historial clínico no se puede eliminar.');
    }
}
