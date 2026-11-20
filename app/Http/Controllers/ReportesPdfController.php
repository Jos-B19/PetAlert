<?php

namespace App\Http\Controllers;

use App\Http\Requests\Reportes\ReporteFiltroRequest;
use App\Services\ReporteService;
use Barryvdh\DomPDF\Facade\Pdf;
use Carbon\CarbonImmutable;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

class ReportesPdfController extends Controller
{
    private const PERIOD_LABELS = [
        'hoy' => 'Hoy',
        'ultimos_7_dias' => 'Últimos 7 días',
        'ultimos_30_dias' => 'Últimos 30 días',
        'mes_actual' => 'Mes actual',
        'anio_actual' => 'Año actual',
        'personalizado' => 'Período personalizado',
    ];

    public function __construct(private ReporteService $reporteService) {}

    public function index(Request $request): Response
    {
        $now = CarbonImmutable::now();
        $range = [
            'periodo' => 'mes_actual',
            'desde' => $now->startOfMonth(),
            'hasta' => $now,
        ];

        return $this->download(
            'reportes.index',
            'Centro de Reportes',
            $this->reporteService->resumenCentro($request->user(), $range),
            [
                'periodo' => 'mes_actual',
                'fecha_inicio' => $range['desde']->toDateString(),
                'fecha_fin' => $range['hasta']->toDateString(),
            ],
            'centro-reportes.pdf',
        );
    }

    public function ingresos(ReporteFiltroRequest $request): Response
    {
        $range = $request->dateRange();

        return $this->download(
            'reportes.ingresos',
            'Ingresos de mascotas',
            $this->reporteService->ingresos($request->user(), $range),
            $request->filterSummary(),
            'reporte-ingresos.pdf',
        );
    }

    public function ocupacion(ReporteFiltroRequest $request): Response
    {
        $range = $request->dateRange();

        return $this->download(
            'reportes.ocupacion',
            'Ocupación y permanencia',
            $this->reporteService->ocupacion($request->user(), $range, false),
            $request->filterSummary(),
            'reporte-ocupacion.pdf',
        );
    }

    public function medicamentos(ReporteFiltroRequest $request): Response
    {
        $range = $request->dateRange();

        return $this->download(
            'reportes.medicamentos',
            'Medicamentos y tratamientos',
            $this->reporteService->medicamentos($request->user(), $range, false),
            $request->filterSummary(),
            'reporte-medicamentos.pdf',
        );
    }

    public function resultados(ReporteFiltroRequest $request): Response
    {
        $range = $request->dateRange();

        return $this->download(
            'reportes.resultados',
            'Resultados clínicos',
            $this->reporteService->resultados($request->user(), $range),
            $request->filterSummary(),
            'reporte-resultados.pdf',
        );
    }

    /**
     * @param  array<string, mixed>  $data
     * @param  array{periodo: string, fecha_inicio: string, fecha_fin: string}  $filters
     */
    private function download(
        string $view,
        string $title,
        array $data,
        array $filters,
        string $filename,
    ): Response {
        return Pdf::loadView($view, [
            'titulo' => $title,
            'datos' => $data,
            'filtros' => $filters,
            'periodoNombre' => self::PERIOD_LABELS[$filters['periodo']] ?? $filters['periodo'],
            'generadoEn' => CarbonImmutable::now(),
        ])
            ->setPaper('a4')
            ->download($filename);
    }
}
