<?php
$currentPage = 'marcas';
include __DIR__ . '/../../layouts/inventario_layout.php';
?>

<link rel="stylesheet" href="<?= BASE_URL ?>/views/inventario/marcas/css/marcas.css">
<meta name="csrf-token" content="<?= htmlspecialchars($csrfToken ?? '') ?>">

<div class="marca-wrap">

    <!-- Encabezado -->
    <div class="marca-header">
        <div>
            <h1 class="marca-title">
                <i class="fas fa-edit"></i> Editar Marca
            </h1>
            <div class="marca-breadcrumb">
                <a href="index.php?controller=dashboard&action=index">Dashboard</a> /
                <a href="index.php?controller=marca&action=index">Marcas</a> /
                <span>Editar</span>
            </div>
        </div>
        <a href="index.php?controller=marca&action=index" class="btn-marca-secondary">
            <i class="fas fa-arrow-left"></i> Volver
        </a>
    </div>

    <div class="row justify-content-center">
        <div class="col-md-8">
            <div class="marca-form-card">
                <div class="marca-form-header">
                    <h5>
                        <i class="fas fa-info-circle me-2" style="color:var(--marca-primary);"></i>
                        Datos de la Marca
                    </h5>
                </div>
                <div class="marca-form-body">

                    <form id="formEditarMarca"
                          method="POST"
                          action="index.php?controller=marca&action=actualizar"
                          novalidate>

                        <input type="hidden" name="csrf_token"
                               value="<?= htmlspecialchars($csrfToken ?? '') ?>">
                        <input type="hidden" name="id_marca"
                               value="<?= (int)$marca['id_marca'] ?>">

                        <!-- Nombre -->
                        <div class="marca-form-group">
                            <label for="inputNombre">
                                Nombre <span class="text-danger">*</span>
                            </label>
                            <div class="input-group">
                                <input type="text"
                                       id="inputNombre"
                                       name="nombre"
                                       class="form-control"
                                       value="<?= htmlspecialchars($marca['nombre']) ?>"
                                       maxlength="100"
                                       autocomplete="off"
                                       required>
                                <span class="input-group-text bg-white" id="nombreStatusIcon">
                                    <i id="iconNombreStatus"
                                       class="fas fa-circle text-secondary"
                                       style="font-size:.65rem;"
                                       title="Escriba un nombre"></i>
                                </span>
                            </div>
                            <div id="nombreFeedback" class="form-text mt-1" style="min-height:1.2rem;"></div>
                        </div>

                        <!-- Descripción -->
                        <div class="marca-form-group">
                            <label for="inputDescripcion">Descripción</label>
                            <textarea id="inputDescripcion"
                                      name="descripcion"
                                      class="form-control"
                                      rows="3"
                                      maxlength="500"
                                      placeholder="Descripción de la marca..."><?= htmlspecialchars($marca['descripcion'] ?? '') ?></textarea>
                            <div class="d-flex justify-content-end">
                                <small id="descCount" class="text-muted">0 / 500</small>
                            </div>
                        </div>

                        <!-- País + Sitio Web -->
                        <div class="row">
                            <div class="col-md-6">
                                <div class="marca-form-group">
                                    <label for="inputPais">País de Origen</label>
                                    <input type="text"
                                           id="inputPais"
                                           name="pais_origen"
                                           class="form-control"
                                           value="<?= htmlspecialchars($marca['pais_origen'] ?? '') ?>"
                                           maxlength="80"
                                           placeholder="Ej: Estados Unidos, China...">
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="marca-form-group">
                                    <label for="inputWeb">Sitio Web</label>
                                    <input type="url"
                                           id="inputWeb"
                                           name="sitio_web"
                                           class="form-control"
                                           value="<?= htmlspecialchars($marca['sitio_web'] ?? '') ?>"
                                           placeholder="https://www.ejemplo.com">
                                </div>
                            </div>
                        </div>

                        <!-- Estado -->
                        <div class="marca-form-group">
                            <label for="inputEstado">Estado</label>
                            <select id="inputEstado" name="estado" class="form-select">
                                <option value="activo"   <?= $marca['estado'] === 'activo'   ? 'selected' : '' ?>>Activo</option>
                                <option value="inactivo" <?= $marca['estado'] === 'inactivo' ? 'selected' : '' ?>>Inactivo</option>
                            </select>
                        </div>

                        <!-- Acciones -->
                        <div class="marca-form-actions">
                            <a href="index.php?controller=marca&action=index"
                               class="btn-marca-secondary">
                                <i class="fas fa-times"></i> Cancelar
                            </a>
                            <button type="submit" id="btnActualizarMarca" class="btn-marca-primary">
                                <i class="fas fa-save"></i> Actualizar
                            </button>
                        </div>

                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
/* Flash del servidor */
const marcaSweetAlert = <?= (isset($sweetAlert) && $sweetAlert)
    ? json_encode([
        'title' => $sweetAlert['title'] ?? 'Aviso',
        'text'  => $sweetAlert['text']  ?? ($sweetAlert['message'] ?? ''),
        'type'  => $sweetAlert['type']  ?? 'info',
    ])
    : 'null' ?>;

/* Datos de la marca actual expuestos al JS */
const marcaEditarData = {
    id:     <?= (int)$marca['id_marca'] ?>,
    nombre: <?= json_encode($marca['nombre']) ?>,
};

const marcaCheckNombreUrl = 'index.php?controller=marca&action=checkNombre';
</script>

<script src="<?= BASE_URL ?>/views/inventario/marcas/js/marcas.js"></script>

<?php include __DIR__ . '/../../layouts/footer.php'; ?>