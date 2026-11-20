<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\Atencion;
use App\Models\Mascota;
use App\Models\Propietario;
use App\Models\Urgencia;
use App\Services\SeguimientoMascotaService;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;
use Symfony\Component\HttpFoundation\Response as HttpResponse;

class AccountController extends Controller
{
    public function __construct(
        private SeguimientoMascotaService $seguimientoMascotaService,
    ) {}

    public function show(Request $request): Response
    {
        $owner = $request->user()->propietario()->first();

        if ($owner !== null) {
            abort_unless($owner->estado === 'activo', 403);
        }

        $stats = [
            'pets' => $owner?->mascotas()->count() ?? 0,
            'urgencies' => $owner?->urgencias()->count() ?? 0,
            'pendingUrgencies' => $owner?->urgencias()->where('urgencias.estado', 'pendiente')->count() ?? 0,
            'activeUrgencies' => $owner?->urgencias()->where('urgencias.estado', 'en_atencion')->count() ?? 0,
            'observationUrgencies' => $owner?->urgencias()->where('urgencias.estado', 'en_observacion')->count() ?? 0,
        ];
        $activities = collect();

        if ($owner !== null) {
            $activities = $owner->urgencias()
                ->with('mascota:id,nombre')
                ->latest('fecha_ingreso')
                ->limit(5)
                ->get()
                ->map(fn (Urgencia $urgency) => [
                    'type' => 'urgency',
                    'title' => 'Urgencia registrada',
                    'detail' => $urgency->mascota->nombre.' · '.$urgency->tipo_urgencia,
                    'date' => $urgency->fecha_ingreso,
                ])
                ->concat(Atencion::query()
                    ->whereHas('urgencia.mascota', fn ($query) => $query->where('propietario_id', $owner->id))
                    ->with('urgencia.mascota:id,nombre')
                    ->latest('fecha_atencion')
                    ->limit(5)
                    ->get()
                    ->map(fn (Atencion $attention) => [
                        'type' => 'attention',
                        'title' => 'Atención veterinaria registrada',
                        'detail' => $attention->urgencia->mascota->nombre,
                        'date' => $attention->fecha_atencion,
                    ]))
                ->sortByDesc('date')
                ->take(6)
                ->values();
        }

        return Inertia::render('Auth/Account', [
            'screen' => 'dashboard',
            'ownerProfile' => $owner !== null ? $this->ownerProfileViewData($owner) : null,
            'stats' => $stats,
            'activities' => $activities,
            'pets' => $owner?->mascotas()
                ->withCount('urgencias')
                ->orderBy('nombre')
                ->paginate(8)
                ->withQueryString() ?? [],
            'activePets' => $owner?->mascotas()
                ->where('estado', 'activo')
                ->orderBy('nombre')
                ->get(['id', 'nombre']) ?? [],
            'urgencies' => $owner?->urgencias()
                ->with([
                    'mascota:id,nombre',
                    'veterinario:id,name',
                ])
                ->latest('fecha_ingreso')
                ->paginate(8)
                ->withQueryString() ?? [],
        ]);
    }

    public function updateProfile(Request $request): RedirectResponse
    {
        $owner = $this->ownerProfile($request);
        $validated = $request->validate([
            'nombres' => ['required', 'string', 'max:100'],
            'apellidos' => ['required', 'string', 'max:100'],
            'tipo_documento' => ['required', 'string', 'max:20'],
            'numero_documento' => ['required', 'string', 'max:30', Rule::unique('propietarios', 'numero_documento')->ignore($owner)],
            'telefono' => ['required', 'string', 'max:20'],
            'email' => [
                'required',
                'email',
                'max:255',
                Rule::unique('users', 'email')->ignore($request->user()),
                Rule::unique('propietarios', 'correo')->ignore($owner),
            ],
        ]);

        DB::transaction(function () use ($validated, $owner, $request): void {
            $owner->update([
                'nombres' => $validated['nombres'],
                'apellidos' => $validated['apellidos'],
                'tipo_documento' => $validated['tipo_documento'],
                'numero_documento' => $validated['numero_documento'],
                'telefono' => $validated['telefono'],
                'correo' => $validated['email'],
            ]);
            $request->user()->update([
                'name' => $validated['nombres'].' '.$validated['apellidos'],
                'email' => $validated['email'],
                'telefono' => $validated['telefono'],
            ]);
        });

        return redirect()->route('account.show')->with('success', 'Tu información personal fue actualizada.');
    }

    public function editProfile(Request $request): Response
    {
        return Inertia::render('Auth/Account', [
            'screen' => 'profile.edit',
            'ownerProfile' => $this->ownerProfileViewData($this->ownerProfile($request)),
        ]);
    }

    public function editPassword(): Response
    {
        return Inertia::render('Auth/Password');
    }

    public function updatePassword(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'current_password' => ['required', 'current_password'],
            'password' => ['required', 'string', 'min:12', 'confirmed', 'different:current_password'],
        ]);

        $request->user()->update([
            'password' => Hash::make($validated['password']),
        ]);
        $request->session()->regenerate();

        return redirect()->route($request->user()->dashboardRouteName())->with('success', 'Tu contraseña fue actualizada.');
    }

    public function createPet(Request $request): Response
    {
        $owner = $this->ownerProfile($request);

        return Inertia::render('Auth/Account', [
            'screen' => 'pets.create',
            'ownerProfile' => $this->ownerProfileViewData($owner),
        ]);
    }

    public function storePet(Request $request): RedirectResponse
    {
        $owner = $this->ownerProfile($request);
        $validated = $request->validate($this->petRules());

        $pet = $owner->mascotas()->create([
            ...$validated,
            'estado' => 'activo',
        ]);

        return redirect()->route('account.pets.show', $pet)->with('success', 'Mascota registrada correctamente.');
    }

    public function showPet(Request $request, Mascota $pet): Response
    {
        $owner = $this->ownerProfile($request);
        $ownedPet = $owner->mascotas()
            ->withCount('urgencias')
            ->with(['urgencias' => fn ($query) => $query
                ->select(['id', 'mascota_id', 'tipo_urgencia', 'nivel_prioridad', 'estado', 'fecha_ingreso'])
                ->latest('fecha_ingreso')
                ->limit(5)])
            ->findOrFail($pet->id);
        $attentions = Atencion::query()
            ->whereHas('urgencia', fn ($query) => $query->where('mascota_id', $ownedPet->id))
            ->with([
                'urgencia:id,mascota_id,tipo_urgencia',
                'veterinario:id,name',
            ])
            ->latest('fecha_atencion')
            ->limit(10)
            ->get(['id', 'urgencia_id', 'veterinario_id', 'fecha_atencion', 'estado'])
            ->map(fn (Atencion $attention) => [
                'id' => $attention->id,
                'fecha_atencion' => $attention->fecha_atencion,
                'estado' => $attention->estado,
                'tipo_urgencia' => $attention->urgencia->tipo_urgencia,
                'veterinario' => $attention->veterinario?->name,
            ]);

        return Inertia::render('Auth/Account', [
            'screen' => 'pets.show',
            'ownerProfile' => $this->ownerProfileViewData($owner),
            'pet' => $ownedPet,
            'attentions' => $attentions,
        ]);
    }

    public function editPet(Request $request, Mascota $pet): Response
    {
        $owner = $this->ownerProfile($request);
        $ownedPet = $owner->mascotas()->findOrFail($pet->id);

        return Inertia::render('Auth/Account', [
            'screen' => 'pets.edit',
            'ownerProfile' => $this->ownerProfileViewData($owner),
            'pet' => $ownedPet,
        ]);
    }

    public function updatePet(Request $request, Mascota $pet): RedirectResponse
    {
        $owner = $this->ownerProfile($request);
        $ownedPet = $owner->mascotas()->findOrFail($pet->id);
        $ownedPet->update($request->validate($this->petRules()));

        return redirect()->route('account.pets.show', $ownedPet)->with('success', 'Información de la mascota actualizada.');
    }

    public function storeUrgency(Request $request): RedirectResponse
    {
        $owner = $this->ownerProfile($request);
        $validated = $request->validate([
            'mascota_id' => ['required', 'integer'],
            'tipo_urgencia' => ['required', 'string', 'max:100'],
            'descripcion' => ['required', 'string', 'max:10000'],
            'nivel_prioridad' => ['required', Rule::in(['baja', 'media', 'alta', 'critica'])],
        ]);
        $pet = $owner->mascotas()
            ->where('estado', 'activo')
            ->findOrFail($validated['mascota_id']);

        $urgency = $pet->urgencias()->create([
            'registrado_por' => $request->user()->id,
            'tipo_urgencia' => $validated['tipo_urgencia'],
            'descripcion' => $validated['descripcion'],
            'nivel_prioridad' => $validated['nivel_prioridad'],
            'estado' => 'pendiente',
            'fecha_ingreso' => now(),
        ]);

        return redirect()->route('account.urgencies.show', $urgency)->with('success', 'Urgencia registrada. El equipo veterinario podrá darle seguimiento.');
    }

    public function showUrgency(Request $request, Urgencia $urgency): Response
    {
        $owner = $this->ownerProfile($request);
        $ownedUrgency = $owner->urgencias()
            ->with([
                'mascota:id,nombre',
                'veterinario:id,name',
            ])
            ->findOrFail($urgency->id);

        $attentions = $ownedUrgency->atenciones()
            ->with('veterinario:id,name')
            ->orderBy('fecha_atencion')
            ->get(['id', 'urgencia_id', 'veterinario_id', 'fecha_atencion', 'estado'])
            ->map(fn ($attention) => [
                'id' => $attention->id,
                'fecha_atencion' => $attention->fecha_atencion,
                'estado' => $attention->estado,
                'veterinario' => $attention->veterinario?->name,
            ]);

        return Inertia::render('Auth/Account', [
            'screen' => 'urgencies.show',
            'ownerProfile' => $this->ownerProfileViewData($owner),
            'urgency' => [
                'id' => $ownedUrgency->id,
                'tipo_urgencia' => $ownedUrgency->tipo_urgencia,
                'descripcion' => $ownedUrgency->descripcion,
                'nivel_prioridad' => $ownedUrgency->nivel_prioridad,
                'estado' => $ownedUrgency->estado,
                'fecha_ingreso' => $ownedUrgency->fecha_ingreso,
                'updated_at' => $ownedUrgency->updated_at,
                'mascota' => $ownedUrgency->mascota->only('id', 'nombre'),
                'veterinario' => $ownedUrgency->veterinario?->only('id', 'name'),
            ],
            'attentions' => $attentions,
        ]);
    }

    public function trackingIndex(Request $request): Response|JsonResponse
    {
        $owner = $this->ownerProfile($request);
        $pets = $this->seguimientoMascotaService->listado($owner);

        if ($request->expectsJson()) {
            return response()->json(['mascotas' => $pets]);
        }

        return Inertia::render('Auth/Account', [
            'screen' => 'tracking.index',
            'trackedPets' => $pets,
        ]);
    }

    public function trackingShow(Request $request, Mascota $pet): Response|JsonResponse
    {
        $owner = $this->ownerProfile($request);
        $detail = $this->seguimientoMascotaService->detalle($owner, $pet);

        if ($request->expectsJson()) {
            return response()->json($detail);
        }

        return Inertia::render('Auth/Account', [
            'screen' => 'tracking.show',
            'trackingPet' => $detail,
        ]);
    }

    public function trackingPdf(Request $request, Mascota $pet): HttpResponse
    {
        $owner = $this->ownerProfile($request);
        $trackingPet = $this->seguimientoMascotaService->detalle($owner, $pet);
        $petName = Str::slug($trackingPet['mascota']['nombre']) ?: 'mascota';

        return Pdf::loadView('pdf.seguimiento-mascota', [
            'trackingPet' => $trackingPet,
            'generatedAt' => now(),
        ])->download("seguimiento-{$petName}-{$pet->id}.pdf");
    }

    public function trackingHistory(Request $request, Mascota $pet): Response|JsonResponse
    {
        $owner = $this->ownerProfile($request);
        $detail = $this->seguimientoMascotaService->detalle($owner, $pet);

        if ($request->expectsJson()) {
            return response()->json([
                'mascota' => $detail['mascota'],
                'timeline' => $detail['timeline'],
                'egreso' => $detail['egreso'],
                'evoluciones' => $detail['evoluciones'],
            ]);
        }

        return Inertia::render('Auth/Account', [
            'screen' => 'tracking.history',
            'trackingPet' => $detail,
            'trackingTimeline' => $detail['timeline'],
        ]);
    }

    /** @return array<string, list<string|ValidationRule>> */
    private function petRules(): array
    {
        return [
            'nombre' => ['required', 'string', 'max:100'],
            'especie' => ['required', 'string', 'max:50'],
            'raza' => ['nullable', 'string', 'max:100'],
            'sexo' => ['nullable', 'string', 'max:20'],
            'fecha_nacimiento' => ['nullable', 'date', 'before_or_equal:today'],
            'peso' => ['nullable', 'numeric', 'gt:0', 'max:999999.99'],
            'color' => ['nullable', 'string', 'max:50'],
            'observaciones' => ['nullable', 'string', 'max:5000'],
        ];
    }

    private function ownerProfile(Request $request): Propietario
    {
        $owner = $request->user()->propietario()->firstOrFail();
        abort_unless($owner->estado === 'activo', 403);

        return $owner;
    }

    /**
     * @return array<string, mixed>
     */
    private function ownerProfileViewData(Propietario $owner): array
    {
        return $owner->only([
            'id',
            'nombres',
            'apellidos',
            'tipo_documento',
            'numero_documento',
            'telefono',
            'estado',
        ]);
    }
}
