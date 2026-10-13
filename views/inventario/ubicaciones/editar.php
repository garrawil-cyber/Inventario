<?php 
$currentPage = 'ubicaciones';
include __DIR__ . '/../../layouts/inventario_layout.php'; 
?>

<link rel="stylesheet" href="<?= BASE_URL ?>assets/css/ubicaciones.css">

<div class="container-fluid px-3 py-2">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h1 class="h5 fw-bold mb-0">
                <i class="fas fa-edit me-2" style="color:var(--vt-primary);"></i>Editar Ubicación
            </h1>
            <ol class="breadcrumb mb-0 mt-1">
                <li class="breadcrumb-item small"><a href="index.php?controller=dashboard&action=index">Dashboard</a></li>
                <li class="breadcrumb-item small"><a href="index.php?controller=ubicacion&action=index">Ubicaciones</a></li>
                <li class="breadcrumb-item small active"><?= htmlspecialchars($ubicacion['codigo']) ?></li>
            </ol>
        </div>
        <div>
            <a href="index.php?controller=ubicacion&action=ver&id=<?= $ubicacion['id_ubicacion'] ?>" class="btn btn-sm btn-outline-secondary">
                <i class="fas fa-arrow-left me-1"></i>Volver
            </a>
        </div>
    </div>

    <div class="row">
        <div class="col-md-8 mx-auto">
            <div class="ubicacion-form-card">
                <div class="card-header">
                    <h5 class="mb-0"><i class="fas fa-map-marker-alt me-2"></i>Editar Información</h5>
                </div>
                <div class="card-body">
                    <form method="POST" action="index.php?controller=ubicacion&action=actualizar">
                        <input type="hidden" name="csrf_token" value="<?= $csrfToken ?>">
                        <input type="hidden" name="id_ubicacion" value="<?= $ubicacion['id_ubicacion'] ?>">

                        <div class="mb-3">
                            <label class="form-label">Almacén</label>
                            <input type="text" class="form-control" readonly
                                   value="<?= htmlspecialchars($ubicacion['almacen_nombre']) ?>">
                            <small class="text-muted">No se puede cambiar el almacén de una ubicación</small>
                        </div>

                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Código *</label>
                                <input type="text" name="codigo" class="form-control" 
                                       value="<?= htmlspecialchars($ubicacion['codigo']) ?>" required>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Estado</label>
                                <select name="estado" class="form-select">
                                    <?php foreach ($estados as $e): ?>
                                    <option value="<?= $e ?>" <?= ($ubicacion['estado'] == $e) ? 'selected' : '' ?>>
                                        <?= ucfirst($e) ?>
                                    </option>
                                    <?php endforeach; ?>
                                </select>
                                <?php if ($ubicacion['estado'] != 'disponible' && !empty($productos)): ?>
                                <small class="text-warning">
                                    <i class="fas fa-exclamation-triangle me-1"></i>
                                    Esta ubicación tiene <?= count($productos) ?> producto(s) asignado(s).
                                </small>
                                <?php endif; ?>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-4 mb-3">
                                <label class="form-label">Pasillo</label>
                                <input type="text" name="pasillo" class="form-control" 
                                       value="<?= htmlspecialchars($ubicacion['pasillo'] ?? '') ?>">
                            </div>
                            <div class="col-md-4 mb-3">
                                <label class="form-label">Nivel</label>
                                <input type="text" name="nivel" class="form-control" 
                                       value="<?= htmlspecialchars($ubicacion['nivel'] ?? '') ?>">
                            </div>
                            <div class="col-md-4 mb-3">
                                <label class="form-label">Posición</label>
                                <input type="text" name="posicion" class="form-control" 
                                       value="<?= htmlspecialchars($ubicacion['posicion'] ?? '') ?>">
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Capacidad (productos)</label>
                                <input type="number" name="capacidad" class="form-control" 
                                       value="<?= htmlspecialchars($ubicacion['capacidad'] ?? '') ?>">
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Descripción</label>
                                <input type="text" name="descripcion" class="form-control" 
                                       value="<?= htmlspecialchars($ubicacion['descripcion'] ?? '') ?>">
                            </div>
                        </div>

                        <div class="alert alert-warning small">
                            <i class="fas fa-exclamation-triangle me-2"></i>
                            <strong>Nota:</strong> Si reduces la capacidad y ya hay más productos asignados, no podrás guardar los cambios.
                        </div>

                        <hr>

                        <div class="d-flex justify-content-end gap-2">
                            <a href="index.php?controller=ubicacion&action=ver&id=<?= $ubicacion['id_ubicacion'] ?>" class="btn btn-secondary">
                                <i class="fas fa-times me-1"></i>Cancelar
                            </a>
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-save me-1"></i>Actualizar Ubicación
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<script src="<?= BASE_URL ?>assets/js/ubicaciones.js"></script>

<?php include __DIR__ . '/../../layouts/footer.php'; ?>