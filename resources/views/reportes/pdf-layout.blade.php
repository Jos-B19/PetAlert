<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <title>{{ $titulo }}</title>
    <style>
        @page {
            margin: 34px 38px 54px;
        }

        body {
            color: #233746;
            font-family: DejaVu Sans, sans-serif;
            font-size: 9px;
            line-height: 1.45;
        }

        .header {
            border-bottom: 3px solid #13877e;
            margin-bottom: 17px;
            padding-bottom: 11px;
        }

        .brand {
            color: #116b68;
            font-size: 15px;
            font-weight: bold;
            letter-spacing: .3px;
        }

        h1 {
            color: #123143;
            font-size: 19px;
            margin: 11px 0 2px;
        }

        .subtitle,
        .muted {
            color: #647887;
            font-size: 8px;
        }

        .metadata {
            background: #f3f8f8;
            border: 1px solid #dce8e8;
            border-radius: 6px;
            margin-top: 11px;
            padding: 8px 10px;
        }

        .metadata table {
            width: 100%;
        }

        .metadata td {
            width: 33.33%;
        }

        .section {
            margin: 15px 0;
        }

        h2 {
            border-bottom: 1px solid #dce8e8;
            color: #123143;
            font-size: 12px;
            margin: 0 0 7px;
            padding-bottom: 4px;
        }

        h3 {
            color: #116b68;
            font-size: 10px;
            margin: 12px 0 5px;
        }

        p {
            margin: 4px 0;
        }

        .table {
            border-collapse: collapse;
            margin-top: 5px;
            width: 100%;
        }

        .table thead {
            display: table-header-group;
        }

        .table th {
            background: #eaf3f3;
            color: #23434f;
            font-size: 8px;
            text-align: left;
            text-transform: uppercase;
        }

        .table th,
        .table td {
            border: 1px solid #dce5e8;
            padding: 5px 6px;
            vertical-align: top;
        }

        .table tr {
            page-break-inside: avoid;
        }

        .value {
            color: #123143;
            font-weight: bold;
        }

        .empty {
            background: #f7f9fa;
            border: 1px dashed #cbd6db !important;
            color: #647887;
            padding: 9px !important;
            text-align: center;
        }

        .note {
            background: #f3f8f8;
            border-left: 3px solid #13877e;
            color: #48606b;
            margin: 8px 0;
            padding: 7px 9px;
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
        <h1>{{ $titulo }}</h1>
        <div class="subtitle">Informe administrativo · Datos consultados en el sistema</div>
        <div class="metadata">
            <table>
                <tr>
                    <td><strong>Período:</strong> {{ $periodoNombre }}</td>
                    <td><strong>Desde:</strong> {{ $filtros['fecha_inicio'] }}</td>
                    <td><strong>Hasta:</strong> {{ $filtros['fecha_fin'] }}</td>
                </tr>
            </table>
            <p><strong>Generado:</strong> {{ $generadoEn->format('d/m/Y H:i') }}</p>
        </div>
    </header>

    <main>
        @yield('content')
    </main>

    <footer class="footer">
        Pet Alert Ipiales · Reportes · Página <span class="page-number"></span> de <span class="page-count"></span>
    </footer>
</body>
</html>
