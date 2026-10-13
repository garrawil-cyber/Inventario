<?php
$currentPage = 'categorias';
include __DIR__ . '/../../layouts/inventario_layout.php';
?>
<link rel="stylesheet" href="<?= BASE_URL ?>/views/inventario/categorias/css/stylos.css">

<!-- Datos del servidor para el JS -->
<script>
    const CATEGORIA_ID = <?= (int) $categoria['id_categoria'] ?>;

    const categoriaSweetAlert = <?= (isset($sweetAlert) && $sweetAlert)
        ? json_encode([
            'title' => $sweetAlert['title'] ?? 'Aviso',
            'text'  => $sweetAlert['text']  ?? ($sweetAlert['message'] ?? ''),
            'type'  => $sweetAlert['type']  ?? 'info',
        ])
        : 'null' ?>;
</script>

<div class="container-fluid">

    <!-- Encabezado -->
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h1 class="h3 mb-0">Editar Categoría</h1>
            <p class="text-muted small mb-0">
                Modifica los datos de <strong><?= htmlspecialchars($categoria['nombre']) ?></strong>
            </p>
        </div>
        <a href="index.php?controller=categoria&action=index" class="btn btn-secondary">
            <i class="fas fa-arrow-left me-2"></i>Volver
        </a>
    </div>

    <div class="row">
        <div class="col-lg-6 mx-auto">

            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0">
                        <i class="fas fa-edit me-2 text-primary"></i>Información de la Categoría
                    </h5>
                </div>
                <div class="card-body">
                    <form id="formEditarCategoria"
                          method="POST"
                          action="index.php?controller=categoria&action=actualizar"
                          novalidate>

                        <input type="hidden" name="csrf_token"
                               value="<?= htmlspecialchars($csrfToken ?? '') ?>">
                        <input type="hidden" name="id_categoria"
                               value="<?= (int) $categoria['id_categoria'] ?>">

                        <!-- NOMBRE — IDs idénticos a crear.php para que el JS reutilice -->
                        <div class="mb-3">
                            <label for="inputNombre" class="form-label">
                                Nombre <span class="text-danger">*</span>
                            </label>
                            <div class="input-group">
                                <input type="text"
                                       id="inputNombre"
                                       name="nombre"
                                       class="form-control"
                                       value="<?= htmlspecialchars($categoria['nombre']) ?>"
                                       maxlength="100"
                                       autocomplete="off"
                                       required>
                                <span class="input-group-text bg-white">
                                    <i id="iconNombreStatus"
                                       class="fas fa-circle text-secondary"
                                       style="font-size:.65rem;"></i>
                                </span>
                            </div>
                            <!-- Feedback tiempo real — mismo id que usar verificarNombre() -->
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
                                      placeholder="Describe brevemente esta categoría..."><?= htmlspecialchars($categoria['descripcion'] ?? '') ?></textarea>
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
                                   value="<?= htmlspecialchars($categoria['icono'] ?? '') ?>"
                                   placeholder="Ej: shirt, pants, dress, shoe, jacket">
                            <div class="form-text">Nombre del icono (opcional).</div>
                        </div>

                        <!-- ESTADO -->
                        <div class="mb-3">
                            <label for="estado" class="form-label">
                                Estado <span class="text-danger">*</span>
                            </label>
                            <select name="estado" id="estado" class="form-select" required>
                                <option value="activo"   <?= $categoria['estado'] === 'activo'   ? 'selected' : '' ?>>Activo</option>
                                <option value="inactivo" <?= $categoria['estado'] === 'inactivo' ? 'selected' : '' ?>>Inactivo</option>
                            </select>
                        </div>

                        <!-- ALERTA PRODUCTOS ASOCIADOS -->
                        <?php
                            require_once __DIR__ . '/../../../models/Categoria.php';
                            $catModel       = new Categoria();
                            $totalProductos = $catModel->countProductos($categoria['id_categoria']);
                        ?>
                        <?php if ($totalProductos > 0): ?>
                        <div class="alert alert-warning d-flex gap-2 align-items-start">
                            <i class="fas fa-exclamation-triangle mt-1 flex-shrink-0"></i>
                            <div>
                                <strong>Productos asociados</strong><br>
                                Esta categoría tiene <strong><?= $totalProductos ?> producto(s)</strong>.
                                Si la desactivas, no podrán asignarse nuevos productos a ella.
                            </div>
                        </div>
                        <?php endif; ?>

                        <!-- ACCIONES -->
                        <div class="mt-4 d-flex justify-content-end gap-2">
                            <a href="index.php?controller=categoria&action=index"
                               class="btn btn-secondary">Cancelar</a>
                            <button type="submit" id="btnActualizar" class="btn btn-primary">
                                <i class="fas fa-save me-2"></i>Actualizar Categoría
                            </button>
                        </div>

                    </form>
                </div>
            </div>

            <!-- CARD PRODUCTOS ASOCIADOS -->
            <?php if ($totalProductos > 0): ?>
            <div class="card mt-3">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h6 class="mb-0">
                        <i class="fas fa-boxes me-2 text-primary"></i>Productos en esta Categoría
                    </h6>
                    <span class="badge bg-primary rounded-pill"><?= $totalProductos ?></span>
                </div>
                <div class="card-body">
                    <p class="mb-3 text-muted small">
                        Hay <strong><?= $totalProductos ?> producto(s)</strong> vinculados a esta categoría.
                    </p>
                    <a href="index.php?controller=producto&action=index&categoria=<?= (int) $categoria['id_categoria'] ?>"
                       class="btn btn-sm btn-outline-primary">
                        <i class="fas fa-eye me-2"></i>Ver Productos
                    </a>
                </div>
            </div>
            <?php endif; ?>

        </div>
    </div>
</div>

<!-- JS al final del body — sin defer, después del bloque <script> con CATEGORIA_ID -->
<script src="<?= BASE_URL ?>/views/inventario/categorias/js/crear.js"></script>

<?php include __DIR__ . '/../../layouts/footer.php'; ?>