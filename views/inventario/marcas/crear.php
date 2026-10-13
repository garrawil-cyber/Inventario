<?php
$currentPage = 'marcas';
include __DIR__ . '/../../layouts/inventario_layout.php';
?>

<link rel="stylesheet" href="<?= BASE_URL ?>/views/inventario/marcas/css/marcas.css">
<meta name="csrf-token" content="<?= htmlspecialchars($csrfToken ?? '') ?>">

<div class="marca-wrap">

    <!-- ── Encabezado ── -->
    <div class="marca-header">
        <div>
            <h1 class="marca-title">
                <i class="fas fa-plus-circle"></i> Nueva Marca
            </h1>
            <div class="marca-breadcrumb">
                <a href="index.php?controller=dashboard&action=index">Dashboard</a> /
                <a href="index.php?controller=marca&action=index">Marcas</a> /
                <span>Crear</span>
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

                    <!-- ══════════════════════════════════════════════════════
                         FORMULARIO — el submit es interceptado por el JS.
                         NO se envía directamente: el JS muestra confirmación
                         SweetAlert → al confirmar llama submitMarcaForm().
                    ════════════════════════════════════════════════════════ -->
                    <form id="formCrearMarca"
                          method="POST"
                          action="index.php?controller=marca&action=guardar"
                          novalidate>

                        <input type="hidden" name="csrf_token"
                               value="<?= htmlspecialchars($csrfToken ?? '') ?>">

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
                                       value="<?= htmlspecialchars($formData['nombre'] ?? '') ?>"
                                       placeholder="Ej: Nike, Adidas, Zara..."
                                       maxlength="100"
                                       autocomplete="off"
                                       required>
                                <!-- Indicador de disponibilidad del nombre -->
                                <span class="input-group-text bg-white" id="nombreStatusIcon">
                                    <i class="fas fa-circle text-secondary" id="iconNombreStatus"
                                       style="font-size:.65rem;" title="Escriba un nombre"></i>
                                </span>
                            </div>
                            <!-- Mensaje de validación en tiempo real -->
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
                                      placeholder="Descripción de la marca..."><?= htmlspecialchars($formData['descripcion'] ?? '') ?></textarea>
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
                                           value="<?= htmlspecialchars($formData['pais_origen'] ?? '') ?>"
                                           placeholder="Ej: Estados Unidos, China..."
                                           maxlength="80">
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="marca-form-group">
                                    <label for="inputWeb">Sitio Web</label>
                                    <input type="url"
                                           id="inputWeb"
                                           name="sitio_web"
                                           class="form-control"
                                           value="<?= htmlspecialchars($formData['sitio_web'] ?? '') ?>"
                                           placeholder="https://www.ejemplo.com">
                                </div>
                            </div>
                        </div>

                        <!-- Acciones -->
                        <div class="marca-form-actions">
                            <a href="index.php?controller=marca&action=index"
                               class="btn-marca-secondary">
                                <i class="fas fa-times"></i> Cancelar
                            </a>
                            <button type="submit" id="btnCrearMarca" class="btn-marca-primary">
                                <i class="fas fa-save"></i> Crear
                            </button>
                        </div>

                    </form><!-- /formCrearMarca -->
                </div>
            </div>
        </div>
    </div>
</div><!-- /marca-wrap -->

<!-- ──────────────────────────────────────────────────────────────
     DATOS PARA JS
     marcaSweetAlert → mensajes flash del servidor (errores, etc.)
     marcaCrearUrl   → URL destino real del formulario
────────────────────────────────────────────────────────────────── -->
<script>
/* Flash del servidor (errores de validación PHP, CSRF, etc.) */
const marcaSweetAlert = <?= (isset($sweetAlert) && $sweetAlert)
    ? json_encode([
        'title' => $sweetAlert['title'] ?? 'Aviso',
        'text'  => $sweetAlert['text']  ?? ($sweetAlert['message'] ?? ''),
        'type'  => $sweetAlert['type']  ?? 'info',
    ])
    : 'null' ?>;

/* URL de verificación de nombre disponible (GET) */
const marcaCheckNombreUrl = 'index.php?controller=marca&action=checkNombre';
</script>

<script src="<?= BASE_URL ?>/views/inventario/marcas/js/marcas.js"></script>

<?php include __DIR__ . '/../../layouts/footer.php'; ?>