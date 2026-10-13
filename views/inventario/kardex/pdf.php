<?php
/**
 * views/inventario/kardex/pdf.php
 * Vista imprimible independiente (sin sidebar/navbar). El usuario usa
 * Ctrl+P → "Guardar como PDF" desde el navegador.
 * Variables: $producto, $kardex, $saldoActual, $fechaInicio, $fechaFin
 */

$totalEntradasCant = $totalSalidasCant = 0;
$totalEntradasVal  = $totalSalidasVal  = 0.0;
foreach ($kardex as $k) {
    $totalEntradasCant += (int) $k['entrada_cantidad'];
    $totalEntradasVal  += (float) $k['entrada_costo_total'];
    $totalSalidasCant  += (int) $k['salida_cantidad'];
    $totalSalidasVal   += (float) $k['salida_costo_total'];
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Kardex - <?= htmlspecialchars($producto['nombre']) ?></title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        * { box-sizing: border-box; }
        body {
            font-family: 'Segoe UI', Arial, sans-serif;
            color: #1e293b; margin: 0; padding: 28px 34px;
            font-size: 12.5px;
        }
        .kx-pdf-head {
            display: flex; justify-content: space-between; align-items: flex-start;
            border-bottom: 3px solid #2563eb; padding-bottom: 14px; margin-bottom: 18px;
        }
        .kx-pdf-title { font-size: 20px; font-weight: 800; color: #1e293b; margin: 0; }
        .kx-pdf-sub { font-size: 12px; color: #64748b; margin-top: 4px; }
        .kx-pdf-meta { text-align: right; font-size: 11.5px; color: #64748b; }
        .kx-pdf-info {
            display: grid; grid-template-columns: repeat(4, 1fr); gap: 10px;
            margin-bottom: 18px;
        }
        .kx-pdf-box {
            border: 1px solid #e2e8f0; border-radius: 8px; padding: 10px 12px;
        }
        .kx-pdf-box-label { font-size: 9.5px; text-transform: uppercase; letter-spacing: .05em; color: #64748b; font-weight: 700; }
        .kx-pdf-box-val { font-size: 15px; font-weight: 800; color: #1e293b; margin-top: 3px; }
        table { width: 100%; border-collapse: collapse; font-size: 11px; }
        th, td { border: 1px solid #e2e8f0; padding: 5px 7px; }
        thead th {
            background: #f0f4f8; font-weight: 700; text-transform: uppercase;
            font-size: 9.5px; letter-spacing: .04em; color: #334155; text-align: center;
        }
        .th-entrada { background: #d1fae5 !important; color: #065f46 !important; }
        .th-salida  { background: #fee2e2 !important; color: #991b1b !important; }
        .th-saldo   { background: #dbeafe !important; color: #1e3a8a !important; }
        td.num { text-align: right; font-variant-numeric: tabular-nums; }
        td.center { text-align: center; }
        tfoot td { font-weight: 800; background: #f8fafc; }
        .kx-print-btn {
            position: fixed; top: 16px; right: 16px;
            background: #2563eb; color: #fff; border: none; padding: 10px 18px;
            border-radius: 8px; font-size: 13px; font-weight: 600; cursor: pointer;
        }
        @media print { .kx-print-btn { display: none; } body { padding: 10px 16px; } }
    </style>
</head>
<body>

<button class="kx-print-btn" onclick="window.print()"><i class="fas fa-print"></i> Imprimir / Guardar PDF</button>

<div class="kx-pdf-head">
    <div>
        <p class="kx-pdf-title">Libro Kardex — <?= htmlspecialchars($producto['nombre']) ?></p>
        <p class="kx-pdf-sub"><?= htmlspecialchars($producto['codigo']) ?> · Costo promedio ponderado móvil</p>
    </div>
    <div class="kx-pdf-meta">
        Período: <?= htmlspecialchars($fechaInicio) ?> al <?= htmlspecialchars($fechaFin) ?><br>
        Generado: <?= date('d/m/Y H:i') ?>
    </div>
</div>

<div class="kx-pdf-info">
    <div class="kx-pdf-box">
        <div class="kx-pdf-box-label">Stock actual</div>
        <div class="kx-pdf-box-val"><?= number_format($saldoActual['saldo_cantidad'] ?? $producto['stock_actual'] ?? 0) ?></div>
    </div>
    <div class="kx-pdf-box">
        <div class="kx-pdf-box-label">Costo unit. actual</div>
        <div class="kx-pdf-box-val">S/ <?= number_format($saldoActual['saldo_costo_unitario'] ?? $producto['precio_compra'] ?? 0, 2) ?></div>
    </div>
    <div class="kx-pdf-box">
        <div class="kx-pdf-box-label">Valor total actual</div>
        <div class="kx-pdf-box-val">S/ <?= number_format($saldoActual['saldo_costo_total'] ?? 0, 2) ?></div>
    </div>
    <div class="kx-pdf-box">
        <div class="kx-pdf-box-label">Movimientos</div>
        <div class="kx-pdf-box-val"><?= number_format(count($kardex)) ?></div>
    </div>
</div>

<table>
    <thead>
        <tr>
            <th rowspan="2" style="vertical-align:middle;">Fecha</th>
            <th rowspan="2" style="vertical-align:middle;">Tipo</th>
            <th rowspan="2" style="vertical-align:middle;">Documento</th>
            <th colspan="3" class="th-entrada">Entrada</th>
            <th colspan="3" class="th-salida">Salida</th>
            <th colspan="3" class="th-saldo">Saldo</th>
        </tr>
        <tr>
            <th class="th-entrada">Cant.</th><th class="th-entrada">C.Unit.</th><th class="th-entrada">Total</th>
            <th class="th-salida">Cant.</th><th class="th-salida">C.Unit.</th><th class="th-salida">Total</th>
            <th class="th-saldo">Cant.</th><th class="th-saldo">C.Unit.</th><th class="th-saldo">Total</th>
        </tr>
    </thead>
    <tbody>
    <?php if (empty($kardex)): ?>
        <tr><td colspan="12" style="text-align:center;padding:20px;color:#94a3b8;">Sin movimientos en el período seleccionado.</td></tr>
    <?php else: ?>
        <?php foreach ($kardex as $k): ?>
        <tr>
            <td><?= date('d/m/Y', strtotime($k['fecha_movimiento'])) ?></td>
            <td class="center"><?= ucfirst($k['tipo_movimiento']) ?></td>
            <td><?= htmlspecialchars(trim(($k['documento_tipo'] ?? '') . ' ' . ($k['documento_numero'] ?? ''))) ?: '—' ?></td>
            <td class="num"><?= $k['entrada_cantidad'] > 0 ? number_format($k['entrada_cantidad']) : '—' ?></td>
            <td class="num"><?= $k['entrada_cantidad'] > 0 ? number_format($k['entrada_costo_unitario'], 2) : '—' ?></td>
            <td class="num"><?= $k['entrada_cantidad'] > 0 ? number_format($k['entrada_costo_total'], 2) : '—' ?></td>
            <td class="num"><?= $k['salida_cantidad'] > 0 ? number_format($k['salida_cantidad']) : '—' ?></td>
            <td class="num"><?= $k['salida_cantidad'] > 0 ? number_format($k['salida_costo_unitario'], 2) : '—' ?></td>
            <td class="num"><?= $k['salida_cantidad'] > 0 ? number_format($k['salida_costo_total'], 2) : '—' ?></td>
            <td class="num"><?= number_format($k['saldo_cantidad']) ?></td>
            <td class="num"><?= number_format($k['saldo_costo_unitario'], 2) ?></td>
            <td class="num"><?= number_format($k['saldo_costo_total'], 2) ?></td>
        </tr>
        <?php endforeach; ?>
    <?php endif; ?>
    </tbody>
    <?php if (!empty($kardex)): ?>
    <tfoot>
        <tr>
            <td colspan="3">TOTALES DEL PERÍODO</td>
            <td class="num"><?= number_format($totalEntradasCant) ?></td>
            <td></td>
            <td class="num">S/ <?= number_format($totalEntradasVal, 2) ?></td>
            <td class="num"><?= number_format($totalSalidasCant) ?></td>
            <td></td>
            <td class="num">S/ <?= number_format($totalSalidasVal, 2) ?></td>
            <td class="num"><?= number_format($saldoActual['saldo_cantidad'] ?? 0) ?></td>
            <td></td>
            <td class="num">S/ <?= number_format($saldoActual['saldo_costo_total'] ?? 0, 2) ?></td>
        </tr>
    </tfoot>
    <?php endif; ?>
</table>

</body>
</html>