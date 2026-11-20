<?php

namespace App\Services;

use App\Models\Mascota;
use App\Models\Propietario;
use Carbon\CarbonInterface;

class SeguimientoMascotaService
{
    private const ACTIVE_STATUSES = [
        'pendiente',
        'en_atencion',
        'en_observacion',
    ];

    /**
     * @return array<int, array<string, mixed>>
     */
    public function listado(Propietario $owner): array
    {
        $pets = $owner->mascotas()
            ->where('estado', 'activo')
            ->whereHas('urgencias', fn ($query) => $query
                ->whereIn('estado', self::ACTIVE_STATUSES)
                ->whereNull('fecha_egreso'))
            ->with([
                'urgencias' => fn ($query) => $query
                    ->whereIn('estado', self::ACTIVE_STATUSES)
                    ->whereNull('fecha_egreso')
                    ->select(['id', 'mascota_id', 'estado', 'fecha_ingreso', 'fecha_egreso'])
                    ->with([
                        'atenciones:id,urgencia_id,fecha_atencion',
                        'atenciones.evolucionesClinicas:id,atencion_id,fecha_evolucion',
                        'atenciones.medicamentosIndicados:id,atencion_id',
                        'atenciones.medicamentosIndicados.administraciones:id,atencion_medicamento_id,fecha_hora',
                    ])
                    ->orderByDesc('fecha_ingreso')
                    ->orderByDesc('id'),
            ])
            ->orderBy('nombre')
            ->get(['id', 'nombre', 'especie', 'raza', 'sexo', 'estado']);

        return $pets->map(function (Mascota $pet): array {
            $currentUrgency = $pet->urgencias->first();
            $lastUpdate = $this->latestUpdate($pet);

            return [
                'id' => $pet->id,
                'nombre' => $pet->nombre,
                'especie' => $pet->especie,
                'raza' => $pet->raza,
                'estado' => $currentUrgency?->estado,
                'fecha_ingreso' => $currentUrgency?->fecha_ingreso?->toDateTimeString(),
                'ultima_actualizacion_clinica' => $lastUpdate?->toDateTimeString(),
            ];
        })->values()->all();
    }

    /**
     * Return only owner-approved pet identity and stay metadata.
     *
     * @return array<string, mixed>
     */
    public function detalle(Propietario $owner, Mascota $pet): array
    {
        $ownedPet = $owner->mascotas()
            ->with([
                'urgencias' => fn ($query) => $query
                    ->select(['id', 'mascota_id', 'estado', 'fecha_ingreso', 'fecha_egreso'])
                    ->with([
                        'atenciones:id,urgencia_id,fecha_atencion',
                        'atenciones.evolucionesClinicas:id,atencion_id,fecha_evolucion',
                        'atenciones.medicamentosIndicados:id,atencion_id',
                        'atenciones.medicamentosIndicados.administraciones:id,atencion_medicamento_id,fecha_hora',
                    ])
                    ->orderBy('fecha_ingreso')
                    ->orderBy('id'),
            ])
            ->findOrFail($pet->id);

        $timeline = collect();

        foreach ($ownedPet->urgencias as $urgency) {
            $timeline->push([
                'tipo' => 'ingreso',
                'fecha' => $urgency->fecha_ingreso?->toDateTimeString(),
                'fecha_orden' => $urgency->fecha_ingreso,
                'estado' => $urgency->estado,
            ]);

            if ($urgency->fecha_egreso !== null) {
                $timeline->push([
                    'tipo' => 'egreso',
                    'fecha' => $urgency->fecha_egreso->toDateTimeString(),
                    'fecha_orden' => $urgency->fecha_egreso,
                ]);
            }
        }

        $timeline = $timeline
            ->sortBy(fn (array $entry) => $entry['fecha_orden']?->timestamp ?? 0)
            ->values()
            ->map(function (array $entry): array {
                unset($entry['fecha_orden']);

                return $entry;
            });

        $currentUrgency = $ownedPet->urgencias
            ->whereIn('estado', self::ACTIVE_STATUSES)
            ->whereNull('fecha_egreso')
            ->sortByDesc(fn ($urgency) => $urgency->fecha_ingreso?->timestamp ?? 0)
            ->first();
        $discharge = $ownedPet->urgencias
            ->whereNotNull('fecha_egreso')
            ->sortByDesc(fn ($urgency) => $urgency->fecha_egreso?->timestamp ?? 0)
            ->first();
        $lastUpdate = $this->latestUpdate($ownedPet);

        return [
            'mascota' => [
                'id' => $ownedPet->id,
                'nombre' => $ownedPet->nombre,
                'especie' => $ownedPet->especie,
                'raza' => $ownedPet->raza,
                'sexo' => $ownedPet->sexo,
                'estado' => $currentUrgency?->estado ?? $ownedPet->estado,
            ],
            'ingreso' => $currentUrgency ? [
                'fecha' => $currentUrgency->fecha_ingreso?->toDateTimeString(),
                'estado' => $currentUrgency->estado,
            ] : null,
            'ultima_actualizacion_clinica' => $lastUpdate?->toDateTimeString(),
            'egreso' => $discharge ? [
                'fecha' => $discharge->fecha_egreso?->toDateTimeString(),
            ] : null,
            'evoluciones' => [],
            'timeline' => $timeline->all(),
        ];
    }

    private function latestUpdate(Mascota $pet): ?CarbonInterface
    {
        $dates = collect();

        foreach ($pet->urgencias as $urgency) {
            $dates->push($urgency->fecha_ingreso);

            foreach ($urgency->atenciones as $attention) {
                $dates->push($attention->fecha_atencion);

                foreach ($attention->evolucionesClinicas as $evolution) {
                    $dates->push($evolution->fecha_evolucion);
                }

                foreach ($attention->medicamentosIndicados as $indication) {
                    foreach ($indication->administraciones as $administration) {
                        $dates->push($administration->fecha_hora);
                    }
                }
            }

            $dates->push($urgency->fecha_egreso);
        }

        return $dates
            ->filter()
            ->sortByDesc(fn ($date) => $date->timestamp)
            ->first();
    }
}
