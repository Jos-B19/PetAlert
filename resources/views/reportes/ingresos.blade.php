@extends('reportes.pdf-layout')

@section('content')
    <section class="section">
        <h2>Resumen de ingresos</h2>
        <table class="table">
            <thead>
                <tr><th>Indicador</th><th>Total</th></tr>
            </thead>
            <tbody>
                <tr><td>Urgencias registradas</td><td class="value">{{ number_format($datos['resumen']['total_urgencias'], 0, ',', '.') }}</td></tr>
                <tr><td>Ingresos hoy</td><td class="value">{{ number_format($datos['resumen']['ingresos_hoy'], 0, ',', '.') }}</td></tr>
                <tr><td>Ingresos en los últimos 7 días</td><td class="value">{{ number_format($datos['resumen']['ingresos_ultimos_7_dias'], 0, ',', '.') }}</td></tr>
                <tr><td>Ingresos del mes actual</td><td class="value">{{ number_format($datos['resumen']['ingresos_mes_actual'], 0, ',', '.') }}</td></tr>
            </tbody>
        </table>
    </section>

    <section class="section">
        <h2>Ingresos por día</h2>
        <table class="table">
            <thead><tr><th>Fecha</th><th>Ingresos</th></tr></thead>
            <tbody>
                @forelse ($datos['ingresos_por_dia'] as $item)
                    <tr><td>{{ $item['periodo'] }}</td><td>{{ number_format($item['total'], 0, ',', '.') }}</td></tr>
                @empty
                    <tr><td class="empty" colspan="2">Sin ingresos diarios para este período.</td></tr>
                @endforelse
            </tbody>
        </table>
    </section>

    <section class="section">
        <h2>Ingresos por semana</h2>
        <table class="table">
            <thead><tr><th>Semana</th><th>Ingresos</th></tr></thead>
            <tbody>
                @forelse ($datos['ingresos_por_semana'] as $item)
                    <tr><td>{{ $item['periodo'] }}</td><td>{{ number_format($item['total'], 0, ',', '.') }}</td></tr>
                @empty
                    <tr><td class="empty" colspan="2">Sin ingresos semanales para este período.</td></tr>
                @endforelse
            </tbody>
        </table>
    </section>

    <section class="section">
        <h2>Ingresos por mes</h2>
        <table class="table">
            <thead><tr><th>Mes</th><th>Ingresos</th></tr></thead>
            <tbody>
                @forelse ($datos['ingresos_por_mes'] as $item)
                    <tr><td>{{ $item['periodo'] }}</td><td>{{ number_format($item['total'], 0, ',', '.') }}</td></tr>
                @empty
                    <tr><td class="empty" colspan="2">Sin ingresos mensuales para este período.</td></tr>
                @endforelse
            </tbody>
        </table>
    </section>

    <section class="section">
        <h2>Distribución por especie</h2>
        <table class="table">
            <thead><tr><th>Especie</th><th>Urgencias</th></tr></thead>
            <tbody>
                @forelse ($datos['distribucion_por_especie'] as $item)
                    <tr><td>{{ $item['especie'] ?: 'No especificada' }}</td><td>{{ number_format($item['total'], 0, ',', '.') }}</td></tr>
                @empty
                    <tr><td class="empty" colspan="2">Sin registros por especie para este período.</td></tr>
                @endforelse
            </tbody>
        </table>
    </section>

    <section class="section">
        <h2>Motivos frecuentes</h2>
        <table class="table">
            <thead><tr><th>Motivo registrado</th><th>Urgencias</th></tr></thead>
            <tbody>
                @forelse ($datos['motivos_frecuentes'] as $item)
                    <tr><td>{{ $item['motivo'] ?: 'No especificado' }}</td><td>{{ number_format($item['total'], 0, ',', '.') }}</td></tr>
                @empty
                    <tr><td class="empty" colspan="2">Sin motivos registrados para este período.</td></tr>
                @endforelse
            </tbody>
        </table>
    </section>
@endsection