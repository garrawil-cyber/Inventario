<?php 
$currentPage = 'reportes';
include __DIR__ . '/../../layouts/inventario_layout.php'; 

// ─────────────────────────────────────────────────────────────────────────────
// CORRECCIÓN: Normalizar array $valorizacion y calcular campos faltantes
// El SQL de Reporte::getValorizacionInventario() agrupa por almacén y retorna
// 'almacen', 'ganancia_potencial'; la vista espera 'categoria', 'utilidad_potencial'
// y 'margen_porcentaje'. Los calculamos aquí de forma defensiva.
// ─────────────────────────────────────────────────────────────────────────────
$valorizacion = array_map(function($item) {
    // Compatibilidad de nombres de campo entre versiones del SQL
    $item['categoria']          = $item['categoria']          ?? $item['almacen']           ?? 'Sin categoría';
    $item['utilidad_potencial'] = $item['utilidad_potencial'] ?? $item['ganancia_potencial'] ?? 0;
    $item['total_productos']    = $item['total_productos']    ?? 0;
    $item['total_unidades']     = $item['total_unidades']     ?? 0;
    $item['valor_compra']       = (float)($item['valor_compra'] ?? 0);
    $item['valor_venta']        = (float)($item['valor_venta']  ?? 0);
    $item['utilidad_potencial'] = (float)$item['utilidad_potencial'];

    // Calcular margen_porcentaje si no viene del SQL
    if (!isset($item['margen_porcentaje'])) {
        $item['margen_porcentaje'] = $item['valor_compra'] > 0
            ? ($item['utilidad_potencial'] / $item['valor_compra']) * 100
            : 0;
    }
    return $item;
}, $valorizacion ?? []);

// ─────────────────────────────────────────────────────────────────────────────
// CORRECCIÓN: Calcular $totales si el controlador no lo pasa
// ─────────────────────────────────────────────────────────────────────────────
if (!isset($totales) || empty($totales)) {
    $totales = [
        'valor_compra'       => array_sum(array_column($valorizacion, 'valor_compra')),
        'valor_venta'        => array_sum(array_column($valorizacion, 'valor_venta')),
        'utilidad_potencial' => array_sum(array_column($valorizacion, 'utilidad_potencial')),
    ];
}

// ─────────────────────────────────────────────────────────────────────────────
// CORRECCIÓN: Calcular $margenPromedio una sola vez (se usa en tarjeta Y en tfoot)
// ─────────────────────────────────────────────────────────────────────────────
$margenPromedio = ($totales['valor_compra'] ?? 0) > 0
    ? (($totales['utilidad_potencial'] ?? 0) / $totales['valor_compra']) * 100
    : 0;
?>

<div class="container-fluid">
    <!-- Encabezado -->
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h1 class="h3 mb-0">Reporte de Valorización de Inventario</h1>
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb mb-0">
                    <li class="breadcrumb-item"><a href="index.php?controller=dashboard&action=index">Dashboard</a></li>
                    <li class="breadcrumb-item"><a href="index.php?controller=reporte&action=dashboard">Reportes</a></li>
                    <li class="breadcrumb-item active">Valorización</li>
                </ol>
            </nav>
        </div>
        <div class="btn-group">
            <button onclick="window.print()" class="btn btn-secondary">
                <i class="fas fa-print me-1"></i> Imprimir
            </button>
            <button class="btn btn-outline-primary" data-bs-toggle="modal" data-bs-target="#modalFiltros">
                <i class="fas fa-filter me-1"></i> Filtros
            </button>
        </div>
    </div>

    <!-- Filtros (Modal) -->
    <div class="modal fade" id="modalFiltros" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Filtrar Reporte</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form method="GET" action="">
                    <div class="modal-body">
                        <input type="hidden" name="controller" value="reporte">
                        <input type="hidden" name="action" value="valorizacion">
                        
                        <div class="mb-3">
                            <label class="form-label">Almacén</label>
                            <select class="form-select" name="id_almacen">
                                <option value="">Todos los almacenes</option>
                                <?php foreach ($almacenes ?? [] as $almacen): ?>
                                    <option value="<?= $almacen['id_almacen'] ?>"
                                        <?= ($_GET['id_almacen'] ?? '') == $almacen['id_almacen'] ? 'selected' : '' ?>>
                                        <?= htmlspecialchars($almacen['nombre']) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        
                        <div class="mb-3">
                            <label class="form-label">Categoría</label>
                            <select class="form-select" name="id_categoria">
                                <option value="">Todas las categorías</option>
                                <?php foreach ($categorias ?? [] as $categoria): ?>
                                    <option value="<?= $categoria['id_categoria'] ?>"
                                        <?= ($_GET['id_categoria'] ?? '') == $categoria['id_categoria'] ? 'selected' : '' ?>>
                                        <?= htmlspecialchars($categoria['nombre']) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                        <button type="submit" class="btn btn-primary">Aplicar Filtros</button>
                        <a href="index.php?controller=reporte&action=valorizacion" class="btn btn-outline-danger">
                            Limpiar
                        </a>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Resumen de totales -->
    <div class="row mb-4">
        <div class="col-md-3">
            <div class="card border-start-primary border-start-3">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h6 class="text-primary">Valor de Compra</h6>
                            <h3 class="mb-0">S/ <?= number_format($totales['valor_compra'] ?? 0, 2) ?></h3>
                        </div>
                        <i class="fas fa-shopping-cart fa-2x text-primary opacity-50"></i>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="col-md-3">
            <div class="card border-start-success border-start-3">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h6 class="text-success">Valor de Venta</h6>
                            <h3 class="mb-0">S/ <?= number_format($totales['valor_venta'] ?? 0, 2) ?></h3>
                        </div>
                        <i class="fas fa-tags fa-2x text-success opacity-50"></i>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="col-md-3">
            <div class="card border-start-info border-start-3">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h6 class="text-info">Utilidad Potencial</h6>
                            <h3 class="mb-0">S/ <?= number_format($totales['utilidad_potencial'] ?? 0, 2) ?></h3>
                        </div>
                        <i class="fas fa-chart-line fa-2x text-info opacity-50"></i>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="col-md-3">
            <div class="card border-start-warning border-start-3">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h6 class="text-warning">Margen Promedio</h6>
                            <!-- CORRECCIÓN: $margenPromedio ya calculado arriba, sin riesgo de división por cero -->
                            <h3 class="mb-0"><?= number_format($margenPromedio, 2) ?>%</h3>
                        </div>
                        <i class="fas fa-percentage fa-2x text-warning opacity-50"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Información del reporte -->
    <div class="card mb-4">
        <div class="card-body">
            <div class="row">
                <div class="col-md-4">
                    <small class="text-muted d-block">Fecha de valorización</small>
                    <div class="fw-bold"><?= date('d/m/Y H:i') ?></div>
                </div>
                <div class="col-md-4">
                    <small class="text-muted d-block">Criterios aplicados</small>
                    <div class="fw-bold">
                        <?php 
                        $criterios = [];
                        if ($_GET['id_almacen'] ?? false) {
                            $criterios[] = 'Almacén específico';
                        }
                        if ($_GET['id_categoria'] ?? false) {
                            $criterios[] = 'Categoría específica';
                        }
                        echo $criterios ? implode(', ', $criterios) : 'Todos los productos';
                        ?>
                    </div>
                </div>
                <div class="col-md-4">
                    <small class="text-muted d-block">Total registros</small>
                    <div class="fw-bold"><?= count($valorizacion) ?> registros</div>
                </div>
            </div>
        </div>
    </div>

    <!-- Tabla de valorización -->
    <div class="card">
        <div class="card-header">
            <div class="d-flex justify-content-between align-items-center">
                <h5 class="mb-0">Valorización por Almacén</h5>
                <span class="badge bg-primary">
                    <?= count($valorizacion) ?> registros
                </span>
            </div>
        </div>
        <div class="card-body p-0">
            <?php if (empty($valorizacion)): ?>
                <div class="text-center py-5">
                    <i class="fas fa-chart-pie fa-4x text-muted mb-3"></i>
                    <h5 class="text-muted">No hay datos para mostrar</h5>
                    <p class="text-muted mb-4">Intente con otros filtros</p>
                </div>
            <?php else: ?>
                <div class="table-responsive">
                    <table class="table table-hover mb-0">
                        <thead class="table-light">
                            <tr>
                                <th>Almacén / Categoría</th>
                                <th class="text-end">Productos</th>
                                <th class="text-end">Unidades</th>
                                <th class="text-end">Valor Compra</th>
                                <th class="text-end">Valor Venta</th>
                                <th class="text-end">Utilidad</th>
                                <th class="text-end">Margen</th>
                                <th class="text-end">% del Total</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($valorizacion as $item): ?>
                                <?php
                                // CORRECCIÓN: calcular porcentajeTotal de forma segura
                                $porcentajeTotal = ($totales['valor_venta'] ?? 0) > 0
                                    ? ($item['valor_venta'] / $totales['valor_venta']) * 100
                                    : 0;

                                // CORRECCIÓN: usar $item['margen_porcentaje'] ya calculado arriba
                                $margen = (float)($item['margen_porcentaje'] ?? 0);

                                // Color según margen
                                if ($margen > 50) {
                                    $colorMargen = 'text-success';
                                } elseif ($margen > 30) {
                                    $colorMargen = 'text-info';
                                } elseif ($margen > 15) {
                                    $colorMargen = 'text-warning';
                                } else {
                                    $colorMargen = 'text-danger';
                                }
                                ?>
                                <tr>
                                    <td>
                                        <div class="fw-bold"><?= htmlspecialchars($item['categoria']) ?></div>
                                    </td>
                                    <td class="text-end"><?= number_format((int)$item['total_productos']) ?></td>
                                    <td class="text-end"><?= number_format((int)$item['total_unidades']) ?></td>
                                    <td class="text-end">
                                        <div class="fw-bold">S/ <?= number_format($item['valor_compra'], 2) ?></div>
                                        <small class="text-muted">
                                            S/ <?= $item['total_unidades'] > 0
                                                ? number_format($item['valor_compra'] / $item['total_unidades'], 2)
                                                : '0.00' ?> c/u
                                        </small>
                                    </td>
                                    <td class="text-end">
                                        <div class="fw-bold text-success">S/ <?= number_format($item['valor_venta'], 2) ?></div>
                                        <small class="text-muted">
                                            S/ <?= $item['total_unidades'] > 0
                                                ? number_format($item['valor_venta'] / $item['total_unidades'], 2)
                                                : '0.00' ?> c/u
                                        </small>
                                    </td>
                                    <td class="text-end">
                                        <div class="fw-bold text-info">S/ <?= number_format($item['utilidad_potencial'], 2) ?></div>
                                        <small class="text-muted">
                                            S/ <?= $item['total_unidades'] > 0
                                                ? number_format($item['utilidad_potencial'] / $item['total_unidades'], 2)
                                                : '0.00' ?> c/u
                                        </small>
                                    </td>
                                    <td class="text-end">
                                        <span class="fw-bold <?= $colorMargen ?>">
                                            <?= number_format($margen, 2) ?>%
                                        </span>
                                    </td>
                                    <td class="text-end">
                                        <div class="fw-bold"><?= number_format($porcentajeTotal, 2) ?>%</div>
                                        <div class="progress mt-1" style="height: 4px;">
                                            <div class="progress-bar bg-primary" 
                                                 style="width: <?= min($porcentajeTotal, 100) ?>%"></div>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                        <tfoot class="table-light">
                            <tr class="fw-bold">
                                <td>TOTALES</td>
                                <td class="text-end"><?= array_sum(array_column($valorizacion, 'total_productos')) ?></td>
                                <td class="text-end"><?= number_format(array_sum(array_column($valorizacion, 'total_unidades'))) ?></td>
                                <td class="text-end">S/ <?= number_format($totales['valor_compra'] ?? 0, 2) ?></td>
                                <td class="text-end">S/ <?= number_format($totales['valor_venta'] ?? 0, 2) ?></td>
                                <td class="text-end">S/ <?= number_format($totales['utilidad_potencial'] ?? 0, 2) ?></td>
                                <!-- CORRECCIÓN: $margenPromedio ya está calculado antes del HTML -->
                                <td class="text-end"><?= number_format($margenPromedio, 2) ?>%</td>
                                <td class="text-end">100%</td>
                            </tr>
                        </tfoot>
                    </table>
                </div>
            <?php endif; ?>
        </div>
        <div class="card-footer">
            <div class="row">
                <div class="col-md-6">
                    <small class="text-muted">
                        <i class="fas fa-info-circle me-1"></i>
                        Valores calculados en base a stock actual y precios vigentes.
                    </small>
                </div>
                <div class="col-md-6 text-end">
                    <small class="text-muted">
                        Método de valorización: Costo promedio ponderado
                    </small>
                </div>
            </div>
        </div>
    </div>

    <!-- Gráficos -->
    <div class="row mt-4">
        <div class="col-md-6">
            <div class="card">
                <div class="card-header">
                    <h6 class="mb-0">Distribución por Almacén (Valor Venta)</h6>
                </div>
                <div class="card-body">
                    <div style="height: 300px;">
                        <canvas id="graficoDonut"></canvas>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="col-md-6">
            <div class="card">
                <div class="card-header">
                    <h6 class="mb-0">Comparación Valor Compra vs Venta</h6>
                </div>
                <div class="card-body">
                    <div style="height: 300px;">
                        <canvas id="graficoBarras"></canvas>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Análisis por margen -->
    <div class="card mt-4">
        <div class="card-header">
            <h6 class="mb-0">Análisis por Nivel de Margen</h6>
        </div>
        <div class="card-body">
            <div class="row text-center">
                <div class="col-md-3">
                    <div class="card border-success">
                        <div class="card-body">
                            <h6 class="text-success">Alto Margen (&gt;50%)</h6>
                            <h3 class="mb-0">
                                <?= count(array_filter($valorizacion, function($v) { return ($v['margen_porcentaje'] ?? 0) > 50; })) ?>
                            </h3>
                            <small class="text-muted">registros</small>
                        </div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="card border-info">
                        <div class="card-body">
                            <h6 class="text-info">Buen Margen (30-50%)</h6>
                            <h3 class="mb-0">
                                <?= count(array_filter($valorizacion, function($v) { return ($v['margen_porcentaje'] ?? 0) >= 30 && ($v['margen_porcentaje'] ?? 0) <= 50; })) ?>
                            </h3>
                            <small class="text-muted">registros</small>
                        </div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="card border-warning">
                        <div class="card-body">
                            <h6 class="text-warning">Margen Regular (15-30%)</h6>
                            <h3 class="mb-0">
                                <?= count(array_filter($valorizacion, function($v) { return ($v['margen_porcentaje'] ?? 0) >= 15 && ($v['margen_porcentaje'] ?? 0) < 30; })) ?>
                            </h3>
                            <small class="text-muted">registros</small>
                        </div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="card border-danger">
                        <div class="card-body">
                            <h6 class="text-danger">Bajo Margen (&lt;15%)</h6>
                            <h3 class="mb-0">
                                <?= count(array_filter($valorizacion, function($v) { return ($v['margen_porcentaje'] ?? 0) < 15; })) ?>
                            </h3>
                            <small class="text-muted">registros</small>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Scripts para gráficos -->
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function() {
    // CORRECCIÓN: datos ya normalizados desde PHP
    const etiquetas   = <?= json_encode(array_column($valorizacion, 'categoria')) ?>;
    const valorVenta  = <?= json_encode(array_map('floatval', array_column($valorizacion, 'valor_venta'))) ?>;
    const valorCompra = <?= json_encode(array_map('floatval', array_column($valorizacion, 'valor_compra'))) ?>;
    const margenes    = <?= json_encode(array_map('floatval', array_column($valorizacion, 'margen_porcentaje'))) ?>;
    
    const colores = [
        '#4e73df', '#1cc88a', '#36b9cc', '#f6c23e', '#e74a3b',
        '#858796', '#6f42c1', '#20c9a6', '#fd7e14', '#e83e8c'
    ];
    
    // Gráfico de donut
    const ctxDonut = document.getElementById('graficoDonut').getContext('2d');
    new Chart(ctxDonut, {
        type: 'doughnut',
        data: {
            labels: etiquetas,
            datasets: [{
                data: valorVenta,
                backgroundColor: colores,
                borderWidth: 1
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: {
                    position: 'right',
                    labels: { boxWidth: 12, padding: 15 }
                },
                tooltip: {
                    callbacks: {
                        label: function(context) {
                            const label = context.label || '';
                            const value = context.raw || 0;
                            const total = context.dataset.data.reduce((a, b) => a + b, 0);
                            const pct   = total > 0 ? Math.round((value / total) * 100) : 0;
                            return `${label}: S/ ${value.toFixed(2)} (${pct}%)`;
                        }
                    }
                }
            }
        }
    });
    
    // Gráfico de barras comparativo
    const ctxBarras = document.getElementById('graficoBarras').getContext('2d');
    new Chart(ctxBarras, {
        type: 'bar',
        data: {
            labels: etiquetas,
            datasets: [
                {
                    label: 'Valor Compra',
                    data: valorCompra,
                    backgroundColor: '#4e73df',
                    borderColor: '#4e73df',
                    borderWidth: 1
                },
                {
                    label: 'Valor Venta',
                    data: valorVenta,
                    backgroundColor: '#1cc88a',
                    borderColor: '#1cc88a',
                    borderWidth: 1
                }
            ]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            scales: {
                y: {
                    beginAtZero: true,
                    ticks: {
                        callback: function(value) {
                            return 'S/ ' + value.toLocaleString();
                        }
                    }
                }
            },
            plugins: {
                tooltip: {
                    callbacks: {
                        label: function(context) {
                            return `${context.dataset.label}: S/ ${context.raw.toFixed(2)}`;
                        }
                    }
                }
            }
        }
    });
});
</script>

<!-- Estilos para impresión -->
<style media="print">
    .sidebar, .navbar, .card-header .btn-group, .card-footer, .row.mt-4, .modal {
        display: none !important;
    }
    .container-fluid { padding: 0 !important; }
    .card { border: none !important; box-shadow: none !important; }
    .table { font-size: 11px !important; }
    h1 { font-size: 16px !important; }
    @page { size: landscape; margin: 0.5cm; }
</style>

<?php include __DIR__ . '/../../layouts/footer.php'; ?>