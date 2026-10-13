<?php
/**
 * views/inventario/categorias/index.php
 * Lista de categorías con dashboard integrado
 */
$currentPage = 'categorias';
include __DIR__ . '/../../layouts/inventario_layout.php';

?>

<!-- CSS específico del módulo categorías -->
<link rel="stylesheet" href="<?= BASE_URL ?>/views/inventario/categorias/css/stylos.css">

<!-- Chart.js para gráficos -->
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>

<div class="container-fluid px-4 py-3">

    <!-- ENCABEZADO -->
    <div class="d-flex justify-content-between align-items-center mb-4 flex-wrap gap-3">
        <div>
            <h1 class="h3 mb-0 fw-bold">
                <i class="fas fa-tags me-2 text-primary"></i>Categorías de Productos
            </h1>
            <p class="text-muted mt-2 mb-0 small">Gestión y análisis del inventario por categoría</p>
        </div>
        <div class="d-flex gap-2">
            <button type="button" class="btn btn-outline-secondary" id="toggleDashboardBtn">
                <i class="fas fa-chart-line me-2"></i>Dashboard
            </button>
            <a href="index.php?controller=categoria&action=crear" class="btn btn-primary">
                <i class="fas fa-plus me-2"></i>Nueva Categoría
            </a>
        </div>
    </div>

    <!-- DASHBOARD PANEL -->
    <div id="dashboardPanel" style="display: none;">
        
        <!-- Tarjetas de estadísticas -->
        <div class="row g-3 mb-4">
            <div class="col-sm-6 col-md-3">
                <div class="card stat-card h-100 border-0 shadow-sm">
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <span class="stat-label text-uppercase small fw-semibold text-secondary">Categorías Activas</span>
                                <h2 class="stat-value fw-bold mb-0 mt-1" id="totalCategorias">—</h2>
                            </div>
                            <div class="stat-icon rounded-3 bg-primary bg-opacity-10 p-3">
                                <i class="fas fa-tags fa-2x text-primary"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-sm-6 col-md-3">
                <div class="card stat-card h-100 border-0 shadow-sm">
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <span class="stat-label text-uppercase small fw-semibold text-secondary">Productos Activos</span>
                                <h2 class="stat-value fw-bold mb-0 mt-1" id="totalProductos">—</h2>
                            </div>
                            <div class="stat-icon rounded-3 bg-success bg-opacity-10 p-3">
                                <i class="fas fa-boxes fa-2x text-success"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-sm-6 col-md-3">
                <div class="card stat-card h-100 border-0 shadow-sm">
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <span class="stat-label text-uppercase small fw-semibold text-secondary">Stock Total</span>
                                <h2 class="stat-value fw-bold mb-0 mt-1" id="stockTotal">—</h2>
                            </div>
                            <div class="stat-icon rounded-3 bg-info bg-opacity-10 p-3">
                                <i class="fas fa-cubes fa-2x text-info"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-sm-6 col-md-3">
                <div class="card stat-card h-100 border-0 shadow-sm">
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <span class="stat-label text-uppercase small fw-semibold text-secondary">Valor Inventario</span>
                                <h2 class="stat-value fw-bold mb-0 mt-1" id="valorInventario">—</h2>
                            </div>
                            <div class="stat-icon rounded-3 bg-warning bg-opacity-10 p-3">
                                <i class="fas fa-dollar-sign fa-2x text-warning"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Gráficos principales -->
        <div class="row g-3 mb-4">
            <div class="col-lg-8">
                <div class="card border-0 shadow-sm h-100">
                    <div class="card-header bg-white border-bottom-0 pt-3">
                        <h5 class="mb-0 fw-semibold">
                            <i class="fas fa-chart-bar me-2 text-primary"></i>Stock por Categoría
                        </h5>
                    </div>
                    <div class="card-body">
                        <canvas id="stockChart" height="280" style="max-height: 280px; width: 100%;"></canvas>
                    </div>
                </div>
            </div>
            <div class="col-lg-4">
                <div class="card border-0 shadow-sm h-100">
                    <div class="card-header bg-white border-bottom-0 pt-3">
                        <h5 class="mb-0 fw-semibold">
                            <i class="fas fa-chart-pie me-2 text-primary"></i>Distribución de Productos
                        </h5>
                    </div>
                    <div class="card-body">
                        <canvas id="distribucionChart" height="280" style="max-height: 280px; width: 100%;"></canvas>
                    </div>
                </div>
            </div>
        </div>

        <!-- Top categorías -->
        <div class="row g-3 mb-4">
            <div class="col-md-6">
                <div class="card border-0 shadow-sm h-100">
                    <div class="card-header bg-white border-bottom-0 pt-3">
                        <h5 class="mb-0 fw-semibold">
                            <i class="fas fa-trophy me-2 text-warning"></i>Top 5 — Más Productos
                        </h5>
                    </div>
                    <div class="card-body">
                        <canvas id="topProductosChart" height="250" style="max-height: 250px; width: 100%;"></canvas>
                    </div>
                </div>
            </div>
            <div class="col-md-6">
                <div class="card border-0 shadow-sm h-100">
                    <div class="card-header bg-white border-bottom-0 pt-3">
                        <h5 class="mb-0 fw-semibold">
                            <i class="fas fa-chart-line me-2 text-success"></i>Top 5 — Mayor Valor
                        </h5>
                    </div>
                    <div class="card-body">
                        <canvas id="topValorChart" height="250" style="max-height: 250px; width: 100%;"></canvas>
                    </div>
                </div>
            </div>
        </div>

        <!-- Alertas de stock -->
        <div class="row mb-4">
            <div class="col-12">
                <div class="card border-0 shadow-sm">
                    <div class="card-header bg-white border-bottom-0 pt-3">
                        <h5 class="mb-0 fw-semibold">
                            <i class="fas fa-exclamation-triangle me-2 text-warning"></i>Categorías con Alertas de Stock
                        </h5>
                    </div>
                    <div class="card-body p-0">
                        <div class="table-responsive">
                            <table class="table table-hover mb-0 align-middle">
                                <thead class="table-light">
                                    <tr>
                                        <th>Categoría</th>
                                        <th class="text-center">Stock Bajo</th>
                                        <th class="text-center">Sin Stock</th>
                                        <th class="text-center">Stock Alto</th>
                                        <th class="text-center">Acción</th>
                                    </tr>
                                </thead>
                                <tbody id="alertasBody">
                                    <tr>
                                        <td colspan="5" class="text-center py-4 text-muted">
                                            <i class="fas fa-spinner fa-spin me-2"></i>Cargando datos...
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- FILTROS -->
    <div class="card border-0 shadow-sm mb-3">
        <div class="card-body py-3">
            <div class="row align-items-center g-3">
                <div class="col-auto">
                    <div class="d-flex gap-3">
                        <div class="form-check">
                            <input class="form-check-input" type="radio" name="mostrar" 
                                   id="mostrarActivas" value="activas" <?= $activas ? 'checked' : '' ?>>
                            <label class="form-check-label" for="mostrarActivas">
                                Activas <span class="badge bg-success ms-1" id="countActivas">0</span>
                            </label>
                        </div>
                        <div class="form-check">
                            <input class="form-check-input" type="radio" name="mostrar" 
                                   id="mostrarInactivas" value="inactivas" <?= !$activas ? 'checked' : '' ?>>
                            <label class="form-check-label" for="mostrarInactivas">
                                Inactivas <span class="badge bg-secondary ms-1" id="countInactivas">0</span>
                            </label>
                        </div>
                    </div>
                </div>
                
                <div class="col-auto flex-grow-1">
                    <form method="GET" action="index.php" class="d-flex gap-2" id="searchForm">
                        <input type="hidden" name="controller" value="categoria">
                        <input type="hidden" name="action" value="index">
                        <input type="hidden" name="mostrar" id="hiddenMostrar" value="<?= $activas ? 'activas' : 'inactivas' ?>">
                        <div class="input-group" style="max-width: 300px;">
                            <input type="text" name="q" id="searchInput" class="form-control" 
                                   placeholder="Buscar categoría..." value="<?= htmlspecialchars($filtro) ?>">
                            <button type="submit" class="btn btn-secondary">
                                <i class="fas fa-search"></i>
                            </button>
                            <a href="index.php?controller=categoria&action=index&mostrar=<?= $activas ? 'activas' : 'inactivas' ?>" 
                               class="btn btn-outline-secondary" title="Limpiar">
                                <i class="fas fa-times"></i>
                            </a>
                        </div>
                    </form>
                </div>
                
                <div class="col-auto">
                    <small class="text-muted">
                        <i class="fas fa-database me-1"></i><?= number_format($totalRegistros) ?> registro(s)
                    </small>
                </div>
            </div>
        </div>
    </div>

    <!-- TABLA DE CATEGORÍAS -->
    <div class="card border-0 shadow-sm">
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover mb-0 align-middle">
                    <thead class="table-light">
                        <tr>
                            <th style="width: 60px;">ID</th>
                            <th>Nombre</th>
                            <th>Descripción</th>
                            <th class="text-center" style="width: 100px;">Productos</th>
                            <th class="text-center" style="width: 100px;">Stock</th>
                            <th class="text-end" style="width: 120px;">Valor Inv.</th>
                            <th class="text-center" style="width: 100px;">Estado</th>
                            <th class="text-center" style="width: 130px;">Acciones</th>
                        </tr>
                    </thead>
                    <tbody id="categoriasBody">
                        <?php if (empty($categorias)): ?>
                            <tr>
                                <td colspan="8" class="text-center py-5">
                                    <i class="fas fa-folder-open fa-3x text-muted mb-3 d-block opacity-25"></i>
                                    <p class="text-muted mb-0">No se encontraron categorías</p>
                                    <?php if ($filtro): ?>
                                        <small class="text-muted">para "<strong><?= htmlspecialchars($filtro) ?></strong>"</small>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($categorias as $cat): ?>
                                <tr data-id="<?= (int) $cat['id_categoria'] ?>"
                                    data-nombre="<?= htmlspecialchars($cat['nombre'], ENT_QUOTES) ?>">
                                    <td><span class="badge bg-light text-dark border"><?= (int) $cat['id_categoria'] ?></span></td>
                                    <td>
                                        <div class="fw-semibold">
                                            <?php if (!empty($cat['icono'])): ?>
                                                <i class="fas fa-<?= htmlspecialchars($cat['icono']) ?> me-2 text-secondary"></i>
                                            <?php else: ?>
                                                <i class="fas fa-tag me-2 text-muted"></i>
                                            <?php endif; ?>
                                            <?= htmlspecialchars($cat['nombre']) ?>
                                        </div>
                                    </td>
                                    <td class="text-muted small"><?= htmlspecialchars(mb_substr($cat['descripcion'] ?? '—', 0, 50)) ?></td>
                                    <td class="text-center"><span class="badge bg-primary"><?= (int) ($cat['total_productos_activos'] ?? 0) ?></span></td>
                                    <td class="text-center fw-medium"><?= number_format((int) ($cat['stock_total'] ?? 0)) ?></td>
                                    <td class="text-end font-monospace">S/ <?= number_format((float) ($cat['valor_inventario'] ?? 0), 2) ?></td>
                                    <td class="text-center">
                                        <?php if ($cat['estado'] === 'activo'): ?>
                                            <span class="badge bg-success px-3 py-1">Activa</span>
                                        <?php else: ?>
                                            <span class="badge bg-secondary px-3 py-1">Inactiva</span>
                                        <?php endif; ?>
                                    </td>
                                    <td class="text-center">
                                        <div class="btn-group btn-group-sm">
                                            <a href="index.php?controller=categoria&action=ver&id=<?= (int) $cat['id_categoria'] ?>" class="btn btn-outline-info"><i class="fas fa-eye"></i></a>
                                            <a href="index.php?controller=categoria&action=editar&id=<?= (int) $cat['id_categoria'] ?>" class="btn btn-outline-primary"><i class="fas fa-edit"></i></a>
                                            <?php if ($cat['estado'] === 'activo'): ?>
                                                <button type="button" class="btn btn-outline-danger btn-delete" 
                                                data-url="index.php?controller=categoria&action=eliminar&id=<?= (int) $cat['id_categoria'] ?>" 
                                                data-nombre="<?= htmlspecialchars($cat['nombre'], ENT_QUOTES) ?>">
                                                <i class="fas fa-ban"></i>
                                                </button>
                                            <?php else: ?>
                                                <button type="button" class="btn btn-outline-success btn-activate" data-url="index.php?controller=categoria&action=activar&id=<?= (int) $cat['id_categoria'] ?>" data-nombre="<?= htmlspecialchars($cat['nombre'], ENT_QUOTES) ?>"><i class="fas fa-check-circle"></i></button>
                                            <?php endif; ?>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
        
        <!-- Paginación -->
        <?php if ($totalPaginas > 1): ?>
        <div class="card-footer bg-white border-top-0 py-3">
            <ul class="pagination justify-content-center mb-0">
                <?php
                $baseUrl = 'index.php?controller=categoria&action=index'
                         . '&mostrar=' . ($activas ? 'activas' : 'inactivas')
                         . ($filtro ? '&q=' . urlencode($filtro) : '');
                ?>
                <li class="page-item <?= $page <= 1 ? 'disabled' : '' ?>">
                    <a class="page-link" href="<?= $baseUrl ?>&page=<?= $page - 1 ?>"><i class="fas fa-chevron-left"></i></a>
                </li>
                <?php for ($p = max(1, $page - 2); $p <= min($totalPaginas, $page + 2); $p++): ?>
                    <li class="page-item <?= $p === $page ? 'active' : '' ?>">
                        <a class="page-link" href="<?= $baseUrl ?>&page=<?= $p ?>"><?= $p ?></a>
                    </li>
                <?php endfor; ?>
                <li class="page-item <?= $page >= $totalPaginas ? 'disabled' : '' ?>">
                    <a class="page-link" href="<?= $baseUrl ?>&page=<?= $page + 1 ?>"><i class="fas fa-chevron-right"></i></a>
                </li>
            </ul>
        </div>
        <?php endif; ?>
    </div>
</div>

<!-- CSS adicional -->
<style>
.stat-card { transition: transform 0.2s ease, box-shadow 0.2s ease; }
.stat-card:hover { transform: translateY(-3px); box-shadow: 0 10px 20px rgba(0,0,0,0.08) !important; }
.stat-label { font-size: 0.7rem; letter-spacing: 0.05em; }
.stat-value { font-size: 1.7rem; }
#dashboardPanel { animation: fadeInDown 0.3s ease; }
@keyframes fadeInDown {
    from { opacity: 0; transform: translateY(-10px); }
    to { opacity: 1; transform: translateY(0); }
}
</style>

<!-- DATOS PARA JAVASCRIPT -->
<script>
    // Flash del servidor (create/update/delete) → leído por initFlashAlert()
    const categoriaSweetAlert = <?= (isset($sweetAlert) && $sweetAlert)
        ? json_encode([
            'title' => $sweetAlert['title'] ?? 'Aviso',
            'text'  => $sweetAlert['text']  ?? ($sweetAlert['message'] ?? ''),
            'type'  => $sweetAlert['type']  ?? 'info',
        ])
        : 'null' ?>;

    function formatNumber(num) {
        if (num === null || num === undefined) return '0';
        return new Intl.NumberFormat('es-PE').format(num);
    }
    
    // Event listener para el botón de dashboard después de que el DOM esté listo
    document.addEventListener('DOMContentLoaded', function() {
        const toggleBtn = document.getElementById('toggleDashboardBtn');
        if (toggleBtn) {
            toggleBtn.addEventListener('click', function() {
                const panel = document.getElementById('dashboardPanel');
                if (panel) {
                    const isVisible = panel.style.display === 'block';
                    panel.style.display = isVisible ? 'none' : 'block';
                    
                    if (!isVisible) {
                        console.log('📊 Abriendo dashboard...');
                        setTimeout(function() {
                            if (typeof window.cargarEstadisticas === 'function') {
                                window.cargarEstadisticas();
                            } else {
                                console.warn('cargarEstadisticas no disponible aún');
                            }
                        }, 100);
                    }
                }
            });
        }
    });
</script>

<!-- JS del módulo categorías (debe cargarse después de definir las variables) -->
<script src="<?= BASE_URL ?>/views/inventario/categorias/js/crear.js"></script>
<?php include __DIR__ . '/../../layouts/footer.php'; ?>