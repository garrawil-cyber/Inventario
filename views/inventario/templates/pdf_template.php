
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Kardex - <?= htmlspecialchars($producto['codigo']) ?></title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: Arial, sans-serif;
            font-size: 9pt;
            color: #333;
        }
        
        .header {
            text-align: center;
            margin-bottom: 20px;
            padding: 15px;
            background-color: #2196F3;
            color: white;
        }
        
        .header h1 {
            font-size: 18pt;
            margin-bottom: 5px;
        }
        
        .header p {
            font-size: 10pt;
        }
        
        .info-section {
            margin-bottom: 15px;
            border: 1px solid #ddd;
            padding: 10px;
            background-color: #f9f9f9;
        }
        
        .info-section h2 {
            font-size: 12pt;
            color: #1976D2;
            border-bottom: 2px solid #1976D2;
            padding-bottom: 5px;
            margin-bottom: 10px;
        }
        
        .info-grid {
            display: table;
            width: 100%;
        }
        
        .info-row {
            display: table-row;
        }
        
        .info-label {
            display: table-cell;
            font-weight: bold;
            padding: 5px;
            width: 20%;
            background-color: #E3F2FD;
        }
        
        .info-value {
            display: table-cell;
            padding: 5px;
            width: 30%;
        }
        
        .stats-section {
            margin-bottom: 15px;
            background-color: #FFF9C4;
            padding: 10px;
            border: 1px solid #F9A825;
        }
        
        .stats-section h3 {
            font-size: 11pt;
            color: #F57F17;
            margin-bottom: 8px;
        }
        
        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 10px;
            font-size: 8pt;
        }
        
        table thead {
            background-color: #424242;
            color: white;
        }
        
        table th, table td {
            border: 1px solid #ddd;
            padding: 6px;
            text-align: left;
        }
        
        table th {
            font-weight: bold;
            font-size: 8pt;
        }
        
        .text-center {
            text-align: center !important;
        }
        
        .text-right {
            text-align: right !important;
        }
        
        .entrada {
            background-color: #C8E6C9;
        }
        
        .salida {
            background-color: #FFCDD2;
        }
        
        .saldo-inicial,
        .saldo-final {
            background-color: #E0E0E0;
            font-weight: bold;
        }
        
        .badge {
            display: inline-block;
            padding: 3px 8px;
            border-radius: 3px;
            font-size: 7pt;
            font-weight: bold;
        }
        
        .badge-success {
            background-color: #4CAF50;
            color: white;
        }
        
        .badge-danger {
            background-color: #F44336;
            color: white;
        }
        
        .badge-info {
            background-color: #2196F3;
            color: white;
        }
        
        .footer {
            margin-top: 20px;
            padding-top: 10px;
            border-top: 1px solid #ddd;
            text-align: center;
            font-size: 8pt;
            color: #666;
        }
        
        .page-break {
            page-break-after: always;
        }
    </style>
</head>
<body>
    <!-- Encabezado -->
    <div class="header">
        <h1>KARDEX DE INVENTARIO</h1>
        <p>Reporte Detallado de Movimientos</p>
        <p>Generado el: <?= date('d/m/Y H:i:s') ?></p>
    </div>

    <!-- Información del Producto -->
    <div class="info-section">
        <h2>INFORMACIÓN DEL PRODUCTO</h2>
        <div class="info-grid">
            <div class="info-row">
                <div class="info-label">Código:</div>
                <div class="info-value"><?= htmlspecialchars($producto['codigo']) ?></div>
                <div class="info-label">SKU:</div>
                <div class="info-value"><?= htmlspecialchars($producto['sku'] ?? 'N/A') ?></div>
            </div>
            <div class="info-row">
                <div class="info-label">Nombre:</div>
                <div class="info-value" colspan="3"><?= htmlspecialchars($producto['nombre']) ?></div>
            </div>
            <div class="info-row">
                <div class="info-label">Categoría:</div>
                <div class="info-value"><?= htmlspecialchars($producto['categoria_nombre'] ?? 'N/A') ?></div>
                <div class="info-label">Marca:</div>
                <div class="info-value"><?= htmlspecialchars($producto['marca_nombre'] ?? 'N/A') ?></div>
            </div>
            <div class="info-row">
                <div class="info-label">Stock Actual:</div>
                <div class="info-value"><?= number_format($producto['stock_actual']) ?> unidades</div>
                <div class="info-label">Almacén:</div>
                <div class="info-value"><?= htmlspecialchars($producto['almacen_nombre'] ?? 'N/A') ?></div>
            </div>
        </div>
    </div>

    <!-- Resumen Estadístico -->
    <div class="stats-section">
        <h3>RESUMEN DEL PERÍODO: <?= date('d/m/Y', strtotime($fechaInicio)) ?> al <?= date('d/m/Y', strtotime($fechaFin)) ?></h3>
        <div class="info-grid">
            <div class="info-row">
                <div class="info-label">Total Entradas:</div>
                <div class="info-value"><?= number_format($resumen['total_entradas'] ?? 0) ?> unidades</div>
                <div class="info-label">Valor Entradas:</div>
                <div class="info-value">S/ <?= number_format($resumen['valor_entradas'] ?? 0, 2) ?></div>
            </div>
            <div class="info-row">
                <div class="info-label">Total Salidas:</div>
                <div class="info-value"><?= number_format($resumen['total_salidas'] ?? 0) ?> unidades</div>
                <div class="info-label">Valor Salidas:</div>
                <div class="info-value">S/ <?= number_format($resumen['valor_salidas'] ?? 0, 2) ?></div>
            </div>
            <div class="info-row">
                <div class="info-label">Movimientos:</div>
                <div class="info-value"><?= number_format($resumen['total_movimientos'] ?? 0) ?></div>
                <div class="info-label">Costo Promedio:</div>
                <div class="info-value">S/ <?= number_format($resumen['costo_promedio'] ?? 0, 2) ?></div>
            </div>
        </div>
    </div>

    <!-- Detalle del Kardex -->
    <table>
        <thead>
            <tr>
                <th style="width: 70px;">Fecha</th>
                <th style="width: 50px;">Tipo</th>
                <th style="width: 70px;">Doc.</th>
                <th style="width: 40px;" class="text-center">Ent.<br>Cant.</th>
                <th style="width: 50px;" class="text-right">Ent.<br>C.U.</th>
                <th style="width: 55px;" class="text-right">Ent.<br>Total</th>
                <th style="width: 40px;" class="text-center">Sal.<br>Cant.</th>
                <th style="width: 50px;" class="text-right">Sal.<br>C.U.</th>
                <th style="width: 55px;" class="text-right">Sal.<br>Total</th>
                <th style="width: 40px;" class="text-center">Saldo<br>Cant.</th>
                <th style="width: 50px;" class="text-right">Saldo<br>C.U.</th>
                <th style="width: 55px;" class="text-right">Saldo<br>Total</th>
            </tr>
        </thead>
        <tbody>
            <!-- Saldo Inicial -->
            <tr class="saldo-inicial">
                <td><?= date('d/m/Y', strtotime($fechaInicio)) ?></td>
                <td colspan="2" class="text-center">SALDO INICIAL</td>
                <td class="text-center">-</td>
                <td class="text-right">-</td>
                <td class="text-right">-</td>
                <td class="text-center">-</td>
                <td class="text-right">-</td>
                <td class="text-right">-</td>
                <td class="text-center"><?= number_format($resumen['saldo_inicial_cantidad'] ?? 0) ?></td>
                <td class="text-right">S/ <?= number_format($producto['precio_compra'], 2) ?></td>
                <td class="text-right">S/ <?= number_format(($resumen['saldo_inicial_cantidad'] ?? 0) * $producto['precio_compra'], 2) ?></td>
            </tr>

            <!-- Movimientos -->
            <?php if (!empty($kardex)): ?>
                <?php foreach ($kardex as $movimiento): ?>
                <tr class="<?= $movimiento['tipo_movimiento'] == 'entrada' ? 'entrada' : 'salida' ?>">
                    <td style="font-size: 7pt;"><?= date('d/m/Y H:i', strtotime($movimiento['fecha_movimiento'])) ?></td>
                    <td class="text-center">
                        <span class="badge badge-<?= $movimiento['tipo_movimiento'] == 'entrada' ? 'success' : 'danger' ?>">
                            <?= strtoupper($movimiento['tipo_movimiento']) ?>
                        </span>
                    </td>
                    <td style="font-size: 7pt;"><?= htmlspecialchars(substr($movimiento['documento_referencia'] ?? 'Sin doc.', 0, 12)) ?></td>
                    
                    <!-- Entrada -->
                    <td class="text-center">
                        <?= $movimiento['tipo_movimiento'] == 'entrada' ? number_format($movimiento['entrada_cantidad']) : '-' ?>
                    </td>
                    <td class="text-right">
                        <?= $movimiento['tipo_movimiento'] == 'entrada' ? number_format($movimiento['entrada_costo_unitario'], 2) : '-' ?>
                    </td>
                    <td class="text-right">
                        <?= $movimiento['tipo_movimiento'] == 'entrada' ? number_format($movimiento['entrada_costo_total'], 2) : '-' ?>
                    </td>
                    
                    <!-- Salida -->
                    <td class="text-center">
                        <?= $movimiento['tipo_movimiento'] == 'salida' ? number_format($movimiento['salida_cantidad']) : '-' ?>
                    </td>
                    <td class="text-right">
                        <?= $movimiento['tipo_movimiento'] == 'salida' ? number_format($movimiento['salida_costo_unitario'], 2) : '-' ?>
                    </td>
                    <td class="text-right">
                        <?= $movimiento['tipo_movimiento'] == 'salida' ? number_format($movimiento['salida_costo_total'], 2) : '-' ?>
                    </td>
                    
                    <!-- Saldo -->
                    <td class="text-center"><strong><?= number_format($movimiento['saldo_cantidad']) ?></strong></td>
                    <td class="text-right"><?= number_format($movimiento['saldo_costo_unitario'], 2) ?></td>
                    <td class="text-right"><strong><?= number_format($movimiento['saldo_costo_total'], 2) ?></strong></td>
                </tr>
                <?php endforeach; ?>
            <?php else: ?>
                <tr>
                    <td colspan="12" class="text-center" style="padding: 20px; color: #999;">
                        No se encontraron movimientos en el período seleccionado
                    </td>
                </tr>
            <?php endif; ?>

            <!-- Saldo Final -->
            <tr class="saldo-final">
                <td><?= date('d/m/Y', strtotime($fechaFin)) ?></td>
                <td colspan="2" class="text-center">SALDO FINAL</td>
                <td class="text-center"><?= number_format($resumen['total_entradas'] ?? 0) ?></td>
                <td class="text-right">-</td>
                <td class="text-right"><?= number_format($resumen['valor_entradas'] ?? 0, 2) ?></td>
                <td class="text-center"><?= number_format($resumen['total_salidas'] ?? 0) ?></td>
                <td class="text-right">-</td>
                <td class="text-right"><?= number_format($resumen['valor_salidas'] ?? 0, 2) ?></td>
                <td class="text-center"><?= number_format($producto['stock_actual']) ?></td>
                <td class="text-right"><?= number_format($resumen['saldo_final_costo_unitario'] ?? $producto['precio_compra'], 2) ?></td>
                <td class="text-right"><?= number_format($producto['stock_actual'] * ($resumen['saldo_final_costo_unitario'] ?? $producto['precio_compra']), 2) ?></td>
            </tr>
        </tbody>
    </table>

    <!-- Footer -->
    <div class="footer">
        <p><strong>Sistema de Gestión de Inventario</strong></p>
        <p>Reporte generado el <?= date('d/m/Y H:i:s') ?></p>
        <p>Usuario: <?= htmlspecialchars($_SESSION['nombre_completo'] ?? 'Sistema') ?></p>
        <p style="margin-top: 5px; font-size: 7pt; color: #999;">
            Este documento es un reporte oficial del sistema de inventario.<br>
            Método de valorización: Costo Promedio Ponderado
        </p>
    </div>
</body>
</html>
