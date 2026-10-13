<?php
/**
 * views/inventario/usuarios/editar.php
 * Formulario de edición de un usuario existente.
 *
 * Variables esperadas del UsuarioController::editar():
 *   $usuario    array       — fila de sp_GetUsuarioById (datos actuales)
 *   $roles      array       — ['admin','supervisor','almacenero','vendedor']
 *   $sweetAlert array|null  — mensaje flash del servidor
 *
 * Diferencias clave vs crear.php:
 *   - Campos pre-poblados con los datos actuales del usuario
 *   - Contraseña OPCIONAL (vacío = no se cambia, SP lo gestiona)
 *   - Envía id_usuario como hidden
 *   - Action → actualizarUsuario
 *   - CSRF: generado DESPUÉS del include del layout (fix colisión con $csrfToken del layout)
 */

$currentPage = 'usuario';
require_once __DIR__ . '/../../../config/SessionMiddleware.php';
SessionMiddleware::startSession();
include __DIR__ . '/../../layouts/inventario_layout.php';

// ── FIX CSRF: generar DESPUÉS del layout para evitar colisión con
//   $csrfToken = AuthController::getCsrfToken() del inventario_layout.php
$csrfFormToken = SessionMiddleware::generateCSRFToken();

// Recuperar datos del formulario si volvemos por error de validación
$formData = $_SESSION['form_data'] ?? [];
unset($_SESSION['form_data']);

// Helpers de display
$rolLabels = [
    'admin'       => 'Administrador',
    'supervisor'  => 'Supervisor',
    'vendedor'    => 'Vendedor',
    'almacenero'  => 'Almacenero',
];
$rolIcons = [
    'admin'       => 'shield-alt',
    'supervisor'  => 'user-tie',
    'vendedor'    => 'tag',
    'almacenero'  => 'boxes',
];
$rolDescripcion = [
    'admin'       => 'Acceso total al sistema. Gestiona usuarios, catálogos y reportes.',
    'supervisor'  => 'Supervisa ventas, compras y puede ver todos los reportes.',
    'vendedor'    => 'Registra ventas, consulta productos y clientes.',
    'almacenero'  => 'Gestiona inventario, movimientos y kardex.',
];

// Prioridad: si hay form_data (error de validación PHP) → usa form_data
// Si no → usa los datos del usuario desde la BD
$v = [
    'id_usuario'      => (int)($usuario['id_usuario'] ?? 0),
    'username'        => htmlspecialchars($formData['username']        ?? $usuario['username']        ?? ''),
    'nombre_completo' => htmlspecialchars($formData['nombre_completo'] ?? $usuario['nombre_completo'] ?? ''),
    'email'           => htmlspecialchars($formData['email']           ?? $usuario['email']           ?? ''),
    'rol'             => $formData['rol']    ?? $usuario['rol']    ?? 'vendedor',
    'estado'          => $formData['estado'] ?? $usuario['estado'] ?? 'activo',
];

// Iniciales para el avatar
$partes    = explode(' ', trim($v['nombre_completo']));
$iniciales = strtoupper(substr(strip_tags($partes[0]),0,1) . (isset($partes[1]) ? substr(strip_tags($partes[1]),0,1) : ''));
?>

<link rel="stylesheet" href="<?= BASE_URL ?>/views/inventario/usuarios/css/usuarios.css">

<!-- SweetAlert data inyectado por PHP (errores de validación, etc.) -->
<script>
const sweetAlertData = <?= $sweetAlert ? json_encode([
    'title' => $sweetAlert['title'] ?? 'Aviso',
    'text'  => $sweetAlert['text']  ?? ($sweetAlert['message'] ?? ''),
    'type'  => $sweetAlert['type']  ?? 'info',
]) : 'null' ?>;

// Datos del usuario para el JS (preview inicial)
const usuarioInicial = {
    nombre:   <?= json_encode(html_entity_decode($v['nombre_completo'])) ?>,
    username: <?= json_encode(html_entity_decode($v['username'])) ?>,
    rol:      <?= json_encode($v['rol']) ?>,
    iniciales:<?= json_encode($iniciales) ?>,
};
</script>

<div class="us-wrapper container-fluid">

    <!-- ── Page header ──────────────────────────────────────── -->
    <div class="us-page-header">
        <div>
            <h1 class="us-page-title">
                <i class="fas fa-user-edit me-2" style="color:var(--us-primary)"></i>Editar Usuario
            </h1>
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb us-breadcrumb mb-0 mt-1">
                    <li class="breadcrumb-item"><a href="index.php?controller=dashboard&action=index">Dashboard</a></li>
                    <li class="breadcrumb-item"><a href="index.php?controller=usuario&action=index">Usuarios</a></li>
                    <li class="breadcrumb-item"><a href="index.php?controller=usuario&action=ver&id=<?= $v['id_usuario'] ?>"><?= $v['nombre_completo'] ?></a></li>
                    <li class="breadcrumb-item active">Editar</li>
                </ol>
            </nav>
        </div>
        <div style="display:flex;gap:.5rem">
            <a href="index.php?controller=usuario&action=ver&id=<?= $v['id_usuario'] ?>" class="us-btn us-btn-secondary">
                <i class="fas fa-arrow-left"></i> Volver
            </a>
        </div>
    </div>

    <!-- ── Formulario ───────────────────────────────────────── -->
    <form method="POST"
          action="index.php?controller=usuario&action=actualizar"
          id="formEditarUsuario"
          novalidate>

        <!-- IDs y CSRF -->
        <input type="hidden" name="id_usuario"  value="<?= $v['id_usuario'] ?>">
        <input type="hidden" name="csrf_token"  value="<?= htmlspecialchars($csrfFormToken) ?>">

        <div class="row g-4">

            <!-- ══ COLUMNA IZQUIERDA ══════════════════════════ -->
            <div class="col-lg-7">

                <!-- ── Datos personales ── -->
                <div class="us-card">
                    <div class="us-card-header">
                        <i class="fas fa-id-card"></i> Datos personales
                    </div>
                    <div class="us-card-body">
                        <div class="row g-3">

                            <!-- Nombre completo -->
                            <div class="col-12">
                                <label class="us-label" for="nombre_completo">
                                    Nombre completo <span class="req">*</span>
                                </label>
                                <input type="text"
                                       id="nombre_completo"
                                       name="nombre_completo"
                                       class="us-input"
                                       placeholder="Ej: María García López"
                                       value="<?= $v['nombre_completo'] ?>"
                                       maxlength="150"
                                       autocomplete="name"
                                       required>
                                <p class="us-input-hint">Nombre y apellidos completos del usuario.</p>
                            </div>

                            <!-- Email -->
                            <div class="col-12">
                                <label class="us-label" for="email">
                                    Correo electrónico <span class="req">*</span>
                                </label>
                                <input type="email"
                                       id="email"
                                       name="email"
                                       class="us-input"
                                       placeholder="usuario@ejemplo.com"
                                       value="<?= $v['email'] ?>"
                                       maxlength="100"
                                       autocomplete="email"
                                       required>
                            </div>

                            <!-- Username -->
                            <div class="col-md-6">
                                <label class="us-label" for="username">
                                    Nombre de usuario <span class="req">*</span>
                                </label>
                                <input type="text"
                                       id="username"
                                       name="username"
                                       class="us-input"
                                       placeholder="Ej: mgarcia"
                                       value="<?= $v['username'] ?>"
                                       maxlength="50"
                                       autocomplete="username"
                                       pattern="[a-zA-Z0-9_]{3,50}"
                                       required>
                                <p class="us-input-hint">3–50 caracteres. Solo letras, números y guion bajo.</p>
                            </div>

                            <!-- Estado -->
                            <div class="col-md-6">
                                <label class="us-label" for="estado">Estado</label>
                                <select id="estado" name="estado" class="us-select">
                                    <option value="activo"   <?= $v['estado']==='activo'   ?'selected':'' ?>>✅ Activo</option>
                                    <option value="inactivo" <?= $v['estado']==='inactivo' ?'selected':'' ?>>⛔ Inactivo</option>
                                </select>
                            </div>

                        </div>
                    </div>
                </div>

                <!-- ── Contraseña (opcional en edición) ── -->
                <div class="us-card">
                    <div class="us-card-header">
                        <i class="fas fa-lock"></i> Cambiar contraseña
                        <span style="margin-left:auto;font-size:.72rem;font-weight:400;color:#94a3b8">
                            <i class="fas fa-info-circle"></i> Dejar en blanco para mantener la actual
                        </span>
                    </div>
                    <div class="us-card-body">

                        <!-- Aviso visual -->
                        <div id="passwordOptionalBanner"
                             style="background:#eff6ff;border:1px solid #bfdbfe;border-radius:8px;padding:.65rem .9rem;margin-bottom:1rem;display:flex;align-items:center;gap:.6rem;font-size:.8rem;color:#1e40af">
                            <i class="fas fa-shield-alt" style="flex-shrink:0"></i>
                            <span>La contraseña actual se mantiene si dejas estos campos vacíos.</span>
                        </div>

                        <div class="row g-3">

                            <!-- Nueva contraseña -->
                            <div class="col-md-6">
                                <label class="us-label" for="password">
                                    Nueva contraseña
                                </label>
                                <div class="us-input-group">
                                    <input type="password"
                                           id="password"
                                           name="password"
                                           class="us-input"
                                           placeholder="Dejar vacío para no cambiar"
                                           maxlength="255"
                                           autocomplete="new-password"
                                           oninput="onPasswordInput()">
                                    <button type="button"
                                            class="us-eye-btn"
                                            onclick="toggleEye('password',this)"
                                            tabindex="-1"
                                            title="Mostrar/ocultar">
                                        <i class="fas fa-eye"></i>
                                    </button>
                                </div>
                                <div class="us-strength-bar mt-2">
                                    <div class="us-strength-fill" id="strengthBar"></div>
                                </div>
                                <p class="us-strength-text" id="strengthText"></p>
                            </div>

                            <!-- Confirmar nueva contraseña -->
                            <div class="col-md-6">
                                <label class="us-label" for="password_confirm">
                                    Confirmar nueva contraseña
                                </label>
                                <div class="us-input-group">
                                    <input type="password"
                                           id="password_confirm"
                                           name="password_confirm"
                                           class="us-input"
                                           placeholder="Repite la nueva contraseña"
                                           maxlength="255"
                                           autocomplete="new-password"
                                           oninput="checkPasswordMatch()">
                                    <button type="button"
                                            class="us-eye-btn"
                                            onclick="toggleEye('password_confirm',this)"
                                            tabindex="-1"
                                            title="Mostrar/ocultar">
                                        <i class="fas fa-eye"></i>
                                    </button>
                                </div>
                                <p class="us-input-hint" id="matchHint"></p>
                            </div>

                        </div>
                    </div>
                </div>

            </div><!-- /col-lg-7 -->

            <!-- ══ COLUMNA DERECHA ════════════════════════════ -->
            <div class="col-lg-5">

                <!-- ── Selector de rol ── -->
                <div class="us-card">
                    <div class="us-card-header">
                        <i class="fas fa-user-tag"></i> Rol del sistema
                        <span class="req" style="margin-left:.25rem">*</span>
                    </div>
                    <div class="us-card-body" style="padding:.75rem">
                        <input type="hidden" name="rol" id="rolHidden" value="<?= htmlspecialchars($v['rol']) ?>">
                        <div id="rolSelectorWrap" style="display:flex;flex-direction:column;gap:.5rem">
                            <?php foreach ($roles as $r): ?>
                            <?php $isSelected = ($v['rol'] === $r); ?>
                            <label class="us-rol-option"
                                   id="rolOption_<?= $r ?>"
                                   onclick="selectRol('<?= $r ?>')"
                                   style="display:flex;align-items:center;gap:.85rem;padding:.75rem 1rem;
                                          border:2px solid <?= $isSelected ? 'var(--us-primary)' : 'var(--us-gray-200)' ?>;
                                          border-radius:8px;
                                          background:<?= $isSelected ? 'rgba(79,70,229,.05)' : '#fff' ?>;
                                          cursor:pointer;transition:all .15s">
                                <div style="width:36px;height:36px;border-radius:8px;display:flex;align-items:center;justify-content:center;flex-shrink:0;background:#ede9fe">
                                    <i class="fas fa-<?= $rolIcons[$r] ?? 'user' ?>" style="color:var(--us-primary);font-size:.9rem"></i>
                                </div>
                                <div style="flex:1;min-width:0">
                                    <div style="font-weight:700;font-size:.85rem;color:#0f172a"><?= $rolLabels[$r] ?? ucfirst($r) ?></div>
                                    <div style="font-size:.73rem;color:#64748b;line-height:1.3"><?= $rolDescripcion[$r] ?? '' ?></div>
                                </div>
                                <div id="rolCheck_<?= $r ?>"
                                     style="width:18px;height:18px;border-radius:50%;flex-shrink:0;display:flex;align-items:center;justify-content:center;transition:all .15s;
                                            border:2px solid <?= $isSelected ? 'var(--us-primary)' : 'var(--us-gray-200)' ?>;
                                            background:<?= $isSelected ? 'var(--us-primary)' : '#fff' ?>">
                                    <?php if ($isSelected): ?>
                                    <i class="fas fa-check" style="color:#fff;font-size:.55rem"></i>
                                    <?php endif; ?>
                                </div>
                            </label>
                            <?php endforeach; ?>
                        </div>
                    </div>
                </div>

                <!-- ── Panel resumen / acciones ── -->
                <div class="us-card" style="position:sticky;top:80px">
                    <div class="us-card-header"><i class="fas fa-save"></i> Guardar cambios</div>
                    <div class="us-card-body">

                        <!-- Preview de avatar dinámico -->
                        <div style="text-align:center;margin-bottom:1.1rem">
                            <div id="avatarPreview"
                                 class="us-avatar-lg us-avatar-<?= htmlspecialchars($v['rol']) ?>"
                                 style="margin-bottom:.6rem;font-size:1.4rem">
                                <?= htmlspecialchars($iniciales) ?: '<i class="fas fa-user"></i>' ?>
                            </div>
                            <p id="previewNombre"   style="font-weight:700;color:#0f172a;margin:0;font-size:.9rem"><?= $v['nombre_completo'] ?></p>
                            <p id="previewUsername" style="color:#94a3b8;font-size:.78rem;margin:0">@<?= $v['username'] ?></p>
                        </div>

                        <hr style="border-color:#e2e8f0;margin:.75rem 0">

                        <!-- Checklist de campos obligatorios -->
                        <ul style="list-style:none;padding:0;margin:0 0 1rem;display:flex;flex-direction:column;gap:.35rem">
                            <?php
                            $checks = [
                                'chkNombre'   => 'Nombre completo',
                                'chkEmail'    => 'Correo electrónico',
                                'chkUsername' => 'Nombre de usuario',
                            ];
                            foreach ($checks as $checkId => $label):
                            ?>
                            <li id="<?= $checkId ?>"
                                style="display:flex;align-items:center;gap:.5rem;font-size:.78rem;color:var(--us-success)">
                                <i class="fas fa-check-circle" style="font-size:.8rem"></i>
                                <?= $label ?>
                            </li>
                            <?php endforeach; ?>
                            <!-- Contraseña (opcional) -->
                            <li id="chkPassword"
                                style="display:flex;align-items:center;gap:.5rem;font-size:.78rem;color:#94a3b8">
                                <i class="fas fa-circle" style="font-size:.45rem"></i>
                                Contraseña (opcional)
                            </li>
                        </ul>

                        <!-- Indicador de cambios -->
                        <div id="changesBanner"
                             style="display:none;background:#fef9c3;border:1px solid #fde047;border-radius:6px;padding:.5rem .75rem;margin-bottom:.75rem;font-size:.78rem;color:#854d0e">
                            <i class="fas fa-pencil-alt"></i> Hay cambios sin guardar
                        </div>

                        <!-- Botones -->
                        <button type="submit" id="btnGuardar" class="us-btn us-btn-primary us-btn-block" style="margin-bottom:.5rem">
                            <i class="fas fa-save"></i> Guardar Cambios
                        </button>
                        <a href="index.php?controller=usuario&action=ver&id=<?= $v['id_usuario'] ?>"
                           class="us-btn us-btn-ghost us-btn-block">
                            <i class="fas fa-times"></i> Cancelar
                        </a>
                    </div>
                </div>

            </div><!-- /col-lg-5 -->

        </div><!-- /row -->
    </form>

</div><!-- /us-wrapper -->

<script src="<?= BASE_URL ?>/views/inventario/usuarios/js/usuarios.js"></script>
<script src="<?= BASE_URL ?>/views/inventario/usuarios/js/editar_usuario.js"></script>
<?php include __DIR__ . '/../../layouts/footer.php'; ?>