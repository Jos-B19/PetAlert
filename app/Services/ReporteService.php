<?php

namespace App\Services;

use App\Models\Urgencia;
use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Database\Query\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class ReporteService
{
    private const ACTIVE_URGENCY_STATUSES = [
        'pendiente',
        'en_atencion',
        'en_observacion',
    ];

    /**
     * @param  array{periodo: string, desde: CarbonImmutable, hasta: CarbonImmutable}  $range
     * @return array<string, int>
     */
    public function resumenCentro(User $user, array $range): array
    {
        $admissions = $this->urgencyQuery($user, $range)
            ->selectRaw('COUNT(DISTINCT mascota_id) AS mascotas_ingresadas')
            ->selectRaw('COUNT(*) AS urgencias_registradas')
            ->first();
        $discharges = $this->urgencyQuery($user, $range, 'fecha_egreso')
            ->whereNotNull('fecha_egreso')
            ->count();

        $summary = [
            'mascotas_ingresadas' => (int) $admissions->mascotas_ingresadas,
            'urgencias_registradas' => (int) $admissions->urgencias_registradas,
            'egresos_registrados' => (int) $discharges,
        ];

        if (in_array($user->rol, [User::ROLE_ADMINISTRADOR, User::ROLE_VETERINARIO], true)) {
            $summary['medicamentos_administrados'] = (int) $this->medicationQuery($user, $range)
                ->count('am.id');
            $summary['resultados_clinicos_registrados'] = (int) $this->evolutionQuery($user, $range)
                ->whereNotNull('e.estado_mascota')
                ->whereRaw("TRIM(e.estado_mascota) <> ''")
                ->count('e.id');
        }

        return $summary;
    }

    /**
     * @param  array{periodo: string, desde: CarbonImmutable, hasta: CarbonImmutable}  $range
     * @return array<string, mixed>
     */
    public function ingresos(User $user, array $range): array
    {
        $today = CarbonImmutable::now();
        $todayWindow = $this->intersectRange($range, $today->startOfDay(), $today);
        $weekWindow = $this->intersectRange($range, $today->subDays(6)->startOfDay(), $today);
        $monthWindow = $this->intersectRange($range, $today->startOfMonth(), $today);

        $summary = $this->urgencyQuery($user, $range)
            ->selectRaw('COUNT(*) AS total_urgencias')
            ->selectRaw(
                'COALESCE(SUM(CASE WHEN fecha_ingreso BETWEEN ? AND ? THEN 1 ELSE 0 END), 0) AS ingresos_hoy',
                $todayWindow,
            )
            ->selectRaw(
                'COALESCE(SUM(CASE WHEN fecha_ingreso BETWEEN ? AND ? THEN 1 ELSE 0 END), 0) AS ingresos_ultimos_7_dias',
                $weekWindow,
            )
            ->selectRaw(
                'COALESCE(SUM(CASE WHEN fecha_ingreso BETWEEN ? AND ? THEN 1 ELSE 0 END), 0) AS ingresos_mes_actual',
                $monthWindow,
            )
            ->first();

        $species = $this->urgencyQuery($user, $range)
            ->join('mascotas', 'mascotas.id', '=', 'urgencias.mascota_id')
            ->select('mascotas.especie')
            ->selectRaw('COUNT(urgencias.id) AS total')
            ->groupBy('mascotas.especie')
            ->orderByDesc('total')
            ->orderBy('mascotas.especie')
            ->get()
            ->map(fn (object $row): array => [
                'especie' => $row->especie,
                'total' => (int) $row->total,
            ]);

        $frequentReasons = $this->urgencyQuery($user, $range)
            ->select('tipo_urgencia')
            ->selectRaw('COUNT(*) AS total')
            ->groupBy('tipo_urgencia')
            ->orderByDesc('total')
            ->orderBy('tipo_urgencia')
            ->limit(10)
            ->get()
            ->map(fn (object $row): array => [
                'motivo' => $row->tipo_urgencia,
                'total' => (int) $row->total,
            ]);

        return [
            'resumen' => [
                'total_urgencias' => (int) $summary->total_urgencias,
                'ingresos_hoy' => (int) $summary->ingresos_hoy,
                'ingresos_ultimos_7_dias' => (int) $summary->ingresos_ultimos_7_dias,
                'ingresos_mes_actual' => (int) $summary->ingresos_mes_actual,
            ],
            'ingresos_por_dia' => $this->urgencySeries($user, $range, 'fecha_ingreso', 'dia'),
            'ingresos_por_semana' => $this->urgencySeries($user, $range, 'fecha_ingreso', 'semana'),
            'ingresos_por_mes' => $this->urgencySeries($user, $range, 'fecha_ingreso', 'mes'),
            'distribucion_por_especie' => $species,
            'motivos_frecuentes' => $frequentReasons,
        ];
    }

    /**
     * @param  array{periodo: string, desde: CarbonImmutable, hasta: CarbonImmutable}  $range
     * @return array<string, mixed>
     */
    public function ocupacion(User $user, array $range, bool $paginate = true): array
    {
        $admissions = $this->urgencyQuery($user, $range);
        $withoutDischarge = (clone $admissions)->whereNull('fecha_egreso');
        $withoutDischargeCounts = (clone $withoutDischarge)
            ->selectRaw('COUNT(*) AS total')
            ->selectRaw(
                "COALESCE(SUM(CASE WHEN estado = 'finalizada' THEN 1 ELSE 0 END), 0) AS finalizadas",
            )
            ->selectRaw(
                "COALESCE(SUM(CASE WHEN estado IN ('pendiente', 'en_atencion', 'en_observacion') THEN 1 ELSE 0 END), 0) AS activas",
            )
            ->first();

        $discharges = $this->urgencyQuery($user, $range, 'fecha_egreso')
            ->whereNotNull('fecha_egreso');
        $dischargeSummary = (clone $discharges)
            ->selectRaw('COUNT(*) AS total_egresos')
            ->selectRaw('COUNT(DISTINCT mascota_id) AS mascotas_con_egreso')
            ->first();
        $durationQuery = (clone $discharges)
            ->whereRaw('fecha_egreso >= fecha_ingreso');
        $durationExpression = $this->durationInMinutesExpression();
        $durationSummary = (clone $durationQuery)
            ->selectRaw("AVG({$durationExpression}) AS promedio_minutos")
            ->selectRaw("MAX({$durationExpression}) AS maximo_minutos")
            ->first();

        $daysExpression = $this->durationInDaysExpression();
        $durationDistribution = (clone $durationQuery)
            ->selectRaw("{$daysExpression} AS dias_completos, COUNT(*) AS casos")
            ->groupByRaw($daysExpression)
            ->orderBy('dias_completos')
            ->get()
            ->map(fn (object $row): array => [
                'dias_completos' => (int) $row->dias_completos,
                'casos' => (int) $row->casos,
            ]);

        $registeredStaysQuery = (clone $durationQuery)
            ->select('id', 'mascota_id', 'estado', 'fecha_ingreso', 'fecha_egreso')
            ->selectRaw("{$durationExpression} AS permanencia_minutos")
            ->orderByDesc('fecha_egreso')
            ->orderByDesc('id');
        $registeredStays = $paginate
            ? $registeredStaysQuery->paginate(25, ['*'], 'estancias_page')->withQueryString()
            : $registeredStaysQuery->get();

        $casesWithoutDischargeQuery = (clone $withoutDischarge)
            ->select('id', 'mascota_id', 'estado', 'fecha_ingreso')
            ->orderByDesc('fecha_ingreso')
            ->orderByDesc('id');
        $casesWithoutDischarge = $paginate
            ? $casesWithoutDischargeQuery->paginate(25)->withQueryString()
            : $casesWithoutDischargeQuery->get();

        return [
            'resumen' => [
                'total_urgencias' => (int) (clone $admissions)->count(),
                'mascotas_con_urgencias_activas' => (int) (clone $admissions)
                    ->whereIn('estado', self::ACTIVE_URGENCY_STATUSES)
                    ->distinct()
                    ->count('mascota_id'),
                'egresos_registrados' => (int) $dischargeSummary->total_egresos,
                'mascotas_con_egreso_registrado' => (int) $dischargeSummary->mascotas_con_egreso,
                'casos_sin_egreso' => (int) $withoutDischargeCounts->total,
                'finalizadas_sin_egreso' => (int) $withoutDischargeCounts->finalizadas,
                'activas_sin_egreso' => (int) $withoutDischargeCounts->activas,
                'promedio_permanencia_minutos' => round((float) $durationSummary->promedio_minutos, 2),
                'maximo_permanencia_minutos' => round((float) $durationSummary->maximo_minutos, 2),
            ],
            'ingresos_y_egresos' => [
                'por_dia' => [
                    'ingresos' => $this->urgencySeries($user, $range, 'fecha_ingreso', 'dia'),
                    'egresos' => $this->urgencySeries($user, $range, 'fecha_egreso', 'dia'),
                ],
                'por_semana' => [
                    'ingresos' => $this->urgencySeries($user, $range, 'fecha_ingreso', 'semana'),
                    'egresos' => $this->urgencySeries($user, $range, 'fecha_egreso', 'semana'),
                ],
                'por_mes' => [
                    'ingresos' => $this->urgencySeries($user, $range, 'fecha_ingreso', 'mes'),
                    'egresos' => $this->urgencySeries($user, $range, 'fecha_egreso', 'mes'),
                ],
            ],
            'distribucion_permanencia_dias' => $durationDistribution,
            'estancias_registradas' => $registeredStays,
            'casos_sin_egreso' => $casesWithoutDischarge,
        ];
    }

    /**
     * @param  array{periodo: string, desde: CarbonImmutable, hasta: CarbonImmutable}  $range
     * @return array<string, mixed>
     */
    public function medicamentos(User $user, array $range, bool $paginate = true): array
    {
        $administrations = $this->medicationQuery($user, $range);

        $mostUsed = (clone $administrations)
            ->select('m.id', 'm.nombre', 'm.presentacion')
            ->selectRaw('COUNT(am.id) AS total_administraciones')
            ->groupBy('m.id', 'm.nombre', 'm.presentacion')
            ->orderByDesc('total_administraciones')
            ->orderBy('m.nombre')
            ->limit(10)
            ->get()
            ->map(fn (object $row): array => [
                'medicamento_id' => (int) $row->id,
                'medicamento' => $row->nombre,
                'presentacion' => $row->presentacion,
                'total_administraciones' => (int) $row->total_administraciones,
            ]);

        $byVeterinarian = (clone $administrations)
            ->select('v.id AS veterinario_id', 'v.name AS veterinario')
            ->selectRaw('COUNT(am.id) AS total_administraciones')
            ->groupBy('v.id', 'v.name')
            ->orderByDesc('total_administraciones')
            ->orderBy('v.name')
            ->get()
            ->map(fn (object $row): array => [
                'veterinario_id' => (int) $row->veterinario_id,
                'veterinario' => $row->veterinario,
                'total_administraciones' => (int) $row->total_administraciones,
            ]);

        $byRoute = (clone $administrations)
            ->select('am.via_administracion')
            ->selectRaw('COUNT(am.id) AS total_administraciones')
            ->groupBy('am.via_administracion')
            ->orderByDesc('total_administraciones')
            ->orderBy('am.via_administracion')
            ->get()
            ->map(fn (object $row): array => [
                'via_administracion' => $row->via_administracion,
                'total_administraciones' => (int) $row->total_administraciones,
            ]);

        $historyQuery = (clone $administrations)
            ->select([
                'am.id',
                'am.atencion_medicamento_id',
                'am.fecha_hora',
                'm.nombre AS medicamento',
                'm.presentacion',
                'am.dosis_administrada',
                'am.cantidad_administrada',
                'am.via_administracion',
                'a.id AS atencion_id',
                'v.id AS veterinario_id',
                'v.name AS veterinario',
            ])
            ->orderByDesc('am.fecha_hora')
            ->orderByDesc('am.id');
        $history = $paginate
            ? $historyQuery->paginate(25)->withQueryString()
            : $historyQuery->get();

        return [
            'resumen' => [
                'total_administraciones' => (int) (clone $administrations)->count('am.id'),
            ],
            'medicamentos_mas_utilizados' => $mostUsed,
            'administraciones_por_mes' => $this->querySeries($administrations, 'am.fecha_hora', 'mes'),
            'administraciones_por_veterinario' => $byVeterinarian,
            'distribucion_por_via' => $byRoute,
            'historial' => $history,
        ];
    }

    /**
     * @param  array{periodo: string, desde: CarbonImmutable, hasta: CarbonImmutable}  $range
     * @return array<string, mixed>
     */
    public function resultados(User $user, array $range): array
    {
        $outcomes = $this->evolutionQuery($user, $range)
            ->whereNotNull('e.estado_mascota')
            ->whereRaw("TRIM(e.estado_mascota) <> ''");
        $totalOutcomes = (int) (clone $outcomes)->count('e.id');
        $groupedOutcomes = (clone $outcomes)
            ->select('e.estado_mascota AS resultado')
            ->selectRaw('COUNT(e.id) AS evoluciones')
            ->selectRaw('COUNT(DISTINCT u.mascota_id) AS mascotas')
            ->groupBy('e.estado_mascota')
            ->orderBy('resultado')
            ->get();

        $outcomeMetrics = [
            'recuperaciones_favorables' => 0,
            'continuan_en_tratamiento' => 0,
            'requieren_seguimiento' => 0,
            'remisiones' => 0,
        ];
        $outcomeMetricNames = [
            'Recuperación favorable' => 'recuperaciones_favorables',
            'Continúa en tratamiento' => 'continuan_en_tratamiento',
            'Requiere seguimiento' => 'requieren_seguimiento',
            'Remisión' => 'remisiones',
        ];

        $outcomeDistribution = $groupedOutcomes->map(function (object $row) use (
            $totalOutcomes,
            &$outcomeMetrics,
            $outcomeMetricNames,
        ): array {
            if (isset($outcomeMetricNames[$row->resultado])) {
                $outcomeMetrics[$outcomeMetricNames[$row->resultado]] = (int) $row->mascotas;
            }

            return [
                'resultado' => $row->resultado,
                'evoluciones' => (int) $row->evoluciones,
                'mascotas' => (int) $row->mascotas,
                'porcentaje' => $totalOutcomes === 0
                    ? 0
                    : round(((int) $row->evoluciones * 100) / $totalOutcomes, 2),
            ];
        });

        $monthlyOutcomes = $this->evolutionQuery($user, $range)
            ->whereNotNull('e.estado_mascota')
            ->whereRaw("TRIM(e.estado_mascota) <> ''");
        $urgencyStates = $this->urgencyQuery($user, $range)
            ->select('estado')
            ->selectRaw('COUNT(*) AS total')
            ->groupBy('estado')
            ->orderBy('estado')
            ->get()
            ->map(fn (object $row): array => [
                'estado' => $row->estado,
                'total' => (int) $row->total,
            ]);

        return [
            'resumen' => [
                'resultados_clinicos_registrados' => $totalOutcomes,
                ...$outcomeMetrics,
            ],
            'resultados_registrados' => $outcomeDistribution,
            'distribucion_por_mes' => $this->querySeries($monthlyOutcomes, 'e.fecha_evolucion', 'mes'),
            'urgencias_por_estado' => $urgencyStates,
        ];
    }

    /**
     * @param  array{periodo: string, desde: CarbonImmutable, hasta: CarbonImmutable}  $range
     */
    private function urgencyQuery(User $user, array $range, string $dateColumn = 'fecha_ingreso'): Builder
    {
        $query = Urgencia::query()
            ->whereBetween($dateColumn, [$range['desde'], $range['hasta']]);

        if ($user->rol === User::ROLE_VETERINARIO) {
            $query->where('veterinario_id', $user->id);
        }

        return $query->toBase();
    }

    /**
     * @param  array{periodo: string, desde: CarbonImmutable, hasta: CarbonImmutable}  $range
     */
    private function urgencySeries(User $user, array $range, string $dateColumn, string $period): Collection
    {
        $bucket = $this->dateBucketExpression($dateColumn, $period);
        $query = $this->urgencyQuery($user, $range, $dateColumn);

        if ($dateColumn === 'fecha_egreso') {
            $query->whereNotNull('fecha_egreso');
        }

        return $query
            ->selectRaw("{$bucket} AS periodo, COUNT(*) AS total")
            ->groupByRaw($bucket)
            ->orderBy('periodo')
            ->get()
            ->map(fn (object $row): array => [
                'periodo' => $row->periodo,
                'total' => (int) $row->total,
            ])
            ->values();
    }

    /**
     * @param  array{periodo: string, desde: CarbonImmutable, hasta: CarbonImmutable}  $range
     */
    private function medicationQuery(User $user, array $range): Builder
    {
        $query = DB::table('administraciones_medicamentos AS am')
            ->join('atencion_medicamentos AS atm', 'atm.id', '=', 'am.atencion_medicamento_id')
            ->join('medicamentos AS m', 'm.id', '=', 'atm.medicamento_id')
            ->join('atenciones AS a', 'a.id', '=', 'atm.atencion_id')
            ->join('users AS v', 'v.id', '=', 'am.veterinario_id')
            ->whereBetween('am.fecha_hora', [$range['desde'], $range['hasta']]);

        if ($user->rol === User::ROLE_VETERINARIO) {
            $query->where('a.veterinario_id', $user->id);
        }

        return $query;
    }

    /**
     * @param  array{periodo: string, desde: CarbonImmutable, hasta: CarbonImmutable}  $range
     */
    private function evolutionQuery(User $user, array $range): Builder
    {
        $query = DB::table('evoluciones_clinicas AS e')
            ->join('atenciones AS a', 'a.id', '=', 'e.atencion_id')
            ->join('urgencias AS u', 'u.id', '=', 'a.urgencia_id')
            ->whereBetween('e.fecha_evolucion', [$range['desde'], $range['hasta']]);

        if ($user->rol === User::ROLE_VETERINARIO) {
            $query->where('a.veterinario_id', $user->id);
        }

        return $query;
    }

    private function querySeries(Builder $query, string $dateColumn, string $period): Collection
    {
        $bucket = $this->dateBucketExpression($dateColumn, $period);

        return (clone $query)
            ->selectRaw("{$bucket} AS periodo, COUNT(*) AS total")
            ->groupByRaw($bucket)
            ->orderBy('periodo')
            ->get()
            ->map(fn (object $row): array => [
                'periodo' => $row->periodo,
                'total' => (int) $row->total,
            ])
            ->values();
    }

    private function dateBucketExpression(string $column, string $period): string
    {
        $driver = DB::connection()->getDriverName();

        return match ($period) {
            'dia' => $driver === 'sqlite'
                ? "strftime('%Y-%m-%d', {$column})"
                : "DATE({$column})",
            'semana' => $driver === 'sqlite'
                ? "strftime('%Y-W%W', {$column})"
                : "DATE_FORMAT({$column}, '%x-W%v')",
            default => $driver === 'sqlite'
                ? "strftime('%Y-%m', {$column})"
                : "DATE_FORMAT({$column}, '%Y-%m')",
        };
    }

    private function durationInMinutesExpression(): string
    {
        return DB::connection()->getDriverName() === 'sqlite'
            ? '(julianday(fecha_egreso) - julianday(fecha_ingreso)) * 1440'
            : 'TIMESTAMPDIFF(SECOND, fecha_ingreso, fecha_egreso) / 60.0';
    }

    private function durationInDaysExpression(): string
    {
        return DB::connection()->getDriverName() === 'sqlite'
            ? 'CAST(julianday(fecha_egreso) - julianday(fecha_ingreso) AS INTEGER)'
            : 'FLOOR(TIMESTAMPDIFF(SECOND, fecha_ingreso, fecha_egreso) / 86400)';
    }

    /**
     * @param  array{periodo: string, desde: CarbonImmutable, hasta: CarbonImmutable}  $range
     * @return array{0: CarbonImmutable, 1: CarbonImmutable}
     */
    private function intersectRange(
        array $range,
        CarbonImmutable $periodStart,
        CarbonImmutable $periodEnd,
    ): array {
        $start = $range['desde']->greaterThan($periodStart) ? $range['desde'] : $periodStart;
        $end = $range['hasta']->lessThan($periodEnd) ? $range['hasta'] : $periodEnd;

        return [$start, $end];
    }
}
