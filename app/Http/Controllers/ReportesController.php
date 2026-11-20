<?php

namespace App\Http\Controllers;

use App\Http\Requests\Reportes\ReporteFiltroRequest;
use App\Models\User;
use App\Services\ReporteService;
use Carbon\CarbonImmutable;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response as InertiaResponse;

class ReportesController extends Controller
{
    private const REPORTS = [
        'ingresos' => 'Ingresos de mascotas',
        'ocupacion' => 'Ocupación y permanencia',
        'medicamentos' => 'Medicamentos y tratamientos',
        'resultados' => 'Resultados clínicos',
    ];

    public function __construct(private ReporteService $reporteService) {}

    public function index(Request $request): JsonResponse|InertiaResponse
    {
        $reportNames = match ($request->user()->rol) {
            User::ROLE_ADMINISTRADOR, User::ROLE_VETERINARIO => array_keys(self::REPORTS),
            User::ROLE_RECEPCION => ['ingresos', 'ocupacion'],
            default => [],
        };

        $reports = [];

        foreach ($reportNames as $reportName) {
            $reports[$reportName] = [
                'nombre' => self::REPORTS[$reportName],
                'url' => route('reports.'.$reportName),
            ];
        }

        if ($request->expectsJson()) {
            return response()->json([
                'reportes' => $reports,
                'periodos_disponibles' => ReporteFiltroRequest::PERIODS,
            ]);
        }

        $now = CarbonImmutable::now();

        return Inertia::render('Reportes/Index', [
            'screen' => 'reports.index',
            'reportes' => $reports,
            'estadisticas' => $this->reporteService->resumenCentro($request->user(), [
                'periodo' => 'mes_actual',
                'desde' => $now->startOfMonth(),
                'hasta' => $now,
            ]),
            'fecha_consulta' => $now->format('Y-m-d H:i:s'),
        ]);
    }

    public function ingresos(ReporteFiltroRequest $request): JsonResponse|InertiaResponse
    {
        return $this->reportResponse(
            $request,
            $this->reporteService->ingresos($request->user(), $request->dateRange()),
            'Reportes/Ingresos',
            'reports.ingresos',
        );
    }

    public function ocupacion(ReporteFiltroRequest $request): JsonResponse|InertiaResponse
    {
        return $this->reportResponse(
            $request,
            $this->reporteService->ocupacion($request->user(), $request->dateRange()),
            'Reportes/Ocupacion',
            'reports.ocupacion',
        );
    }

    public function medicamentos(ReporteFiltroRequest $request): JsonResponse|InertiaResponse
    {
        return $this->reportResponse(
            $request,
            $this->reporteService->medicamentos($request->user(), $request->dateRange()),
            'Reportes/Medicamentos',
            'reports.medicamentos',
        );
    }

    public function resultados(ReporteFiltroRequest $request): JsonResponse|InertiaResponse
    {
        return $this->reportResponse(
            $request,
            $this->reporteService->resultados($request->user(), $request->dateRange()),
            'Reportes/Resultados',
            'reports.resultados',
        );
    }

    /**
     * @param  array<string, mixed>  $data
     */
    private function reportResponse(
        ReporteFiltroRequest $request,
        array $data,
        string $component,
        string $screen,
    ): JsonResponse|InertiaResponse {
        $props = [
            'screen' => $screen,
            'filtros' => $request->filterSummary(),
            'datos' => $data,
        ];

        if ($request->expectsJson()) {
            return response()->json([
                'filtros' => $props['filtros'],
                'datos' => $props['datos'],
            ]);
        }

        return Inertia::render($component, $props);
    }
}
