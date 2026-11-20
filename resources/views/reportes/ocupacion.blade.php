@extends('reportes.pdf-layout')

@section('content')
    <section class="section">
        <h2>Resumen de ocupación y permanencia</h2>
        <table class="table">
            <thead><tr><th>Indicador</th><th>Total</th></tr></thead>
            <tbody>
                <tr><td>Urgencias registradas</td><td class="value">{{ number_format($datos['resumen']['total_urgencias'], 0, ',', '.') }}</td></tr>
                <tr><td>Mascotas con urgencias activas</td><td class="value">{{ number_format($datos['resumen']['mascotas_con_urgencias_activas'], 0, ',', '.') }}</td></tr>
                <tr><td>Egresos registrados</td><td class="value">{{ number_format($datos['resumen']['egresos_registrados'], 0, ',', '.') }}</td></tr>
                <tr><td>Mascotas con egreso registrado</td><td class="value">{{ number_format($datos['resumen']['mascotas_con_egreso_registrado'], 0, ',', '.') }}</td></tr>
                <tr><td>Casos sin egreso</td><td class="value">{{ number_format($datos['resumen']['casos_sin_egreso'], 0, ',', '.') }}</td></tr>
                <tr><td>Casos activos sin egreso</td><td class="value">{{ number_format($datos['resumen']['activas_sin_egreso'], 0, ',', '.') }}</td></tr>
                <tr><td>Casos finalizados sin egreso físico</td><td class="value">{{ number_format($datos['resumen']['finalizadas_sin_egreso'], 0, ',', '.') }}</td></tr>
                <tr><td>Permanencia promedio</td><td class="value">{{ number_format($datos['resumen']['promedio_permanencia_minutos'], 2, ',', '.') }} min</td></tr>
                <tr><td>Permanencia máxima</td><td class="value">{{ number_format($datos['resumen']['maximo_permanencia_minutos'], 2, ',', '.') }} min</td></tr>
            </tbody>
        </table>
        <p class="note">Los casos activos se identifican por su estado vigente; no se clasifican automáticamente como hospitalizaciones.</p>
    </section>

    @foreach ([
        'por_dia' => 'por día',
        'por_semana' => 'por semana',
        'por_mes' => 'por mes',
    ] as $periodKey => $periodTitle)
        <section class="section">
            <h2>Ingresos y egresos {{ $periodTitle }}</h2>
            <table class="table">
                <thead><tr><th>Período</th><th>Tipo</th><th>Total</th></tr></thead>
                <tbody>
                    @forelse ($datos['ingresos_y_egresos'][$periodKey]['ingresos'] as $item)
                        <tr>
                            <td>{{ $item['periodo'] }}</td>
                            <td>Ingresos</td>
                            <td>{{ number_format($item['total'], 0, ',', '.') }}</td>
                        </tr>
                    @empty
                        <tr><td class="empty" colspan="3">Sin ingresos para este período.</td></tr>
                    @endforelse
                    @forelse ($datos['ingresos_y_egresos'][$periodKey]['egresos'] as $item)
                        <tr>
                            <td>{{ $item['periodo'] }}</td>
                            <td>Egresos físicos</td>
                            <td>{{ number_format($item['total'], 0, ',', '.') }}</td>
                        </tr>
                    @empty
                        <tr><td class="empty" colspan="3">Sin egresos para este período.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </section>
    @endforeach

    <section class="section">
        <h2>Distribución de permanencias</h2>
        <table class="table">
            <thead><tr><th>Días completos</th><th>Casos</th></tr></thead>
            <tbody>
                @forelse ($datos['distribucion_permanencia_dias'] as $item)
                    <tr>
                        <td>{{ number_format($item['dias_completos'], 0, ',', '.') }}</td>
                        <td>{{ number_format($item['casos'], 0, ',', '.') }}</td>
                    </tr>
                @empty
                    <tr><td class="empty" colspan="2">Sin permanencias con egreso válido.</td></tr>
                @endforelse
            </tbody>
        </table>
    </section>

    <section class="section">
        <h2>Estancias con egreso</h2>
        <table class="table">
            <thead>
                <tr><th>Identificador de mascota</th><th>Estado</th><th>Ingreso</th><th>Egreso</th><th>Permanencia</th></tr>
            </thead>
            <tbody>
                @forelse ($datos['estancias_registradas'] as $stay)
                    <tr>
                        <td>Mascota #{{ $stay->mascota_id }}</td>
                        <td>{{ $stay->estado }}</td>
                        <td>{{ \Carbon\Carbon::parse($stay->fecha_ingreso)->format('d/m/Y H:i') }}</td>
                        <td>{{ \Carbon\Carbon::parse($stay->fecha_egreso)->format('d/m/Y H:i') }}</td>
                        <td>{{ number_format($stay->permanencia_minutos, 0, ',', '.') }} min</td>
                    </tr>
                @empty
                    <tr><td class="empty" colspan="5">Sin estancias con egreso para este período.</td></tr>
                @endforelse
            </tbody>
        </table>
    </section>

    <section class="section">
        <h2>Casos sin egreso registrado</h2>
        <table class="table">
            <thead><tr><th>Identificador de mascota</th><th>Estado</th><th>Fecha de ingreso</th></tr></thead>
            <tbody>
                @forelse ($datos['casos_sin_egreso'] as $case)
                    <tr>
                        <td>Mascota #{{ $case->mascota_id }}</td>
                        <td>{{ $case->estado }}</td>
                        <td>{{ \Carbon\Carbon::parse($case->fecha_ingreso)->format('d/m/Y H:i') }}</td>
                    </tr>
                @empty
                    <tr><td class="empty" colspan="3">No hay casos sin egreso en el período consultado.</td></tr>
                @endforelse
            </tbody>
        </table>
    </section>
@endsection