<?php
$currentPage = 'categorias';
include __DIR__ . '/../../layouts/inventario_layout.php';
?>

<link rel="stylesheet" href="<?= BASE_URL ?>/views/inventario/categorias/css/stylos.css">
<meta name="csrf-token" content="<?= htmlspecialchars($csrfToken ?? '') ?>">
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>

<!-- Datos del servidor para el JS -->
<script>
    const CATEGORIA_ID = <?= (int) $categoria['id_categoria'] ?>;
    const PRODUCTOS_ACTIVOS = <?= (int) ($productosActivos ?? 0) ?>;
    const PRODUCTOS_HISTORICO = <?= (int) ($productosHistorico ?? 0) ?>;
    const NOMBRE_CATEGORIA = <?= json_encode($categoria['nombre'] ?? '') ?>;

    // Datos de productos reales desde el controlador
    const PRODUCTOS_REALES = <?= json_encode($graficos['top_productos'] ?? []) ?>;
    const PRECIOS_REALES   = <?= json_encode($graficos['precios'] ?? []) ?>;

    // Flash del servidor
    const categoriaSweetAlert = <?= (isset($sweetAlert) && $sweetAlert)
        ? json_encode([
            'title' => $sweetAlert['title'] ?? 'Aviso',
            'text'  => $sweetAlert['text']  ?? ($sweetAlert['message'] ?? ''),
            'type'  => $sweetAlert['type']  ?? 'info',
        ])
        : 'null' ?>;
</script>

<div class="container-fluid px-4 py-3">

    <!-- ── Encabezado ─────────────────────────────────────────────── -->
    <div class="d-flex justify-content-between align-items-center mb-4 flex-wrap gap-3">
        <div class="d-flex align-items-center gap-3">
            <div class="cat-avatar rounded-circle d-flex align-items-center justify-content-center
                        bg-primary bg-opacity-10 text-primary flex-shrink-0"
                 style="width:56px;height:56px;font-size:1.6rem;">
                <i class="fas fa-<?= htmlspecialchars($categoria['icono'] ?? 'tag') ?>"></i>
            </div>
            <div>
                <h1 class="h3 mb-0 fw-bold"><?= htmlspecialchars($categoria['nombre']) ?></h1>
                <div class="mt-1">
                    <span class="badge <?= $categoria['estado'] === 'activo' ? 'bg-success' : 'bg-secondary' ?> px-3 py-1">
                        <i class="fas <?= $categoria['estado'] === 'activo' ? 'fa-check-circle' : 'fa-ban' ?> me-1"></i>
                        <?= $categoria['estado'] === 'activo' ? 'Activa' : 'Inactiva' ?>
                    </span>
                </div>
            </div>
        </div>
        <div class="d-flex gap-2">
            <a href="index.php?controller=categoria&action=index" class="btn btn-outline-secondary">
                <i class="fas fa-arrow-left me-2"></i>Volver
            </a>
            <a href="index.php?controller=categoria&action=editar&id=<?= (int) $categoria['id_categoria'] ?>"
               class="btn btn-primary">
                <i class="fas fa-edit me-2"></i>Editar
            </a>
            <?php if ($categoria['estado'] === 'activo'): ?>
                <button class="btn btn-outline-danger btn-delete"
                        data-url="index.php?controller=categoria&action=eliminar&id=<?= (int) $categoria['id_categoria'] ?>"
                        data-nombre="<?= htmlspecialchars($categoria['nombre']) ?>">
                    <i class="fas fa-ban me-2"></i>Desactivar
                </button>
            <?php else: ?>
                <button class="btn btn-outline-success btn-activate"
                        data-url="index.php?controller=categoria&action=activar&id=<?= (int) $categoria['id_categoria'] ?>"
                        data-nombre="<?= htmlspecialchars($categoria['nombre']) ?>">
                    <i class="fas fa-check me-2"></i>Activar
                </button>
            <?php endif; ?>
        </div>
    </div>

    <!-- ── Tarjetas de estadísticas ───────────────────────────────── -->
    <div class="row g-3 mb-4">
        <div class="col-sm-6 col-lg-3">
            <div class="card h-100 border-0 shadow-sm">
                <div class="card-body">
                    <div class="d-flex align-items-center gap-3">
                        <div class="rounded-3 bg-primary bg-opacity-10 p-3">
                            <i class="fas fa-boxes fa-2x text-primary"></i>
                        </div>
                        <div>
                            <div class="text-muted small text-uppercase fw-semibold">Productos Activos</div>
                            <div class="h3 mb-0 fw-bold text-primary"><?= (int) ($productosActivos ?? 0) ?></div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-sm-6 col-lg-3">
            <div class="card h-100 border-0 shadow-sm">
                <div class="card-body">
                    <div class="d-flex align-items-center gap-3">
                        <div class="rounded-3 bg-success bg-opacity-10 p-3">
                            <i class="fas fa-history fa-2x text-success"></i>
                        </div>
                        <div>
                            <div class="text-muted small text-uppercase fw-semibold">Total Histórico</div>
                            <div class="h3 mb-0 fw-bold text-success"><?= (int) ($productosHistorico ?? 0) ?></div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-sm-6 col-lg-3">
            <div class="card h-100 border-0 shadow-sm">
                <div class="card-body">
                    <div class="d-flex align-items-center gap-3">
                        <div class="rounded-3 bg-warning bg-opacity-10 p-3">
                            <i class="fas fa-exclamation-triangle fa-2x text-warning"></i>
                        </div>
                        <div>
                            <div class="text-muted small text-uppercase fw-semibold">Stock Bajo (&lt;5)</div>
                            <div class="h3 mb-0 fw-bold text-warning" id="statStockBajo">0</div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-sm-6 col-lg-3">
            <div class="card h-100 border-0 shadow-sm">
                <div class="card-body">
                    <div class="d-flex align-items-center gap-3">
                        <div class="rounded-3 bg-danger bg-opacity-10 p-3">
                            <i class="fas fa-times-circle fa-2x text-danger"></i>
                        </div>
                        <div>
                            <div class="text-muted small text-uppercase fw-semibold">Sin Stock</div>
                            <div class="h3 mb-0 fw-bold text-danger" id="statSinStock">0</div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- ── Contenido principal ────────────────────────────────────── -->
    <div class="row g-4">

        <!-- Columna izquierda -->
        <div class="col-lg-8">

            <!-- Información de la categoría -->
            <div class="card border-0 shadow-sm mb-4">
                <div class="card-header bg-white border-bottom-0 pt-3">
                    <h5 class="mb-0 fw-semibold">
                        <i class="fas fa-info-circle me-2 text-primary"></i>Información de la Categoría
                    </h5>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-6">
                            <table class="table table-sm table-borderless">
                                <tr>
                                    <td class="text-muted" style="width: 100px;">ID:</td>
                                    <td class="font-monospace"><?= (int) $categoria['id_categoria'] ?></td>
                                </tr>
                                <tr>
                                    <td class="text-muted">Nombre:</td>
                                    <td class="fw-semibold"><?= htmlspecialchars($categoria['nombre']) ?></td>
                                </tr>
                                <?php if (!empty($categoria['icono'])): ?>
                                <tr>
                                    <td class="text-muted">Icono:</td>
                                    <td><i class="fas fa-<?= htmlspecialchars($categoria['icono']) ?> me-1"></i> <code><?= htmlspecialchars($categoria['icono']) ?></code></td>
                                </tr>
                                <?php endif; ?>
                            </table>
                        </div>
                        <div class="col-md-6">
                            <table class="table table-sm table-borderless">
                                <?php if (!empty($categoria['fecha_creacion'])): ?>
                                <tr>
                                    <td class="text-muted" style="width: 100px;">Creación:</td>
                                    <td><?= date('d/m/Y H:i', strtotime($categoria['fecha_creacion'])) ?></td>
                                </tr>
                                <?php endif; ?>
                                <?php if (!empty($categoria['fecha_modificacion'])): ?>
                                <tr>
                                    <td class="text-muted">Modificación:</td>
                                    <td><?= date('d/m/Y H:i', strtotime($categoria['fecha_modificacion'])) ?></td>
                                </tr>
                                <?php endif; ?>
                            </table>
                        </div>
                    </div>

                    <?php if (!empty($categoria['descripcion'])): ?>
                        <hr>
                        <h6 class="text-muted mb-2">
                            <i class="fas fa-align-left me-1"></i> Descripción
                        </h6>
                        <div class="bg-light rounded-3 p-3 small">
                            <?= nl2br(htmlspecialchars($categoria['descripcion'])) ?>
                        </div>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Gráfico de estado de productos -->
            <div class="card border-0 shadow-sm mb-4">
                <div class="card-header bg-white border-bottom-0 pt-3">
                    <h5 class="mb-0 fw-semibold">
                        <i class="fas fa-chart-pie me-2 text-primary"></i>Estado de Productos
                    </h5>
                </div>
                <div class="card-body">
                    <div class="row align-items-center">
                        <div class="col-md-5 text-center">
                            <canvas id="chartProductos" style="max-height: 200px; width: 100%;"></canvas>
                        </div>
                        <div class="col-md-7">
                            <div class="row g-3 text-center" id="statsProductosDetalle"></div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Productos de la categoría (DATOS REALES CON ACCIONES) -->
            <div class="card border-0 shadow-sm">
                <div class="card-header bg-white border-bottom-0 pt-3 d-flex justify-content-between align-items-center flex-wrap gap-2">
                    <h5 class="mb-0 fw-semibold">
                        <i class="fas fa-boxes me-2 text-primary"></i>Productos en esta categoría
                    </h5>
                    <div class="d-flex gap-2">
                        <button class="btn btn-sm btn-outline-success" id="btnActualizarStock">
                            <i class="fas fa-sync-alt me-1"></i>Actualizar Stock
                        </button>
                        <a href="index.php?controller=producto&action=crear&categoria_id=<?= (int) $categoria['id_categoria'] ?>"
                           class="btn btn-sm btn-primary">
                            <i class="fas fa-plus me-1"></i>Nuevo Producto
                        </a>
                    </div>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-hover mb-0 align-middle">
                            <thead class="table-light">
                                <tr>
                                    <th class="ps-3" style="width: 50px;">#</th>
                                    <th>Producto</th>
                                    <th class="text-center" style="width: 130px;">Stock</th>
                                    <th class="text-end pe-3" style="width: 120px;">Precio</th>
                                    <th class="text-center" style="width: 80px;">Acción</th>
                                </tr>
                            </thead>
                            <tbody id="tablaProductosBody">
                                <?php if (empty($graficos['top_productos'])): ?>
                                <tr>
                                    <td colspan="5" class="text-center py-5 text-muted">
                                        <i class="fas fa-box-open fa-3x mb-3 d-block opacity-25"></i>
                                        No hay productos registrados en esta categoría
                                        <div class="mt-3">
                                            <a href="index.php?controller=producto&action=crear&categoria_id=<?= (int) $categoria['id_categoria'] ?>"
                                               class="btn btn-sm btn-primary">
                                                <i class="fas fa-plus me-1"></i>Agregar primer producto
                                            </a>
                                        </div>
                                    </td>
                                </tr>
                                <?php else: ?>
                                    <?php $contador = 1; ?>
                                    <?php foreach ($graficos['top_productos'] as $prod): ?>
                                        <?php 
                                            $stock = (int) ($prod['stock_actual'] ?? 0);
                                            $productoId = (int) ($prod['id_producto'] ?? $prod['id'] ?? 0);
                                            $nombreProd = htmlspecialchars($prod['nombre'] ?? '—');
                                        ?>
                                        <tr>
                                            <td class="ps-3"><span class="badge bg-secondary"><?= $contador++ ?></span></td>
                                            <td class="fw-medium">
                                                <?= $nombreProd ?>
                                                <?php if (!empty($prod['codigo'])): ?>
                                                    <small class="text-muted d-block"><?= htmlspecialchars($prod['codigo']) ?></small>
                                                <?php endif; ?>
                                            </td>
                                            <td class="text-center">
                                                <?php if ($stock <= 0): ?>
                                                    <span class="badge bg-danger px-3 py-2" style="min-width: 90px;">
                                                        <i class="fas fa-times-circle me-1"></i>Sin stock
                                                    </span>
                                                <?php elseif ($stock < 5): ?>
                                                    <span class="badge bg-warning text-dark px-3 py-2" style="min-width: 90px;">
                                                        <i class="fas fa-exclamation-triangle me-1"></i><?= number_format($stock) ?> uds
                                                    </span>
                                                <?php else: ?>
                                                    <span class="badge bg-success px-3 py-2" style="min-width: 90px;">
                                                        <i class="fas fa-check-circle me-1"></i><?= number_format($stock) ?> uds
                                                    </span>
                                                <?php endif; ?>
                                            </td>
                                            <td class="text-end pe-3 fw-bold text-primary">
                                                S/ <?= number_format((float) ($prod['precio_venta'] ?? 0), 2) ?>
                                            </td>
                                            <td class="text-center">
                                                <div class="btn-group btn-group-sm">
                                                    <a href="index.php?controller=producto&action=editar&id=<?= $productoId ?>"
                                                       class="btn btn-outline-primary" title="Editar producto">
                                                        <i class="fas fa-edit"></i>
                                                    </a>
                                                    <button type="button"
                                                            class="btn btn-outline-warning btn-edit-stock"
                                                            data-id="<?= $productoId ?>"
                                                            data-nombre="<?= $nombreProd ?>"
                                                            data-stock="<?= $stock ?>"
                                                            title="Editar stock">
                                                        <i class="fas fa-boxes"></i>
                                                    </button>
                                                </div>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
                <?php if (count($graficos['top_productos'] ?? []) > 5): ?>
                <div class="card-footer bg-white text-center py-2">
                    <a href="index.php?controller=producto&action=index&categoria=<?= (int) $categoria['id_categoria'] ?>"
                       class="text-decoration-none">
                        <i class="fas fa-external-link-alt me-1"></i>Ver todos los productos
                    </a>
                </div>
                <?php endif; ?>
            </div>
        </div>

        <!-- Columna derecha -->
        <div class="col-lg-4">

            <!-- Acciones rápidas -->
            <div class="card border-0 shadow-sm mb-4">
                <div class="card-header bg-white border-bottom-0 pt-3">
                    <h6 class="mb-0 fw-semibold">
                        <i class="fas fa-bolt me-2 text-warning"></i>Acciones Rápidas
                    </h6>
                </div>
                <div class="card-body">
                    <div class="d-grid gap-2">
                        <a href="index.php?controller=producto&action=crear&categoria_id=<?= (int) $categoria['id_categoria'] ?>"
                           class="btn btn-success">
                            <i class="fas fa-plus-circle me-2"></i>Agregar Producto
                        </a>
                        <button class="btn btn-outline-info" id="btnExportarProductos">
                            <i class="fas fa-file-export me-2"></i>Exportar Productos
                        </button>
                        <button class="btn btn-outline-secondary" id="btnReporteCategoria">
                            <i class="fas fa-chart-bar me-2"></i>Generar Reporte
                        </button>
                    </div>
                </div>
            </div>

            <!-- Gráfico de precios -->
            <?php if (!empty($graficos['precios'])): ?>
            <div class="card border-0 shadow-sm mb-4">
                <div class="card-header bg-white border-bottom-0 pt-3">
                    <h6 class="mb-0 fw-semibold">
                        <i class="fas fa-chart-line me-2 text-success"></i>Top Precios
                    </h6>
                </div>
                <div class="card-body">
                    <canvas id="chartPrecios" style="max-height: 250px; width: 100%;"></canvas>
                </div>
            </div>
            <?php endif; ?>
            
            <!-- Resumen rápido -->
            <div class="card border-0 shadow-sm">
                <div class="card-header bg-white border-bottom-0 pt-3">
                    <h6 class="mb-0 fw-semibold">
                        <i class="fas fa-chart-simple me-2 text-info"></i>Resumen Rápido
                    </h6>
                </div>
                <div class="card-body">
                    <div class="list-group list-group-flush">
                        <div class="list-group-item d-flex justify-content-between align-items-center px-0">
                            <span><i class="fas fa-tag me-2 text-primary"></i>Total Productos:</span>
                            <span class="fw-bold"><?= (int) ($productosHistorico ?? 0) ?></span>
                        </div>
                        <div class="list-group-item d-flex justify-content-between align-items-center px-0">
                            <span><i class="fas fa-check-circle me-2 text-success"></i>Activos:</span>
                            <span class="fw-bold text-success"><?= (int) ($productosActivos ?? 0) ?></span>
                        </div>
                        <div class="list-group-item d-flex justify-content-between align-items-center px-0">
                            <span><i class="fas fa-ban me-2 text-danger"></i>Inactivos:</span>
                            <span class="fw-bold text-danger"><?= max(0, ($productosHistorico ?? 0) - ($productosActivos ?? 0)) ?></span>
                        </div>
                        <div class="list-group-item d-flex justify-content-between align-items-center px-0">
                            <span><i class="fas fa-chart-line me-2 text-warning"></i>Valor Total:</span>
                            <span class="fw-bold text-warning" id="valorTotalInventario">Calculando...</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Modal para editar stock -->
<div class="modal fade" id="modalEditarStock" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header bg-warning text-dark">
                <h5 class="modal-title"><i class="fas fa-boxes me-2"></i>Editar Stock</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <p>Producto: <strong id="modalProductoNombre"></strong></p>
                <div class="mb-3">
                    <label for="nuevoStock" class="form-label">Cantidad en stock</label>
                    <input type="number" class="form-control" id="nuevoStock" min="0" value="0">
                </div>
                <div class="mb-3">
                    <label for="motivoStock" class="form-label">Motivo (opcional)</label>
                    <select class="form-select" id="motivoStock">
                        <option value="ajuste">Ajuste manual</option>
                        <option value="compra">Nueva compra</option>
                        <option value="devolucion">Devolución</option>
                        <option value="venta">Venta registrada</option>
                    </select>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                <button type="button" class="btn btn-warning" id="btnGuardarStock">
                    <i class="fas fa-save me-1"></i>Guardar Cambios
                </button>
            </div>
        </div>
    </div>
</div>

<style>
.cat-avatar { transition: transform 0.2s ease; }
.cat-avatar:hover { transform: scale(1.05); }
.table tbody tr:hover { background-color: rgba(59, 130, 246, 0.05); }
.badge { font-weight: 500; }
</style>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Variables para el modal
    let productoActual = { id: 0, nombre: '', stock: 0 };
    
    // Gráfico de estado de productos
    const ctxProductos = document.getElementById('chartProductos')?.getContext('2d');
    if (ctxProductos) {
        const activos = PRODUCTOS_ACTIVOS || 0;
        const inactivos = Math.max(0, (PRODUCTOS_HISTORICO || 0) - activos);
        
        new Chart(ctxProductos, {
            type: 'doughnut',
            data: {
                labels: ['Activos', 'Inactivos'],
                datasets: [{
                    data: [activos, inactivos],
                    backgroundColor: ['#10b981', '#94a3b8'],
                    borderWidth: 0,
                    borderRadius: 8
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: true,
                cutout: '65%',
                plugins: {
                    legend: { position: 'bottom', labels: { font: { size: 11 } } },
                    tooltip: { callbacks: { label: ctx => `${ctx.label}: ${ctx.raw} productos` } }
                }
            }
        });
        
        const statsContainer = document.getElementById('statsProductosDetalle');
        if (statsContainer) {
            statsContainer.innerHTML = `
                <div class="col-6">
                    <div class="border rounded-3 p-3 text-center bg-success bg-opacity-10">
                        <div class="h2 mb-0 text-success fw-bold">${activos}</div>
                        <div class="small text-muted">Activos</div>
                    </div>
                </div>
                <div class="col-6">
                    <div class="border rounded-3 p-3 text-center bg-secondary bg-opacity-10">
                        <div class="h2 mb-0 text-secondary fw-bold">${inactivos}</div>
                        <div class="small text-muted">Inactivos</div>
                    </div>
                </div>
            `;
        }
    }
    
    // Gráfico de precios
    const preciosReales = PRECIOS_REALES || [];
    if (preciosReales.length > 0) {
        const ctxPrecios = document.getElementById('chartPrecios')?.getContext('2d');
        if (ctxPrecios) {
            const topPrecios = preciosReales.slice(0, 5);
            new Chart(ctxPrecios, {
                type: 'bar',
                data: {
                    labels: topPrecios.map(p => (p.nombre || 'Producto').substring(0, 20)),
                    datasets: [{
                        label: 'Precio (S/)',
                        data: topPrecios.map(p => parseFloat(p.precio_venta) || 0),
                        backgroundColor: 'rgba(59, 130, 246, 0.7)',
                        borderRadius: 6
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: true,
                    indexAxis: 'y',
                    plugins: { legend: { display: false } },
                    scales: { x: { beginAtZero: true, title: { display: true, text: 'Precio (S/)' } } }
                }
            });
        }
    }
    
    // Calcular estadísticas de stock
    const productos = PRODUCTOS_REALES || [];
    let sinStock = 0;
    let stockBajo = 0;
    let valorTotal = 0;
    
    productos.forEach(prod => {
        const stock = parseInt(prod.stock_actual ?? prod.stock) || 0;
        const precio = parseFloat(prod.precio_venta) || 0;
        if (stock === 0) sinStock++;
        else if (stock < 5) stockBajo++;
        valorTotal += stock * precio;
    });
    
    document.getElementById('statSinStock').textContent = sinStock;
    document.getElementById('statStockBajo').textContent = stockBajo;
    document.getElementById('valorTotalInventario').innerHTML = `S/ ${formatNumber(valorTotal)}`;
    
    // Modal editar stock
    document.querySelectorAll('.btn-edit-stock').forEach(btn => {
        btn.addEventListener('click', function() {
            productoActual.id = parseInt(this.dataset.id);
            productoActual.nombre = this.dataset.nombre;
            productoActual.stock = parseInt(this.dataset.stock);
            
            document.getElementById('modalProductoNombre').textContent = productoActual.nombre;
            document.getElementById('nuevoStock').value = productoActual.stock;
            
            const modal = new bootstrap.Modal(document.getElementById('modalEditarStock'));
            modal.show();
        });
    });
    
    // Guardar stock
    document.getElementById('btnGuardarStock')?.addEventListener('click', async function() {
        const nuevoStock = parseInt(document.getElementById('nuevoStock').value) || 0;
        const motivo = document.getElementById('motivoStock').value;
        
        if (isNaN(nuevoStock)) {
            Swal.fire('Error', 'Ingrese una cantidad válida', 'error');
            return;
        }
        
        this.disabled = true;
        this.innerHTML = '<i class="fas fa-spinner fa-spin me-1"></i>Guardando...';
        
        try {
            const response = await fetch('index.php?controller=producto&action=actualizarStock', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                    'X-Requested-With': 'XMLHttpRequest'
                },
                body: `id=${productoActual.id}&stock=${nuevoStock}&motivo=${motivo}&csrf_token=${encodeURIComponent(document.querySelector('meta[name="csrf-token"]')?.content || '')}`
            });
            
            const data = await response.json();
            
            if (data.success) {
                Swal.fire('Actualizado', 'Stock actualizado correctamente', 'success');
                setTimeout(() => location.reload(), 1500);
            } else {
                Swal.fire('Error', data.message || 'No se pudo actualizar el stock', 'error');
            }
        } catch (error) {
            Swal.fire('Error', 'Error de conexión', 'error');
        } finally {
            this.disabled = false;
            this.innerHTML = '<i class="fas fa-save me-1"></i>Guardar Cambios';
        }
    });
    
    // Botón actualizar stock masivo
    document.getElementById('btnActualizarStock')?.addEventListener('click', function() {
        location.reload();
    });
    
});

function formatNumber(num) {
    if (num === null || num === undefined) return '0';
    return new Intl.NumberFormat('es-PE').format(num);
}

// Exportar productos
document.getElementById('btnExportarProductos')?.addEventListener('click', function() {
    Swal.fire({ icon: 'info', title: 'Exportando', text: 'Generando exportación...', showConfirmButton: false, timer: 1500 });
    window.open(`index.php?controller=categoria&action=exportarExcel&id=${CATEGORIA_ID}`, '_blank');
});

// Generar reporte
document.getElementById('btnReporteCategoria')?.addEventListener('click', function() {
    Swal.fire({ icon: 'info', title: 'Generando reporte', text: 'El reporte se abrirá en una nueva pestaña', showConfirmButton: false, timer: 1500 });
    window.open(`index.php?controller=categoria&action=exportarPdf&id=${CATEGORIA_ID}`, '_blank');
});
</script>

<!-- crear.js — inicializa btn-delete, btn-activate e initFlashAlert() -->
<script src="<?= BASE_URL ?>/views/inventario/categorias/js/crear.js"></script>

<?php include __DIR__ . '/../../layouts/footer.php'; ?>