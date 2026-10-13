<?php 
$currentPage = 'almacenes';
include __DIR__ . '/../../layouts/inventario_layout.php'; 
?>

<link rel="stylesheet" href="<?= BASE_URL ?>assets/css/almacenes.css">

<div class="container-fluid px-3 py-2">
    <!-- Encabezado -->
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h1 class="h5 fw-bold mb-0">
                <i class="fas fa-warehouse me-2" style="color:var(--vt-primary);"></i>Detalle de Almacén
            </h1>
            <ol class="breadcrumb mb-0 mt-1">
                <li class="breadcrumb-item small"><a href="index.php?controller=dashboard&action=index">Dashboard</a></li>
                <li class="breadcrumb-item small"><a href="index.php?controller=almacen&action=index">Almacenes</a></li>
                <li class="breadcrumb-item small active"><?= htmlspecialchars($almacen['nombre']) ?></li>
            </ol>
        </div>
        <div>
            <a href="index.php?controller=almacen&action=index" class="btn btn-sm btn-outline-secondary">
                <i class="fas fa-arrow-left me-1"></i>Volver
            </a>
            <?php if ($almacen['estado'] === 'activo'): ?>
            <a href="index.php?controller=almacen&action=editar&id=<?= $almacen['id_almacen'] ?>" class="btn btn-sm btn-primary ms-2">
                <i class="fas fa-edit me-1"></i>Editar
            </a>
            <button id="btnEliminarAlmacen" class="btn btn-sm btn-danger ms-2"
                    data-id="<?= $almacen['id_almacen'] ?>"
                    data-nombre="<?= htmlspecialchars($almacen['nombre']) ?>">
                <i class="fas fa-trash-alt me-1"></i>Desactivar
            </button>
            <?php endif; ?>
        </div>
    </div>

    <div class="row">
        <!-- Información principal -->
        <div class="col-md-5">
            <div class="almacen-info-card">
                <div class="card-header">
                    <h5 class="mb-0"><i class="fas fa-info-circle me-2"></i>Información del Almacén</h5>
                </div>
                <div class="card-body">
                    <table class="table table-borderless mb-0">
                        <tr>
                            <td class="info-label">Código</td>
                            <td class="info-value"><code><?= htmlspecialchars($almacen['codigo']) ?></code></td>
                        </tr>
                        <tr>
                            <td class="info-label">Nombre</td>
                            <td class="info-value"><?= htmlspecialchars($almacen['nombre']) ?></td>
                        </tr>
                        <tr>
                            <td class="info-label">Tipo</td>
                            <td class="info-value">
                                <span class="badge-almacen-<?= $almacen['tipo'] ?>">
                                    <?= ucfirst($almacen['tipo']) ?>
                                </span>
                            </td>
                        </tr>
                        <tr>
                            <td class="info-label">Estado</td>
                            <td class="info-value">
                                <span class="badge-almacen-<?= $almacen['estado'] ?>">
                                    <?= ucfirst($almacen['estado']) ?>
                                </span>
                            </td>
                        </tr>
                        <tr>
                            <td class="info-label">Responsable</td>
                            <td class="info-value"><?= htmlspecialchars($almacen['responsable']) ?></td>
                        </tr>
                        <?php if (!empty($almacen['telefono'])): ?>
                        <tr>
                            <td class="info-label">Teléfono</td>
                            <td class="info-value"><?= htmlspecialchars($almacen['telefono']) ?></td>
                        </tr>
                        <?php endif; ?>
                        <?php if (!empty($almacen['direccion'])): ?>
                        <tr>
                            <td class="info-label">Dirección</td>
                            <td class="info-value"><?= htmlspecialchars($almacen['direccion']) ?></td>
                        </tr>
                        <?php endif; ?>
                        <?php if (!empty($almacen['ciudad'])): ?>
                        <tr>
                            <td class="info-label">Ciudad</td>
                            <td class="info-value"><?= htmlspecialchars($almacen['ciudad']) ?></td>
                        </tr>
                        <?php endif; ?>
                        <tr>
                            <td class="info-label">Capacidad Máxima</td>
                            <td class="info-value">
                                <?= $almacen['capacidad_maxima'] ? number_format($almacen['capacidad_maxima']) . ' unidades' : 'Sin límite' ?>
                            </td>
                        </tr>
                        <tr>
                            <td class="info-label">Fecha Creación</td>
                            <td class="info-value"><?= $almacen['fecha_creacion_format'] ?></td>
                        </tr>
                    </table>
                </div>
            </div>
        </div>

        <!-- Estadísticas -->
        <div class="col-md-7">
            <div class="row g-3">
                <div class="col-md-6">
                    <div class="almacen-stat-card p-3 position-relative">
                        <i class="fas fa-map-marker-alt stat-icon"></i>
                        <h6 class="stat-label">Total Ubicaciones</h6>
                        <div class="stat-value"><?= $almacen['total_ubicaciones'] ?? 0 ?></div>
                        <small class="text-muted">
                            <?= ($almacen['ubicaciones_disponibles'] ?? 0) ?> disponibles |
                            <?= ($almacen['ubicaciones_ocupadas'] ?? 0) ?> ocupadas
                        </small>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="almacen-stat-card p-3 position-relative">
                        <i class="fas fa-boxes stat-icon"></i>
                        <h6 class="stat-label">Productos</h6>
                        <div class="stat-value"><?= number_format($almacen['total_productos'] ?? 0) ?></div>
                        <small class="text-muted">
                            Stock total: <?= number_format($almacen['stock_total'] ?? 0) ?> unidades
                        </small>
                    </div>
                </div>
                <div class="col-md-12">
                    <div class="almacen-stat-card p-3 position-relative">
                        <i class="fas fa-chart-line stat-icon"></i>
                        <h6 class="stat-label">Valor del Inventario</h6>
                        <div class="stat-value">S/ <?= number_format($almacen['valor_inventario'] ?? 0, 2) ?></div>
                        <div class="almacen-capacity-bar mt-2">
                            <div class="progress-fill" style="width: <?= min(100, ($almacen['stock_total'] ?? 0) / max(1, $almacen['capacidad_maxima']) * 100) ?>%"></div>
                        </div>
                        <small class="text-muted">
                            Ocupación: <?= round(min(100, ($almacen['stock_total'] ?? 0) / max(1, $almacen['capacidad_maxima']) * 100)) ?>% de capacidad
                        </small>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Ubicaciones del almacén -->
    <div class="card shadow-sm mt-4">
        <div class="card-header bg-white d-flex justify-content-between align-items-center">
            <h5 class="mb-0"><i class="fas fa-map-marker-alt me-2"></i>Ubicaciones del Almacén</h5>
            <a href="index.php?controller=ubicacion&action=crear&id_almacen=<?= $almacen['id_almacen'] ?>" class="btn btn-sm btn-primary">
                <i class="fas fa-plus me-1"></i>Agregar Ubicación
            </a>
        </div>
        <div class="card-body">
            <?php if (empty($ubicaciones)): ?>
            <div class="text-center py-4 text-muted">
                <i class="fas fa-map-marked-alt fa-3x mb-3 d-block"></i>
                <p>No hay ubicaciones registradas en este almacén.</p>
                <a href="index.php?controller=ubicacion&action=crear&id_almacen=<?= $almacen['id_almacen'] ?>" class="btn btn-sm btn-primary">
                    <i class="fas fa-plus me-1"></i>Crear primera ubicación
                </a>
            </div>
            <?php else: ?>
            <div class="almacen-ubicaciones-grid">
                <?php foreach ($ubicaciones as $ubi): ?>
                <div class="ubicacion-mini-card <?= $ubi['estado'] ?>"
                     data-id="<?= $ubi['id_ubicacion'] ?>"
                     data-codigo="<?= htmlspecialchars($ubi['codigo']) ?>">
                    <div class="codigo"><?= htmlspecialchars($ubi['codigo']) ?></div>
                    <div class="ubicacion"><?= htmlspecialchars($ubi['ubicacion_completa'] ?? $ubi['codigo']) ?></div>
                    <span class="estado-badge bg-<?= $ubi['estado'] === 'disponible' ? 'success' : ($ubi['estado'] === 'ocupado' ? 'danger' : 'warning') ?>">
                        <?= ucfirst($ubi['estado']) ?>
                    </span>
                    <div class="productos-count mt-2">
                        <i class="fas fa-box"></i> <?= $ubi['productos_en_ubicacion'] ?? 0 ?> productos
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<meta name="csrf-token" content="<?= SessionMiddleware::generateCSRFToken() ?>">
<script src="<?= BASE_URL ?>assets/js/almacenes.js"></script>

<?php include __DIR__ . '/../../layouts/footer.php'; ?>