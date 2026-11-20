<?php

namespace App\Http\Controllers;

use App\Models\Atencion;
use App\Models\EvolucionClinica;
use App\Models\Mascota;
use App\Models\Propietario;
use App\Models\Urgencia;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class DashboardController extends Controller
{
    public function redirectForRole(Request $request): RedirectResponse
    {
        return redirect()->route($request->user()->dashboardRouteName());
    }

    public function __invoke(Request $request): Response
    {
        $stats = [
            'owners' => Propietario::count(),
            'pets' => Mascota::count(),
            'activePets' => Mascota::where('estado', 'activo')->count(),
            'inactivePets' => Mascota::where('estado', 'inactivo')->count(),
            'urgencies' => Urgencia::count(),
            'pendingUrgencies' => Urgencia::where('estado', 'pendiente')->count(),
            'activeUrgencies' => Urgencia::where('estado', 'en_atencion')->count(),
            'observationUrgencies' => Urgencia::where('estado', 'en_observacion')->count(),
            'completedUrgencies' => Urgencia::where('estado', 'finalizada')->count(),
            'attentions' => Atencion::count(),
            'staff' => User::whereIn('rol', User::STAFF_ROLES)->count(),
            'activeStaff' => User::whereIn('rol', User::STAFF_ROLES)->where('estado', 'activo')->count(),
        ];

        $latestUrgencies = Urgencia::query()
            ->with(['mascota:id,nombre,propietario_id', 'mascota.propietario:id,nombres,apellidos'])
            ->latest('fecha_ingreso')
            ->limit(5)
            ->get();

        $latestPets = Mascota::query()
            ->with('propietario:id,nombres,apellidos')
            ->latest()
            ->limit(5)
            ->get();

        $activities = collect()
            ->concat(Urgencia::query()->with('mascota:id,nombre')->latest('fecha_ingreso')->limit(5)->get()
                ->map(fn (Urgencia $urgency) => [
                    'type' => 'urgency',
                    'title' => 'Urgencia registrada',
                    'detail' => $urgency->mascota->nombre.' · '.$urgency->tipo_urgencia,
                    'date' => $urgency->fecha_ingreso,
                ]))
            ->concat(Atencion::query()->with('urgencia.mascota:id,nombre')->latest('fecha_atencion')->limit(5)->get()
                ->map(fn (Atencion $attention) => [
                    'type' => 'attention',
                    'title' => 'Atención veterinaria',
                    'detail' => $attention->urgencia->mascota->nombre,
                    'date' => $attention->fecha_atencion,
                ]))
            ->concat(EvolucionClinica::query()->with('atencion.urgencia.mascota:id,nombre')->latest('fecha_evolucion')->limit(5)->get()
                ->map(fn (EvolucionClinica $evolution) => [
                    'type' => 'evolution',
                    'title' => 'Evolución clínica',
                    'detail' => $evolution->atencion->urgencia->mascota->nombre,
                    'date' => $evolution->fecha_evolucion,
                ]))
            ->sortByDesc('date')
            ->take(8)
            ->values();

        return Inertia::render('PetAlert', [
            'screen' => 'dashboard.admin',
            'stats' => $stats,
            'latestUrgencies' => $latestUrgencies,
            'latestPets' => $latestPets,
            'activities' => $activities,
        ]);
    }

    public function reception(): Response
    {
        $stats = [
            'owners' => Propietario::count(),
            'pets' => Mascota::count(),
            'urgencies' => Urgencia::count(),
            'pendingUrgencies' => Urgencia::where('estado', 'pendiente')->count(),
            'activeUrgencies' => Urgencia::where('estado', 'en_atencion')->count(),
            'observationUrgencies' => Urgencia::where('estado', 'en_observacion')->count(),
            'completedUrgencies' => Urgencia::where('estado', 'finalizada')->count(),
        ];

        $latestUrgencies = Urgencia::query()
            ->with(['mascota:id,nombre,propietario_id', 'mascota.propietario:id,nombres,apellidos', 'veterinario:id,name'])
            ->latest('fecha_ingreso')
            ->limit(8)
            ->get();

        $veterinarians = User::query()
            ->where('rol', User::ROLE_VETERINARIO)
            ->where('estado', 'activo')
            ->orderBy('name')
            ->get(['id', 'name']);

        return Inertia::render('PetAlert', [
            'screen' => 'dashboard.reception',
            'stats' => $stats,
            'latestUrgencies' => $latestUrgencies,
            'veterinarians' => $veterinarians,
        ]);
    }

    public function veterinarian(Request $request): Response
    {
        $veterinarianId = $request->user()->id;
        $assignedUrgencies = Urgencia::query()->where('veterinario_id', $veterinarianId);
        $stats = [
            'assignedUrgencies' => (clone $assignedUrgencies)->count(),
            'pendingUrgencies' => (clone $assignedUrgencies)->where('estado', 'pendiente')->count(),
            'activeUrgencies' => (clone $assignedUrgencies)->where('estado', 'en_atencion')->count(),
            'observationUrgencies' => (clone $assignedUrgencies)->where('estado', 'en_observacion')->count(),
            'petsInCare' => (clone $assignedUrgencies)->where('estado', 'en_atencion')->distinct()->count('mascota_id'),
            'attentions' => Atencion::where('veterinario_id', $veterinarianId)->count(),
            'evolutions' => EvolucionClinica::where('veterinario_id', $veterinarianId)->count(),
        ];

        $latestUrgencies = (clone $assignedUrgencies)
            ->with(['mascota:id,nombre,propietario_id', 'mascota.propietario:id,nombres,apellidos'])
            ->latest('fecha_ingreso')
            ->limit(8)
            ->get();

        return Inertia::render('PetAlert', [
            'screen' => 'dashboard.veterinarian',
            'stats' => $stats,
            'latestUrgencies' => $latestUrgencies,
        ]);
    }
}
