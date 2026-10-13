<?php
/**
 * exportar.php — Clientes a Excel (XLS compatible)
 * El controlador debe emitir los headers antes de incluir esta vista:
 *   header('Content-Type: application/vnd.ms-excel; charset=UTF-8');
 *   header('Content-Disposition: attachment; filename="clientes_YYYY-MM-DD.xls"');
 *   header('Cache-Control: no-cache, no-store, must-revalidate');
 *   header('Pragma: no-cache'); header('Expires: 0');
 */
?>
<html>
<head>
    <meta charset="UTF-8">
    <!--[if gte mso 9]>
    <xml>
        <x:ExcelWorkbook>
            <x:ExcelWorksheets>
                <x:ExcelWorksheet>
                    <x:Name>Clientes</x:Name>
                    <x:WorksheetOptions><x:DisplayGridlines/></x:WorksheetOptions>
                </x:ExcelWorksheet>
            </x:ExcelWorksheets>
        </x:ExcelWorkbook>
    </xml>
    <![endif]-->
    <style>
        body { font-family: Arial, sans-serif; font-size: 11pt; }

        table { border-collapse: collapse; width: 100%; }

        /* ── Cabecera del reporte ── */
        .rpt-title td {
            background-color: #0d9488;
            color: #ffffff;
            font-size: 14pt;
            font-weight: bold;
            padding: 10px 14px;
            border: none;
        }
        .rpt-meta td {
            background-color: #f0fdf4;
            color: #334155;
            font-size: 9pt;
            padding: 4px 14px;
            border: none;
        }
        .rpt-spacer td { height: 10px; border: none; }

        /* ── Encabezados de columna ── */
        thead tr th {
            background-color: #0f766e;
            color: #ffffff;
            font-weight: bold;
            font-size: 10pt;
            padding: 8px 10px;
            border: 1px solid #0d9488;
            text-align: center;
            white-space: nowrap;
        }

        /* ── Filas de datos ── */
        tbody tr td {
            padding: 7px 10px;
            border: 1px solid #e2e8f0;
            font-size: 10pt;
            vertical-align: middle;
            color: #1e293b;
        }
        tbody tr:nth-child(even) td {
            background-color: #f8fafc;
        }

        /* ── Columnas especiales ── */
        .col-id    { text-align: center; width: 40px; color: #64748b; font-size: 9pt; }
        .col-doc   { text-align: center; white-space: nowrap; }
        .col-num   { text-align: center; font-family: monospace; }
        .col-money { text-align: right; }
        .col-date  { text-align: center; white-space: nowrap; }

        /* ── Pills de estado y tipo ── */
        .badge-activo    { color: #065f46; font-weight: bold; }
        .badge-inactivo  { color: #991b1b; font-weight: bold; }
        .badge-vip       { color: #92400e; font-weight: bold; }
        .badge-frecuente { color: #0e7490; font-weight: bold; }
        .badge-mayorista { color: #065f46; font-weight: bold; }
        .badge-regular   { color: #334155; }

        /* ── Totales ── */
        .rpt-footer td {
            background-color: #f1f5f9;
            font-weight: bold;
            font-size: 10pt;
            padding: 7px 10px;
            border: 1px solid #cbd5e1;
        }
    </style>
</head>
<body>

<table>
    <!-- Título del reporte -->
    <tr class="rpt-title">
        <td colspan="12">📋 Reporte de Clientes — Sistema Almacén RopaVirtual</td>
    </tr>
    <tr class="rpt-meta">
        <td colspan="12">
            Generado el: <?= date('d/m/Y H:i:s') ?>
            &nbsp;&nbsp;|&nbsp;&nbsp;
            Total de registros: <?= count($clientes) ?>
        </td>
    </tr>
    <tr class="rpt-spacer"><td colspan="12"></td></tr>

    <!-- Encabezados -->
    <thead>
        <tr>
            <th>#</th>
            <th>Tipo Doc.</th>
            <th>Nro. Documento</th>
            <th>Nombre Completo</th>
            <th>Email</th>
            <th>Teléfono</th>
            <th>Dirección</th>
            <th>Fec. Nacimiento</th>
            <th>Género</th>
            <th>Tipo Cliente</th>
            <th>Estado</th>
            <th>Fec. Registro</th>
        </tr>
    </thead>

    <!-- Datos -->
    <tbody>
    <?php
    $totalActivos   = 0;
    $totalInactivos = 0;
    foreach ($clientes as $c):
        if ($c['estado'] === 'activo') $totalActivos++;
        else $totalInactivos++;
    ?>
    <tr>
        <td class="col-id"><?= (int)$c['id_cliente'] ?></td>
        <td class="col-doc"><?= htmlspecialchars($c['tipo_documento']) ?></td>
        <td class="col-num"><?= htmlspecialchars($c['numero_documento']) ?></td>
        <td><?= htmlspecialchars($c['nombre_completo']) ?></td>
        <td><?= htmlspecialchars($c['email'] ?? '') ?></td>
        <td class="col-num"><?= htmlspecialchars($c['telefono'] ?? '') ?></td>
        <td><?= htmlspecialchars($c['direccion'] ?? '') ?></td>
        <td class="col-date">
            <?= !empty($c['fecha_nacimiento'])
                ? date('d/m/Y', strtotime($c['fecha_nacimiento']))
                : '' ?>
        </td>
        <td><?= htmlspecialchars($c['genero'] ?? '') ?></td>
        <td>
            <span class="badge-<?= $c['tipo_cliente'] ?>">
                <?= ucfirst(htmlspecialchars($c['tipo_cliente'])) ?>
            </span>
        </td>
        <td>
            <span class="badge-<?= $c['estado'] ?>">
                <?= ucfirst(htmlspecialchars($c['estado'])) ?>
            </span>
        </td>
        <td class="col-date">
            <?= !empty($c['fecha_registro'])
                ? date('d/m/Y H:i', strtotime($c['fecha_registro']))
                : '' ?>
        </td>
    </tr>
    <?php endforeach; ?>
    </tbody>

    <!-- Fila de totales -->
    <tr class="rpt-footer">
        <td colspan="10" style="text-align:right;">
            Total: <?= count($clientes) ?> registros
        </td>
        <td style="color:#065f46;">✓ Activos: <?= $totalActivos ?></td>
        <td style="color:#991b1b;">✗ Inactivos: <?= $totalInactivos ?></td>
    </tr>

</table>

</body>
</html>