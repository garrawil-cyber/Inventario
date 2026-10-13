<?php
/**
 * dashboard.php — Vista del Dashboard de Inventario
 * ─────────────────────────────────────────────────────────────────────────────
 * FIX: calcularMargen() definida aquí como función global para ser accesible
 * independientemente de qué controlador cargue esta vista
 * (puede ser DashboardController o ReporteController).
 * No depende de $this ni de ningún require_once externo.
 */

// ── Helper de margen ─────────────────────────────────────────────────────────
if (!function_exists('calcularMargen')) {
    function calcularMargen(float $venta, float $compra): string {
        if ($compra <= 0.0) return '0.0';
        return number_format((($venta - $compra) / $compra) * 100, 1);
    }
}

// ── Variables de vista con fallbacks seguros ─────────────────────────────────
// Garantiza que ninguna variable esté indefinida si el controlador que cargó
// esta vista no la declaró explícitamente.
$estadisticas       = $estadisticas       ?? ['total_productos'=>0,'productos_bajos'=>0,'productos_criticos'=>0,'valor_total_compra'=>0,'valor_total_venta'=>0];
$alertasCriticas    = $alertasCriticas    ?? [];
$alertasPendientes  = $alertasPendientes  ?? count($alertasCriticas);
$productosStockBajo = $productosStockBajo ?? [];
$topVendidos        = $topVendidos        ?? [];
$ultimosMovimientos = $ultimosMovimientos ?? [];
$rotacionInventario = $rotacionInventario ?? ['rotacion'=>0,'clasificacion'=>'Sin datos','color'=>'secondary'];
$distribucionStock  = $distribucionStock  ?? [];
$movimientosPorDia  = $movimientosPorDia  ?? ['labels'=>[],'entradas'=>[],'salidas'=>[]];
$topCategorias      = $topCategorias      ?? ['labels'=>[],'values'=>[]];
$ventasPorMes       = $ventasPorMes       ?? ['labels'=>[],'valores'=>[]];
$productosPorVencer = $productosPorVencer ?? [];

$currentPage = 'dashboard';
include __DIR__ . '/../Inventario/views/layouts/inventario_layout.php';
?>

<div class="container-fluid">
    <!-- Encabezado con selector de rango -->
    <div class="d-flex justify-content-between align-items-center mb-4 flex-wrap gap-2">
        <h1 class="h3 mb-0">
            <i class="fas fa-tachometer-alt me-2 text-primary"></i>Dashboard de Inventario
        </h1>
        <div class="d-flex gap-2">
            <div class="btn-group" role="group">
                <button type="button" class="btn btn-outline-secondary btn-sm rango-btn" data-rango="semana">
                    <i class="fas fa-calendar-week me-1"></i>Semana
                </button>
                <button type="button" class="btn btn-outline-secondary btn-sm rango-btn" data-rango="mes">
                    <i class="fas fa-calendar-alt me-1"></i>Mes
                </button>
                <button type="button" class="btn btn-outline-secondary btn-sm rango-btn" data-rango="trimestre">
                    <i class="fas fa-calendar me-1"></i>Trimestre
                </button>
            </div>
            <div class="text-muted">
                <i class="fas fa-calendar-check me-1"></i> <?= date('d/m/Y') ?>
            </div>
        </div>
    </div>

    <!-- ── Widgets de resumen ─────────────────────────────────────────────── -->
    <div class="row mb-4">
        <div class="col-xl-3 col-md-6 mb-3">
            <div class="card border-left-primary shadow h-100">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <div class="text-xs font-weight-bold text-primary text-uppercase mb-1">
                                Total Productos
                            </div>
                            <div class="h4 mb-0 font-weight-bold">
                                <?= number_format($estadisticas['total_productos'] ?? 0) ?>
                            </div>
                            <div class="mt-2">
                                <span class="badge bg-success">Normal: <?= ($estadisticas['total_productos'] ?? 0) - (($estadisticas['productos_bajos'] ?? 0) + ($estadisticas['productos_criticos'] ?? 0)) ?></span>
                                <span class="badge bg-warning text-dark">Bajo: <?= $estadisticas['productos_bajos'] ?? 0 ?></span>
                                <span class="badge bg-danger">Crítico: <?= $estadisticas['productos_criticos'] ?? 0 ?></span>
                            </div>
                        </div>
                        <div>
                            <i class="fas fa-boxes fa-3x text-gray-300"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-xl-3 col-md-6 mb-3">
            <div class="card border-left-success shadow h-100">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <div class="text-xs font-weight-bold text-success text-uppercase mb-1">
                                Valor del Inventario
                            </div>
                            <div class="h4 mb-0 font-weight-bold text-success">
                                S/ <?= number_format($estadisticas['valor_total_venta'] ?? 0, 2) ?>
                            </div>
                            <div class="text-xs mt-2">
                                <span class="text-muted">Costo: S/ <?= number_format($estadisticas['valor_total_compra'] ?? 0, 2) ?></span>
                                <br>
                                <span class="text-info">Margen: <?= calcularMargen($estadisticas['valor_total_venta'] ?? 0, $estadisticas['valor_total_compra'] ?? 0) ?>%</span>
                            </div>
                        </div>
                        <div>
                            <i class="fas fa-dollar-sign fa-3x text-gray-300"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-xl-3 col-md-6 mb-3">
            <div class="card border-left-warning shadow h-100">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <div class="text-xs font-weight-bold text-warning text-uppercase mb-1">
                                Alertas Activas
                            </div>
                            <div class="h4 mb-0 font-weight-bold text-warning">
                                <?= $alertasPendientes ?? 0 ?>
                            </div>
                            <div class="mt-2">
                                <span class="badge bg-danger">Críticas: <?= count($alertasCriticas) ?></span>
                            </div>
                        </div>
                        <div>
                            <i class="fas fa-exclamation-triangle fa-3x text-gray-300"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-xl-3 col-md-6 mb-3">
            <div class="card border-left-info shadow h-100">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <div class="text-xs font-weight-bold text-info text-uppercase mb-1">
                                Rotación de Inventario
                            </div>
                            <div class="h4 mb-0 font-weight-bold">
                                <?= $rotacionInventario['rotacion'] ?? 0 ?>
                            </div>
                            <div class="mt-2">
                                <span class="badge bg-<?= $rotacionInventario['color'] ?? 'secondary' ?>">
                                    <?= $rotacionInventario['clasificacion'] ?? 'Sin datos' ?>
                                </span>
                                <small class="text-muted ms-2">veces/mes</small>
                            </div>
                        </div>
                        <div>
                            <i class="fas fa-chart-line fa-3x text-gray-300"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- ── GRÁFICOS PRINCIPALES ─────────────────────────────────────────── -->
    <div class="row mb-4">
        <div class="col-lg-8">
            <div class="card shadow">
                <div class="card-header">
                    <h6 class="m-0 font-weight-bold text-primary">
                        <i class="fas fa-chart-line me-2"></i>Movimientos de Inventario
                    </h6>
                </div>
                <div class="card-body">
                    <canvas id="movimientosChart" height="300"></canvas>
                </div>
            </div>
        </div>
        <div class="col-lg-4">
            <div class="card shadow">
                <div class="card-header">
                    <h6 class="m-0 font-weight-bold text-primary">
                        <i class="fas fa-chart-pie me-2"></i>Distribución por Estado
                    </h6>
                </div>
                <div class="card-body">
                    <canvas id="stockPieChart" height="250"></canvas>
                    <div class="mt-3">
                        <?php foreach ($distribucionStock as $estado => $datos): ?>
                            <?php if ($datos['cantidad'] > 0): ?>
                                <div class="d-flex justify-content-between align-items-center mb-2">
                                    <div>
                                        <span class="badge" style="background-color: <?= $datos['color'] ?>;">●</span>
                                        <span><?= $datos['nombre'] ?></span>
                                    </div>
                                    <div>
                                        <span class="fw-bold"><?= $datos['cantidad'] ?></span>
                                        <small class="text-muted">(<?= $datos['unidades'] ?> uds)</small>
                                    </div>
                                </div>
                            <?php endif; ?>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- ── SEGUNDA FILA DE GRÁFICOS ─────────────────────────────────────── -->
    <div class="row mb-4">
        <div class="col-lg-6">
            <div class="card shadow">
                <div class="card-header">
                    <h6 class="m-0 font-weight-bold text-success">
                        <i class="fas fa-chart-bar me-2"></i>Top 5 Categorías
                    </h6>
                </div>
                <div class="card-body">
                    <canvas id="categoriasChart" height="250"></canvas>
                </div>
            </div>
        </div>
        <div class="col-lg-6">
            <div class="card shadow">
                <div class="card-header">
                    <h6 class="m-0 font-weight-bold text-info">
                        <i class="fas fa-chart-line me-2"></i>Ventas por Mes
                    </h6>
                </div>
                <div class="card-body">
                    <canvas id="ventasChart" height="250"></canvas>
                </div>
            </div>
        </div>
    </div>

    <!-- ── TABLAS Y LISTADOS ────────────────────────────────────────────── -->
    <div class="row">
        <div class="col-lg-7">
            <!-- Productos con Stock Bajo -->
            <div class="card shadow mb-4">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h6 class="m-0 font-weight-bold text-danger">
                        <i class="fas fa-exclamation-circle me-2"></i>Productos con Stock Bajo
                    </h6>
                    <a href="index.php?controller=reporte&action=stock_bajo" class="btn btn-sm btn-outline-danger">
                        Ver Todos <i class="fas fa-arrow-right ms-1"></i>
                    </a>
                </div>
                <div class="card-body p-0">
                    <?php if (empty($productosStockBajo)): ?>
                        <div class="p-4 text-center text-muted">
                            <i class="fas fa-check-circle fa-3x mb-2 d-block text-success"></i>
                            <p class="mb-0">¡Excelente! No hay productos con stock bajo.</p>
                        </div>
                    <?php else: ?>
                        <div class="table-responsive">
                            <table class="table table-hover mb-0">
                                <thead class="table-light">
                                    <tr><th>Producto</th><th>Stock</th><th>Mínimo</th><th>Estado</th><th></th></tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($productosStockBajo as $producto): ?>
                                        <?php
                                        $stockActual = (int)($producto['stock_actual'] ?? 0);
                                        $stockMinimo = (int)($producto['stock_minimo'] ?? 0);
                                        $porcentaje = $stockMinimo > 0 ? ($stockActual / $stockMinimo) * 100 : 0;
                                        $estadoClass = $porcentaje < 30 ? 'danger' : ($porcentaje < 70 ? 'warning' : 'info');
                                        ?>
                                        <tr>
                                            <td>
                                                <div class="fw-bold"><?= htmlspecialchars($producto['nombre'] ?? '') ?></div>
                                                <small class="text-muted"><?= htmlspecialchars($producto['codigo'] ?? '') ?></small>
                                            </td>
                                            <td><span class="badge bg-<?= $estadoClass ?>"><?= $stockActual ?></span></td>
                                            <td><?= $stockMinimo ?></td>
                                            <td>
                                                <div class="progress" style="height:6px; width:80px;">
                                                    <div class="progress-bar bg-<?= $estadoClass ?>" style="width:<?= min($porcentaje, 100) ?>%"></div>
                                                </div>
                                            </td>
                                            <td>
                                                <a href="index.php?controller=movimiento&action=entrada&id_producto=<?= $producto['id_producto'] ?>"
                                                   class="btn btn-sm btn-outline-primary" title="Reponer">
                                                    <i class="fas fa-cart-plus"></i>
                                                </a>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Últimos Movimientos -->
            <div class="card shadow">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h6 class="m-0 font-weight-bold text-primary">
                        <i class="fas fa-history me-2"></i>Últimos Movimientos
                    </h6>
                    <a href="index.php?controller=movimiento&action=index" class="btn btn-sm btn-outline-primary">
                        Historial <i class="fas fa-arrow-right ms-1"></i>
                    </a>
                </div>
                <div class="card-body p-0">
                    <?php if (empty($ultimosMovimientos)): ?>
                        <div class="p-4 text-center text-muted">
                            <i class="fas fa-history fa-3x mb-2 d-block"></i>
                            <p class="mb-0">No hay movimientos registrados.</p>
                        </div>
                    <?php else: ?>
                        <div class="table-responsive">
                            <table class="table table-hover mb-0">
                                <thead class="table-light">
                                    <tr><th>Fecha</th><th>Producto</th><th>Tipo</th><th>Cantidad</th><th>Usuario</th></tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($ultimosMovimientos as $movimiento): ?>
                                        <tr>
                                            <td><small><?= date('d/m/Y H:i', strtotime($movimiento['fecha_movimiento'])) ?></small></td>
                                            <td>
                                                <div class="fw-bold"><?= htmlspecialchars($movimiento['producto_nombre'] ?? '') ?></div>
                                                <small class="text-muted"><?= htmlspecialchars($movimiento['producto_codigo'] ?? '') ?></small>
                                            </td>
                                            <td>
                                                <span class="badge bg-<?= ($movimiento['tipo_movimiento'] ?? '') === 'entrada' ? 'success' : 'danger' ?>">
                                                    <?= ($movimiento['tipo_movimiento'] ?? '') === 'entrada' ? 'Entrada' : 'Salida' ?>
                                                </span>
                                            </td>
                                            <td class="fw-bold <?= ($movimiento['tipo_movimiento'] ?? '') === 'entrada' ? 'text-success' : 'text-danger' ?>">
                                                <?= ($movimiento['tipo_movimiento'] ?? '') === 'entrada' ? '+' : '-' ?> <?= $movimiento['cantidad'] ?? 0 ?>
                                            </td>
                                            <td><small><?= htmlspecialchars($movimiento['usuario_nombre'] ?? '-') ?></small></td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <div class="col-lg-5">
            <!-- Alertas Críticas -->
            <div class="card shadow mb-4 border-danger">
                <div class="card-header bg-danger text-white">
                    <h6 class="m-0 font-weight-bold">
                        <i class="fas fa-bell me-2"></i>Alertas Críticas
                    </h6>
                </div>
                <div class="card-body p-0">
                    <?php if (empty($alertasCriticas)): ?>
                        <div class="p-4 text-center text-muted">
                            <i class="fas fa-check-circle fa-3x mb-2 d-block text-success"></i>
                            <p class="mb-0">No hay alertas críticas activas.</p>
                        </div>
                    <?php else: ?>
                        <div class="list-group list-group-flush">
                            <?php foreach ($alertasCriticas as $alerta): ?>
                                <a href="index.php?controller=producto&action=ver&id=<?= $alerta['id_producto'] ?>"
                                   class="list-group-item list-group-item-action">
                                    <div class="d-flex justify-content-between align-items-start">
                                        <div>
                                            <div class="fw-bold"><?= htmlspecialchars($alerta['producto_nombre'] ?? '') ?></div>
                                            <small class="text-muted">Stock: <?= $alerta['stock_actual'] ?? 0 ?> / Mín: <?= $alerta['stock_minimo'] ?? 0 ?></small>
                                        </div>
                                        <span class="badge bg-danger">Crítica</span>
                                    </div>
                                    <small class="text-danger d-block mt-1">
                                        <i class="fas fa-exclamation-triangle me-1"></i>
                                        <?= htmlspecialchars(substr($alerta['mensaje'] ?? '', 0, 60)) ?>...
                                    </small>
                                </a>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                </div>
                <div class="card-footer text-center bg-light">
                    <a href="index.php?controller=alerta&action=index" class="btn btn-sm btn-outline-danger">
                        <i class="fas fa-cog me-1"></i>Gestionar Alertas
                    </a>
                </div>
            </div>

            <!-- Top 5 Más Vendidos -->
            <div class="card shadow mb-4">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h6 class="m-0 font-weight-bold text-success">
                        <i class="fas fa-trophy me-2"></i>Top 5 Más Vendidos
                    </h6>
                    <small class="text-muted"><i class="fas fa-calendar me-1"></i>Último mes</small>
                </div>
                <div class="card-body p-0">
                    <?php if (empty($topVendidos)): ?>
                        <div class="p-4 text-center text-muted">
                            <i class="fas fa-chart-line fa-3x mb-2 d-block"></i>
                            <p class="mb-0">No hay datos de ventas.</p>
                        </div>
                    <?php else: ?>
                        <div class="list-group list-group-flush">
                            <?php foreach ($topVendidos as $index => $producto): ?>
                                <div class="list-group-item">
                                    <div class="d-flex justify-content-between align-items-center">
                                        <div class="d-flex align-items-center">
                                            <div class="position-relative me-3">
                                                <span class="badge bg-success rounded-circle p-2" style="width: 32px; height: 32px; display: flex; align-items: center; justify-content: center;">
                                                    <?= $index + 1 ?>
                                                </span>
                                            </div>
                                            <div>
                                                <div class="fw-bold"><?= htmlspecialchars($producto['nombre'] ?? '') ?></div>
                                                <small class="text-muted"><?= htmlspecialchars($producto['categoria_nombre'] ?? $producto['categoria'] ?? '') ?></small>
                                            </div>
                                        </div>
                                        <div class="text-end">
                                            <div class="fw-bold text-success">
                                                <?= number_format($producto['total_vendido'] ?? 0) ?> unds
                                            </div>
                                            <small class="text-muted">
                                                S/ <?= number_format($producto['total_ingresos'] ?? $producto['valor_total_ventas'] ?? 0, 2) ?>
                                            </small>
                                        </div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Productos Próximos a Vencer -->
            <?php if (!empty($productosPorVencer)): ?>
            <div class="card shadow">
                <div class="card-header bg-warning text-dark">
                    <h6 class="m-0 font-weight-bold">
                        <i class="fas fa-hourglass-half me-2"></i>Próximos a Vencer
                    </h6>
                </div>
                <div class="card-body p-0">
                    <div class="list-group list-group-flush">
                        <?php foreach ($productosPorVencer as $producto): ?>
                            <div class="list-group-item">
                                <div class="d-flex justify-content-between align-items-center">
                                    <div>
                                        <div class="fw-bold"><?= htmlspecialchars($producto['nombre'] ?? '') ?></div>
                                        <small class="text-muted">
                                            Stock: <?= $producto['stock_actual'] ?? 0 ?> uds
                                        </small>
                                    </div>
                                    <div class="text-end">
                                        <span class="badge bg-<?= $producto['dias_restantes'] <= 0 ? 'dark' : ($producto['dias_restantes'] <= 7 ? 'danger' : 'warning') ?>">
                                            <?= $producto['dias_restantes'] <= 0 ? 'Vencido' : $producto['dias_restantes'] . ' días' ?>
                                        </span>
                                        <br>
                                        <small class="text-muted">
                                            Vence: <?= date('d/m/Y', strtotime($producto['fecha_vencimiento'])) ?>
                                        </small>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<!-- Scripts para gráficos -->
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function() {
    // ============================================
    // GRÁFICO 1: Movimientos por día
    // ============================================
    const movimientosData = <?= json_encode($movimientosPorDia) ?>;
    const ctx1 = document.getElementById('movimientosChart').getContext('2d');
    new Chart(ctx1, {
        type: 'line',
        data: {
            labels: movimientosData.labels,
            datasets: [
                {
                    label: 'Entradas',
                    data: movimientosData.entradas,
                    borderColor: '#28a745',
                    backgroundColor: 'rgba(40, 167, 69, 0.1)',
                    fill: true,
                    tension: 0.4
                },
                {
                    label: 'Salidas',
                    data: movimientosData.salidas,
                    borderColor: '#dc3545',
                    backgroundColor: 'rgba(220, 53, 69, 0.1)',
                    fill: true,
                    tension: 0.4
                }
            ]
        },
        options: {
            responsive: true,
            maintainAspectRatio: true,
            plugins: {
                legend: { position: 'top' },
                tooltip: { mode: 'index', intersect: false }
            },
            scales: {
                y: { beginAtZero: true, title: { display: true, text: 'Cantidad' } },
                x: { title: { display: true, text: 'Fecha' } }
            }
        }
    });

    // ============================================
    // GRÁFICO 2: Distribución de Stock (Pie)
    // ============================================
    const distribucionStock = <?= json_encode($distribucionStock) ?>;
    const pieLabels = [];
    const pieData = [];
    const pieColors = [];
    
    <?php foreach ($distribucionStock as $estado => $datos): ?>
        <?php if ($datos['cantidad'] > 0): ?>
            pieLabels.push('<?= $datos['nombre'] ?>');
            pieData.push(<?= $datos['cantidad'] ?>);
            pieColors.push('<?= $datos['color'] ?>');
        <?php endif; ?>
    <?php endforeach; ?>
    
    const ctx2 = document.getElementById('stockPieChart').getContext('2d');
    new Chart(ctx2, {
        type: 'doughnut',
        data: {
            labels: pieLabels,
            datasets: [{
                data: pieData,
                backgroundColor: pieColors,
                borderWidth: 0
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: true,
            plugins: {
                legend: { position: 'bottom' }
            }
        }
    });

    // ============================================
    // GRÁFICO 3: Top Categorías (Barra)
    // ============================================
    const categoriasData = <?= json_encode($topCategorias) ?>;
    const ctx3 = document.getElementById('categoriasChart').getContext('2d');
    new Chart(ctx3, {
        type: 'bar',
        data: {
            labels: categoriasData.labels,
            datasets: [{
                label: 'Cantidad de Productos',
                data: categoriasData.values,
                backgroundColor: '#36b9cc',
                borderRadius: 8
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: true,
            plugins: {
                legend: { display: false }
            },
            scales: {
                y: { beginAtZero: true, title: { display: true, text: 'Productos' } }
            }
        }
    });

    // ============================================
    // GRÁFICO 4: Ventas por Mes
    // ============================================
    const ventasData = <?= json_encode($ventasPorMes) ?>;
    const ctx4 = document.getElementById('ventasChart').getContext('2d');
    new Chart(ctx4, {
        type: 'line',
        data: {
            labels: ventasData.labels,
            datasets: [{
                label: 'Valor de Ventas (S/)',
                data: ventasData.valores,
                borderColor: '#1cc88a',
                backgroundColor: 'rgba(28, 200, 138, 0.1)',
                fill: true,
                tension: 0.4
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: true,
            plugins: {
                tooltip: {
                    callbacks: {
                        label: function(context) {
                            return 'S/ ' + context.raw.toLocaleString('es-PE', {minimumFractionDigits: 2});
                        }
                    }
                }
            },
            scales: {
                y: { 
                    beginAtZero: true, 
                    title: { display: true, text: 'Soles (S/)' },
                    ticks: { callback: function(value) { return 'S/ ' + value.toLocaleString(); } }
                }
            }
        }
    });

    // ============================================
    // Selector de rango (AJAX)
    // ============================================
    document.querySelectorAll('.rango-btn').forEach(btn => {
        btn.addEventListener('click', function() {
            const rango = this.dataset.rango;
            
            // Actualizar estilo activo
            document.querySelectorAll('.rango-btn').forEach(b => b.classList.remove('btn-primary', 'active'));
            this.classList.add('btn-primary', 'active');
            
            // Cargar nuevos datos vía AJAX
            fetch(`index.php?controller=dashboard&action=get_datos_graficos&rango=${rango}`)
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        // Actualizar gráficos con nuevos datos
                        location.reload(); // Recarga simple por simplicidad
                    }
                })
                .catch(error => console.error('Error:', error));
        });
    });
});
</script>

<!-- Estilos adicionales -->
<style>
    .border-left-primary { border-left: 4px solid #4e73df !important; }
    .border-left-success { border-left: 4px solid #1cc88a !important; }
    .border-left-warning { border-left: 4px solid #f6c23e !important; }
    .border-left-info { border-left: 4px solid #36b9cc !important; }
    .card { border-radius: 0.5rem; overflow: hidden; }
    .table-responsive { max-height: 400px; overflow-y: auto; }
    .progress { background-color: #e9ecef; border-radius: 10px; }
</style>

<?php include __DIR__ . '/../Inventario/views/layouts/footer.php'; ?>