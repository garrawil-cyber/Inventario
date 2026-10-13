<?php
$currentPage = 'clientes';
include __DIR__ . '/../../layouts/inventario_layout.php';
?>
<link rel="stylesheet" href="<?= BASE_URL ?>/views/inventario/clientes/css/clientes.css">
<meta name="csrf-token" content="<?= htmlspecialchars($csrfToken ?? '') ?>">

<?php
/* Avatar inicial y color según tipo para mostrar en el header */
$inicial  = mb_strtoupper(mb_substr($cliente['nombre_completo'] ?? '?', 0, 1));
$tipoClte = $cliente['tipo_cliente'] ?? 'regular';
?>

<div class="cl-shell">

    <!-- TOPBAR -->
    <header class="cl-topbar">
        <div class="cl-topbar-left">
            <div class="cl-page-icon">
                <i class="fas fa-user-pen"></i>
            </div>
            <div>
                <h1 class="cl-page-title">Editar Cliente</h1>
                <nav class="cl-breadcrumb">
                    <a href="index.php?controller=dashboard&action=index">Dashboard</a>
                    <span class="cl-breadcrumb-sep">/</span>
                    <a href="index.php?controller=cliente&action=index">Clientes</a>
                    <span class="cl-breadcrumb-sep">/</span>
                    <span>Editar</span>
                </nav>
            </div>
        </div>
        <a href="index.php?controller=cliente&action=index" class="cl-btn cl-btn-ghost">
            <i class="fas fa-arrow-left"></i> Volver
        </a>
    </header>

    <!-- IDENTIDAD DEL CLIENTE (solo lectura, contexto visual) -->
    <div class="cl-client-identity">
        <div class="cl-avatar cl-avatar-lg" data-tipo="<?= $tipoClte ?>">
            <?= $inicial ?>
        </div>
        <div class="cl-identity-info">
            <strong class="cl-identity-nombre"><?= htmlspecialchars($cliente['nombre_completo']) ?></strong>
            <div class="cl-identity-meta">
                <span class="cl-pill cl-pill-<?= $tipoClte ?>"><?= ucfirst($tipoClte) ?></span>
                <span class="cl-status <?= $cliente['estado'] === 'activo' ? 'cl-status-activo' : 'cl-status-inactivo' ?>">
                    <i class="fas <?= $cliente['estado'] === 'activo' ? 'fa-circle-check' : 'fa-circle-xmark' ?>"></i>
                    <?= ucfirst($cliente['estado']) ?>
                </span>
                <span class="cl-identity-doc">
                    <i class="fas fa-id-card"></i>
                    <?= htmlspecialchars($cliente['tipo_documento']) ?> <?= htmlspecialchars($cliente['numero_documento']) ?>
                </span>
            </div>
        </div>
    </div>

    <!-- CARD FORMULARIO -->
    <div class="cl-form-shell">
        <div class="cl-form-card">

            <div class="cl-form-card-header">
                <i class="fas fa-pen-to-square" style="color:var(--cl-primary);"></i>
                Modificar Datos
            </div>

            <div class="cl-form-card-body">
                <form id="formCliente"
                      method="POST"
                      action="index.php?controller=cliente&action=actualizar"
                      novalidate>

                    <input type="hidden" name="csrf_token"
                           value="<?= htmlspecialchars($csrfToken ?? '') ?>">
                    <input type="hidden" name="id_cliente"
                           id="id_cliente"
                           value="<?= (int)$cliente['id_cliente'] ?>">

                    <!-- ── Sección: Documento ── -->
                    <div class="cl-form-section-title">
                        <span>Identificación</span>
                    </div>

                    <div class="cl-form-row cl-form-row-doc">

                        <div class="cl-form-group cl-field-tipo">
                            <label class="cl-form-label cl-required">Tipo Documento</label>
                            <select name="tipo_documento" id="tipo_documento" class="cl-form-select">
                                <option value="DNI"       <?= ($cliente['tipo_documento'] === 'DNI')       ? 'selected' : '' ?>>DNI</option>
                                <option value="RUC"       <?= ($cliente['tipo_documento'] === 'RUC')       ? 'selected' : '' ?>>RUC</option>
                                <option value="CE"        <?= ($cliente['tipo_documento'] === 'CE')        ? 'selected' : '' ?>>CE — Carné Extranjería</option>
                                <option value="Pasaporte" <?= ($cliente['tipo_documento'] === 'Pasaporte') ? 'selected' : '' ?>>Pasaporte</option>
                            </select>
                        </div>

                        <div class="cl-form-group cl-field-numero">
                            <label class="cl-form-label cl-required">Número de Documento</label>
                            <div class="cl-input-doc-wrap">
                                <input type="text"
                                       name="numero_documento"
                                       id="numero_documento"
                                       class="cl-form-input"
                                       value="<?= htmlspecialchars($cliente['numero_documento']) ?>"
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
                               value="<?= htmlspecialchars($cliente['nombre_completo']) ?>"
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
                                   value="<?= htmlspecialchars($cliente['email'] ?? '') ?>"
                                   autocomplete="off">
                            <div class="cl-field-row-feedback">
                                <span id="error_email" class="cl-feedback-error"></span>
                                <span id="valid_email" class="cl-feedback-ok"></span>
                            </div>
                        </div>
                        <div class="cl-form-group">
                            <label class="cl-form-label">Teléfono</label>
                            <input type="text"
                                   name="telefono"
                                   id="telefono"
                                   class="cl-form-input"
                                   value="<?= htmlspecialchars($cliente['telefono'] ?? '') ?>"
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
                                  rows="2"><?= htmlspecialchars($cliente['direccion'] ?? '') ?></textarea>
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
                                   value="<?= htmlspecialchars($cliente['fecha_nacimiento'] ?? '') ?>">
                            <div class="cl-field-row-feedback">
                                <span id="error_fecha" class="cl-feedback-error"></span>
                                <span id="valid_fecha" class="cl-feedback-ok"></span>
                            </div>
                        </div>
                        <div class="cl-form-group">
                            <label class="cl-form-label">Género</label>
                            <select name="genero" id="genero" class="cl-form-select">
                                <option value="">— Seleccionar —</option>
                                <option value="Hombre" <?= ($cliente['genero'] ?? '') === 'Hombre' ? 'selected' : '' ?>>Hombre</option>
                                <option value="Mujer"  <?= ($cliente['genero'] ?? '') === 'Mujer'  ? 'selected' : '' ?>>Mujer</option>
                                <option value="Otro"   <?= ($cliente['genero'] ?? '') === 'Otro'   ? 'selected' : '' ?>>Otro</option>
                            </select>
                        </div>
                        <div class="cl-form-group">
                            <label class="cl-form-label">Tipo Cliente</label>
                            <select name="tipo_cliente" id="tipo_cliente_form" class="cl-form-select">
                                <option value="regular"   <?= ($cliente['tipo_cliente'] === 'regular')   ? 'selected' : '' ?>>Regular</option>
                                <option value="frecuente" <?= ($cliente['tipo_cliente'] === 'frecuente') ? 'selected' : '' ?>>Frecuente</option>
                                <option value="vip"       <?= ($cliente['tipo_cliente'] === 'vip')       ? 'selected' : '' ?>>VIP</option>
                                <option value="mayorista" <?= ($cliente['tipo_cliente'] === 'mayorista') ? 'selected' : '' ?>>Mayorista</option>
                            </select>
                        </div>
                    </div>

                    <!-- ── Estado (solo en editar) ── -->
                    <div class="cl-form-section-title">
                        <span>Estado de la cuenta</span>
                    </div>

                    <div class="cl-form-group cl-field-estado">
                        <label class="cl-form-label">Estado</label>
                        <div class="cl-estado-options">
                            <label class="cl-estado-opt <?= $cliente['estado'] === 'activo' ? 'cl-estado-opt-activo' : '' ?>">
                                <input type="radio" name="estado" value="activo"
                                       <?= $cliente['estado'] === 'activo' ? 'checked' : '' ?>>
                                <i class="fas fa-circle-check"></i> Activo
                            </label>
                            <label class="cl-estado-opt <?= $cliente['estado'] === 'inactivo' ? 'cl-estado-opt-inactivo' : '' ?>">
                                <input type="radio" name="estado" value="inactivo"
                                       <?= $cliente['estado'] === 'inactivo' ? 'checked' : '' ?>>
                                <i class="fas fa-circle-xmark"></i> Inactivo
                            </label>
                        </div>
                    </div>

                    <!-- ACCIONES -->
                    <div class="cl-form-actions">
                        <a href="index.php?controller=cliente&action=index" class="cl-btn cl-btn-ghost">
                            <i class="fas fa-times"></i> Cancelar
                        </a>
                        <button type="submit" id="btnActualizarCliente" class="cl-btn cl-btn-primary">
                            <i class="fas fa-floppy-disk"></i> Guardar Cambios
                        </button>
                    </div>

                </form>
            </div>
        </div>
    </div>

</div><!-- /cl-shell -->

<script>
const CLIENTE_ID = <?= (int)$cliente['id_cliente'] ?>;

const clienteSweetAlert = <?= (isset($sweetAlert) && $sweetAlert)
    ? json_encode([
        'title' => $sweetAlert['title'] ?? 'Aviso',
        'text'  => $sweetAlert['text']  ?? ($sweetAlert['message'] ?? ''),
        'type'  => $sweetAlert['type']  ?? 'info',
    ])
    : 'null' ?>;
</script>

<!-- clientes.js — al final, después del bloque script con CLIENTE_ID -->
<script src="<?= BASE_URL ?>/views/inventario/clientes/js/clientes.js"></script>
<?php include __DIR__ . '/../../layouts/footer.php'; ?>