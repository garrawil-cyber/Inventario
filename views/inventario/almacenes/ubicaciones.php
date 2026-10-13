<?php 
$currentPage = 'almacenes';
include __DIR__ . '/../../layouts/inventario_layout.php'; 
?>

<link rel="stylesheet" href="<?= BASE_URL ?>assets/css/ubicaciones.css">

<div class="container-fluid px-3 py-2">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h1 class="h5 fw-bold mb-0">
                <i class="fas fa-map-marker-alt me-2" style="color:var(--vt-primary);"></i>Ubicaciones de <?= htmlspecialchars($almacen['nombre']) ?>
            </h1>
            <ol class="breadcrumb mb-0 mt-1">
                <li class="breadcrumb-item small"><a href="index.php?controller=dashboard&action=index">Dashboard</a></li>
                <li class="breadcrumb-item small"><a href="index.php?controller=almacen&action=index">Almacenes</a></li>
                <li class="breadcrumb-item small"><a href="index.php?controller=almacen&action=ver&id=<?= $almacen['id_almacen'] ?>"><?= htmlspecialchars($almacen['nombre']) ?></a></li>
                <li class="breadcrumb-item small active">Ubicaciones</li>
            </ol>
        </div>
        <div>
            <a href="index.php?controller=almacen&action=ver&id=<?= $almacen['id_almacen'] ?>" class="btn btn-sm btn-outline-secondary">
                <i class="fas fa-arrow-left me-1"></i>Volver al Almacén
            </a>
            <a href="index.php?controller=ubicacion&action=crear&id_almacen=<?= $almacen['id_almacen'] ?>" class="btn btn-sm btn-primary ms-2">
                <i class="fas fa-plus me-1"></i>Nueva Ubicación
            </a>
        </div>
    </div>

    <!-- Resumen rápido -->
    <div class="row g-3 mb-4">
        <div class="col-md-3">
            <div class="card text-center border-0 shadow-sm">
                <div class="card-body">
                    <i class="fas fa-map-marker-alt fa-2x text-primary mb-2"></i>
                    <h3 class="mb-0"><?= count($ubicaciones) ?></h3>
                    <small class="text-muted">Total Ubicaciones</small>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card text-center border-0 shadow-sm">
                <div class="card-body">
                    <i class="fas fa-check-circle fa-2x text-success mb-2"></i>
                    <h3 class="mb-0"><?= count(array_filter($ubicaciones, fn($u) => $u['estado'] == 'disponible')) ?></h3>
                    <small class="text-muted">Disponibles</small>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card text-center border-0 shadow-sm">
                <div class="card-body">
                    <i class="fas fa-box fa-2x text-danger mb-2"></i>
                    <h3 class="mb-0"><?= count(array_filter($ubicaciones, fn($u) => $u['estado'] == 'ocupado')) ?></h3>
                    <small class="text-muted">Ocupadas</small>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card text-center border-0 shadow-sm">
                <div class="card-body">
                    <i class="fas fa-tools fa-2x text-warning mb-2"></i>
                    <h3 class="mb-0"><?= count(array_filter($ubicaciones, fn($u) => $u['estado'] == 'mantenimiento')) ?></h3>
                    <small class="text-muted">Mantenimiento</small>
                </div>
            </div>
        </div>
    </div>

    <!-- Grid de ubicaciones -->
    <?php if (empty($ubicaciones)): ?>
    <div class="card shadow-sm">
        <div class="card-body text-center py-5">
            <i class="fas fa-map-marked-alt fa-4x text-muted mb-3 d-block"></i>
            <h6>No hay ubicaciones registradas</h6>
            <p class="text-muted">Este almacén aún no tiene ubicaciones configuradas.</p>
            <a href="index.php?controller=ubicacion&action=crear&id_almacen=<?= $almacen['id_almacen'] ?>" class="btn btn-primary">
                <i class="fas fa-plus me-1"></i>Crear primera ubicación
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
             data-productos="<?= $ubi['productos_en_ubicacion'] ?? 0 ?>"
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
            </div>
            
            <div class="capacidad-info">
                <div class="capacidad-label">CAPACIDAD</div>
                <div class="capacidad-bar">
                    <?php 
                    $capacidad = $ubi['capacidad'];
                    $productos = $ubi['productos_en_ubicacion'] ?? 0;
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
                    <i class="fas fa-box"></i> <?= $productos ?> producto(s) asignado(s)
                </div>
            </div>
        </div>
        <?php endforeach; ?>
    </div>
    <?php endif; ?>
</div>

<meta name="csrf-token" content="<?= SessionMiddleware::generateCSRFToken() ?>">
<script src="<?= BASE_URL ?>assets/js/ubicaciones.js"></script>

<?php include __DIR__ . '/../../layouts/footer.php'; ?>