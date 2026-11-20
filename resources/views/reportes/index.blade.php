@extends('reportes.pdf-layout')

@section('content')
    <section class="section">
        <h2>Indicadores generales</h2>
        <table class="table">
            <thead>
                <tr>
                    <th>Indicador</th>
                    <th>Total del período</th>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <td>Mascotas ingresadas</td>
                    <td class="value">{{ number_format($datos['mascotas_ingresadas'] ?? 0, 0, ',', '.') }}</td>
                </tr>
                <tr>
                    <td>Urgencias registradas</td>
                    <td class="value">{{ number_format($datos['urgencias_registradas'] ?? 0, 0, ',', '.') }}</td>
                </tr>
                @if (isset($datos['medicamentos_administrados']))
                    <tr>
                        <td>Medicamentos administrados</td>
                        <td class="value">{{ number_format($datos['medicamentos_administrados'], 0, ',', '.') }}</td>
                    </tr>
                @endif
                <tr>
                    <td>Egresos registrados</td>
                    <td class="value">{{ number_format($datos['egresos_registrados'] ?? 0, 0, ',', '.') }}</td>
                </tr>
                @if (isset($datos['resultados_clinicos_registrados']))
                    <tr>
                        <td>Resultados clínicos registrados</td>
                        <td class="value">{{ number_format($datos['resultados_clinicos_registrados'], 0, ',', '.') }}</td>
                    </tr>
                @endif
            </tbody>
        </table>
        @if (collect($datos)->every(fn ($total) => (int) $total === 0))
            <p class="empty">No hay registros para los indicadores del período consultado.</p>
        @endif
    </section>
@endsection