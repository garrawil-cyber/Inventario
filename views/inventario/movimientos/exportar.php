<?php
/**
 * views/inventario/movimientos/exportar.php
 * Vista de exportación a Excel (.xls) del módulo de Movimientos.
 *
 * Variables provistas por MovimientoController::exportar():
 *   $movimientos → array completo con joins (producto, almacén, usuario, proveedor)
 *   $filters     → array con los filtros activos en el momento de la exportación
 *
 * El controlador ya envió los headers necesarios:
 *   Content-Type: application/vnd.ms-excel; charset=UTF-8
 *   Content-Disposition: attachment; filename="movimientos_YYYYMMDD_His.xls"
 *   Cache-Control: max-age=0
 */

if (!isset($movimientos)) {
    die('Error: Variables requeridas no están definidas.');
}

// ── Cálculo de totales ────────────────────────────────────────────────────────
$totalEntradas    = 0; $valorEntradas    = 0.0;
$totalSalidas     = 0; $valorSalidas     = 0.0;
$totalAjustes     = 0; $valorAjustes     = 0.0;
$totalMovimientos = count($movimientos);
$sumCantidad      = 0; $sumValorTotal    = 0.0;

foreach ($movimientos as $m) {
    $val = (float)($m['costo_total'] ?? ((float)$m['cantidad'] * (float)$m['costo_unitario']));
    $sumCantidad  += (float)$m['cantidad'];
    $sumValorTotal += $val;

    if ($m['tipo_movimiento'] === 'entrada')     { $totalEntradas++; $valorEntradas += $val; }
    elseif ($m['tipo_movimiento'] === 'salida')  { $totalSalidas++;  $valorSalidas  += $val; }
    else                                          { $totalAjustes++;  $valorAjustes  += $val; }
}

// ── Texto descriptivo de los filtros ─────────────────────────────────────────
$filtrosDesc = [];
if (!empty($filters['tipo_movimiento'])) $filtrosDesc[] = 'Tipo: ' . ucfirst($filters['tipo_movimiento']);
if (!empty($filters['fecha_desde']))     $filtrosDesc[] = 'Desde: ' . date('d/m/Y', strtotime($filters['fecha_desde']));
if (!empty($filters['fecha_hasta']))     $filtrosDesc[] = 'Hasta: ' . date('d/m/Y', strtotime($filters['fecha_hasta']));
if (!empty($filters['id_almacen']))      $filtrosDesc[] = 'Almacén ID: ' . $filters['id_almacen'];
$filtrosTexto = !empty($filtrosDesc) ? implode('  |  ', $filtrosDesc) : 'Sin filtros — todos los movimientos';

// BOM UTF-8: necesario para que Excel abra correctamente tildes y ñ
echo "\xEF\xBB\xBF";
?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <style>
        body  { font-family: Arial, sans-serif; font-size: 11px; }
        table { border-collapse: collapse; width: 100%; margin-bottom: 14px; }
        td, th { border: 1px solid #c8cdd4; padding: 4px 7px; vertical-align: middle; }

        /* ── Encabezado ──────────────────────────────────── */
        .hdr-titulo { background-color: #1e3a5f; color: #ffffff; font-size: 15px;
                      font-weight: bold; text-align: center; padding: 10px; letter-spacing: .03em; }
        .hdr-sub    { background-color: #2563eb; color: #ffffff; font-weight: bold;
                      font-size: 10px; text-transform: uppercase; letter-spacing: .04em; }
        .hdr-meta   { background-color: #dbeafe; font-size: 10px; }

        /* ── Resumen ─────────────────────────────────────── */
        .sec-titulo   { background-color: #1e40af; color: #ffffff;
                        font-weight: bold; letter-spacing: .03em; }
        .res-entrada  { background-color: #d1fae5; }
        .res-salida   { background-color: #fee2e2; }
        .res-ajuste   { background-color: #fef3c7; }
        .res-total    { background-color: #e0e7ff; font-weight: bold; }
        .res-diferencia { background-color: #f0fdf4; font-weight: bold; }

        /* ── Cabecera de la tabla de detalle ─────────────── */
        .col-hdr { background-color: #1e3a5f; color: #ffffff; font-weight: bold;
                   text-align: center; white-space: nowrap; font-size: 10px;
                   text-transform: uppercase; letter-spacing: .04em; }

        /* ── Filas por tipo ──────────────────────────────── */
        .row-entrada      { background-color: #f0fdf4; }
        .row-entrada-alt  { background-color: #dcfce7; }
        .row-salida       { background-color: #fff5f5; }
        .row-salida-alt   { background-color: #fee2e2; }
        .row-ajuste       { background-color: #fffbeb; }
        .row-ajuste-alt   { background-color: #fef3c7; }

        /* ── Fila de totales ─────────────────────────────── */
        .row-totals { background-color: #1e3a5f; color: #ffffff;
                      font-weight: bold; font-size: 11px; }

        /* ── Alineaciones y utilidades ───────────────────── */
        .text-right  { text-align: right; }
        .text-center { text-align: center; }
        .mono        { font-family: 'Courier New', monospace; font-size: 10px; }
        .muted       { color: #6b7280; font-style: italic; }
        .val-pos     { color: #065f46; font-weight: bold; }
        .val-neg     { color: #991b1b; font-weight: bold; }
        .val-neu     { color: #92400e; font-weight: bold; }
    </style>
</head>
<body>

<!-- ╔══════════════════════════════════════════════════════════════════════╗ -->
<!--   ENCABEZADO GENERAL                                                     -->
<!-- ╚══════════════════════════════════════════════════════════════════════╝ -->
<table>
    <tr>
        <td colspan="15" class="hdr-titulo">
            REPORTE DE MOVIMIENTOS DE INVENTARIO
        </td>
    </tr>
    <tr class="hdr-meta">
        <td colspan="5"><strong>Sistema:</strong> Sistema de Almacén</td>
        <td colspan="5"><strong>Generado:</strong> <?= date('d/m/Y H:i:s') ?></td>
        <td colspan="5"><strong>Usuario:</strong> <?= htmlspecialchars($_SESSION['usuario_nombre'] ?? 'Sistema') ?></td>
    </tr>
    <tr class="hdr-meta">
        <td colspan="15">
            <strong>Filtros aplicados:</strong> <?= htmlspecialchars($filtrosTexto) ?>
        </td>
    </tr>
</table>

<!-- ╔══════════════════════════════════════════════════════════════════════╗ -->
<!--   RESUMEN ESTADÍSTICO                                                    -->
<!-- ╚══════════════════════════════════════════════════════════════════════╝ -->
<table>
    <tr>
        <td colspan="6" class="sec-titulo">RESUMEN DEL PERÍODO</td>
    </tr>
    <tr class="hdr-sub">
        <th>Tipo</th>
        <th class="text-center">Registros</th>
        <th class="text-right">Valor Total (S/)</th>
        <th>Tipo</th>
        <th class="text-center">Registros</th>
        <th class="text-right">Valor Total (S/)</th>
    </tr>
    <tr>
        <td class="res-entrada">↓ <strong>Entradas</strong></td>
        <td class="res-entrada text-center"><?= number_format($totalEntradas) ?></td>
        <td class="res-entrada text-right val-pos">S/ <?= number_format($valorEntradas, 2) ?></td>
        <td class="res-salida">↑ <strong>Salidas</strong></td>
        <td class="res-salida text-center"><?= number_format($totalSalidas) ?></td>
        <td class="res-salida text-right val-neg">S/ <?= number_format($valorSalidas, 2) ?></td>
    </tr>
    <tr>
        <td class="res-ajuste">⇄ <strong>Ajustes</strong></td>
        <td class="res-ajuste text-center"><?= number_format($totalAjustes) ?></td>
        <td class="res-ajuste text-right val-neu">S/ <?= number_format($valorAjustes, 2) ?></td>
        <td class="res-total"><strong>TOTAL MOVIMIENTOS</strong></td>
        <td class="res-total text-center"><strong><?= number_format($totalMovimientos) ?></strong></td>
        <td class="res-total text-right"><strong>S/ <?= number_format($valorEntradas + $valorSalidas + $valorAjustes, 2) ?></strong></td>
    </tr>
    <tr>
        <td colspan="3" class="res-diferencia text-right" style="padding-right:10px;">
            Diferencia neta (Entradas − Salidas): <strong>S/ <?= number_format($valorEntradas - $valorSalidas, 2) ?></strong>
        </td>
        <td colspan="3" class="hdr-meta"></td>
    </tr>
</table>

<!-- ╔══════════════════════════════════════════════════════════════════════╗ -->
<!--   DETALLE DE MOVIMIENTOS                                                 -->
<!-- ╚══════════════════════════════════════════════════════════════════════╝ -->
<table>
    <thead>
        <tr>
            <th class="col-hdr" style="width:90px;">Código</th>
            <th class="col-hdr" style="width:115px;">Fecha / Hora</th>
            <th class="col-hdr" style="width:190px;">Producto</th>
            <th class="col-hdr" style="width:75px;">Cód. Prod.</th>
            <th class="col-hdr" style="width:75px;">Tipo</th>
            <th class="col-hdr text-center" style="width:70px;">Cantidad</th>
            <th class="col-hdr text-right" style="width:85px;">Costo U.</th>
            <th class="col-hdr text-right" style="width:95px;">Total S/</th>
            <th class="col-hdr text-center" style="width:85px;">Stock Ant.</th>
            <th class="col-hdr text-center" style="width:85px;">Stock Nvo.</th>
            <th class="col-hdr" style="width:125px;">Almacén</th>
            <th class="col-hdr" style="width:130px;">Proveedor</th>
            <th class="col-hdr" style="width:110px;">Tipo Doc.</th>
            <th class="col-hdr" style="width:115px;">Nº Documento</th>
            <th class="col-hdr" style="width:115px;">Usuario</th>
            <th class="col-hdr" style="width:200px;">Observaciones</th>
        </tr>
    </thead>
    <tbody>
        <?php if (empty($movimientos)): ?>
            <tr>
                <td colspan="16" class="text-center muted" style="padding:12px;">
                    No se encontraron movimientos con los filtros aplicados.
                </td>
            </tr>
        <?php else: ?>
            <?php
            $cntEntrada = 0; $cntSalida = 0; $cntAjuste = 0;  // contadores por tipo para alternancia
            foreach ($movimientos as $m):
                $tipo    = $m['tipo_movimiento'] ?? 'otro';
                $total   = (float)($m['costo_total'] ?? ((float)$m['cantidad'] * (float)$m['costo_unitario']));

                // Signo y clase por tipo
                switch ($tipo) {
                    case 'entrada':
                        $signo    = '+';
                        $cntEntrada++;
                        $rowClass = $cntEntrada % 2 === 0 ? 'row-entrada-alt' : 'row-entrada';
                        $tipoLbl  = '↓ ENTRADA';
                        $valClass = 'val-pos';
                        break;
                    case 'salida':
                        $signo    = '−';
                        $cntSalida++;
                        $rowClass = $cntSalida % 2 === 0 ? 'row-salida-alt' : 'row-salida';
                        $tipoLbl  = '↑ SALIDA';
                        $valClass = 'val-neg';
                        break;
                    default:
                        $signo    = '±';
                        $cntAjuste++;
                        $rowClass = $cntAjuste % 2 === 0 ? 'row-ajuste-alt' : 'row-ajuste';
                        $tipoLbl  = '⇄ AJUSTE';
                        $valClass = 'val-neu';
                }
            ?>
            <tr class="<?= $rowClass ?>">
                <td class="mono"><?= htmlspecialchars($m['codigo'] ?? '—') ?></td>
                <td class="text-center">
                    <?= date('d/m/Y H:i', strtotime($m['fecha_movimiento'])) ?>
                </td>
                <td><?= htmlspecialchars($m['producto_nombre'] ?? '—') ?></td>
                <td class="mono text-center"><?= htmlspecialchars($m['producto_codigo'] ?? '—') ?></td>
                <td class="text-center"><strong><?= $tipoLbl ?></strong></td>
                <td class="text-center <?= $valClass ?>">
                    <strong><?= $signo . number_format((float)$m['cantidad'], 0) ?></strong>
                </td>
                <td class="text-right">S/ <?= number_format((float)$m['costo_unitario'], 2) ?></td>
                <td class="text-right <?= $valClass ?>">
                    <strong>S/ <?= number_format($total, 2) ?></strong>
                </td>
                <td class="text-center"><?= number_format((float)($m['stock_anterior'] ?? 0), 0) ?></td>
                <td class="text-center"><?= number_format((float)($m['stock_nuevo'] ?? 0), 0) ?></td>
                <td><?= htmlspecialchars($m['almacen_nombre'] ?? '—') ?></td>
                <td><?= htmlspecialchars($m['proveedor_nombre'] ?? '—') ?></td>
                <td class="text-center"><?= htmlspecialchars($m['documento_tipo'] ?? '—') ?></td>
                <td class="mono"><?= htmlspecialchars($m['documento_numero'] ?? '—') ?></td>
                <td><?= htmlspecialchars($m['usuario_nombre'] ?? '—') ?></td>
                <td class="muted"><?= htmlspecialchars($m['observaciones'] ?? '') ?></td>
            </tr>
            <?php endforeach; ?>

            <!-- ── Fila de totales ──────────────────────────────────────── -->
            <tr class="row-totals">
                <td colspan="5" class="text-right" style="padding-right:10px; letter-spacing:.02em;">
                    TOTALES DEL PERÍODO
                </td>
                <td class="text-center">
                    <strong><?= number_format($sumCantidad, 0) ?> uds.</strong>
                </td>
                <td class="text-right">—</td>
                <td class="text-right">
                    <strong>S/ <?= number_format($sumValorTotal, 2) ?></strong>
                </td>
                <td colspan="8" class="text-center" style="font-size:10px;opacity:.8;">
                    <?= $totalMovimientos ?> registro<?= $totalMovimientos !== 1 ? 's' : '' ?> exportado<?= $totalMovimientos !== 1 ? 's' : '' ?>
                    &nbsp;|&nbsp;
                    Entradas: <?= $totalEntradas ?> &nbsp;·&nbsp;
                    Salidas: <?= $totalSalidas ?> &nbsp;·&nbsp;
                    Ajustes: <?= $totalAjustes ?>
                </td>
            </tr>
        <?php endif; ?>
    </tbody>
</table>

<!-- ── Pie de página ──────────────────────────────────────────────────────── -->
<table>
    <tr>
        <td colspan="16" style="border:none; text-align:center; padding:16px;
                                 color:#6b7280; font-size:10px;">
            Reporte generado por <strong>Sistema de Almacén</strong>
            &nbsp;—&nbsp; <?= date('d/m/Y H:i:s') ?>
            &nbsp;—&nbsp; Usuario: <?= htmlspecialchars($_SESSION['usuario_nombre'] ?? 'Sistema') ?>
        </td>
    </tr>
</table>

</body>
</html>