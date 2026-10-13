<?php 
$currentPage = 'almacenes';
include __DIR__ . '/../../layouts/inventario_layout.php'; 
?>

<link rel="stylesheet" href="<?= BASE_URL ?>assets/css/almacenes.css">

<div class="container-fluid px-3 py-2">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h1 class="h5 fw-bold mb-0">
                <i class="fas fa-plus-circle me-2" style="color:var(--vt-primary);"></i>Nuevo Almacén
            </h1>
            <ol class="breadcrumb mb-0 mt-1">
                <li class="breadcrumb-item small"><a href="index.php?controller=dashboard&action=index">Dashboard</a></li>
                <li class="breadcrumb-item small"><a href="index.php?controller=almacen&action=index">Almacenes</a></li>
                <li class="breadcrumb-item small active">Nuevo Almacén</li>
            </ol>
        </div>
        <div>
            <a href="index.php?controller=almacen&action=index" class="btn btn-sm btn-outline-secondary">
                <i class="fas fa-times me-1"></i>Cancelar
            </a>
        </div>
    </div>

    <div class="row">
        <div class="col-md-8 mx-auto">
            <div class="card shadow-sm">
                <div class="card-header bg-white">
                    <h5 class="mb-0"><i class="fas fa-warehouse me-2"></i>Información del Almacén</h5>
                </div>
                <div class="card-body">
                    <form method="POST" action="index.php?controller=almacen&action=guardar" id="almacen-form">
                        <input type="hidden" name="csrf_token" value="<?= $csrfToken ?>">

                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Código <span class="text-muted">(opcional)</span></label>
                                <input type="text" name="codigo" class="form-control" 
                                       placeholder="Ej: ALM-001" value="<?= htmlspecialchars($formData['codigo'] ?? '') ?>">
                                <small class="text-muted">Dejar en blanco para auto-generar</small>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Nombre *</label>
                                <input type="text" name="nombre" class="form-control" 
                                       placeholder="Ej: Almacén Central" required
                                       value="<?= htmlspecialchars($formData['nombre'] ?? '') ?>">
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Tipo</label>
                                <select name="tipo" class="form-select">
                                    <?php foreach ($tipos as $t): ?>
                                    <option value="<?= $t ?>" <?= (($formData['tipo'] ?? 'secundario') == $t) ? 'selected' : '' ?>>
                                        <?= ucfirst($t) ?>
                                    </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Estado</label>
                                <select name="estado" class="form-select">
                                    <option value="activo" <?= (($formData['estado'] ?? 'activo') == 'activo') ? 'selected' : '' ?>>Activo</option>
                                    <option value="inactivo" <?= (($formData['estado'] ?? '') == 'inactivo') ? 'selected' : '' ?>>Inactivo</option>
                                </select>
                            </div>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Responsable *</label>
                            <input type="text" name="responsable" class="form-control" 
                                   placeholder="Nombre del responsable" required
                                   value="<?= htmlspecialchars($formData['responsable'] ?? '') ?>">
                        </div>

                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Teléfono</label>
                                <input type="tel" name="telefono" class="form-control" 
                                       placeholder="Ej: 987654321" value="<?= htmlspecialchars($formData['telefono'] ?? '') ?>">
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Capacidad Máxima</label>
                                <input type="number" name="capacidad_maxima" id="capacidad_maxima" class="form-control" 
                                       placeholder="Unidades (opcional)" value="<?= htmlspecialchars($formData['capacidad_maxima'] ?? '') ?>">
                                <small class="text-muted" id="capacidad_display">
                                    <?= !empty($formData['capacidad_maxima']) ? number_format($formData['capacidad_maxima']) . ' unidades' : 'Sin límite' ?>
                                </small>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Ciudad</label>
                                <input type="text" name="ciudad" class="form-control" 
                                       placeholder="Ej: Lima" value="<?= htmlspecialchars($formData['ciudad'] ?? '') ?>">
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Dirección</label>
                                <input type="text" name="direccion" class="form-control" 
                                       placeholder="Dirección completa" value="<?= htmlspecialchars($formData['direccion'] ?? '') ?>">
                            </div>
                        </div>

                        <hr>

                        <div class="d-flex justify-content-end gap-2">
                            <a href="index.php?controller=almacen&action=index" class="btn btn-secondary">
                                <i class="fas fa-times me-1"></i>Cancelar
                            </a>
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-save me-1"></i>Guardar Almacén
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