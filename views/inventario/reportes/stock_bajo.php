<?php 
$currentPage = 'reportes';
include __DIR__ . '/../../layouts/inventario_layout.php'; 
?>

<div class="container-fluid">
    <!-- Encabezado -->
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1 class="h3 mb-0">Reporte de Stock Bajo</h1>
        <div>
            <a href="index.php?controller=reporte&action=exportar&tipo=stock_bajo&nivel=<?= $nivel ?>" 
               class="btn btn-success me-2">
                <i class="fas fa-file-excel me-2"></i>Exportar Excel
            </a>
            <a href="index.php?controller=reporte&action=dashboard" class="btn btn-secondary">
                <i class="fas fa-chart-bar me-2"></i>Dashboard
            </a>
        </div>
    </div>

    <!-- Filtros -->
    <div class="card mb-4">
        <div class="card-body">
            <form method="get" action="" class="row g-3">
                <input type="hidden" name="controller" value="reporte">
                <input type="hidden" name="action" value="stock_bajo">
                
                <div class="col-md-6">
                    <label class="form-label">Nivel de Stock</label>
                    <select name="nivel" class="form-select" onchange="this.form.submit()">
                        <option value="todos" <?= $nivel == 'todos' ? 'selected' : '' ?>>Todos los niveles</option>
                        <option value="critico" <?= $nivel == 'critico' ? 'selected' : '' ?>>Crítico</option>
                        <option value="bajo" <?= $nivel == 'bajo' ? 'selected' : '' ?>>Bajo</option>
                        <option value="agotado" <?= $nivel == 'agotado' ? 'selected' : '' ?>>Agotado</option>
                    </select>
                </div>
                
                <div class="col-md-6 d-flex align-items-end">
                    <button type="submit" class="btn btn-primary me-2">
                        <i class="fas fa-filter"></i> Filtrar
                    </button>
                    <a href="index.php?controller=reporte&action=stock_bajo" class="btn btn-secondary">
                        <i class="fas fa-redo"></i>
                    </a>
                </div>
            </form>
        </div>
    </div>

    <!-- Resumen -->
    <div class="row mb-4">
        <div class="col-md-4">
            <div class="card border-danger">
                <div class="card-body text-center">
                    <h5 class="card-title text-danger">Productos con Stock Bajo</h5>
                    <h1 class="text-danger"><?= count($productosStockBajo) ?></h1>
                    <small>Necesitan reposición inmediata</small>
                </div>
            </div>
        </div>
        
        <div class="col-md-4">
            <div class="card border-warning">
                <div class="card-body text-center">
                    <h5 class="card-title text-warning">Valor de Reposición</h5>
                    <?php
                    $valorReposicion = array_reduce($productosStockBajo, function($carry, $item) {
                        $faltante = max(0, $item['stock_minimo'] - $item['stock_actual']);
                        return $carry + ($faltante * $item['precio_compra']);
                    }, 0);
                    ?>
                    <h2 class="text-warning">S/ <?= number_format($valorReposicion, 2) ?></h2>
                    <small>Inversión requerida</small>
                </div>
            </div>
        </div>
        
        <div class="col-md-4">
            <div class="card border-info">
                <div class="card-body text-center">
                    <h5 class="card-title text-info">Unidades Faltantes</h5>
                    <?php
                    $unidadesFaltantes = array_reduce($productosStockBajo, function($carry, $item) {
                        $faltante = max(0, $item['stock_minimo'] - $item['stock_actual']);
                        return $carry + $faltante;
                    }, 0);
                    ?>
                    <h2 class="text-info"><?= number_format($unidadesFaltantes) ?></h2>
                    <small>Unidades por reponer</small>
                </div>
            </div>
        </div>
    </div>

    <!-- Tabla de productos -->
    <div class="card">
        <div class="card-body">
            <?php if (empty($productosStockBajo)): ?>
                <div class="text-center py-5">
                    <i class="fas fa-check-circle fa-4x text-success mb-3"></i>
                    <h4>¡Excelente! No hay productos con stock bajo</h4>
                    <p class="text-muted">Todos los productos tienen stock suficiente.</p>
                </div>
            <?php else: ?>
                <div class="table-responsive">
                    <table class="table table-hover datatable">
                        <thead>
                            <tr>
                                <th>Producto</th>
                                <th>Categoría</th>
                                <th>Almacén</th>
                                <th>Stock Actual</th>
                                <th>Stock Mínimo</th>
                                <th>Faltante</th>
                                <th>Nivel</th>
                                <th>Precio Compra</th>
                                <th>Valor Reposición</th>
                                <th>Acciones</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($productosStockBajo as $producto): ?>
                                <?php
                                $faltante = max(0, $producto['stock_minimo'] - $producto['stock_actual']);
                                $porcentaje = ($producto['stock_actual'] / $producto['stock_minimo']) * 100;
                                $valorReposicion = $faltante * $producto['precio_compra'];
                                ?>
                                <tr>
                                    <td>
                                        <div class="fw-bold"><?= htmlspecialchars($producto['nombre']) ?></div>
                                        <small class="text-muted"><?= htmlspecialchars($producto['codigo']) ?></small>
                                    </td>
                                    <td><?= htmlspecialchars($producto['categoria'] ?? 'Sin categoría') ?></td>
                                    <td><?= htmlspecialchars($producto['almacen'] ?? 'Sin almacén') ?></td>
                                    <td>
                                        <span class="badge 
                                            <?= $producto['estado_stock'] == 'agotado' ? 'bg-danger' : 
                                               ($producto['estado_stock'] == 'critico' ? 'bg-danger' : 
                                               ($producto['estado_stock'] == 'bajo' ? 'bg-warning' : 'bg-success')) ?>">
                                            <?= $producto['stock_actual'] ?>
                                        </span>
                                    </td>
                                    <td><?= $producto['stock_minimo'] ?></td>
                                    <td>
                                        <span class="fw-bold text-danger">-<?= $faltante ?></span>
                                    </td>
                                    <td>
                                        <span class="badge 
                                            <?= $producto['estado_stock'] == 'agotado' ? 'bg-danger' : 
                                               ($producto['estado_stock'] == 'critico' ? 'bg-danger' : 
                                               ($producto['estado_stock'] == 'bajo' ? 'bg-warning' : 'bg-success')) ?>">
                                            <?= ucfirst($producto['estado_stock']) ?>
                                        </span>
                                    </td>
                                    <td>S/ <?= number_format($producto['precio_compra'], 2) ?></td>
                                    <td>
                                        <span class="fw-bold text-warning">S/ <?= number_format($valorReposicion, 2) ?></span>
                                    </td>
                                    <td>
                                        <div class="btn-group btn-group-sm">
                                            <a href="index.php?controller=movimiento&action=entrada&id_producto=<?= $producto['id_producto'] ?>" 
                                               class="btn btn-outline-success" title="Reponer">
                                                <i class="fas fa-arrow-down"></i>
                                            </a>
                                            <a href="index.php?controller=producto&action=editar&id=<?= $producto['id_producto'] ?>" 
                                               class="btn btn-outline-primary" title="Editar">
                                                <i class="fas fa-edit"></i>
                                            </a>
                                            <a href="index.php?controller=kardex&action=producto&id_producto=<?= $producto['id_producto'] ?>" 
                                               class="btn btn-outline-info" title="Kardex">
                                                <i class="fas fa-book"></i>
                                            </a>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
                
                <!-- Gráfico de distribución -->
                <div class="row mt-4">
                    <div class="col-md-6">
                        <div class="card">
                            <div class="card-header">
                                <h6 class="mb-0">Distribución por Nivel</h6>
                            </div>
                            <div class="card-body">
                                <canvas id="chartNiveles" height="200"></canvas>
                            </div>
                        </div>
                    </div>
                    
                    <div class="col-md-6">
                        <div class="card">
                            <div class="card-header">
                                <h6 class="mb-0">Top 5 Mayor Valor de Reposición</h6>
                            </div>
                            <div class="card-body">
                                <div class="list-group">
                                    <?php
                                    $topValor = array_slice($productosStockBajo, 0, 5);
                                    usort($topValor, function($a, $b) {
                                        $valorA = max(0, $a['stock_minimo'] - $a['stock_actual']) * $a['precio_compra'];
                                        $valorB = max(0, $b['stock_minimo'] - $b['stock_actual']) * $b['precio_compra'];
                                        return $valorB <=> $valorA;
                                    });
                                    ?>
                                    <?php foreach ($topValor as $index => $producto): ?>
                                        <?php
                                        $faltante = max(0, $producto['stock_minimo'] - $producto['stock_actual']);
                                        $valorReposicion = $faltante * $producto['precio_compra'];
                                        ?>
                                        <div class="list-group-item">
                                            <div class="d-flex justify-content-between align-items-center">
                                                <div>
                                                    <span class="badge bg-primary me-2"><?= $index + 1 ?></span>
                                                    <span class="fw-bold"><?= htmlspecialchars($producto['nombre']) ?></span>
                                                </div>
                                                <div class="text-end">
                                                    <div class="fw-bold text-warning">S/ <?= number_format($valorReposicion, 2) ?></div>
                                                    <small class="text-muted">Faltan <?= $faltante ?> unidades</small>
                                                </div>
                                            </div>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function() {
    // Datos para el gráfico
    const niveles = {};
    <?php foreach ($productosStockBajo as $producto): ?>
        const nivel = '<?= $producto["nivel_stock"] ?>';
        niveles[nivel] = (niveles[nivel] || 0) + 1;
    <?php endforeach; ?>
    
    const ctx = document.getElementById('chartNiveles').getContext('2d');
    const chart = new Chart(ctx, {
        type: 'doughnut',
        data: {
            labels: Object.keys(niveles).map(n => n.charAt(0).toUpperCase() + n.slice(1)),
            datasets: [{
                data: Object.values(niveles),
                backgroundColor: [
                    '#dc3545', // Rojo para crítico/agotado
                    '#ffc107', // Amarillo para bajo
                    '#28a745', // Verde para normal
                    '#6c757d'  // Gris para otros
                ],
                borderWidth: 1
            }]
        },
        options: {
            responsive: true,
            plugins: {
                legend: {
                    position: 'bottom'
                }
            }
        }
    });
});
</script>

<?php include __DIR__ . '/../../layouts/footer.php'; ?>