<?php 
$currentPage = 'ubicaciones';
include __DIR__ . '/../../layouts/inventario_layout.php'; 
?>

<link rel="stylesheet" href="<?= BASE_URL ?>assets/css/ubicaciones.css">

<div class="container-fluid px-3 py-2">
    <!-- Encabezado -->
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h1 class="h5 fw-bold mb-0">
                <i class="fas fa-map-marker-alt me-2" style="color:var(--vt-primary);"></i>Gestión de Ubicaciones
            </h1>
            <ol class="breadcrumb mb-0 mt-1">
                <li class="breadcrumb-item small"><a href="index.php?controller=dashboard&action=index">Dashboard</a></li>
                <li class="breadcrumb-item small active">Ubicaciones</li>
            </ol>
        </div>
        <div>
            <a href="index.php?controller=ubicacion&action=crear" class="btn btn-sm btn-primary">
                <i class="fas fa-plus me-1"></i>Nueva Ubicación
            </a>
        </div>
    </div>

    <!-- Filtros -->
    <div class="almacen-filtros-card">
        <form id="filter-form" method="GET" action="index.php">
            <input type="hidden" name="controller" value="ubicacion">
            <input type="hidden" name="action" value="index">
            
            <div class="row g-3 align-items-end">
                <div class="col-md-4">
                    <label class="form-label">Buscar</label>
                    <input type="text" name="filtro" class="form-control form-control-sm filter-auto-submit" 
                           placeholder="Código, ubicación..." value="<?= htmlspecialchars($_GET['filtro'] ?? '') ?>">
                </div>
                <div class="col-md-3">
                    <label class="form-label">Almacén</label>
                    <select name="id_almacen" class="form-select form-select-sm filter-auto-submit">
                        <option value="">Todos</option>
                        <?php foreach ($almacenes as $alm): ?>
                        <option value="<?= $alm['value'] ?>" <?= (($_GET['id_almacen'] ?? '') == $alm['value']) ? 'selected' : '' ?>>
                            <?= htmlspecialchars($alm['text']) ?>
                        </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-3">
                    <label class="form-label">Estado</label>
                    <select name="estado" class="form-select form-select-sm filter-auto-submit">
                        <option value="">Todos</option>
                        <?php foreach ($estados as $e): ?>
                        <option value="<?= $e ?>" <?= (($_GET['estado'] ?? '') == $e) ? 'selected' : '' ?>>
                            <?= ucfirst($e) ?>
                        </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-2">
                    <button type="submit" class="btn btn-sm btn-secondary w-100">
                        <i class="fas fa-search me-1"></i>Filtrar
                    </button>
                </div>
            </div>
        </form>
    </div>

    <!-- Grid de ubicaciones -->
    <?php if (empty($ubicaciones)): ?>
    <div class="card shadow-sm">
        <div class="card-body text-center py-5">
            <i class="fas fa-map-marked-alt fa-4x text-muted mb-3 d-block"></i>
            <h6>No hay ubicaciones registradas</h6>
            <p class="text-muted">Comienza creando una nueva ubicación.</p>
            <a href="index.php?controller=ubicacion&action=crear" class="btn btn-primary">
                <i class="fas fa-plus me-1"></i>Nueva Ubicación
            </a>
        </div>
    </div>
    <?php else: ?>
    <div class="ubicacion-grid-container">
        <?php foreach ($ubicaciones as $ubi): ?>
        <div class="ubicacion-card <?= $ubi['estado'] ?>"
             data-id="<?= $ubi['id_ubicacion'] ?>"
             data-codigo="<?= htmlspecialchars($ubi['codigo']) ?>"
             data-estado="<?= $ubi['estado'] ?>"
             data-capacidad="<?= $ubi['capacidad'] ?>"
             data-productos="<?= $ubi['productos_activos'] ?? 0 ?>"
             onclick="verDetalleUbicacion(<?= $ubi['id_ubicacion'] ?>)">
            
            <div class="card-header">
                <span class="codigo"><?= htmlspecialchars($ubi['codigo']) ?></span>
                <span class="estado-badge bg-<?= $ubi['estado'] === 'disponible' ? 'success' : ($ubi['estado'] === 'ocupado' ? 'danger' : 'warning') ?>">
                    <?= ucfirst($ubi['estado']) ?>
                </span>
            </div>
            
            <div class="ubicacion-fisica">
                <span>Ubicación</span><br>
                <strong><?= htmlspecialchars($ubi['ubicacion_completa'] ?? $ubi['codigo']) ?></strong>
                <small class="d-block text-muted"><?= htmlspecialchars($ubi['almacen_nombre']) ?></small>
            </div>
            
            <div class="capacidad-info">
                <div class="capacidad-label">CAPACIDAD</div>
                <div class="capacidad-bar">
                    <?php 
                    $capacidad = $ubi['capacidad'];
                    $productos = $ubi['productos_activos'] ?? 0;
                    $porcentaje = $capacidad ? min(100, round($productos / $capacidad * 100)) : 0;
                    $fillClass = $porcentaje < 50 ? 'bajo' : ($porcentaje < 80 ? 'medio' : ($porcentaje < 100 ? 'alto' : 'lleno'));
                    ?>
                    <div class="capacidad-fill <?= $fillClass ?>" style="width: <?= $porcentaje ?>%"></div>
                </div>
                <div class="capacidad-text">
                    <?= $capacidad ? "{$productos} / {$capacidad} productos" : "{$productos} productos (sin límite)" ?>
                </div>
            </div>
            
            <div class="productos-info">
                <div class="productos-count">
                    <i class="fas fa-box"></i> <?= $ubi['productos_activos'] ?? 0 ?> producto(s)
                </div>
                <div class="productos-count">
                    <i class="fas fa-chart-line"></i> S/ <?= number_format($ubi['valor_inventario'] ?? 0, 2) ?>
                </div>
            </div>
        </div>
        <?php endforeach; ?>
    </div>
    
    <!-- Paginación -->
    <?php if ($totalPaginas > 1): ?>
    <div class="mt-4">
        <nav>
            <ul class="pagination justify-content-center">
                <li class="page-item <?= ($paginaActual <= 1) ? 'disabled' : '' ?>">
                    <a class="page-link" href="?controller=ubicacion&action=index&pagina=<?= $paginaActual-1 ?>&<?= http_build_query(array_diff_key($_GET, ['pagina' => 0])) ?>">
                        <i class="fas fa-chevron-left"></i>
                    </a>
                </li>
                <?php 
                $start = max(1, $paginaActual - 2);
                $end = min($totalPaginas, $paginaActual + 2);
                for ($i = $start; $i <= $end; $i++): ?>
                <li class="page-item <?= ($i == $paginaActual) ? 'active' : '' ?>">
                    <a class="page-link" href="?controller=ubicacion&action=index&pagina=<?= $i ?>&<?= http_build_query(array_diff_key($_GET, ['pagina' => 0])) ?>"><?= $i ?></a>
                </li>
                <?php endfor; ?>
                <li class="page-item <?= ($paginaActual >= $totalPaginas) ? 'disabled' : '' ?>">
                    <a class="page-link" href="?controller=ubicacion&action=index&pagina=<?= $paginaActual+1 ?>&<?= http_build_query(array_diff_key($_GET, ['pagina' => 0])) ?>">
                        <i class="fas fa-chevron-right"></i>
                    </a>
                </li>
            </ul>
        </nav>
    </div>
    <?php endif; ?>
    <?php endif; ?>
</div>

<meta name="csrf-token" content="<?= SessionMiddleware::generateCSRFToken() ?>">
<script src="<?= BASE_URL ?>assets/js/ubicaciones.js"></script>

<?php include __DIR__ . '/../../layouts/footer.php'; ?>