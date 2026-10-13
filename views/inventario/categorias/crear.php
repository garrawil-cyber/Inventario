<?php
$currentPage = 'categorias';
include __DIR__ . '/../../layouts/inventario_layout.php';
?>
<link rel="stylesheet" href="<?= BASE_URL ?>/views/inventario/categorias/css/stylos.css">

<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1 class="h3 mb-0">Nueva Categoría</h1>
        <a href="index.php?controller=categoria&action=index" class="btn btn-secondary">
            <i class="fas fa-arrow-left me-2"></i>Volver
        </a>
    </div>

    <div class="row">
        <div class="col-lg-6 mx-auto">
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0">Información de la Categoría</h5>
                </div>
                <div class="card-body">
                    <form id="formCrearCategoria"
                          method="POST"
                          action="index.php?controller=categoria&action=guardar"
                          novalidate>
                        
                        <input type="hidden" name="csrf_token" 
                               value="<?= htmlspecialchars($csrfToken ?? '') ?>">

                        <!-- CAMPO NOMBRE CON VALIDACIÓN -->
                        <div class="mb-3">
                            <label for="inputNombre" class="form-label">
                                Nombre <span class="text-danger">*</span>
                            </label>
                            <div class="input-group">
                                <input type="text"
                                       id="inputNombre"
                                       name="nombre"
                                       class="form-control"
                                       value="<?= htmlspecialchars($formData['nombre'] ?? '') ?>"
                                       placeholder="Ej: Accesorios, Bisutería, Calzado..."
                                       maxlength="100"
                                       autocomplete="off">
                                <span class="input-group-text bg-white">
                                    <i id="iconNombreStatus"
                                       class="fas fa-circle text-secondary"
                                       style="font-size:.65rem;"></i>
                                </span>
                            </div>
                            <div id="nombreFeedback" class="form-text mt-1"></div>
                        </div>

                        <!-- DESCRIPCIÓN -->
                        <div class="mb-3">
                            <label for="descripcion" class="form-label">Descripción</label>
                            <textarea name="descripcion"
                                      id="descripcion"
                                      class="form-control"
                                      rows="3"
                                      maxlength="500"
                                      placeholder="Describe brevemente esta categoría..."><?= htmlspecialchars($formData['descripcion'] ?? '') ?></textarea>
                            <div class="d-flex justify-content-end">
                                <small id="descCount" class="text-muted">0 / 500</small>
                            </div>
                        </div>

                        <!-- ICONO -->
                        <div class="mb-3">
                            <label for="icono" class="form-label">Icono</label>
                            <input type="text"
                                   name="icono"
                                   id="icono"
                                   class="form-control"
                                   maxlength="50"
                                   value="<?= htmlspecialchars($formData['icono'] ?? '') ?>"
                                   placeholder="Ej: shirt, pants, dress, shoe, jacket">
                            <div class="form-text">Nombre del icono (opcional).</div>
                        </div>

                        <div class="alert alert-info">
                            <i class="fas fa-info-circle me-2"></i>
                            La categoría se creará con estado <strong>Activo</strong> por defecto.
                        </div>

                        <div class="mt-4 d-flex justify-content-end gap-2">
                            <a href="index.php?controller=categoria&action=index" class="btn btn-secondary">Cancelar</a>
                            <button type="submit" id="btnGuardar" class="btn btn-primary">
                                <i class="fas fa-save me-2"></i>Crear Categoría
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
// Configuración para mensajes flash
const categoriaSweetAlert = <?= (isset($sweetAlert) && $sweetAlert)
    ? json_encode([
        'title' => $sweetAlert['title'] ?? 'Aviso',
        'text'  => $sweetAlert['text']  ?? ($sweetAlert['message'] ?? ''),
        'type'  => $sweetAlert['type']  ?? 'info',
    ])
    : 'null' ?>;
</script>

<script src="<?= BASE_URL ?>/views/inventario/categorias/js/crear.js"></script>

<?php include __DIR__ . '/../../layouts/footer.php'; ?>