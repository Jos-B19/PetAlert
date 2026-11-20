@extends('reportes.pdf-layout')

@section('content')
    <section class="section">
        <h2>Resumen de administraciones</h2>
        <table class="table">
            <thead><tr><th>Indicador</th><th>Total</th></tr></thead>
            <tbody>
                <tr><td>Administraciones realizadas</td><td class="value">{{ number_format($datos['resumen']['total_administraciones'], 0, ',', '.') }}</td></tr>
                <tr><td>Medicamentos destacados</td><td class="value">{{ number_format($datos['medicamentos_mas_utilizados']->count(), 0, ',', '.') }}</td></tr>
                <tr><td>Veterinarios con administraciones</td><td class="value">{{ number_format($datos['administraciones_por_veterinario']->count(), 0, ',', '.') }}</td></tr>
                <tr><td>Vías de administración registradas</td><td class="value">{{ number_format($datos['distribucion_por_via']->count(), 0, ',', '.') }}</td></tr>
            </tbody>
        </table>
        <p class="note">Se cuentan únicamente administraciones registradas. Una indicación o prescripción por sí sola no se considera un medicamento administrado.</p>
    </section>

    <section class="section">
        <h2>Medicamentos más utilizados</h2>
        <table class="table">
            <thead><tr><th>Medicamento</th><th>Presentación</th><th>Administraciones</th></tr></thead>
            <tbody>
                @forelse ($datos['medicamentos_mas_utilizados'] as $item)
                    <tr>
                        <td>{{ $item['medicamento'] }}</td>
                        <td>{{ $item['presentacion'] ?: 'No informada' }}</td>
                        <td>{{ number_format($item['total_administraciones'], 0, ',', '.') }}</td>
                    </tr>
                @empty
                    <tr><td class="empty" colspan="3">Sin administraciones de medicamentos para este período.</td></tr>
                @endforelse
            </tbody>
        </table>
    </section>

    <section class="section">
        <h2>Administraciones por mes</h2>
        <table class="table">
            <thead><tr><th>Mes</th><th>Administraciones</th></tr></thead>
            <tbody>
                @forelse ($datos['administraciones_por_mes'] as $item)
                    <tr><td>{{ $item['periodo'] }}</td><td>{{ number_format($item['total'], 0, ',', '.') }}</td></tr>
                @empty
                    <tr><td class="empty" colspan="2">Sin administraciones mensuales para este período.</td></tr>
                @endforelse
            </tbody>
        </table>
    </section>

    <section class="section">
        <h2>Administraciones por veterinario</h2>
        <table class="table">
            <thead><tr><th>Veterinario</th><th>Administraciones</th></tr></thead>
            <tbody>
                @forelse ($datos['administraciones_por_veterinario'] as $item)
                    <tr><td>{{ $item['veterinario'] }}</td><td>{{ number_format($item['total_administraciones'], 0, ',', '.') }}</td></tr>
                @empty
                    <tr><td class="empty" colspan="2">Sin administraciones por veterinario para este período.</td></tr>
                @endforelse
            </tbody>
        </table>
    </section>

    <section class="section">
        <h2>Distribución por vía de administración</h2>
        <table class="table">
            <thead><tr><th>Vía</th><th>Administraciones</th></tr></thead>
            <tbody>
                @forelse ($datos['distribucion_por_via'] as $item)
                    <tr>
                        <td>{{ $item['via_administracion'] ?: 'No informada' }}</td>
                        <td>{{ number_format($item['total_administraciones'], 0, ',', '.') }}</td>
                    </tr>
                @empty
                    <tr><td class="empty" colspan="2">Sin vías registradas para este período.</td></tr>
                @endforelse
            </tbody>
        </table>
    </section>

    <section class="section">
        <h2>Historial completo de administraciones</h2>
        <table class="table">
            <thead>
                <tr><th>Fecha</th><th>Medicamento</th><th>Dosis y cantidad</th><th>Vía</th><th>Veterinario</th><th>Atención</th></tr>
            </thead>
            <tbody>
                @forelse ($datos['historial'] as $item)
                    <tr>
                        <td>{{ \Carbon\Carbon::parse($item->fecha_hora)->format('d/m/Y H:i') }}</td>
                        <td>{{ $item->medicamento }}<br><span class="muted">{{ $item->presentacion }}</span></td>
                        <td>{{ $item->dosis_administrada }}<br><span class="muted">Cantidad: {{ number_format($item->cantidad_administrada, 2, ',', '.') }}</span></td>
                        <td>{{ $item->via_administracion ?: 'No informada' }}</td>
                        <td>{{ $item->veterinario }}</td>
                        <td>#{{ $item->atencion_id }}</td>
                    </tr>
                @empty
                    <tr><td class="empty" colspan="6">Sin administraciones registradas para este período.</td></tr>
                @endforelse
            </tbody>
        </table>
    </section>
@endsection