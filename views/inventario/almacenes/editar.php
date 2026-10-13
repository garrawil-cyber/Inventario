<?php 
$currentPage = 'almacenes';
include __DIR__ . '/../../layouts/inventario_layout.php'; 
?>

<link rel="stylesheet" href="<?= BASE_URL ?>assets/css/almacenes.css">

<div class="container-fluid px-3 py-2">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h1 class="h5 fw-bold mb-0">
                <i class="fas fa-edit me-2" style="color:var(--vt-primary);"></i>Editar Almacén
            </h1>
            <ol class="breadcrumb mb-0 mt-1">
                <li class="breadcrumb-item small"><a href="index.php?controller=dashboard&action=index">Dashboard</a></li>
                <li class="breadcrumb-item small"><a href="index.php?controller=almacen&action=index">Almacenes</a></li>
                <li class="breadcrumb-item small active"><?= htmlspecialchars($almacen['nombre']) ?></li>
            </ol>
        </div>
        <div>
            <a href="index.php?controller=almacen&action=ver&id=<?= $almacen['id_almacen'] ?>" class="btn btn-sm btn-outline-secondary">
                <i class="fas fa-arrow-left me-1"></i>Volver
            </a>
        </div>
    </div>

    <div class="row">
        <div class="col-md-8 mx-auto">
            <div class="card shadow-sm">
                <div class="card-header bg-white">
                    <h5 class="mb-0"><i class="fas fa-warehouse me-2"></i>Editar Información</h5>
                </div>
                <div class="card-body">
                    <form method="POST" action="index.php?controller=almacen&action=actualizar">
                        <input type="hidden" name="csrf_token" value="<?= $csrfToken ?>">
                        <input type="hidden" name="id_almacen" value="<?= $almacen['id_almacen'] ?>">

                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Código</label>
                                <input type="text" name="codigo" class="form-control" 
                                       value="<?= htmlspecialchars($almacen['codigo']) ?>" required>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Nombre *</label>
                                <input type="text" name="nombre" class="form-control" 
                                       value="<?= htmlspecialchars($almacen['nombre']) ?>" required>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Tipo</label>
                                <select name="tipo" class="form-select">
                                    <?php foreach ($tipos as $t): ?>
                                    <option value="<?= $t ?>" <?= ($almacen['tipo'] == $t) ? 'selected' : '' ?>>
                                        <?= ucfirst($t) ?>
                                    </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Estado</label>
                                <select name="estado" class="form-select">
                                    <option value="activo" <?= ($almacen['estado'] == 'activo') ? 'selected' : '' ?>>Activo</option>
                                    <option value="inactivo" <?= ($almacen['estado'] == 'inactivo') ? 'selected' : '' ?>>Inactivo</option>
                                </select>
                                <?php if ($almacen['estado'] == 'activo' && ($almacen['total_productos'] ?? 0) > 0): ?>
                                <small class="text-warning">
                                    <i class="fas fa-exclamation-triangle me-1"></i>
                                    Este almacén tiene productos activos. Desactivarlo también afectará a sus ubicaciones.
                                </small>
                                <?php endif; ?>
                            </div>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Responsable *</label>
                            <input type="text" name="responsable" class="form-control" 
                                   value="<?= htmlspecialchars($almacen['responsable']) ?>" required>
                        </div>

                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Teléfono</label>
                                <input type="tel" name="telefono" class="form-control" 
                                       value="<?= htmlspecialchars($almacen['telefono'] ?? '') ?>">
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Capacidad Máxima</label>
                                <input type="number" name="capacidad_maxima" id="capacidad_maxima" class="form-control" 
                                       value="<?= htmlspecialchars($almacen['capacidad_maxima'] ?? '') ?>">
                                <small class="text-muted" id="capacidad_display">
                                    <?= !empty($almacen['capacidad_maxima']) ? number_format($almacen['capacidad_maxima']) . ' unidades' : 'Sin límite' ?>
                                </small>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Ciudad</label>
                                <input type="text" name="ciudad" class="form-control" 
                                       value="<?= htmlspecialchars($almacen['ciudad'] ?? '') ?>">
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Dirección</label>
                                <input type="text" name="direccion" class="form-control" 
                                       value="<?= htmlspecialchars($almacen['direccion'] ?? '') ?>">
                            </div>
                        </div>

                        <div class="alert alert-info small">
                            <i class="fas fa-info-circle me-2"></i>
                            <strong>Nota:</strong> No se puede modificar la capacidad si hay productos ocupando ubicaciones que excedan la nueva capacidad.
                        </div>

                        <hr>

                        <div class="d-flex justify-content-end gap-2">
                            <a href="index.php?controller=almacen&action=ver&id=<?= $almacen['id_almacen'] ?>" class="btn btn-secondary">
                                <i class="fas fa-times me-1"></i>Cancelar
                            </a>
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-save me-1"></i>Actualizar Almacén
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<script src="<?= BASE_URL ?>assets/js/almacenes.js"></script>

<?php include __DIR__ . '/../../layouts/footer.php'; ?>