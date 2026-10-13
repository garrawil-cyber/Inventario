<?php
$currentPage = 'clientes';
include __DIR__ . '/../../layouts/inventario_layout.php';
?>
<link rel="stylesheet" href="<?= BASE_URL ?>/views/inventario/clientes/css/clientes.css">
<meta name="csrf-token" content="<?= htmlspecialchars($csrfToken ?? '') ?>">

<div class="cl-shell">

    <!-- TOPBAR -->
    <header class="cl-topbar">
        <div class="cl-topbar-left">
            <div class="cl-page-icon">
                <i class="fas fa-user-plus"></i>
            </div>
            <div>
                <h1 class="cl-page-title">Nuevo Cliente</h1>
                <nav class="cl-breadcrumb">
                    <a href="index.php?controller=dashboard&action=index">Dashboard</a>
                    <span class="cl-breadcrumb-sep">/</span>
                    <a href="index.php?controller=cliente&action=index">Clientes</a>
                    <span class="cl-breadcrumb-sep">/</span>
                    <span>Crear</span>
                </nav>
            </div>
        </div>
        <a href="index.php?controller=cliente&action=index" class="cl-btn cl-btn-ghost">
            <i class="fas fa-arrow-left"></i> Volver
        </a>
    </header>

    <!-- CARD FORMULARIO -->
    <div class="cl-form-shell">
        <div class="cl-form-card">

            <div class="cl-form-card-header">
                <i class="fas fa-id-card" style="color:var(--cl-primary);"></i>
                Datos del Cliente
            </div>

            <div class="cl-form-card-body">
                <form id="formCliente"
                      method="POST"
                      action="index.php?controller=cliente&action=guardar"
                      novalidate>

                    <input type="hidden" name="csrf_token"
                           value="<?= htmlspecialchars($csrfToken ?? '') ?>">

                    <!-- ── Sección: Documento ── -->
                    <div class="cl-form-section-title">
                        <span>Identificación</span>
                    </div>

                    <div class="cl-form-row cl-form-row-doc">

                        <div class="cl-form-group cl-field-tipo">
                            <label class="cl-form-label cl-required">Tipo Documento</label>
                            <select name="tipo_documento" id="tipo_documento" class="cl-form-select">
                                <option value="DNI"       <?= (($formData['tipo_documento'] ?? 'DNI') === 'DNI')       ? 'selected' : '' ?>>DNI</option>
                                <option value="RUC"       <?= (($formData['tipo_documento'] ?? '') === 'RUC')       ? 'selected' : '' ?>>RUC</option>
                                <option value="CE"        <?= (($formData['tipo_documento'] ?? '') === 'CE')        ? 'selected' : '' ?>>CE — Carné Extranjería</option>
                                <option value="Pasaporte" <?= (($formData['tipo_documento'] ?? '') === 'Pasaporte') ? 'selected' : '' ?>>Pasaporte</option>
                            </select>
                        </div>

                        <div class="cl-form-group cl-field-numero">
                            <label class="cl-form-label cl-required">Número de Documento</label>
                            <div class="cl-input-doc-wrap">
                                <input type="text"
                                       name="numero_documento"
                                       id="numero_documento"
                                       class="cl-form-input"
                                       value="<?= htmlspecialchars($formData['numero_documento'] ?? '') ?>"
                                       placeholder="Ej: 12345678"
                                       maxlength="20"
                                       autocomplete="off">
                                <span class="cl-doc-status-icon">
                                    <i id="iconDocumentoStatus"
                                       class="fas fa-circle text-secondary"
                                       style="font-size:.6rem;"></i>
                                </span>
                            </div>
                            <div id="documentoFeedback" class="cl-field-feedback"></div>
                        </div>

                    </div>

                    <!-- ── Sección: Datos personales ── -->
                    <div class="cl-form-section-title">
                        <span>Datos Personales</span>
                    </div>

                    <div class="cl-form-group">
                        <label class="cl-form-label cl-required">Nombre Completo</label>
                        <input type="text"
                               name="nombre_completo"
                               id="nombre_completo"
                               class="cl-form-input"
                               value="<?= htmlspecialchars($formData['nombre_completo'] ?? '') ?>"
                               placeholder="Ej: Juan Pérez García"
                               maxlength="100"
                               autocomplete="off">
                        <div class="cl-field-row-feedback">
                            <span id="error_nombre" class="cl-feedback-error"></span>
                            <span id="valid_nombre" class="cl-feedback-ok"></span>
                        </div>
                    </div>

                    <div class="cl-form-row">
                        <div class="cl-form-group">
                            <label class="cl-form-label">Email</label>
                            <input type="email"
                                   name="email"
                                   id="email"
                                   class="cl-form-input"
                                   value="<?= htmlspecialchars($formData['email'] ?? '') ?>"
                                   placeholder="cliente@ejemplo.com"
                                   autocomplete="off">
                            <div class="cl-field-row-feedback">
                                <span id="error_email"  class="cl-feedback-error"></span>
                                <span id="valid_email"  class="cl-feedback-ok"></span>
                            </div>
                        </div>
                        <div class="cl-form-group">
                            <label class="cl-form-label">Teléfono</label>
                            <input type="text"
                                   name="telefono"
                                   id="telefono"
                                   class="cl-form-input"
                                   value="<?= htmlspecialchars($formData['telefono'] ?? '') ?>"
                                   placeholder="Ej: 999999999"
                                   maxlength="15"
                                   autocomplete="off">
                            <div class="cl-field-row-feedback">
                                <span id="error_telefono" class="cl-feedback-error"></span>
                                <span id="valid_telefono" class="cl-feedback-ok"></span>
                            </div>
                        </div>
                    </div>

                    <div class="cl-form-group">
                        <label class="cl-form-label">Dirección</label>
                        <textarea name="direccion"
                                  id="direccion"
                                  class="cl-form-input cl-form-textarea"
                                  rows="2"
                                  placeholder="Dirección completa..."><?= htmlspecialchars($formData['direccion'] ?? '') ?></textarea>
                    </div>

                    <!-- ── Sección: Clasificación ── -->
                    <div class="cl-form-section-title">
                        <span>Clasificación</span>
                    </div>

                    <div class="cl-form-row cl-form-row-3">
                        <div class="cl-form-group">
                            <label class="cl-form-label">Fecha Nacimiento</label>
                            <input type="date"
                                   name="fecha_nacimiento"
                                   id="fecha_nacimiento"
                                   class="cl-form-input"
                                   value="<?= htmlspecialchars($formData['fecha_nacimiento'] ?? '') ?>">
                            <div class="cl-field-row-feedback">
                                <span id="error_fecha" class="cl-feedback-error"></span>
                                <span id="valid_fecha" class="cl-feedback-ok"></span>
                            </div>
                        </div>
                        <div class="cl-form-group">
                            <label class="cl-form-label">Género</label>
                            <select name="genero" id="genero" class="cl-form-select">
                                <option value="">— Seleccionar —</option>
                                <option value="Hombre"   <?= (($formData['genero'] ?? '') === 'Hombre') ? 'selected' : '' ?>>Hombre</option>
                                <option value="Mujer"    <?= (($formData['genero'] ?? '') === 'Mujer')  ? 'selected' : '' ?>>Mujer</option>
                                <option value="Otro"     <?= (($formData['genero'] ?? '') === 'Otro')   ? 'selected' : '' ?>>Otro</option>
                            </select>
                        </div>
                        <div class="cl-form-group">
                            <label class="cl-form-label">Tipo Cliente</label>
                            <select name="tipo_cliente" id="tipo_cliente_form" class="cl-form-select">
                                <option value="regular"   <?= (($formData['tipo_cliente'] ?? 'regular') === 'regular')   ? 'selected' : '' ?>>Regular</option>
                                <option value="frecuente" <?= (($formData['tipo_cliente'] ?? '') === 'frecuente') ? 'selected' : '' ?>>Frecuente</option>
                                <option value="vip"       <?= (($formData['tipo_cliente'] ?? '') === 'vip')       ? 'selected' : '' ?>>VIP</option>
                                <option value="mayorista" <?= (($formData['tipo_cliente'] ?? '') === 'mayorista') ? 'selected' : '' ?>>Mayorista</option>
                            </select>
                        </div>
                    </div>

                    <!-- ACCIONES -->
                    <div class="cl-form-actions">
                        <a href="index.php?controller=cliente&action=index" class="cl-btn cl-btn-ghost">
                            <i class="fas fa-times"></i> Cancelar
                        </a>
                        <button type="submit" id="btnGuardarCliente" class="cl-btn cl-btn-primary">
                            <i class="fas fa-user-plus"></i> Crear Cliente
                        </button>
                    </div>

                </form>
            </div>
        </div>
    </div>

</div><!-- /cl-shell -->

<script>
const clienteSweetAlert = <?= (isset($sweetAlert) && $sweetAlert)
    ? json_encode([
        'title' => $sweetAlert['title'] ?? 'Aviso',
        'text'  => $sweetAlert['text']  ?? ($sweetAlert['message'] ?? ''),
        'type'  => $sweetAlert['type']  ?? 'info',
    ])
    : 'null' ?>;
</script>

<script src="<?= BASE_URL ?>/views/inventario/clientes/js/clientes.js"></script>
<?php include __DIR__ . '/../../layouts/footer.php'; ?>