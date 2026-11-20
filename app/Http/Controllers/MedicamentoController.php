<?php

namespace App\Http\Controllers;

use App\Models\Medicamento;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;

class MedicamentoController extends Controller
{
    public function index(): Response
    {
        return Inertia::render('PetAlert', [
            'screen' => 'medications.index',
            'medications' => Medicamento::query()
                ->withCount('administraciones')
                ->orderBy('nombre')
                ->get(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'nombre' => [
                'required',
                'string',
                'max:150',
                Rule::unique('medicamentos', 'nombre')
                    ->where('presentacion', $request->input('presentacion')),
            ],
            'presentacion' => ['required', 'string', 'max:100'],
        ], [
            'nombre.unique' => 'Ya existe un medicamento con estos datos.',
        ]);

        Medicamento::query()->create([
            ...$validated,
            'activo' => true,
        ]);

        return redirect()->route('medications.index')->with('success', 'Medicamento agregado al catálogo.');
    }
}
