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
                <i class="fas fa-map-marker-alt me-2" style="color:var(--vt-primary);"></i>Detalle de Ubicación
            </h1>
            <ol class="breadcrumb mb-0 mt-1">
                <li class="breadcrumb-item small"><a href="index.php?controller=dashboard&action=index">Dashboard</a></li>
                <li class="breadcrumb-item small"><a href="index.php?controller=ubicacion&action=index">Ubicaciones</a></li>
                <li class="breadcrumb-item small active"><?= htmlspecialchars($ubicacion['codigo']) ?></li>
            </ol>
        </div>
        <div>
            <a href="index.php?controller=ubicacion&action=index" class="btn btn-sm btn-outline-secondary">
                <i class="fas fa-arrow-left me-1"></i>Volver
            </a>
            <a href="index.php?controller=ubicacion&action=editar&id=<?= $ubicacion['id_ubicacion'] ?>" class="btn btn-sm btn-primary ms-2">
                <i class="fas fa-edit me-1"></i>Editar
            </a>
            <?php if (empty($productos)): ?>
            <button id="btnEliminarUbicacion" class="btn btn-sm btn-danger ms-2"
                    data-id="<?= $ubicacion['id_ubicacion'] ?>"
                    data-codigo="<?= htmlspecialchars($ubicacion['codigo']) ?>">
                <i class="fas fa-trash-alt me-1"></i>Eliminar
            </button>
            <?php endif; ?>
        </div>
    </div>

    <div class="row">
        <!-- Información de la ubicación -->
        <div class="col-md-5">
            <div class="ubicacion-detalle-card">
                <div class="card-header">
                    <h5 class="mb-0">
                        <i class="fas fa-info-circle me-2"></i>
                        <?= htmlspecialchars($ubicacion['codigo']) ?>
                    </h5>
                </div>
                <div class="info-section">
                    <div class="row">
                        <div class="col-6">
                            <div class="info-label">Almacén</div>
                            <div class="info-value">
                                <a href="index.php?controller=almacen&action=ver&id=<?= $ubicacion['id_almacen'] ?>">
                                    <?= htmlspecialchars($ubicacion['almacen_nombre']) ?>
                                </a>
                            </div>
                        </div>
                        <div class="col-6">
                            <div class="info-label">Estado</div>
                            <div class="info-value">
                                <span class="badge bg-<?= $ubicacion['estado'] === 'disponible' ? 'success' : ($ubicacion['estado'] === 'ocupado' ? 'danger' : 'warning') ?>">
                                    <?= ucfirst($ubicacion['estado']) ?>
                                </span>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="info-section">
                    <div class="info-label">Ubicación Física</div>
                    <div class="info-value">
                        <?= htmlspecialchars($ubicacion['ubicacion_completa'] ?? $ubicacion['codigo']) ?>
                    </div>
                </div>
                <div class="info-section">
                    <div class="row">
                        <div class="col-6">
                            <div class="info-label">Capacidad</div>
                            <div class="info-value">
                                <?= $ubicacion['capacidad'] ? number_format($ubicacion['capacidad']) . ' productos' : 'Sin límite' ?>
                            </div>
                        </div>
                        <div class="col-6">
                            <div class="info-label">Productos Actuales</div>
                            <div class="info-value"><?= count($productos) ?> producto(s)</div>
                        </div>
                    </div>
                </div>
                <?php if (!empty($ubicacion['descripcion'])): ?>
                <div class="info-section">
                    <div class="info-label">Descripción</div>
                    <div class="info-value"><?= nl2br(htmlspecialchars($ubicacion['descripcion'])) ?></div>
                </div>
                <?php endif; ?>
                <div class="info-section">
                    <div class="info-label">Fecha Creación</div>
                    <div class="info-value"><?= $ubicacion['fecha_creacion_format'] ?></div>
                </div>
            </div>
            
            <!-- Acciones rápidas -->
            <div class="card mt-3">
                <div class="card-body">
                    <h6 class="mb-3"><i class="fas fa-bolt me-2"></i>Acciones rápidas</h6>
                    <div class="d-grid gap-2">
                        <button id="btnActualizarEstado" class="btn btn-outline-info btn-sm"
                                data-id="<?= $ubicacion['id_ubicacion'] ?>">
                            <i class="fas fa-sync-alt me-1"></i>Actualizar estado automático
                        </button>
                        <?php if ($ubicacion['estado'] !== 'mantenimiento'): ?>
                        <button id="btnAsignarProducto" class="btn btn-success btn-sm"
                                data-id="<?= $ubicacion['id_ubicacion'] ?>"
                                data-codigo="<?= htmlspecialchars($ubicacion['codigo']) ?>">
                            <i class="fas fa-box me-1"></i>Asignar producto
                        </button>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>

        <!-- Productos en esta ubicación -->
        <div class="col-md-7">
            <div class="card shadow-sm">
                <div class="card-header bg-white">
                    <h5 class="mb-0"><i class="fas fa-boxes me-2"></i>Productos en esta ubicación</h5>
                </div>
                <div class="card-body p-0">
                    <?php if (empty($productos)): ?>
                    <div class="text-center py-4">
                        <i class="fas fa-box-open fa-3x text-muted mb-3 d-block"></i>
                        <p class="text-muted">No hay productos asignados a esta ubicación.</p>
                        <?php if ($ubicacion['estado'] !== 'mantenimiento'): ?>
                        <button class="btn btn-sm btn-success" id="btnAsignarProductoEmpty"
                                data-id="<?= $ubicacion['id_ubicacion'] ?>"
                                data-codigo="<?= htmlspecialchars($ubicacion['codigo']) ?>">
                            <i class="fas fa-plus me-1"></i>Asignar producto
                        </button>
                        <?php endif; ?>
                    </div>
                    <?php else: ?>
                    <div class="table-responsive">
                        <table class="table table-hover mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th>Código</th>
                                    <th>Producto</th>
                                    <th>Categoría</th>
                                    <th class="text-center">Stock</th>
                                    <th class="text-end">Valor</th>
                                    <th class="text-center">Acciones</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($productos as $prod): ?>
                                <tr>
                                    <td><code><?= htmlspecialchars($prod['codigo']) ?></code></td>
                                    <td>
                                        <strong><?= htmlspecialchars($prod['nombre']) ?></strong>
                                        <?php if ($prod['estado_stock'] !== 'normal'): ?>
                                        <span class="badge bg-warning text-dark ms-1"><?= ucfirst($prod['estado_stock']) ?></span>
                                        <?php endif; ?>
                                    </td>
                                    <td><?= htmlspecialchars($prod['categoria_nombre'] ?? '-') ?></td>
                                    <td class="text-center"><?= number_format($prod['stock_actual']) ?></td>
                                    <td class="text-end">S/ <?= number_format($prod['valor_inventario'] ?? $prod['stock_actual'] * $prod['precio_venta'], 2) ?></td>
                                    <td class="text-center">
                                        <a href="index.php?controller=producto&action=ver&id=<?= $prod['id_producto'] ?>" 
                                           class="btn btn-sm btn-outline-primary" title="Ver producto">
                                            <i class="fas fa-eye"></i>
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

            <!-- Últimos movimientos -->
            <?php if (!empty($movimientos)): ?>
            <div class="card shadow-sm mt-3">
                <div class="card-header bg-white">
                    <h5 class="mb-0"><i class="fas fa-history me-2"></i>Últimos movimientos</h5>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-sm mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th>Fecha</th>
                                    <th>Tipo</th>
                                    <th>Producto</th>
                                    <th class="text-center">Cantidad</th>
                                    <th>Usuario</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($movimientos as $mov): ?>
                                <tr>
                                    <td><?= date('d/m/Y H:i', strtotime($mov['fecha_movimiento'])) ?></td>
                                    <td>
                                        <span class="badge bg-<?= $mov['tipo_movimiento'] === 'entrada' ? 'success' : 'danger' ?>">
                                            <?= ucfirst($mov['tipo_movimiento']) ?>
                                        </span>
                                    </td>
                                    <td><?= htmlspecialchars($mov['producto_nombre']) ?></td>
                                    <td class="text-center">
                                        <?= $mov['tipo_movimiento'] === 'entrada' ? '+' : '-' ?> <?= number_format($mov['cantidad']) ?>
                                    </td>
                                    <td><?= htmlspecialchars($mov['usuario_nombre']) ?></td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<meta name="csrf-token" content="<?= SessionMiddleware::generateCSRFToken() ?>">
<script src="<?= BASE_URL ?>assets/js/ubicaciones.js"></script>

<?php include __DIR__ . '/../../layouts/footer.php'; ?>