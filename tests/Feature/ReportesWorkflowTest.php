<?php

namespace Tests\Feature;

use App\Models\AdministracionMedicamento;
use App\Models\Atencion;
use App\Models\AtencionMedicamento;
use App\Models\EvolucionClinica;
use App\Models\Mascota;
use App\Models\Medicamento;
use App\Models\Propietario;
use App\Models\Urgencia;
use App\Models\User;
use App\Services\ReporteService;
use Barryvdh\DomPDF\Facade\Pdf;
use Carbon\CarbonImmutable;
use Closure;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Mockery;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class ReportesWorkflowTest extends TestCase
{
    use RefreshDatabase;

    private int $ownerNumber = 0;

    public function test_unauthenticated_user_is_redirected_from_the_report_center(): void
    {
        $this->get(route('reports.index'))
            ->assertRedirect(route('login', ['redirect' => '/reports']));
    }

    public function test_administrator_receives_all_report_endpoints(): void
    {
        $administrator = $this->createUser(User::ROLE_ADMINISTRADOR);

        $this->actingAs($administrator)
            ->getJson(route('reports.index'))
            ->assertOk()
            ->assertJsonPath('reportes.ingresos.url', route('reports.ingresos'))
            ->assertJsonPath('reportes.ocupacion.url', route('reports.ocupacion'))
            ->assertJsonPath('reportes.medicamentos.url', route('reports.medicamentos'))
            ->assertJsonPath('reportes.resultados.url', route('reports.resultados'))
            ->assertJsonPath('periodos_disponibles', [
                'hoy',
                'ultimos_7_dias',
                'ultimos_30_dias',
                'mes_actual',
                'anio_actual',
                'personalizado',
            ]);
    }

    public function test_administrator_receives_report_center_as_an_inertia_page_with_server_statistics(): void
    {
        $this->travelTo('2026-11-19 12:00:00');
        $administrator = $this->createUser(User::ROLE_ADMINISTRADOR);

        $this->actingAs($administrator)
            ->get(route('reports.index'))
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->component('Reportes/Index')
                ->where('screen', 'reports.index')
                ->where('estadisticas.mascotas_ingresadas', 0)
                ->where('estadisticas.urgencias_registradas', 0)
                ->where('estadisticas.medicamentos_administrados', 0)
                ->where('estadisticas.egresos_registrados', 0));
    }

    public function test_reception_report_center_omits_clinical_statistics(): void
    {
        $receptionist = $this->createUser(User::ROLE_RECEPCION);

        $this->actingAs($receptionist)
            ->get(route('reports.index'))
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->component('Reportes/Index')
                ->has('reportes', 2)
                ->missing('estadisticas.medicamentos_administrados')
                ->missing('estadisticas.resultados_clinicos_registrados'));
    }

    /**
     * @return array<string, array{string, string}>
     */
    public static function inertiaReportPages(): array
    {
        return [
            'admissions' => ['reports.ingresos', 'Reportes/Ingresos'],
            'occupancy' => ['reports.ocupacion', 'Reportes/Ocupacion'],
            'medications' => ['reports.medicamentos', 'Reportes/Medicamentos'],
            'outcomes' => ['reports.resultados', 'Reportes/Resultados'],
        ];
    }

    #[DataProvider('inertiaReportPages')]
    public function test_report_routes_render_their_inertia_page_with_server_data(
        string $routeName,
        string $component,
    ): void {
        $administrator = $this->createUser(User::ROLE_ADMINISTRADOR);

        $this->actingAs($administrator)
            ->get(route($routeName))
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->component($component)
                ->has('filtros')
                ->has('datos'));
    }

    public function test_reception_can_access_operational_reports_but_not_clinical_reports(): void
    {
        $receptionist = $this->createUser(User::ROLE_RECEPCION);

        $this->actingAs($receptionist)
            ->getJson(route('reports.ingresos'))
            ->assertOk();

        $this->getJson(route('reports.ocupacion'))->assertOk();
        $this->getJson(route('reports.medicamentos'))->assertForbidden();
        $this->getJson(route('reports.resultados'))->assertForbidden();
    }

    /**
     * @return array<string, array{string, string}>
     */
    public static function pdfReportRoutes(): array
    {
        return [
            'report center' => ['reports.pdf', 'centro-reportes.pdf'],
            'admissions' => ['reports.ingresos.pdf', 'reporte-ingresos.pdf'],
            'occupancy' => ['reports.ocupacion.pdf', 'reporte-ocupacion.pdf'],
            'medications' => ['reports.medicamentos.pdf', 'reporte-medicamentos.pdf'],
            'outcomes' => ['reports.resultados.pdf', 'reporte-resultados.pdf'],
        ];
    }

    #[DataProvider('pdfReportRoutes')]
    public function test_administrator_downloads_each_report_as_a_valid_pdf(
        string $routeName,
        string $filename,
    ): void {
        $this->travelTo('2026-11-19 12:00:00');
        $administrator = $this->createUser(User::ROLE_ADMINISTRADOR);

        $response = $this->actingAs($administrator)
            ->get(route($routeName));

        $response->assertOk()
            ->assertHeader('content-type', 'application/pdf')
            ->assertDownload($filename);
        $this->assertStringStartsWith('%PDF-', $response->getContent());
    }

    public function test_unauthenticated_user_is_redirected_from_report_pdf(): void
    {
        $this->get(route('reports.pdf'))
            ->assertRedirect(route('login', ['redirect' => '/reports/pdf']));
    }

    public function test_veterinarian_can_download_all_reports_with_existing_scope(): void
    {
        $veterinarian = $this->createUser(User::ROLE_VETERINARIO);

        foreach (self::pdfReportRoutes() as [$routeName, $filename]) {
            $response = $this->actingAs($veterinarian)->get(route($routeName));

            $response->assertOk()
                ->assertHeader('content-type', 'application/pdf')
                ->assertDownload($filename);
        }
    }

    public function test_reception_can_download_only_operational_report_pdfs(): void
    {
        $receptionist = $this->createUser(User::ROLE_RECEPCION);

        foreach ([
            ['reports.pdf', 'centro-reportes.pdf'],
            ['reports.ingresos.pdf', 'reporte-ingresos.pdf'],
            ['reports.ocupacion.pdf', 'reporte-ocupacion.pdf'],
        ] as [$routeName, $filename]) {
            $this->actingAs($receptionist)
                ->get(route($routeName))
                ->assertOk()
                ->assertDownload($filename);
        }

        $this->get(route('reports.medicamentos.pdf'))->assertForbidden();
        $this->get(route('reports.resultados.pdf'))->assertForbidden();
    }

    /**
     * @return array<string, array{string}>
     */
    public static function administrativePdfRoutes(): array
    {
        return [
            'report center' => ['reports.pdf'],
            'admissions' => ['reports.ingresos.pdf'],
            'occupancy' => ['reports.ocupacion.pdf'],
            'medications' => ['reports.medicamentos.pdf'],
            'outcomes' => ['reports.resultados.pdf'],
        ];
    }

    #[DataProvider('administrativePdfRoutes')]
    public function test_owner_receives_403_for_each_report_pdf(string $routeName): void
    {
        $owner = $this->createUser(User::ROLE_PROPIETARIO);

        $this->actingAs($owner)
            ->get(route($routeName))
            ->assertForbidden();
    }

    /**
     * @return array<string, array{string}>
     */
    public static function supportedPdfPeriods(): array
    {
        return [
            'today' => ['hoy'],
            'last seven days' => ['ultimos_7_dias'],
            'last thirty days' => ['ultimos_30_dias'],
            'current month' => ['mes_actual'],
            'current year' => ['anio_actual'],
            'custom period' => ['personalizado'],
        ];
    }

    #[DataProvider('supportedPdfPeriods')]
    public function test_report_pdf_accepts_each_supported_period(string $period): void
    {
        $this->travelTo('2026-11-19 12:00:00');
        $administrator = $this->createUser(User::ROLE_ADMINISTRADOR);
        $query = $period === 'personalizado'
            ? '?periodo=personalizado&fecha_inicio=2026-11-01&fecha_fin=2026-11-19'
            : '?periodo='.$period;

        $this->actingAs($administrator)
            ->get(route('reports.ingresos.pdf').$query)
            ->assertOk()
            ->assertDownload('reporte-ingresos.pdf');
    }

    public function test_report_pdf_rejects_invalid_period_parameters(): void
    {
        $administrator = $this->createUser(User::ROLE_ADMINISTRADOR);

        $this->actingAs($administrator)
            ->getJson(route('reports.ingresos.pdf').'?periodo=periodo-invalido')
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['periodo'])
            ->assertJsonPath('errors.periodo.0', 'El período seleccionado no es válido.');
    }

    public function test_admissions_pdf_receives_the_selected_range_and_filtered_service_data(): void
    {
        $this->travelTo('2026-11-19 12:00:00');
        $administrator = $this->createUser(User::ROLE_ADMINISTRADOR);
        $veterinarian = $this->createUser(User::ROLE_VETERINARIO);
        $this->createUrgency(
            $administrator,
            $veterinarian,
            'Nala',
            'Canino',
            '2026-11-16 08:15:00',
            'pendiente',
            null,
            'Herida en pata',
        );
        $this->createUrgency(
            $administrator,
            $veterinarian,
            'Milo',
            'Felino',
            '2026-10-25 11:00:00',
            'finalizada',
        );
        $this->expectPdfDownload(
            'reportes.ingresos',
            'reporte-ingresos.pdf',
            function (array $payload): void {
                $this->assertSame('2026-11-16', $payload['filtros']['fecha_inicio']);
                $this->assertSame('2026-11-19', $payload['filtros']['fecha_fin']);
                $this->assertSame(1, $payload['datos']['resumen']['total_urgencias']);
                $this->assertSame('Herida en pata', $payload['datos']['motivos_frecuentes'][0]['motivo']);
            },
        );

        $this->actingAs($administrator)
            ->get(route('reports.ingresos.pdf').'?periodo=personalizado&fecha_inicio=2026-11-16&fecha_fin=2026-11-19')
            ->assertOk()
            ->assertDownload('reporte-ingresos.pdf');
    }

    public function test_reception_pdf_center_does_not_expose_clinical_statistics_even_with_role_parameter(): void
    {
        $administrator = $this->createUser(User::ROLE_ADMINISTRADOR);
        $veterinarian = $this->createUser(User::ROLE_VETERINARIO);
        $receptionist = $this->createUser(User::ROLE_RECEPCION);
        $urgency = $this->createUrgency(
            $administrator,
            $veterinarian,
            'Nala',
            'Canino',
            '2026-11-10 08:00:00',
            'en_atencion',
        );
        $attention = $this->createAttention($urgency, $veterinarian, '2026-11-10 08:30:00');
        $medication = Medicamento::create(['nombre' => 'Medicamento restringido', 'presentacion' => 'Tableta']);
        $this->createAdministration($attention, $veterinarian, $medication, '2026-11-10 09:00:00', 'Oral');
        $this->createEvolution($attention, $veterinarian, '2026-11-10 09:30:00', 'Requiere seguimiento');
        $this->expectPdfDownload(
            'reportes.index',
            'centro-reportes.pdf',
            function (array $payload): void {
                $this->assertArrayNotHasKey('medicamentos_administrados', $payload['datos']);
                $this->assertArrayNotHasKey('resultados_clinicos_registrados', $payload['datos']);
            },
        );

        $this->actingAs($receptionist)
            ->get(route('reports.pdf').'?rol=administrador')
            ->assertOk()
            ->assertDownload('centro-reportes.pdf');
    }

    public function test_veterinarian_pdf_contains_only_records_with_existing_assignment_scope(): void
    {
        $registrar = $this->createUser(User::ROLE_ADMINISTRADOR);
        $veterinarian = $this->createUser(User::ROLE_VETERINARIO);
        $otherVeterinarian = $this->createUser(User::ROLE_VETERINARIO);
        $this->createUrgency($registrar, $veterinarian, 'Nala', 'Canino', '2026-11-10 08:00:00', 'en_atencion');
        $this->createUrgency($registrar, $otherVeterinarian, 'Milo', 'Felino', '2026-11-10 09:00:00', 'en_atencion');
        $this->expectPdfDownload(
            'reportes.ingresos',
            'reporte-ingresos.pdf',
            function (array $payload): void {
                $this->assertSame(1, $payload['datos']['resumen']['total_urgencias']);
                $this->assertSame(1, $payload['datos']['distribucion_por_especie'][0]['total']);
            },
        );

        $this->actingAs($veterinarian)
            ->get(route('reports.ingresos.pdf').'?periodo=personalizado&fecha_inicio=2026-11-01&fecha_fin=2026-11-30')
            ->assertOk()
            ->assertDownload('reporte-ingresos.pdf');
    }

    public function test_occupancy_and_medication_pdf_exports_include_all_filtered_rows(): void
    {
        $this->travelTo('2026-11-19 12:00:00');
        $administrator = $this->createUser(User::ROLE_ADMINISTRADOR);
        $veterinarian = $this->createUser(User::ROLE_VETERINARIO);
        $medication = Medicamento::create(['nombre' => 'Medicamento de prueba', 'presentacion' => 'Tableta']);

        for ($index = 1; $index <= 26; $index++) {
            $urgency = $this->createUrgency(
                $administrator,
                $veterinarian,
                'Mascota '.$index,
                'Canino',
                '2026-11-01 08:00:00',
                'finalizada',
                '2026-11-02 08:00:00',
            );
            $attention = $this->createAttention($urgency, $veterinarian, '2026-11-01 09:00:00');
            $this->createAdministration($attention, $veterinarian, $medication, '2026-11-02 09:00:00', 'Oral');
        }

        $this->expectPdfDownload(
            'reportes.ocupacion',
            'reporte-ocupacion.pdf',
            function (array $payload): void {
                $this->assertCount(26, $payload['datos']['estancias_registradas']);
            },
        );
        $this->expectPdfDownload(
            'reportes.medicamentos',
            'reporte-medicamentos.pdf',
            function (array $payload): void {
                $this->assertCount(26, $payload['datos']['historial']);
            },
        );

        $this->actingAs($administrator)
            ->get(route('reports.ocupacion.pdf').'?periodo=personalizado&fecha_inicio=2026-11-01&fecha_fin=2026-11-30')
            ->assertOk()
            ->assertDownload('reporte-ocupacion.pdf');

        $this->get(route('reports.medicamentos.pdf').'?periodo=personalizado&fecha_inicio=2026-11-01&fecha_fin=2026-11-30')
            ->assertOk()
            ->assertDownload('reporte-medicamentos.pdf');
    }

    public function test_report_pdf_template_escapes_dynamic_report_values(): void
    {
        $administrator = $this->createUser(User::ROLE_ADMINISTRADOR);
        $veterinarian = $this->createUser(User::ROLE_VETERINARIO);
        $this->createUrgency(
            $administrator,
            $veterinarian,
            'Nala',
            'Canino',
            '2026-11-10 08:00:00',
            'pendiente',
            null,
            '<script>alert(1)</script>',
        );
        $filters = [
            'periodo' => 'personalizado',
            'fecha_inicio' => '2026-11-01',
            'fecha_fin' => '2026-11-30',
        ];
        $datos = app(ReporteService::class)->ingresos(
            $administrator,
            [
                'periodo' => $filters['periodo'],
                'desde' => CarbonImmutable::parse($filters['fecha_inicio'])->startOfDay(),
                'hasta' => CarbonImmutable::parse($filters['fecha_fin'])->endOfDay(),
            ],
        );

        $html = view('reportes.ingresos', [
            'datos' => $datos,
            'filtros' => $filters,
            'periodoNombre' => 'Período personalizado',
            'generadoEn' => CarbonImmutable::parse('2026-11-19 12:00:00'),
            'titulo' => 'Ingresos de mascotas',
        ])->render();

        $this->assertStringContainsString('&lt;script&gt;', $html);
        $this->assertStringNotContainsString('<script>alert(1)</script>', $html);
    }

    /**
     * @return array<string, array{string}>
     */
    public static function generalReportRoutes(): array
    {
        return [
            'report center' => ['reports.index'],
            'admissions' => ['reports.ingresos'],
            'occupancy' => ['reports.ocupacion'],
            'medications' => ['reports.medicamentos'],
            'clinical outcomes' => ['reports.resultados'],
        ];
    }

    #[DataProvider('generalReportRoutes')]
    public function test_owner_receives_403_for_general_reports(string $routeName): void
    {
        $owner = $this->createUser(User::ROLE_PROPIETARIO);

        $this->actingAs($owner)
            ->getJson(route($routeName))
            ->assertForbidden();
    }

    /**
     * @return array<string, array{array<string, string>, string, string}>
     */
    public static function invalidDateRanges(): array
    {
        return [
            'nonexistent start date' => [
                ['periodo' => 'personalizado', 'fecha_inicio' => '2026-02-30', 'fecha_fin' => '2026-03-01'],
                'fecha_inicio',
                'La fecha inicial debe ser una fecha válida con formato AAAA-MM-DD.',
            ],
            'end date before start date' => [
                ['periodo' => 'personalizado', 'fecha_inicio' => '2026-03-02', 'fecha_fin' => '2026-03-01'],
                'fecha_fin',
                'La fecha final debe ser igual o posterior a la fecha inicial.',
            ],
            'missing end date' => [
                ['periodo' => 'personalizado', 'fecha_inicio' => '2026-03-01'],
                'fecha_fin',
                'Indica la fecha final del período personalizado.',
            ],
            'dates conflict with selected period' => [
                ['periodo' => 'mes_actual', 'fecha_inicio' => '2026-03-01', 'fecha_fin' => '2026-03-02'],
                'periodo',
                'Usa el período personalizado cuando envíes una fecha inicial y final.',
            ],
        ];
    }

    #[DataProvider('invalidDateRanges')]
    public function test_invalid_date_filters_return_their_validation_message(
        array $filters,
        string $field,
        string $message,
    ): void {
        $administrator = $this->createUser(User::ROLE_ADMINISTRADOR);
        $url = route('reports.ingresos').'?'.http_build_query($filters);

        $this->actingAs($administrator)
            ->getJson($url)
            ->assertUnprocessable()
            ->assertJsonValidationErrors([$field])
            ->assertJsonPath("errors.{$field}.0", $message);
    }

    public function test_admissions_report_filters_and_aggregates_by_day_week_month_species_and_reason(): void
    {
        $this->travelTo('2026-11-19 12:00:00');
        $administrator = $this->createUser(User::ROLE_ADMINISTRADOR);
        $veterinarian = $this->createUser(User::ROLE_VETERINARIO);
        $this->createUrgency($administrator, $veterinarian, 'Nala', 'Canino', '2026-11-16 08:15:00', 'pendiente', null, 'Herida');
        $this->createUrgency($administrator, $veterinarian, 'Milo', 'Felino', '2026-11-19 09:30:00', 'en_atencion', null, 'Intoxicación');
        $this->createUrgency($administrator, $veterinarian, 'Luna', 'Canino', '2026-10-25 11:00:00', 'finalizada');
        $url = route('reports.ingresos').'?'.http_build_query([
            'periodo' => 'personalizado',
            'fecha_inicio' => '2026-11-16',
            'fecha_fin' => '2026-11-19',
        ]);

        $response = $this->actingAs($administrator)->getJson($url)->assertOk();

        $response->assertJsonPath('filtros.fecha_inicio', '2026-11-16')
            ->assertJsonPath('filtros.fecha_fin', '2026-11-19')
            ->assertJsonPath('datos.resumen.total_urgencias', 2)
            ->assertJsonPath('datos.resumen.ingresos_hoy', 1)
            ->assertJsonPath('datos.resumen.ingresos_ultimos_7_dias', 2)
            ->assertJsonPath('datos.resumen.ingresos_mes_actual', 2)
            ->assertJsonPath('datos.ingresos_por_dia.0.periodo', '2026-11-16')
            ->assertJsonPath('datos.ingresos_por_dia.1.periodo', '2026-11-19')
            ->assertJsonPath('datos.ingresos_por_semana.0.total', 2)
            ->assertJsonPath('datos.ingresos_por_mes.0.total', 2)
            ->assertJsonPath('datos.distribucion_por_especie.0.total', 1)
            ->assertJsonPath('datos.motivos_frecuentes.0.total', 1);
    }

    public function test_occupancy_report_separates_active_cases_from_discharge_and_calculates_stay(): void
    {
        $this->travelTo('2026-11-19 12:00:00');
        $administrator = $this->createUser(User::ROLE_ADMINISTRADOR);
        $veterinarian = $this->createUser(User::ROLE_VETERINARIO);
        $this->createUrgency(
            $administrator,
            $veterinarian,
            'Nala',
            'Canino',
            '2026-11-01 08:00:00',
            'finalizada',
            '2026-11-02 08:00:00',
        );
        $this->createUrgency($administrator, $veterinarian, 'Milo', 'Felino', '2026-11-10 10:00:00', 'en_atencion');
        $this->createUrgency($administrator, $veterinarian, 'Luna', 'Canino', '2026-11-15 17:00:00', 'finalizada');
        $url = route('reports.ocupacion').'?'.http_build_query([
            'periodo' => 'personalizado',
            'fecha_inicio' => '2026-11-01',
            'fecha_fin' => '2026-11-30',
        ]);

        $response = $this->actingAs($administrator)->getJson($url)->assertOk();

        $response->assertJsonPath('datos.resumen.total_urgencias', 3)
            ->assertJsonPath('datos.resumen.mascotas_con_urgencias_activas', 1)
            ->assertJsonPath('datos.resumen.egresos_registrados', 1)
            ->assertJsonPath('datos.resumen.mascotas_con_egreso_registrado', 1)
            ->assertJsonPath('datos.resumen.casos_sin_egreso', 2)
            ->assertJsonPath('datos.resumen.activas_sin_egreso', 1)
            ->assertJsonPath('datos.resumen.finalizadas_sin_egreso', 1)
            ->assertJsonPath('datos.resumen.promedio_permanencia_minutos', 1440)
            ->assertJsonPath('datos.resumen.maximo_permanencia_minutos', 1440)
            ->assertJsonPath('datos.distribucion_permanencia_dias.0.dias_completos', 1)
            ->assertJsonPath('datos.distribucion_permanencia_dias.0.casos', 1)
            ->assertJsonPath('datos.estancias_registradas.total', 1)
            ->assertJsonPath('datos.estancias_registradas.data.0.estado', 'finalizada')
            ->assertJsonPath('datos.estancias_registradas.data.0.fecha_egreso', '2026-11-02 08:00:00')
            ->assertJsonPath('datos.casos_sin_egreso.total', 2)
            ->assertJsonPath('datos.ingresos_y_egresos.por_mes.egresos.0.total', 1);
    }

    public function test_medication_report_counts_actual_administrations_not_prescriptions(): void
    {
        $administrator = $this->createUser(User::ROLE_ADMINISTRADOR);
        $veterinarian = $this->createUser(User::ROLE_VETERINARIO);
        $urgency = $this->createUrgency(
            $administrator,
            $veterinarian,
            'Nala',
            'Canino',
            '2026-11-10 08:00:00',
            'en_atencion',
        );
        $attention = $this->createAttention($urgency, $veterinarian, '2026-11-10 08:30:00');
        $paracetamol = Medicamento::create(['nombre' => 'Paracetamol Demo', 'presentacion' => 'Tableta']);
        $amoxicilina = Medicamento::create(['nombre' => 'Amoxicilina Demo', 'presentacion' => 'Suspensión']);
        $this->createAdministration($attention, $veterinarian, $paracetamol, '2026-11-10 09:15:00', 'Oral');
        $this->createIndication($attention, $veterinarian, $amoxicilina);
        $url = route('reports.medicamentos').'?'.http_build_query([
            'periodo' => 'personalizado',
            'fecha_inicio' => '2026-11-01',
            'fecha_fin' => '2026-11-30',
        ]);

        $response = $this->actingAs($administrator)->getJson($url)->assertOk();

        $response->assertJsonPath('datos.resumen.total_administraciones', 1)
            ->assertJsonPath('datos.medicamentos_mas_utilizados.0.medicamento', 'Paracetamol Demo')
            ->assertJsonPath('datos.medicamentos_mas_utilizados.0.total_administraciones', 1)
            ->assertJsonPath('datos.administraciones_por_mes.0.periodo', '2026-11')
            ->assertJsonPath('datos.administraciones_por_mes.0.total', 1)
            ->assertJsonPath('datos.administraciones_por_veterinario.0.veterinario_id', $veterinarian->id)
            ->assertJsonPath('datos.distribucion_por_via.0.via_administracion', 'Oral')
            ->assertJsonPath('datos.historial.total', 1)
            ->assertJsonPath('datos.historial.data.0.medicamento', 'Paracetamol Demo');
    }

    public function test_outcome_report_uses_recorded_evolution_states_and_percentages(): void
    {
        $administrator = $this->createUser(User::ROLE_ADMINISTRADOR);
        $veterinarian = $this->createUser(User::ROLE_VETERINARIO);

        foreach ([
            ['Nala', '2026-11-10 08:00:00', 'Recuperación favorable', 'en_atencion'],
            ['Milo', '2026-11-11 08:00:00', 'Continúa en tratamiento', 'en_observacion'],
            ['Luna', '2026-11-12 08:00:00', 'Requiere seguimiento', 'finalizada'],
            ['Toby', '2026-11-13 08:00:00', 'Remisión', 'finalizada'],
        ] as [$petName, $admissionDate, $outcome, $status]) {
            $urgency = $this->createUrgency($administrator, $veterinarian, $petName, 'Canino', $admissionDate, $status);
            $attention = $this->createAttention($urgency, $veterinarian, $admissionDate);
            $this->createEvolution($attention, $veterinarian, $admissionDate, $outcome);
        }

        $url = route('reports.resultados').'?'.http_build_query([
            'periodo' => 'personalizado',
            'fecha_inicio' => '2026-11-01',
            'fecha_fin' => '2026-11-30',
        ]);
        $response = $this->actingAs($administrator)->getJson($url)->assertOk();

        $response->assertJsonPath('datos.resumen.resultados_clinicos_registrados', 4)
            ->assertJsonPath('datos.resumen.recuperaciones_favorables', 1)
            ->assertJsonPath('datos.resumen.continuan_en_tratamiento', 1)
            ->assertJsonPath('datos.resumen.requieren_seguimiento', 1)
            ->assertJsonPath('datos.resumen.remisiones', 1)
            ->assertJsonPath('datos.distribucion_por_mes.0.periodo', '2026-11')
            ->assertJsonPath('datos.distribucion_por_mes.0.total', 4)
            ->assertJsonPath('datos.urgencias_por_estado.0.estado', 'en_atencion');

        $this->assertSame(
            [25.0, 25.0, 25.0, 25.0],
            array_map(
                static fn (array $outcome): float => (float) $outcome['porcentaje'],
                $response->json('datos.resultados_registrados'),
            ),
        );
    }

    public function test_veterinarian_reports_only_include_cases_assigned_to_that_veterinarian(): void
    {
        $administrator = $this->createUser(User::ROLE_ADMINISTRADOR);
        $veterinarian = $this->createUser(User::ROLE_VETERINARIO);
        $otherVeterinarian = $this->createUser(User::ROLE_VETERINARIO);

        foreach ([
            ['Nala', $veterinarian],
            ['Milo', $otherVeterinarian],
        ] as [$petName, $assignedVeterinarian]) {
            $urgency = $this->createUrgency(
                $administrator,
                $assignedVeterinarian,
                $petName,
                'Canino',
                '2026-11-10 08:00:00',
                'en_atencion',
            );
            $attention = $this->createAttention($urgency, $assignedVeterinarian, '2026-11-10 08:30:00');
            $medicine = Medicamento::create(['nombre' => 'Medicamento '.$petName, 'presentacion' => 'Tableta']);
            $this->createAdministration($attention, $assignedVeterinarian, $medicine, '2026-11-10 09:00:00', 'Oral');
            $this->createEvolution($attention, $assignedVeterinarian, '2026-11-10 09:30:00', 'Requiere seguimiento');
        }

        $this->actingAs($veterinarian)
            ->getJson(route('reports.ingresos').'?periodo=personalizado&fecha_inicio=2026-11-01&fecha_fin=2026-11-30')
            ->assertOk()
            ->assertJsonPath('datos.resumen.total_urgencias', 1);

        $this->getJson(route('reports.ocupacion').'?periodo=personalizado&fecha_inicio=2026-11-01&fecha_fin=2026-11-30')
            ->assertOk()
            ->assertJsonPath('datos.resumen.total_urgencias', 1);

        $this->getJson(route('reports.medicamentos').'?periodo=personalizado&fecha_inicio=2026-11-01&fecha_fin=2026-11-30')
            ->assertOk()
            ->assertJsonPath('datos.resumen.total_administraciones', 1)
            ->assertJsonPath('datos.administraciones_por_veterinario.0.veterinario_id', $veterinarian->id);

        $this->getJson(route('reports.resultados').'?periodo=personalizado&fecha_inicio=2026-11-01&fecha_fin=2026-11-30')
            ->assertOk()
            ->assertJsonPath('datos.resumen.resultados_clinicos_registrados', 1)
            ->assertJsonPath('datos.resultados_registrados.0.mascotas', 1);
    }

    /**
     * @return array<string, array{string, string, int|string}>
     */
    public static function emptyReportEndpoints(): array
    {
        return [
            'admissions' => ['reports.ingresos', 'datos.resumen.total_urgencias', 0],
            'occupancy' => ['reports.ocupacion', 'datos.resumen.total_urgencias', 0],
            'medications' => ['reports.medicamentos', 'datos.resumen.total_administraciones', 0],
            'outcomes' => ['reports.resultados', 'datos.resumen.resultados_clinicos_registrados', 0],
        ];
    }

    #[DataProvider('emptyReportEndpoints')]
    public function test_report_returns_zero_totals_when_no_records_exist(
        string $routeName,
        string $totalPath,
        int|string $expectedTotal,
    ): void {
        $administrator = $this->createUser(User::ROLE_ADMINISTRADOR);

        $this->actingAs($administrator)
            ->getJson(route($routeName))
            ->assertOk()
            ->assertJsonPath($totalPath, $expectedTotal);
    }

    /**
     * @param  Closure(array<string, mixed>): void  $inspectPayload
     */
    private function expectPdfDownload(string $view, string $filename, Closure $inspectPayload): void
    {
        $pdf = Mockery::mock(\Barryvdh\DomPDF\PDF::class);
        $pdf->shouldReceive('setPaper')
            ->once()
            ->with('a4')
            ->andReturnSelf();
        $pdf->shouldReceive('download')
            ->once()
            ->with($filename)
            ->andReturn(response('PDF test content', 200, [
                'Content-Type' => 'application/pdf',
                'Content-Disposition' => 'attachment; filename="'.$filename.'"',
            ]));

        Pdf::shouldReceive('loadView')
            ->once()
            ->with($view, Mockery::on(function ($payload) use ($inspectPayload): bool {
                if (! is_array($payload)) {
                    return false;
                }

                $inspectPayload($payload);

                return true;
            }))
            ->andReturn($pdf);
    }

    private function createUser(string $role): User
    {
        return User::factory()->create([
            'rol' => $role,
            'estado' => 'activo',
        ]);
    }

    private function createUrgency(
        User $registrar,
        User $veterinarian,
        string $petName,
        string $species,
        string $admissionDate,
        string $status,
        ?string $dischargeDate = null,
        string $reason = 'Consulta',
    ): Urgencia {
        $pet = $this->createPet($petName, $species);

        return $pet->urgencias()->create([
            'registrado_por' => $registrar->id,
            'veterinario_id' => $veterinarian->id,
            'tipo_urgencia' => $reason,
            'descripcion' => 'Caso de prueba para reportes.',
            'nivel_prioridad' => 'media',
            'estado' => $status,
            'fecha_ingreso' => $admissionDate,
            'fecha_egreso' => $dischargeDate,
        ]);
    }

    private function createPet(string $name, string $species): Mascota
    {
        $this->ownerNumber++;
        $owner = Propietario::create([
            'nombres' => 'Propietario de '.$name,
            'apellidos' => 'Demo',
            'tipo_documento' => 'CC',
            'numero_documento' => str_pad((string) $this->ownerNumber, 8, '0', STR_PAD_LEFT),
            'telefono' => '3001234567',
        ]);

        return $owner->mascotas()->create([
            'nombre' => $name,
            'especie' => $species,
            'estado' => 'activo',
        ]);
    }

    private function createAttention(Urgencia $urgency, User $veterinarian, string $date): Atencion
    {
        return Atencion::create([
            'urgencia_id' => $urgency->id,
            'veterinario_id' => $veterinarian->id,
            'diagnostico' => 'Diagnóstico de demostración.',
            'fecha_atencion' => $date,
            'estado' => 'en_proceso',
        ]);
    }

    private function createEvolution(
        Atencion $attention,
        User $veterinarian,
        string $date,
        string $outcome,
    ): EvolucionClinica {
        return EvolucionClinica::create([
            'atencion_id' => $attention->id,
            'veterinario_id' => $veterinarian->id,
            'descripcion' => 'Evolución clínica de prueba.',
            'estado_mascota' => $outcome,
            'fecha_evolucion' => $date,
        ]);
    }

    private function createAdministration(
        Atencion $attention,
        User $veterinarian,
        Medicamento $medication,
        string $date,
        string $route,
    ): AdministracionMedicamento {
        $indication = $this->createIndication($attention, $veterinarian, $medication);

        return AdministracionMedicamento::create([
            'atencion_medicamento_id' => $indication->id,
            'veterinario_id' => $veterinarian->id,
            'dosis_administrada' => '1 tableta',
            'cantidad_administrada' => 1,
            'via_administracion' => $route,
            'fecha_hora' => $date,
        ]);
    }

    private function createIndication(
        Atencion $attention,
        User $veterinarian,
        Medicamento $medication,
    ): AtencionMedicamento {
        return AtencionMedicamento::create([
            'atencion_id' => $attention->id,
            'medicamento_id' => $medication->id,
            'veterinario_id' => $veterinarian->id,
            'dosis' => '1 tableta',
            'cantidad' => 1,
            'frecuencia' => 'Cada 24 horas',
            'via_administracion' => 'Oral',
        ]);
    }
}
