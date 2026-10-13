<?php
/**
 * views/inventario/usuarios/index.php
 * Lista paginada de usuarios con filtros, KPIs y acciones CRUD.
 *
 * Variables esperadas del UsuarioController::index():
 *   $usuarios       array  — filas devueltas por sp_ListarUsuarios
 *   $totalRegistros int
 *   $paginaActual   int
 *   $totalPaginas   int
 *   $limite         int
 *   $roles          array
 *   $sweetAlert     array|null
 *   $filters        array   — filtros activos (filtro, rol, estado)
 */

$currentPage = 'usuario';
require_once __DIR__ . '/../../../config/SessionMiddleware.php';
SessionMiddleware::startSession();
include __DIR__ . '/../../layouts/inventario_layout.php';

// CSRF token para acciones AJAX (eliminar)
$csrfGlobal = SessionMiddleware::generateCSRFToken();

// helpers de display
$rolLabels  = ['admin'=>'Admin','supervisor'=>'Supervisor','vendedor'=>'Vendedor','almacenero'=>'Almacenero'];
$rolClasses = ['admin'=>'admin','supervisor'=>'supervisor','vendedor'=>'vendedor','almacenero'=>'almacenero'];
$estadoBadge= ['activo'=>'activo','inactivo'=>'inactivo'];

function avatarIniciales(string $nombre): string {
    $partes = explode(' ', trim($nombre));
    return strtoupper(substr($partes[0],0,1) . (isset($partes[1]) ? substr($partes[1],0,1) : ''));
}
?>

<link rel="stylesheet" href="<?= BASE_URL ?>/views/inventario/usuarios/css/usuarios.css">

<!-- SweetAlert data -->
<script>
const sweetAlertData = <?= $sweetAlert ? json_encode([
    'title' => $sweetAlert['title'] ?? 'Aviso',
    'text'  => $sweetAlert['text']  ?? '',
    'type'  => $sweetAlert['type']  ?? 'info',
]) : 'null' ?>;
const csrfGlobal = <?= json_encode($csrfGlobal) ?>;
</script>

<!-- Token CSRF oculto para JS -->
<input type="hidden" id="csrfGlobal" value="<?= htmlspecialchars($csrfGlobal) ?>">

<div class="us-wrapper container-fluid">

    <!-- ── Page header ── -->
    <div class="us-page-header">
        <div>
            <h1 class="us-page-title"><i class="fas fa-users me-2" style="color:var(--us-primary)"></i>Gestión de Usuarios</h1>
            <p class="us-page-subtitle">Administra los accesos y roles del sistema</p>
        </div>
        <a href="index.php?controller=usuario&action=crear" class="us-btn us-btn-primary">
            <i class="fas fa-user-plus"></i> Nuevo Usuario
        </a>
    </div>

    <!-- ── KPIs ── -->
    <?php
    $totalActivos   = count(array_filter($usuarios, fn($u) => $u['estado'] === 'activo'));
    $totalInactivos = count(array_filter($usuarios, fn($u) => $u['estado'] === 'inactivo'));
    $totalAdmins    = count(array_filter($usuarios, fn($u) => $u['rol']    === 'admin'));
    $kpis = [
        ['icon'=>'fas fa-users','label'=>'Total','value'=> $totalRegistros,'color'=>'#4f46e5'],
        ['icon'=>'fas fa-user-check','label'=>'Activos','value'=>$totalActivos,'color'=>'#10b981'],
        ['icon'=>'fas fa-user-slash','label'=>'Inactivos','value'=>$totalInactivos,'color'=>'#94a3b8'],
        ['icon'=>'fas fa-user-shield','label'=>'Admins','value'=>$totalAdmins,'color'=>'#f59e0b'],
    ];
    ?>
    <div class="row g-3 mb-3">
        <?php foreach ($kpis as $k): ?>
        <div class="col-6 col-md-3">
            <div class="us-card mb-0" style="border-left:4px solid <?= $k['color'] ?>">
                <div class="us-card-body" style="display:flex;align-items:center;gap:.85rem;padding:.85rem 1rem">
                    <div style="width:38px;height:38px;border-radius:8px;background:<?= $k['color'] ?>1a;display:flex;align-items:center;justify-content:center;flex-shrink:0">
                        <i class="<?= $k['icon'] ?>" style="color:<?= $k['color'] ?>;font-size:.95rem"></i>
                    </div>
                    <div>
                        <div style="font-size:1.3rem;font-weight:800;color:#0f172a;line-height:1"><?= $k['value'] ?></div>
                        <div style="font-size:.73rem;color:#94a3b8;font-weight:600;text-transform:uppercase;letter-spacing:.04em"><?= $k['label'] ?></div>
                    </div>
                </div>
            </div>
        </div>
        <?php endforeach; ?>
    </div>

    <!-- ── Filtros ── -->
    <div class="us-card">
        <div class="us-card-header"><i class="fas fa-filter"></i> Filtros</div>
        <div class="us-card-body">
            <form method="GET" action="index.php" id="formFiltros">
                <input type="hidden" name="controller" value="usuario">
                <input type="hidden" name="action" value="index">
                <div class="us-filters">
                    <div class="us-filter-group us-filter-search">
                        <label class="us-filter-label">Buscar</label>
                        <i class="fas fa-search"></i>
                        <input type="text" id="inputFiltro" name="filtro" class="us-filter-input"
                               placeholder="Nombre, usuario o email..."
                               value="<?= htmlspecialchars($filters['filtro'] ?? '') ?>">
                    </div>
                    <div class="us-filter-group">
                        <label class="us-filter-label">Rol</label>
                        <select name="rol" class="us-filter-select" onchange="this.form.submit()">
                            <option value="">Todos los roles</option>
                            <?php foreach ($roles as $r): ?>
                            <option value="<?= $r ?>" <?= ($filters['rol'] ?? '') === $r ? 'selected' : '' ?>>
                                <?= $rolLabels[$r] ?? ucfirst($r) ?>
                            </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="us-filter-group">
                        <label class="us-filter-label">Estado</label>
                        <select name="estado" class="us-filter-select" onchange="this.form.submit()">
                            <option value="">Todos</option>
                            <option value="activo"   <?= ($filters['estado'] ?? '') === 'activo'   ? 'selected' : '' ?>>Activo</option>
                            <option value="inactivo" <?= ($filters['estado'] ?? '') === 'inactivo' ? 'selected' : '' ?>>Inactivo</option>
                        </select>
                    </div>
                    <div class="us-filter-group" style="justify-content:flex-end">
                        <label class="us-filter-label">&nbsp;</label>
                        <a href="index.php?controller=usuario&action=index" class="us-btn us-btn-ghost" title="Limpiar filtros">
                            <i class="fas fa-times"></i> Limpiar
                        </a>
                    </div>
                </div>
            </form>
        </div>
    </div>

    <!-- ── Tabla ── -->
    <div class="us-card">
        <div class="us-card-header">
            <i class="fas fa-list"></i> Usuarios
            <span style="margin-left:auto;font-size:.75rem;color:#94a3b8;font-weight:400">
                <?= $totalRegistros ?> registro<?= $totalRegistros !== 1 ? 's' : '' ?>
            </span>
        </div>
        <div class="us-card-body" style="padding:0">
            <?php if (empty($usuarios)): ?>
            <div class="us-empty">
                <i class="fas fa-users-slash"></i>
                <p>No se encontraron usuarios con los filtros aplicados.</p>
            </div>
            <?php else: ?>
            <div class="us-table-wrap">
                <table class="us-table">
                    <thead>
                        <tr>
                            <th>#</th>
                            <th>Usuario</th>
                            <th>Rol</th>
                            <th>Último acceso</th>
                            <th>Estado</th>
                            <th style="text-align:center">Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($usuarios as $u): ?>
                        <?php
                            $rol    = $u['rol']    ?? 'vendedor';
                            $estado = $u['estado'] ?? 'activo';
                            $iniciales = avatarIniciales($u['nombre_completo'] ?? $u['username'] ?? '?');
                        ?>
                        <tr>
                            <td style="color:#94a3b8;font-size:.75rem"><?= (int)$u['id_usuario'] ?></td>
                            <td>
                                <div class="us-user-cell">
                                    <div class="us-avatar us-avatar-<?= htmlspecialchars($rol) ?>">
                                        <?= htmlspecialchars($iniciales) ?>
                                    </div>
                                    <div>
                                        <div class="us-user-name"><?= htmlspecialchars($u['nombre_completo'] ?? '') ?></div>
                                        <div class="us-user-email">@<?= htmlspecialchars($u['username'] ?? '') ?> · <?= htmlspecialchars($u['email'] ?? '') ?></div>
                                    </div>
                                </div>
                            </td>
                            <td>
                                <span class="us-badge us-badge-<?= htmlspecialchars($rolClasses[$rol] ?? 'vendedor') ?>">
                                    <i class="fas fa-<?= $rol==='admin'?'shield-alt':($rol==='supervisor'?'user-tie':($rol==='almacenero'?'boxes':'tag')) ?> fa-xs"></i>
                                    <?= htmlspecialchars($rolLabels[$rol] ?? ucfirst($rol)) ?>
                                </span>
                            </td>
                            <td style="color:#64748b;font-size:.8rem">
                                <?= htmlspecialchars($u['ultimo_acceso_format'] ?? '—') ?>
                            </td>
                            <td>
                                <span class="us-badge us-badge-<?= htmlspecialchars($estado) ?>">
                                    <i class="fas fa-circle fa-xs"></i>
                                    <?= ucfirst($estado) ?>
                                </span>
                            </td>
                            <td>
                                <div class="us-actions" style="justify-content:center">
                                    <a href="index.php?controller=usuario&action=ver&id=<?= (int)$u['id_usuario'] ?>"
                                       class="us-btn-icon" title="Ver detalle">
                                        <i class="fas fa-eye"></i>
                                    </a>
                                    <a href="index.php?controller=usuario&action=editar&id=<?= (int)$u['id_usuario'] ?>"
                                       class="us-btn-icon" title="Editar">
                                        <i class="fas fa-edit"></i>
                                    </a>
                                    <button class="us-btn-icon warning"
                                            data-action="cambiar-password"
                                            data-id="<?= (int)$u['id_usuario'] ?>"
                                            data-nombre="<?= htmlspecialchars($u['nombre_completo'] ?? $u['username']) ?>"
                                            title="Cambiar contraseña">
                                        <i class="fas fa-key"></i>
                                    </button>
                                    <button class="us-btn-icon danger"
                                            data-action="eliminar"
                                            data-id="<?= (int)$u['id_usuario'] ?>"
                                            data-nombre="<?= htmlspecialchars($u['nombre_completo'] ?? $u['username']) ?>"
                                            data-csrf="<?= htmlspecialchars($csrfGlobal) ?>"
                                            title="Desactivar">
                                        <i class="fas fa-user-slash"></i>
                                    </button>
                                </div>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            <?php endif; ?>
        </div>

        <!-- Paginación -->
        <?php if ($totalPaginas > 1): ?>
        <div style="padding:.75rem 1.1rem;border-top:1px solid #e2e8f0;display:flex;align-items:center;justify-content:space-between;flex-wrap:wrap;gap:.5rem">
            <span style="font-size:.78rem;color:#94a3b8">
                Página <?= $paginaActual ?> de <?= $totalPaginas ?> · <?= $totalRegistros ?> registros
            </span>
            <div class="us-pagination">
                <?php
                $qs = http_build_query(array_merge($_GET, ['controller'=>'usuario','action'=>'index','pagina'=>1]));
                ?>
                <a href="index.php?<?= $qs ?>" class="us-page-btn <?= $paginaActual <= 1 ? 'disabled' : '' ?>" title="Primera">
                    <i class="fas fa-angle-double-left"></i>
                </a>
                <?php for ($p = max(1,$paginaActual-2); $p <= min($totalPaginas,$paginaActual+2); $p++): ?>
                <?php $qs2 = http_build_query(array_merge($_GET, ['controller'=>'usuario','action'=>'index','pagina'=>$p])); ?>
                <a href="index.php?<?= $qs2 ?>" class="us-page-btn <?= $p===$paginaActual?'active':'' ?>"><?= $p ?></a>
                <?php endfor; ?>
                <?php $qsLast = http_build_query(array_merge($_GET, ['controller'=>'usuario','action'=>'index','pagina'=>$totalPaginas])); ?>
                <a href="index.php?<?= $qsLast ?>" class="us-page-btn <?= $paginaActual >= $totalPaginas ? 'disabled' : '' ?>" title="Última">
                    <i class="fas fa-angle-double-right"></i>
                </a>
            </div>
        </div>
        <?php endif; ?>
    </div>

</div><!-- /us-wrapper -->

<script src="<?= BASE_URL ?>/views/inventario/usuarios/js/usuarios.js"></script>
<?php include __DIR__ . '/../../layouts/footer.php'; ?>