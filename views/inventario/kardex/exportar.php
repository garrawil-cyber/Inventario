<?php
/**
 * views/inventario/kardex/exportar.php
 * Se sirve con headers de descarga .xls (ver KardexController::exportar()).
 * Variables: $producto, $kardex, $fechaInicio, $fechaFin
 */
?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Kardex - <?= htmlspecialchars($producto['nombre']) ?></title>
</head>
<body>
<table border="1">
    <tr>
        <td colspan="10" style="font-size:16px;font-weight:bold;">
            KARDEX VALORIZADO — <?= htmlspecialchars($producto['nombre']) ?> (<?= htmlspecialchars($producto['codigo']) ?>)
        </td>
    </tr>
    <tr>
        <td colspan="10">Período: <?= htmlspecialchars($fechaInicio) ?> al <?= htmlspecialchars($fechaFin) ?> — Generado: <?= date('d/m/Y H:i') ?></td>
    </tr>
    <tr><td colspan="10"></td></tr>
    <tr style="font-weight:bold;background:#f0f4f8;">
        <td>Fecha</td>
        <td>Tipo</td>
        <td>Documento</td>
        <td>Entrada Cant.</td>
        <td>Entrada C.Unit.</td>
        <td>Entrada Total</td>
        <td>Salida Cant.</td>
        <td>Salida C.Unit.</td>
        <td>Salida Total</td>
        <td>Saldo Cant.</td>
        <td>Saldo C.Unit.</td>
        <td>Saldo Total</td>
    </tr>
    <?php foreach ($kardex as $k): ?>
    <tr>
        <td><?= date('d/m/Y H:i', strtotime($k['fecha_movimiento'])) ?></td>
        <td><?= ucfirst($k['tipo_movimiento']) ?></td>
        <td><?= htmlspecialchars(trim(($k['documento_tipo'] ?? '') . ' ' . ($k['documento_numero'] ?? ''))) ?></td>
        <td><?= $k['entrada_cantidad'] ?></td>
        <td><?= number_format($k['entrada_costo_unitario'], 2) ?></td>
        <td><?= number_format($k['entrada_costo_total'], 2) ?></td>
        <td><?= $k['salida_cantidad'] ?></td>
        <td><?= number_format($k['salida_costo_unitario'], 2) ?></td>
        <td><?= number_format($k['salida_costo_total'], 2) ?></td>
        <td><?= $k['saldo_cantidad'] ?></td>
        <td><?= number_format($k['saldo_costo_unitario'], 2) ?></td>
        <td><?= number_format($k['saldo_costo_total'], 2) ?></td>
    </tr>
    <?php endforeach; ?>
</table>
</body>
</html>