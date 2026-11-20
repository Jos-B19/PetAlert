<?php

namespace App\Http\Controllers;

use App\Models\AtencionMedicamento;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class AdministracionMedicamentoController extends Controller
{
    public function store(Request $request, AtencionMedicamento $attentionMedication): RedirectResponse
    {
        $attentionMedication->loadMissing('atencion.urgencia');
        $attention = $attentionMedication->atencion;

        if ($request->user()->rol === User::ROLE_VETERINARIO) {
            abort_unless(
                $attention->veterinario_id === $request->user()->id,
                404,
            );
        }

        if (
            $attention->estado !== 'en_proceso'
            || ! in_array($attention->urgencia->estado, ['pendiente', 'en_atencion', 'en_observacion'], true)
            || $attention->urgencia->fecha_egreso !== null
        ) {
            return back()->withErrors([
                'fecha_hora' => 'Solo puedes registrar administraciones mientras la atención continúa activa.',
            ]);
        }

        $validated = $request->validate([
            'veterinario_id' => [
                'nullable',
                Rule::exists('users', 'id')->where(fn ($query) => $query
                    ->where('rol', User::ROLE_VETERINARIO)
                    ->where('estado', 'activo')),
            ],
            'dosis_administrada' => ['required', 'string', 'max:100'],
            'cantidad_administrada' => ['required', 'numeric', 'gt:0', 'max:999999.99'],
            'via_administracion' => ['required', 'string', 'max:50'],
            'fecha_hora' => [
                'required',
                'date',
                'after_or_equal:'.$attention->fecha_atencion->format('Y-m-d H:i:s'),
                'before_or_equal:'.now()->format('Y-m-d H:i:s'),
            ],
            'observaciones' => ['nullable', 'string', 'max:2000'],
        ], [
            'fecha_hora.after_or_equal' => 'La administración debe ocurrir después de la atención.',
            'fecha_hora.before_or_equal' => 'La fecha y hora de administración no puede ser futura.',
        ]);

        $veterinarianId = $request->user()->rol === User::ROLE_VETERINARIO
            ? $request->user()->id
            : ($validated['veterinario_id'] ?? null);

        if (! $veterinarianId) {
            return back()->withErrors([
                'veterinario_id' => 'Selecciona un veterinario activo.',
            ]);
        }

        $administration = $attentionMedication->administraciones()->firstOrCreate(
            ['fecha_hora' => $validated['fecha_hora']],
            [
                'veterinario_id' => $veterinarianId,
                'dosis_administrada' => $validated['dosis_administrada'],
                'cantidad_administrada' => $validated['cantidad_administrada'],
                'via_administracion' => $validated['via_administracion'],
                'observaciones' => $validated['observaciones'] ?? null,
            ],
        );

        if (! $administration->wasRecentlyCreated) {
            return back()->withErrors([
                'fecha_hora' => 'Ya existe una administración registrada para esa fecha y hora.',
            ]);
        }

        return redirect()->route('attentions.show', $attention)
            ->with('success', 'Administración de medicamento registrada.');
    }
}
