<?php 
$currentPage = 'ubicaciones';
include __DIR__ . '/../../layouts/inventario_layout.php'; 
?>

<link rel="stylesheet" href="<?= BASE_URL ?>assets/css/ubicaciones.css">

<div class="container-fluid px-3 py-2">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h1 class="h5 fw-bold mb-0">
                <i class="fas fa-plus-circle me-2" style="color:var(--vt-primary);"></i>Nueva Ubicación
            </h1>
            <ol class="breadcrumb mb-0 mt-1">
                <li class="breadcrumb-item small"><a href="index.php?controller=dashboard&action=index">Dashboard</a></li>
                <li class="breadcrumb-item small"><a href="index.php?controller=ubicacion&action=index">Ubicaciones</a></li>
                <li class="breadcrumb-item small active">Nueva Ubicación</li>
            </ol>
        </div>
        <div>
            <a href="index.php?controller=ubicacion&action=index" class="btn btn-sm btn-outline-secondary">
                <i class="fas fa-times me-1"></i>Cancelar
            </a>
        </div>
    </div>

    <div class="row">
        <div class="col-md-8 mx-auto">
            <div class="ubicacion-form-card">
                <div class="card-header">
                    <h5 class="mb-0"><i class="fas fa-map-marker-alt me-2"></i>Información de la Ubicación</h5>
                </div>
                <div class="card-body">
                    <form method="POST" action="index.php?controller=ubicacion&action=guardar" id="ubicacion-form">
                        <input type="hidden" name="csrf_token" value="<?= $csrfToken ?>">
                        <?php if (isset($_GET['id_almacen'])): ?>
                        <input type="hidden" name="id_almacen" value="<?= (int)$_GET['id_almacen'] ?>">
                        <?php endif; ?>

                        <div class="mb-3">
                            <label class="form-label">Almacén *</label>
                            <select name="id_almacen" class="form-select" <?= isset($_GET['id_almacen']) ? 'disabled' : '' ?> required>
                                <option value="">Seleccione un almacén</option>
                                <?php foreach ($almacenes as $alm): ?>
                                <option value="<?= $alm['value'] ?>" <?= (($formData['id_almacen'] ?? $_GET['id_almacen'] ?? '') == $alm['value']) ? 'selected' : '' ?>>
                                    <?= htmlspecialchars($alm['text']) ?>
                                </option>
                                <?php endforeach; ?>
                            </select>
                            <?php if (isset($_GET['id_almacen'])): ?>
                            <small class="text-muted">Almacén pre-seleccionado desde la vista anterior</small>
                            <?php endif; ?>
                        </div>

                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Código *</label>
                                <input type="text" name="codigo" class="form-control" 
                                       placeholder="Ej: A-01-01" required
                                       value="<?= htmlspecialchars($formData['codigo'] ?? '') ?>">
                                <small class="text-muted">Código único dentro del almacén</small>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Estado</label>
                                <select name="estado" class="form-select">
                                    <?php foreach ($estados as $e): ?>
                                    <option value="<?= $e ?>" <?= (($formData['estado'] ?? 'disponible') == $e) ? 'selected' : '' ?>>
                                        <?= ucfirst($e) ?>
                                    </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-4 mb-3">
                                <label class="form-label">Pasillo</label>
                                <input type="text" name="pasillo" class="form-control" 
                                       placeholder="Ej: A" value="<?= htmlspecialchars($formData['pasillo'] ?? '') ?>">
                            </div>
                            <div class="col-md-4 mb-3">
                                <label class="form-label">Nivel</label>
                                <input type="text" name="nivel" class="form-control" 
                                       placeholder="Ej: 01" value="<?= htmlspecialchars($formData['nivel'] ?? '') ?>">
                            </div>
                            <div class="col-md-4 mb-3">
                                <label class="form-label">Posición</label>
                                <input type="text" name="posicion" class="form-control" 
                                       placeholder="Ej: 05" value="<?= htmlspecialchars($formData['posicion'] ?? '') ?>">
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Capacidad (productos)</label>
                                <input type="number" name="capacidad" class="form-control" 
                                       placeholder="Sin límite" value="<?= htmlspecialchars($formData['capacidad'] ?? '') ?>">
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Descripción</label>
                                <input type="text" name="descripcion" class="form-control" 
                                       placeholder="Descripción opcional" value="<?= htmlspecialchars($formData['descripcion'] ?? '') ?>">
                            </div>
                        </div>

                        <div class="alert alert-info small">
                            <i class="fas fa-info-circle me-2"></i>
                            <strong>Consejo:</strong> La ubicación física se forma automáticamente con Pasillo/Nivel/Posición.
                            Ejemplo: <code>A-01-05</code> representa Pasillo A, Nivel 01, Posición 05.
                        </div>

                        <hr>

                        <div class="d-flex justify-content-end gap-2">
                            <a href="index.php?controller=ubicacion&action=index" class="btn btn-secondary">
                                <i class="fas fa-times me-1"></i>Cancelar
                            </a>
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-save me-1"></i>Guardar Ubicación
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