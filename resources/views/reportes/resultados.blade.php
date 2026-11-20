@extends('reportes.pdf-layout')

@section('content')
    <section class="section">
        <h2>Resumen de resultados clínicos</h2>
        <table class="table">
            <thead><tr><th>Indicador</th><th>Total</th></tr></thead>
            <tbody>
                <tr><td>Resultados clínicos registrados</td><td class="value">{{ number_format($datos['resumen']['resultados_clinicos_registrados'], 0, ',', '.') }}</td></tr>
                <tr><td>Recuperación favorable</td><td class="value">{{ number_format($datos['resumen']['recuperaciones_favorables'], 0, ',', '.') }}</td></tr>
                <tr><td>Continúan en tratamiento</td><td class="value">{{ number_format($datos['resumen']['continuan_en_tratamiento'], 0, ',', '.') }}</td></tr>
                <tr><td>Requieren seguimiento</td><td class="value">{{ number_format($datos['resumen']['requieren_seguimiento'], 0, ',', '.') }}</td></tr>
                <tr><td>Remisiones</td><td class="value">{{ number_format($datos['resumen']['remisiones'], 0, ',', '.') }}</td></tr>
            </tbody>
        </table>
        <p class="note">Los resultados reflejan estados registrados en evoluciones clínicas; un estado favorable no implica un alta médica ni un egreso físico.</p>
    </section>

    <section class="section">
        <h2>Distribución de resultados registrados</h2>
        <table class="table">
            <thead><tr><th>Resultado</th><th>Evoluciones</th><th>Mascotas</th><th>Porcentaje</th></tr></thead>
            <tbody>
                @forelse ($datos['resultados_registrados'] as $item)
                    <tr>
                        <td>{{ $item['resultado'] }}</td>
                        <td>{{ number_format($item['evoluciones'], 0, ',', '.') }}</td>
                        <td>{{ number_format($item['mascotas'], 0, ',', '.') }}</td>
                        <td>{{ number_format($item['porcentaje'], 2, ',', '.') }}%</td>
                    </tr>
                @empty
                    <tr><td class="empty" colspan="4">Sin resultados clínicos registrados para este período.</td></tr>
                @endforelse
            </tbody>
        </table>
    </section>

    <section class="section">
        <h2>Resultados por mes</h2>
        <table class="table">
            <thead><tr><th>Mes</th><th>Resultados</th></tr></thead>
            <tbody>
                @forelse ($datos['distribucion_por_mes'] as $item)
                    <tr><td>{{ $item['periodo'] }}</td><td>{{ number_format($item['total'], 0, ',', '.') }}</td></tr>
                @empty
                    <tr><td class="empty" colspan="2">Sin resultados mensuales para este período.</td></tr>
                @endforelse
            </tbody>
        </table>
    </section>

    <section class="section">
        <h2>Urgencias por estado</h2>
        <table class="table">
            <thead><tr><th>Estado de urgencia</th><th>Urgencias</th></tr></thead>
            <tbody>
                @forelse ($datos['urgencias_por_estado'] as $item)
                    <tr><td>{{ $item['estado'] }}</td><td>{{ number_format($item['total'], 0, ',', '.') }}</td></tr>
                @empty
                    <tr><td class="empty" colspan="2">Sin urgencias para distribuir por estado.</td></tr>
                @endforelse
            </tbody>
        </table>
    </section>
@endsection