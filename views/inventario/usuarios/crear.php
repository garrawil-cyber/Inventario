<?php
/**
 * views/inventario/usuarios/crear.php
 * Formulario de creación de nuevo usuario.
 *
 * Variables esperadas del UsuarioController::crear():
 *   $roles      array       — ['admin','supervisor','almacenero','vendedor']
 *   $csrfToken  string      — token CSRF generado por SessionMiddleware
 *   $sweetAlert array|null  — mensaje flash del servidor
 *
 * Flujo SweetAlert:
 *   - Errores de validación PHP  → sweetAlertData inyectado (type: warning)
 *   - form_data en sesión        → repobla campos tras error
 *   - Éxito                      → redirectWithSweetAlert en UsuarioController::guardar()
 */

$currentPage = 'usuario';
require_once __DIR__ . '/../../../config/SessionMiddleware.php';
SessionMiddleware::startSession();
include __DIR__ . '/../../layouts/inventario_layout.php';   // ← genera su propio $csrfToken

// ✅ Generar token PROPIO del formulario DESPUÉS del layout
//    Nombre distinto para no colisionar con $csrfToken del layout
$csrfFormToken = SessionMiddleware::generateCSRFToken();

// Recuperar form_data si hay error de validación
$formData = $_SESSION['form_data'] ?? [];
unset($_SESSION['form_data']);

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

// Valores por defecto (repoblación tras error)
$v = [
    'username'        => htmlspecialchars($formData['username']        ?? ''),
    'nombre_completo' => htmlspecialchars($formData['nombre_completo'] ?? ''),
    'email'           => htmlspecialchars($formData['email']           ?? ''),
    'rol'             => $formData['rol']    ?? 'vendedor',
    'estado'          => $formData['estado'] ?? 'activo',
];
?>

<link rel="stylesheet" href="<?= BASE_URL ?>/views/inventario/usuarios/css/usuarios.css">

<!-- SweetAlert data inyectado por PHP (errores de validación, etc.) -->
<script>
const sweetAlertData = <?= $sweetAlert ? json_encode([
    'title' => $sweetAlert['title'] ?? 'Aviso',
    'text'  => $sweetAlert['text']  ?? ($sweetAlert['message'] ?? ''),
    'type'  => $sweetAlert['type']  ?? 'info',
]) : 'null' ?>;
</script>

<div class="us-wrapper container-fluid">

    <!-- ── Page header ──────────────────────────────────────── -->
    <div class="us-page-header">
        <div>
            <h1 class="us-page-title">
                <i class="fas fa-user-plus me-2" style="color:var(--us-primary)"></i>Nuevo Usuario
            </h1>
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb us-breadcrumb mb-0 mt-1">
                    <li class="breadcrumb-item"><a href="index.php?controller=dashboard&action=index">Dashboard</a></li>
                    <li class="breadcrumb-item"><a href="index.php?controller=usuario&action=index">Usuarios</a></li>
                    <li class="breadcrumb-item active">Nuevo</li>
                </ol>
            </nav>
        </div>
        <a href="index.php?controller=usuario&action=index" class="us-btn us-btn-secondary">
            <i class="fas fa-arrow-left"></i> Volver
        </a>
    </div>

    <!-- ── Formulario ───────────────────────────────────────── -->
    <form method="POST"
          action="index.php?controller=usuario&action=guardar"
          id="formCrearUsuario"
          novalidate>

        <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfFormToken) ?>">

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

                <!-- ── Seguridad ── -->
                <div class="us-card">
                    <div class="us-card-header">
                        <i class="fas fa-lock"></i> Contraseña de acceso
                    </div>
                    <div class="us-card-body">
                        <div class="row g-3">

                            <!-- Contraseña -->
                            <div class="col-md-6">
                                <label class="us-label" for="password">
                                    Contraseña <span class="req">*</span>
                                </label>
                                <div class="us-input-group">
                                    <input type="password"
                                           id="password"
                                           name="password"
                                           class="us-input"
                                           placeholder="Mínimo 6 caracteres"
                                           maxlength="255"
                                           autocomplete="new-password"
                                           oninput="updateStrengthForm(this.value,'strengthBar','strengthText')"
                                           required>
                                    <button type="button"
                                            class="us-eye-btn"
                                            onclick="toggleEye('password',this)"
                                            tabindex="-1"
                                            title="Mostrar/ocultar">
                                        <i class="fas fa-eye"></i>
                                    </button>
                                </div>
                                <!-- Barra de fortaleza -->
                                <div class="us-strength-bar mt-2">
                                    <div class="us-strength-fill" id="strengthBar"></div>
                                </div>
                                <p class="us-strength-text" id="strengthText"></p>
                            </div>

                            <!-- Confirmar contraseña -->
                            <div class="col-md-6">
                                <label class="us-label" for="password_confirm">
                                    Confirmar contraseña <span class="req">*</span>
                                </label>
                                <div class="us-input-group">
                                    <input type="password"
                                           id="password_confirm"
                                           name="password_confirm"
                                           class="us-input"
                                           placeholder="Repite la contraseña"
                                           maxlength="255"
                                           autocomplete="new-password"
                                           oninput="checkPasswordMatch()"
                                           required>
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
                            <label class="us-rol-option <?= $v['rol']===$r ? 'selected' : '' ?>"
                                   id="rolOption_<?= $r ?>"
                                   onclick="selectRol('<?= $r ?>')"
                                   style="display:flex;align-items:center;gap:.85rem;padding:.75rem 1rem;border:2px solid <?= $v['rol']===$r ? 'var(--us-primary)' : 'var(--us-gray-200)' ?>;border-radius:8px;background:<?= $v['rol']===$r ? 'rgba(79,70,229,.05)' : '#fff' ?>;cursor:pointer;transition:all .15s;border-radius:8px">
                                <div style="width:36px;height:36px;border-radius:8px;display:flex;align-items:center;justify-content:center;flex-shrink:0;background:var(--us-primary-bg,#ede9fe)">
                                    <i class="fas fa-<?= $rolIcons[$r] ?? 'user' ?>" style="color:var(--us-primary);font-size:.9rem"></i>
                                </div>
                                <div style="flex:1;min-width:0">
                                    <div style="font-weight:700;font-size:.85rem;color:#0f172a"><?= $rolLabels[$r] ?? ucfirst($r) ?></div>
                                    <div style="font-size:.73rem;color:#64748b;line-height:1.3"><?= $rolDescripcion[$r] ?? '' ?></div>
                                </div>
                                <div class="us-rol-check" id="rolCheck_<?= $r ?>"
                                     style="width:18px;height:18px;border-radius:50%;border:2px solid <?= $v['rol']===$r ? 'var(--us-primary)' : 'var(--us-gray-200)' ?>;display:flex;align-items:center;justify-content:center;flex-shrink:0;background:<?= $v['rol']===$r ? 'var(--us-primary)' : '#fff' ?>;transition:all .15s">
                                    <?php if ($v['rol']===$r): ?>
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
                    <div class="us-card-header"><i class="fas fa-paper-plane"></i> Guardar</div>
                    <div class="us-card-body">

                        <!-- Preview de avatar dinámico -->
                        <div style="text-align:center;margin-bottom:1.1rem">
                            <div id="avatarPreview"
                                 class="us-avatar-lg us-avatar-vendedor"
                                 style="margin-bottom:.6rem;font-size:1.4rem">
                                <i class="fas fa-user"></i>
                            </div>
                            <p id="previewNombre" style="font-weight:700;color:#0f172a;margin:0;font-size:.9rem">Nuevo usuario</p>
                            <p id="previewUsername" style="color:#94a3b8;font-size:.78rem;margin:0">@username</p>
                        </div>

                        <hr style="border-color:#e2e8f0;margin:.75rem 0">

                        <!-- Checklist de campos requeridos -->
                        <ul style="list-style:none;padding:0;margin:0 0 1rem;display:flex;flex-direction:column;gap:.35rem">
                            <?php
                            $checks = [
                                'chkNombre'   => 'Nombre completo',
                                'chkEmail'    => 'Correo electrónico',
                                'chkUsername' => 'Nombre de usuario',
                                'chkPassword' => 'Contraseña',
                                'chkConfirm'  => 'Confirmar contraseña',
                            ];
                            foreach ($checks as $id => $label):
                            ?>
                            <li id="<?= $id ?>"
                                style="display:flex;align-items:center;gap:.5rem;font-size:.78rem;color:#94a3b8">
                                <i class="fas fa-circle" style="font-size:.45rem"></i>
                                <?= $label ?>
                            </li>
                            <?php endforeach; ?>
                        </ul>

                        <!-- Botones -->
                        <button type="submit" id="btnGuardar" class="us-btn us-btn-primary us-btn-block" style="margin-bottom:.5rem">
                            <i class="fas fa-user-plus"></i> Crear Usuario
                        </button>
                        <a href="index.php?controller=usuario&action=index"
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
<script src="<?= BASE_URL ?>/views/inventario/usuarios/js/crear_usuario.js"></script>
<?php include __DIR__ . '/../../layouts/footer.php'; ?>