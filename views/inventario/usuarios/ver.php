<?php
/**
 * views/inventario/usuarios/ver.php
 * Detalle completo de un usuario.
 *
 * Variables esperadas del UsuarioController::ver():
 *   $usuario    array  — fila de sp_GetUsuarioById
 *   $sweetAlert array|null
 */

$currentPage = 'usuario';
require_once __DIR__ . '/../../../config/SessionMiddleware.php';
SessionMiddleware::startSession();
include __DIR__ . '/../../layouts/inventario_layout.php';

$csrfGlobal = SessionMiddleware::generateCSRFToken();

$rolLabels  = ['admin'=>'Admin','supervisor'=>'Supervisor','vendedor'=>'Vendedor','almacenero'=>'Almacenero'];
$rolIcons   = ['admin'=>'shield-alt','supervisor'=>'user-tie','vendedor'=>'tag','almacenero'=>'boxes'];
$rolClasses = ['admin'=>'admin','supervisor'=>'supervisor','vendedor'=>'vendedor','almacenero'=>'almacenero'];

$rol    = $usuario['rol']    ?? 'vendedor';
$estado = $usuario['estado'] ?? 'activo';
$nombre = $usuario['nombre_completo'] ?? $usuario['username'] ?? '?';

// Iniciales avatar
$partes = explode(' ', trim($nombre));
$iniciales = strtoupper(substr($partes[0],0,1) . (isset($partes[1]) ? substr($partes[1],0,1) : ''));
?>

<link rel="stylesheet" href="<?= BASE_URL ?>/views/inventario/usuarios/css/usuarios.css">

<script>
const sweetAlertData = <?= $sweetAlert ? json_encode([
    'title' => $sweetAlert['title'] ?? 'Aviso',
    'text'  => $sweetAlert['text']  ?? '',
    'type'  => $sweetAlert['type']  ?? 'info',
]) : 'null' ?>;
const csrfGlobal = <?= json_encode($csrfGlobal) ?>;
</script>
<input type="hidden" id="csrfGlobal" value="<?= htmlspecialchars($csrfGlobal) ?>">

<div class="us-wrapper container-fluid">

    <!-- ── Page header ── -->
    <div class="us-page-header">
        <div>
            <h1 class="us-page-title"><i class="fas fa-user-circle me-2" style="color:var(--us-primary)"></i>Detalle de Usuario</h1>
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb us-breadcrumb mb-0 mt-1">
                    <li class="breadcrumb-item"><a href="index.php?controller=dashboard&action=index">Dashboard</a></li>
                    <li class="breadcrumb-item"><a href="index.php?controller=usuario&action=index">Usuarios</a></li>
                    <li class="breadcrumb-item active"><?= htmlspecialchars($nombre) ?></li>
                </ol>
            </nav>
        </div>
        <div style="display:flex;gap:.5rem;flex-wrap:wrap">
            <button class="us-btn us-btn-ghost"
                    data-action="cambiar-password"
                    data-id="<?= (int)$usuario['id_usuario'] ?>"
                    data-nombre="<?= htmlspecialchars($nombre) ?>">
                <i class="fas fa-key"></i> Cambiar contraseña
            </button>
            <a href="index.php?controller=usuario&action=editar&id=<?= (int)$usuario['id_usuario'] ?>"
               class="us-btn us-btn-primary">
                <i class="fas fa-edit"></i> Editar
            </a>
            <a href="index.php?controller=usuario&action=index" class="us-btn us-btn-secondary">
                <i class="fas fa-arrow-left"></i> Volver
            </a>
        </div>
    </div>

    <div class="row g-4">

        <!-- ── Columna izquierda: perfil ── -->
        <div class="col-lg-4">
            <div class="us-card">
                <div class="us-card-body" style="text-align:center;padding:2rem 1.5rem">
                    <div class="us-avatar-lg us-avatar-<?= htmlspecialchars($rol) ?>">
                        <?= htmlspecialchars($iniciales) ?>
                    </div>
                    <h2 style="font-size:1.1rem;font-weight:700;margin:0 0 .25rem;color:#0f172a">
                        <?= htmlspecialchars($nombre) ?>
                    </h2>
                    <p style="color:#64748b;font-size:.85rem;margin:0 0 1rem">
                        @<?= htmlspecialchars($usuario['username'] ?? '') ?>
                    </p>
                    <div style="display:flex;gap:.5rem;justify-content:center;flex-wrap:wrap">
                        <span class="us-badge us-badge-<?= htmlspecialchars($rolClasses[$rol] ?? 'vendedor') ?>">
                            <i class="fas fa-<?= $rolIcons[$rol] ?? 'tag' ?> fa-xs"></i>
                            <?= htmlspecialchars($rolLabels[$rol] ?? ucfirst($rol)) ?>
                        </span>
                        <span class="us-badge us-badge-<?= htmlspecialchars($estado) ?>">
                            <i class="fas fa-circle fa-xs"></i>
                            <?= ucfirst($estado) ?>
                        </span>
                    </div>
                </div>
            </div>

            <!-- Acciones rápidas -->
            <div class="us-card">
                <div class="us-card-header"><i class="fas fa-bolt"></i> Acciones rápidas</div>
                <div class="us-card-body" style="display:flex;flex-direction:column;gap:.5rem;padding:.85rem">
                    <a href="index.php?controller=usuario&action=editar&id=<?= (int)$usuario['id_usuario'] ?>"
                       class="us-btn us-btn-primary us-btn-block">
                        <i class="fas fa-edit"></i> Editar usuario
                    </a>
                    <button class="us-btn us-btn-ghost us-btn-block"
                            data-action="cambiar-password"
                            data-id="<?= (int)$usuario['id_usuario'] ?>"
                            data-nombre="<?= htmlspecialchars($nombre) ?>">
                        <i class="fas fa-key"></i> Cambiar contraseña
                    </button>
                    <?php if ($estado === 'activo'): ?>
                    <button class="us-btn us-btn-danger us-btn-block"
                            data-action="eliminar"
                            data-id="<?= (int)$usuario['id_usuario'] ?>"
                            data-nombre="<?= htmlspecialchars($nombre) ?>"
                            data-csrf="<?= htmlspecialchars($csrfGlobal) ?>">
                        <i class="fas fa-user-slash"></i> Desactivar usuario
                    </button>
                    <?php else: ?>
                    <a href="index.php?controller=usuario&action=editar&id=<?= (int)$usuario['id_usuario'] ?>"
                       class="us-btn us-btn-success us-btn-block">
                        <i class="fas fa-user-check"></i> Reactivar usuario
                    </a>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <!-- ── Columna derecha: datos ── -->
        <div class="col-lg-8">
            <div class="us-card">
                <div class="us-card-header"><i class="fas fa-id-card"></i> Información del usuario</div>
                <div class="us-card-body" style="padding:0">
                    <table class="us-info-table">
                        <tr>
                            <th>ID</th>
                            <td>#<?= (int)$usuario['id_usuario'] ?></td>
                        </tr>
                        <tr>
                            <th>Nombre completo</th>
                            <td><?= htmlspecialchars($usuario['nombre_completo'] ?? '—') ?></td>
                        </tr>
                        <tr>
                            <th>Usuario</th>
                            <td><code style="background:#f1f5f9;padding:.15rem .4rem;border-radius:4px;font-size:.82rem">@<?= htmlspecialchars($usuario['username'] ?? '—') ?></code></td>
                        </tr>
                        <tr>
                            <th>Email</th>
                            <td>
                                <a href="mailto:<?= htmlspecialchars($usuario['email'] ?? '') ?>" style="color:var(--us-primary);text-decoration:none">
                                    <?= htmlspecialchars($usuario['email'] ?? '—') ?>
                                </a>
                            </td>
                        </tr>
                        <tr>
                            <th>Rol</th>
                            <td>
                                <span class="us-badge us-badge-<?= htmlspecialchars($rolClasses[$rol] ?? 'vendedor') ?>">
                                    <i class="fas fa-<?= $rolIcons[$rol] ?? 'tag' ?> fa-xs"></i>
                                    <?= htmlspecialchars($rolLabels[$rol] ?? ucfirst($rol)) ?>
                                </span>
                            </td>
                        </tr>
                        <tr>
                            <th>Estado</th>
                            <td>
                                <span class="us-badge us-badge-<?= $estado ?>">
                                    <i class="fas fa-circle fa-xs"></i> <?= ucfirst($estado) ?>
                                </span>
                            </td>
                        </tr>
                        <tr>
                            <th>Último acceso</th>
                            <td><?= htmlspecialchars($usuario['ultimo_acceso_format'] ?? '—') ?></td>
                        </tr>
                        <tr>
                            <th>Fecha de creación</th>
                            <td><?= htmlspecialchars($usuario['fecha_creacion_format'] ?? '—') ?></td>
                        </tr>
                    </table>
                </div>
            </div>
        </div>

    </div>
</div>

<script src="<?= BASE_URL ?>/views/inventario/usuarios/js/usuarios.js"></script>
<?php include __DIR__ . '/../../layouts/footer.php'; ?>