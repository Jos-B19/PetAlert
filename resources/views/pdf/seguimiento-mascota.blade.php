<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <title>Seguimiento clínico de mascota</title>
    <style>
        @page {
            margin: 34px 38px 55px;
        }

        body {
            color: #233746;
            font-family: DejaVu Sans, sans-serif;
            font-size: 10px;
            line-height: 1.5;
        }

        .header {
            border-bottom: 3px solid #13877e;
            margin-bottom: 22px;
            padding-bottom: 13px;
        }

        .brand {
            color: #116b68;
            font-size: 17px;
            font-weight: bold;
            letter-spacing: .3px;
        }

        .subtitle {
            color: #647887;
            font-size: 9px;
            margin-top: 2px;
        }

        h1 {
            color: #123143;
            font-size: 20px;
            margin: 18px 0 3px;
        }

        h2 {
            color: #123143;
            font-size: 13px;
            margin: 0 0 10px;
        }

        .generated {
            color: #647887;
            font-size: 9px;
        }

        .panel {
            background: #f3f8f8;
            border: 1px solid #dce8e8;
            border-radius: 7px;
            margin-bottom: 15px;
            padding: 12px 14px;
        }

        .details {
            width: 100%;
        }

        .details td {
            padding: 4px 8px 4px 0;
            vertical-align: top;
            width: 50%;
        }

        .label {
            color: #58707d;
            display: block;
            font-size: 8px;
            font-weight: bold;
            text-transform: uppercase;
        }

        .value {
            color: #1d3441;
            display: block;
            font-size: 10px;
            margin-top: 2px;
        }

        .status {
            color: #116b68;
            font-weight: bold;
        }

        .discharge {
            background: #edf8f1;
            border-color: #c9e8d4;
        }

        .timeline {
            border-left: 2px solid #b9ded8;
            margin-left: 5px;
            padding-left: 15px;
        }

        .event {
            border: 1px solid #e0e8eb;
            border-radius: 6px;
            margin: 0 0 11px;
            padding: 9px 11px;
            page-break-inside: avoid;
        }

        .event-title {
            color: #116b68;
            font-size: 10px;
            font-weight: bold;
        }

        .event-date {
            color: #647887;
            font-size: 9px;
            margin-bottom: 5px;
        }

        .event p {
            margin: 3px 0;
        }

        .empty {
            background: #f7f9fa;
            border: 1px dashed #cbd6db;
            border-radius: 6px;
            color: #647887;
            padding: 12px;
        }

        .privacy-note {
            background: #f3f8f8;
            border: 1px solid #dce8e8;
            border-radius: 6px;
            color: #58707d;
            margin-top: 12px;
            padding: 10px 12px;
        }

        .footer {
            bottom: -34px;
            color: #74858f;
            font-size: 8px;
            left: 0;
            position: fixed;
            right: 0;
            text-align: center;
        }

        .page-number:before {
            content: counter(page);
        }

        .page-count:before {
            content: counter(pages);
        }
    </style>
</head>
<body>
    <header class="header">
        <div class="brand">Pet Alert Ipiales</div>
        <div class="subtitle">Información de seguimiento para propietarios</div>
        <h1>Seguimiento clínico de mascota</h1>
        <div class="generated">Generado el {{ $generatedAt->format('d/m/Y H:i') }}</div>
    </header>

    <section class="panel">
        <h2>Identificación de la mascota</h2>
        <table class="details">
            <tr>
                <td>
                    <span class="label">Nombre</span>
                    <span class="value">{{ $trackingPet['mascota']['nombre'] }}</span>
                </td>
                <td>
                    <span class="label">Especie</span>
                    <span class="value">{{ $trackingPet['mascota']['especie'] }}</span>
                </td>
            </tr>
            <tr>
                <td>
                    <span class="label">Raza</span>
                    <span class="value">{{ $trackingPet['mascota']['raza'] ?: 'No informada' }}</span>
                </td>
                <td>
                    <span class="label">Sexo</span>
                    <span class="value">{{ $trackingPet['mascota']['sexo'] ?: 'No informado' }}</span>
                </td>
            </tr>
        </table>
    </section>

    <section class="panel">
        <h2>Ingreso y estado actual</h2>
        <table class="details">
            <tr>
                <td>
                    <span class="label">Fecha de ingreso</span>
                    <span class="value">
                        {{ !empty($trackingPet['ingreso']['fecha']) ? \Carbon\Carbon::parse($trackingPet['ingreso']['fecha'])->format('d/m/Y H:i') : 'Sin ingreso activo registrado' }}
                    </span>
                </td>
                <td>
                    <span class="label">Estado actual</span>
                    <span class="value status">{{ $trackingPet['mascota']['estado'] ?: 'No informado' }}</span>
                </td>
            </tr>
        </table>
    </section>

    @if (!empty($trackingPet['egreso']))
        <section class="panel discharge">
            <h2>Egreso registrado</h2>
            <p>
                Fecha:
                {{ !empty($trackingPet['egreso']['fecha']) ? \Carbon\Carbon::parse($trackingPet['egreso']['fecha'])->format('d/m/Y H:i') : 'No informada' }}
            </p>
        </section>
    @endif

    <section>
        <h2>Fechas de seguimiento</h2>
        @forelse ($trackingPet['timeline'] as $entry)
            <div class="timeline">
                <article class="event">
                    <div class="event-title">
                        @switch($entry['tipo'])
                            @case('ingreso') Ingreso a la clínica @break
                            @case('egreso') Egreso físico registrado @break
                            @default Fecha registrada
                        @endswitch
                    </div>
                    <div class="event-date">
                        {{ !empty($entry['fecha']) ? \Carbon\Carbon::parse($entry['fecha'])->format('d/m/Y H:i') : 'Fecha no disponible' }}
                    </div>

                    @switch($entry['tipo'])
                        @case('ingreso')
                            @if (!empty($entry['estado']))
                                <p><strong>Estado:</strong> {{ $entry['estado'] }}</p>
                            @endif
                            @break
                    @endswitch
                </article>
            </div>
        @empty
            <p class="empty">No hay fechas de ingreso o egreso disponibles para mostrar.</p>
        @endforelse
        <p class="privacy-note">
            Este documento muestra únicamente datos de identificación y fechas de seguimiento.
            No incluye evoluciones, observaciones, tratamientos ni medicamentos.
        </p>
    </section>

    <footer class="footer">
        Pet Alert Ipiales · Seguimiento clínico · Página <span class="page-number"></span> de <span class="page-count"></span>
    </footer>
</body>
</html>
