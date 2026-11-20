<?php

namespace App\Http\Controllers;

use App\Models\Atencion;
use App\Models\AtencionMedicamento;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class AtencionMedicamentoController extends Controller
{
    public function store(Request $request, Atencion $attention): RedirectResponse
    {
        $this->ensureVeterinarianOwnsAttention($request, $attention);
        $attention->loadMissing('urgencia');

        if (
            $attention->estado !== 'en_proceso'
            || ! in_array($attention->urgencia->estado, ['pendiente', 'en_atencion', 'en_observacion'], true)
            || $attention->urgencia->fecha_egreso !== null
        ) {
            return back()->withErrors([
                'medicamento_id' => 'Solo puedes indicar medicamentos en una atención que continúa activa.',
            ]);
        }

        $validated = $request->validate([
            'medicamento_id' => [
                'required',
                Rule::exists('medicamentos', 'id')->where('activo', true),
            ],
            'veterinario_id' => [
                'nullable',
                Rule::exists('users', 'id')->where(fn ($query) => $query
                    ->where('rol', User::ROLE_VETERINARIO)
                    ->where('estado', 'activo')),
            ],
            'dosis' => ['required', 'string', 'max:100'],
            'cantidad' => ['required', 'numeric', 'gt:0', 'max:999999.99'],
            'frecuencia' => ['required', 'string', 'max:100'],
            'via_administracion' => ['required', 'string', 'max:50'],
        ]);

        $veterinarianId = $request->user()->rol === User::ROLE_VETERINARIO
            ? $request->user()->id
            : ($validated['veterinario_id'] ?? null);

        if (! $veterinarianId) {
            return back()->withErrors([
                'veterinario_id' => 'Selecciona un veterinario activo.',
            ]);
        }

        $indication = AtencionMedicamento::query()->firstOrCreate(
            [
                'atencion_id' => $attention->id,
                'medicamento_id' => $validated['medicamento_id'],
            ],
            [
                'veterinario_id' => $veterinarianId,
                'dosis' => $validated['dosis'],
                'cantidad' => $validated['cantidad'],
                'frecuencia' => $validated['frecuencia'],
                'via_administracion' => $validated['via_administracion'],
            ],
        );

        if (! $indication->wasRecentlyCreated) {
            return back()->withErrors([
                'medicamento_id' => 'Este medicamento ya está indicado para la atención.',
            ]);
        }

        return redirect()->route('attentions.show', $attention)
            ->with('success', 'Medicamento asociado a la atención.');
    }

    private function ensureVeterinarianOwnsAttention(Request $request, Atencion $attention): void
    {
        if ($request->user()->rol === User::ROLE_VETERINARIO) {
            abort_unless($attention->veterinario_id === $request->user()->id, 404);
        }
    }
}
