<?php 
$currentPage = 'inventario_fisico';
include __DIR__ . '/../../layouts/inventario_layout.php'; 
// El token ya está definido en el layout como $csrfToken
?>

<link rel="stylesheet" href="<?= BASE_URL ?>/views/inventario/inventario_fisico/css/inventario_fisico.css">
<meta name="csrf-token" content="<?= htmlspecialchars($csrfToken ?? '') ?>">

<!-- resto del contenido -->

<link rel="stylesheet" href="<?= BASE_URL ?>/views/inventario/inventario_fisico/css/inventario_fisico.css">
<meta name="csrf-token" content="<?= htmlspecialchars($csrfToken ?? '') ?>">

<div class="container-fluid px-3 py-2">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h1 class="h5 fw-bold mb-0">
                <i class="fas fa-plus-circle me-2" style="color:var(--if-primary);"></i>Nuevo Inventario Físico
            </h1>
            <ol class="breadcrumb mb-0 mt-1">
                <li class="breadcrumb-item small"><a href="index.php?controller=dashboard&action=index">Dashboard</a></li>
                <li class="breadcrumb-item small"><a href="index.php?controller=inventario&action=index">Inventario Físico</a></li>
                <li class="breadcrumb-item small active">Crear</li>
            </ol>
        </div>
        <a href="index.php?controller=inventario&action=index" class="btn btn-sm btn-outline-secondary">
            <i class="fas fa-arrow-left me-1"></i>Volver
        </a>
    </div>

    <div class="row justify-content-center">
        <div class="col-md-8">
            <div class="card shadow-sm">
                <div class="card-header bg-white">
                    <h5 class="mb-0">Datos del Inventario</h5>
                </div>
                <div class="card-body">
                    <form method="POST" action="index.php?controller=inventario&action=guardar">
                        <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken ?? '') ?>">
                        
                        <div class="mb-3">
                            <label class="form-label">Almacén <span class="text-danger">*</span></label>
                            <select name="id_almacen" class="form-select" required>
                                <option value="">Seleccione un almacén...</option>
                                <?php foreach ($almacenes as $alm): ?>
                                <option value="<?= $alm['id_almacen'] ?>" <?= (($formData['id_almacen'] ?? '') == $alm['id_almacen']) ? 'selected' : '' ?>>
                                    <?= htmlspecialchars($alm['nombre']) ?> (<?= htmlspecialchars($alm['codigo']) ?>)
                                </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        
                        <div class="mb-3">
                            <label class="form-label">Tipo de Inventario</label>
                            <select name="tipo" class="form-select">
                                <option value="total" <?= (($formData['tipo'] ?? 'total') == 'total') ? 'selected' : '' ?>>Total</option>
                                <option value="parcial" <?= (($formData['tipo'] ?? '') == 'parcial') ? 'selected' : '' ?>>Parcial</option>
                                <option value="ciclico" <?= (($formData['tipo'] ?? '') == 'ciclico') ? 'selected' : '' ?>>Cíclico</option>
                            </select>
                        </div>
                        
                        <div class="mb-3">
                            <label class="form-label">Fecha de Inicio</label>
                            <input type="date" name="fecha_inicio" class="form-control" 
                                   value="<?= htmlspecialchars($formData['fecha_inicio'] ?? date('Y-m-d')) ?>">
                        </div>
                        
                        <div class="mb-3">
                            <label class="form-label">Responsable</label>
                            <input type="text" name="responsable" class="form-control" 
                                   placeholder="Nombre del responsable" 
                                   value="<?= htmlspecialchars($formData['responsable'] ?? '') ?>">
                            <small class="text-muted">Dejar en blanco para usar el usuario actual.</small>
                        </div>
                        
                        <div class="mb-3">
                            <label class="form-label">Observaciones</label>
                            <textarea name="observaciones" class="form-control" rows="3" 
                                      placeholder="Notas adicionales..."><?= htmlspecialchars($formData['observaciones'] ?? '') ?></textarea>
                        </div>
                        
                        <hr>
                        <div class="d-flex justify-content-end gap-2">
                            <a href="index.php?controller=inventario&action=index" class="btn btn-secondary">
                                <i class="fas fa-times me-1"></i>Cancelar
                            </a>
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-save me-1"></i>Crear Inventario
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<input type="hidden" id="ifCsrfToken" value="<?= htmlspecialchars($csrfToken ?? '') ?>">

<script>
const ifSweetAlert = <?= isset($sweetAlert) && $sweetAlert ? json_encode([
    'title' => $sweetAlert['title'] ?? 'Aviso',
    'text'  => $sweetAlert['text'] ?? ($sweetAlert['message'] ?? ''),
    'type'  => $sweetAlert['type'] ?? 'info',
]) : 'null' ?>;
</script>

<script src="<?= BASE_URL ?>/views/inventario/inventario_fisico/js/inventario_fisico.js"></script>
<?php include __DIR__ . '/../../layouts/footer.php'; ?>