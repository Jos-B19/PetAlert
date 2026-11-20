<?php

namespace Database\Seeders;

use App\Models\AdministracionMedicamento;
use App\Models\Atencion;
use App\Models\AtencionMedicamento;
use App\Models\EvolucionClinica;
use App\Models\HistorialClinico;
use App\Models\Mascota;
use App\Models\Medicamento;
use App\Models\Propietario;
use App\Models\Urgencia;
use App\Models\User;
use Carbon\CarbonImmutable;
use Carbon\CarbonInterface;
use Illuminate\Database\Seeder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use RuntimeException;

class ReportesDemoSeeder extends Seeder
{
    private const OWNER_TARGET = 15;

    private const PET_TARGET = 30;

    private const URGENCY_TARGET = 45;

    private const ATTENTION_TARGET = 40;

    private const EVOLUTION_TARGET = 60;

    public function run(): void
    {
        if (! app()->environment(['local', 'development'])) {
            throw new RuntimeException('ReportesDemoSeeder solo puede ejecutarse en entornos local o development.');
        }

        $recorder = User::query()
            ->whereIn('rol', [User::ROLE_RECEPCION, User::ROLE_ADMINISTRADOR])
            ->where('estado', 'activo')
            ->orderBy('id')
            ->first();
        $veterinarian = User::query()
            ->where('rol', User::ROLE_VETERINARIO)
            ->where('estado', 'activo')
            ->orderBy('id')
            ->first();

        if (! $recorder || ! $veterinarian) {
            throw new RuntimeException('Se requiere un usuario activo de recepción o administración y un veterinario activo.');
        }

        DB::transaction(function () use ($recorder, $veterinarian): void {
            $owners = $this->createDemoOwners();
            $pets = $this->createDemoPets($owners);
            $this->createDemoHistories($pets);
            $urgencies = $this->createDemoUrgencies($pets, $recorder, $veterinarian);
            $attentions = $this->createDemoAttentions($urgencies, $veterinarian);
            $this->createDemoEvolutions($attentions, $veterinarian);
            $this->createDemoMedicationRecords($veterinarian);
        });
    }

    private function createDemoMedicationRecords(User $veterinarian): void
    {
        $medications = collect([
            ['nombre' => '[DEMO REPORTES] Analgésico de prueba', 'presentacion' => 'Tableta ficticia'],
            ['nombre' => '[DEMO REPORTES] Solución de hidratación', 'presentacion' => 'Solución ficticia'],
            ['nombre' => '[DEMO REPORTES] Antiinflamatorio de prueba', 'presentacion' => 'Suspensión ficticia'],
        ])->map(fn (array $attributes): Medicamento => Medicamento::query()->firstOrCreate(
            [
                'nombre' => $attributes['nombre'],
                'presentacion' => $attributes['presentacion'],
            ],
            ['activo' => true],
        ));

        $attentions = Atencion::query()
            ->where('diagnostico', 'like', '[DEMO REPORTES]%')
            ->with('urgencia')
            ->orderBy('id')
            ->get();

        foreach ($attentions as $index => $attention) {
            $medication = $medications[$index % $medications->count()];
            $indication = AtencionMedicamento::query()->firstOrCreate(
                [
                    'atencion_id' => $attention->id,
                    'medicamento_id' => $medication->id,
                ],
                [
                    'veterinario_id' => $veterinarian->id,
                    'dosis' => 'Dosis ficticia de prueba',
                    'cantidad' => 1,
                    'frecuencia' => 'Cada 12 horas (demostración)',
                    'via_administracion' => 'Oral (demostración)',
                ],
            );

            $administrationCount = $index % 2 === 0 ? 2 : 1;

            for ($sequence = 1; $sequence <= $administrationCount; $sequence++) {
                $administrationDate = CarbonImmutable::instance($attention->fecha_atencion)
                    ->addHours($sequence);

                if (
                    $attention->urgencia->fecha_egreso !== null
                    && $administrationDate->greaterThanOrEqualTo($attention->urgencia->fecha_egreso)
                ) {
                    $administrationDate = CarbonImmutable::instance($attention->urgencia->fecha_egreso)->subMinute();
                }

                AdministracionMedicamento::query()->firstOrCreate(
                    [
                        'atencion_medicamento_id' => $indication->id,
                        'fecha_hora' => $administrationDate,
                    ],
                    [
                        'veterinario_id' => $veterinarian->id,
                        'dosis_administrada' => 'Dosis ficticia administrada',
                        'cantidad_administrada' => 1,
                        'via_administracion' => 'Oral (demostración)',
                        'observaciones' => '[DEMO REPORTES] Administración ficticia de prueba.',
                    ],
                );
            }
        }
    }

    /**
     * @return Collection<int, Propietario>
     */
    private function createDemoOwners(): Collection
    {
        $ownerPrefix = 'DEMO-REPORT-OWNER-';
        $existingOwners = Propietario::query()
            ->where('numero_documento', 'not like', $ownerPrefix.'%')
            ->count();
        $targetDemoOwners = max(1, self::OWNER_TARGET - $existingOwners);

        for ($index = 1; $index <= $targetDemoOwners; $index++) {
            $documentNumber = $ownerPrefix.str_pad((string) $index, 3, '0', STR_PAD_LEFT);

            Propietario::query()->firstOrCreate(
                ['numero_documento' => $documentNumber],
                [
                    'nombres' => 'Demo Reportes '.$index,
                    'apellidos' => 'Propietario Ficticio',
                    'tipo_documento' => 'OTRO',
                    'telefono' => '300'.str_pad((string) $index, 7, '0', STR_PAD_LEFT),
                    'correo' => 'reportes-demo-owner-'.$index.'@example.test',
                    'direccion' => 'Dirección ficticia '.$index.', Ipiales',
                    'estado' => 'activo',
                ],
            );
        }

        return Propietario::query()
            ->where('numero_documento', 'like', $ownerPrefix.'%')
            ->orderBy('numero_documento')
            ->get();
    }

    /**
     * @param  Collection<int, Propietario>  $owners
     * @return Collection<int, Mascota>
     */
    private function createDemoPets(Collection $owners): Collection
    {
        $petPrefix = 'DEMO REPORTES - Mascota ';
        $pets = collect();

        for ($index = 1; $index <= self::PET_TARGET; $index++) {
            $owner = $owners[($index - 1) % $owners->count()];
            $name = $petPrefix.str_pad((string) $index, 3, '0', STR_PAD_LEFT);

            $pet = Mascota::query()->firstOrCreate(
                ['nombre' => $name],
                [
                    'propietario_id' => $owner->id,
                    'especie' => $index % 3 === 0 ? 'Felino' : 'Canino',
                    'raza' => $index % 3 === 0 ? 'Mestizo' : 'Criollo',
                    'sexo' => $index % 2 === 0 ? 'Hembra' : 'Macho',
                    'fecha_nacimiento' => CarbonImmutable::parse('2021-01-01')->subMonths($index * 2)->toDateString(),
                    'peso' => number_format(3.5 + ($index % 18), 2, '.', ''),
                    'color' => $index % 2 === 0 ? 'Café' : 'Negro',
                    'observaciones' => '[DEMO REPORTES] Registro ficticio para pruebas.',
                    'estado' => 'activo',
                ],
            );

            $pets->push($pet);
        }

        return $pets;
    }

    /**
     * @param  Collection<int, Mascota>  $pets
     */
    private function createDemoHistories(Collection $pets): void
    {
        foreach ($pets as $pet) {
            HistorialClinico::query()->firstOrCreate(
                ['mascota_id' => $pet->id],
                [
                    'antecedentes' => '[DEMO REPORTES] Sin antecedentes relevantes; dato ficticio.',
                    'alergias' => 'No registradas en este caso ficticio.',
                    'enfermedades_previas' => 'No registradas en este caso ficticio.',
                    'vacunas' => 'Esquema de prueba, sin validez clínica.',
                    'observaciones' => '[DEMO REPORTES] Historial creado solo para pruebas.',
                ],
            );
        }
    }

    /**
     * @param  Collection<int, Mascota>  $pets
     * @return Collection<int, Urgencia>
     */
    private function createDemoUrgencies(Collection $pets, User $recorder, User $veterinarian): Collection
    {
        $urgencies = collect();
        $types = ['Digestiva', 'Traumatológica', 'Respiratoria', 'Herida', 'Observación clínica'];
        $priorities = ['baja', 'media', 'alta', 'critica'];

        for ($index = 1; $index <= self::URGENCY_TARGET; $index++) {
            $pet = $pets[($index - 1) % $pets->count()];
            $isFinalized = $index <= 30;
            $hasAttention = $index <= self::ATTENTION_TARGET;
            $urgencyStatus = match (true) {
                $isFinalized => 'finalizada',
                $index <= 35 => 'en_atencion',
                $index <= 40 => 'en_observacion',
                $index <= 43 => 'pendiente',
                default => 'cancelada',
            };
            $ingressDate = $this->ingressDate($index);
            $evolutionCount = $index <= self::EVOLUTION_TARGET - self::ATTENTION_TARGET
                ? 2
                : ($hasAttention ? 1 : 0);
            $lastEvolutionDate = $hasAttention
                ? $this->evolutionDate($this->attentionDate($ingressDate, $index), $index, $evolutionCount)
                : null;
            $dischargeDate = $isFinalized && $lastEvolutionDate !== null
                ? $lastEvolutionDate->addHours(2 + ($index % 7))
                : null;
            $urgencyType = '[DEMO REPORTES] '.$types[($index - 1) % count($types)].' #'.str_pad((string) $index, 3, '0', STR_PAD_LEFT);

            $urgency = Urgencia::query()->firstOrCreate(
                [
                    'mascota_id' => $pet->id,
                    'tipo_urgencia' => $urgencyType,
                ],
                [
                    'registrado_por' => $recorder->id,
                    'veterinario_id' => $urgencyStatus === 'pendiente' || $urgencyStatus === 'cancelada' ? null : $veterinarian->id,
                    'descripcion' => '[DEMO REPORTES] Caso clínico ficticio #'.str_pad((string) $index, 3, '0', STR_PAD_LEFT).'.',
                    'nivel_prioridad' => $priorities[($index - 1) % count($priorities)],
                    'estado' => $urgencyStatus,
                    'fecha_ingreso' => $ingressDate,
                    'fecha_egreso' => $dischargeDate,
                ],
            );

            $urgencies->push($urgency);
        }

        return $urgencies;
    }

    /**
     * @param  Collection<int, Urgencia>  $urgencies
     * @return Collection<int, Atencion>
     */
    private function createDemoAttentions(Collection $urgencies, User $veterinarian): Collection
    {
        $attentions = collect();
        $diagnoses = ['Valoración digestiva', 'Evaluación de lesión', 'Valoración respiratoria', 'Curación y control', 'Observación clínica'];
        $plans = [
            'Hidratación, reposo y control clínico.',
            'Limpieza local y seguimiento de la evolución.',
            'Observación clínica y nueva valoración.',
            'Cuidados de soporte y control de signos.',
        ];

        for ($index = 1; $index <= self::ATTENTION_TARGET; $index++) {
            $urgency = $urgencies[$index - 1];
            $diagnosis = '[DEMO REPORTES] '.$diagnoses[($index - 1) % count($diagnoses)].' #'.str_pad((string) $index, 3, '0', STR_PAD_LEFT);

            $attention = Atencion::query()->firstOrCreate(
                [
                    'urgencia_id' => $urgency->id,
                    'diagnostico' => $diagnosis,
                ],
                [
                    'veterinario_id' => $veterinarian->id,
                    'tratamiento' => $plans[($index - 1) % count($plans)],
                    'procedimientos' => 'Examen físico y monitorización clínica.',
                    'observaciones' => '[DEMO REPORTES] Atención ficticia.',
                    'fecha_atencion' => $this->attentionDate($urgency->fecha_ingreso, $index),
                    'estado' => $index <= 30 ? 'finalizada' : 'en_proceso',
                ],
            );

            $attentions->push($attention);
        }

        return $attentions;
    }

    /**
     * @param  Collection<int, Atencion>  $attentions
     */
    private function createDemoEvolutions(Collection $attentions, User $veterinarian): void
    {
        $outcomes = ['Recuperación favorable', 'Continúa en tratamiento', 'Requiere seguimiento', 'Remisión'];
        $plans = [
            'Continúa con hidratación y reposo.',
            'Se mantiene observación clínica.',
            'Control de signos y seguimiento.',
            'Se remite para valoración complementaria.',
        ];
        $evolutionIndex = 0;

        foreach ($attentions as $attentionIndex => $attention) {
            $attentionNumber = $attentionIndex + 1;
            $evolutionCount = $attentionNumber <= self::EVOLUTION_TARGET - self::ATTENTION_TARGET ? 2 : 1;

            for ($sequence = 1; $sequence <= $evolutionCount; $sequence++) {
                $evolutionIndex++;
                $description = '[DEMO REPORTES] Evolución #'.str_pad((string) $evolutionIndex, 3, '0', STR_PAD_LEFT);

                EvolucionClinica::query()->firstOrCreate(
                    [
                        'atencion_id' => $attention->id,
                        'descripcion' => $description,
                    ],
                    [
                        'veterinario_id' => $veterinarian->id,
                        'tratamiento_aplicado' => $plans[($evolutionIndex - 1) % count($plans)],
                        'estado_mascota' => $outcomes[($evolutionIndex - 1) % count($outcomes)],
                        'peso' => 3.5 + ($attentionNumber % 18),
                        'fecha_evolucion' => $this->evolutionDate($attention->fecha_atencion, $attentionNumber, $sequence),
                        'observaciones' => '[DEMO REPORTES] Dato ficticio sin valor clínico.',
                    ],
                );
            }
        }
    }

    private function ingressDate(int $index): CarbonImmutable
    {
        $times = ['07:00:00', '10:00:00', '14:00:00', '17:00:00', '20:00:00', '22:00:00'];
        $dayOffset = intdiv(($index - 1) * 74, self::URGENCY_TARGET - 1);

        return CarbonImmutable::parse('2026-09-01')
            ->addDays($dayOffset)
            ->setTimeFromTimeString($times[($index - 1) % count($times)]);
    }

    private function attentionDate(CarbonInterface $ingressDate, int $index): CarbonImmutable
    {
        return CarbonImmutable::instance($ingressDate)->addHours(1 + ($index % 8));
    }

    private function evolutionDate(CarbonInterface $attentionDate, int $attentionIndex, int $sequence): CarbonImmutable
    {
        return CarbonImmutable::instance($attentionDate)->addHours(2 + ($sequence * 4) + ($attentionIndex % 3));
    }
}
