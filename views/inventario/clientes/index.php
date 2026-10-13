<?php
$currentPage = 'clientes';
include __DIR__ . '/../../layouts/inventario_layout.php';
?>

<link rel="stylesheet" href="<?= BASE_URL ?>/views/inventario/clientes/css/clientes.css">
<meta name="csrf-token" content="<?= htmlspecialchars($csrfToken ?? '') ?>">

<?php
/* ── Cálculos de estadísticas ── */
$activos   = 0; $inactivos = 0;
$vip       = 0; $mayorista = 0;
foreach ($clientes as $c) {
    if ($c['estado'] === 'activo') $activos++; else $inactivos++;
    if ($c['tipo_cliente'] === 'vip')       $vip++;
    if ($c['tipo_cliente'] === 'mayorista') $mayorista++;
}
$pct_activos = $totalRegistros > 0 ? round($activos / $totalRegistros * 100) : 0;
?>

<div class="cl-shell">

    <!-- ══════════════════════════════════════════════════════
         TOPBAR
    ══════════════════════════════════════════════════════ -->
    <header class="cl-topbar">
        <div class="cl-topbar-left">
            <div class="cl-page-icon">
                <i class="fas fa-users"></i>
            </div>
            <div>
                <h1 class="cl-page-title">Clientes</h1>
                <nav class="cl-breadcrumb">
                    <a href="index.php?controller=dashboard&action=index">Dashboard</a>
                    <span class="cl-breadcrumb-sep">/</span>
                    <span>Clientes</span>
                </nav>
            </div>
        </div>
        <div class="cl-topbar-actions">
            <a href="index.php?controller=cliente&action=exportar" class="cl-btn cl-btn-ghost">
                <i class="fas fa-file-excel"></i>
                <span>Exportar</span>
            </a>
            <a href="index.php?controller=cliente&action=crear" class="cl-btn cl-btn-primary">
                <i class="fas fa-user-plus"></i>
                <span>Nuevo Cliente</span>
            </a>
        </div>
    </header>

    <!-- ══════════════════════════════════════════════════════
         KPI STRIP
    ══════════════════════════════════════════════════════ -->
    <div class="cl-kpi-strip">

        <div class="cl-kpi-card cl-kpi-total">
            <div class="cl-kpi-icon"><i class="fas fa-users"></i></div>
            <div class="cl-kpi-body">
                <span class="cl-kpi-value"><?= number_format($totalRegistros) ?></span>
                <span class="cl-kpi-label">Total</span>
            </div>
        </div>

        <div class="cl-kpi-card cl-kpi-activo">
            <div class="cl-kpi-icon"><i class="fas fa-user-check"></i></div>
            <div class="cl-kpi-body">
                <span class="cl-kpi-value"><?= number_format($activos) ?></span>
                <span class="cl-kpi-label">Activos</span>
            </div>
            <div class="cl-kpi-bar">
                <div class="cl-kpi-bar-fill" style="width:<?= $pct_activos ?>%"></div>
            </div>
        </div>

        <div class="cl-kpi-card cl-kpi-inactivo">
            <div class="cl-kpi-icon"><i class="fas fa-user-slash"></i></div>
            <div class="cl-kpi-body">
                <span class="cl-kpi-value"><?= number_format($inactivos) ?></span>
                <span class="cl-kpi-label">Inactivos</span>
            </div>
        </div>

        <div class="cl-kpi-card cl-kpi-vip">
            <div class="cl-kpi-icon"><i class="fas fa-star"></i></div>
            <div class="cl-kpi-body">
                <span class="cl-kpi-value"><?= number_format($vip) ?></span>
                <span class="cl-kpi-label">VIP</span>
            </div>
        </div>

        <div class="cl-kpi-card cl-kpi-mayorista">
            <div class="cl-kpi-icon"><i class="fas fa-handshake"></i></div>
            <div class="cl-kpi-body">
                <span class="cl-kpi-value"><?= number_format($mayorista) ?></span>
                <span class="cl-kpi-label">Mayoristas</span>
            </div>
        </div>

    </div>

    <!-- ══════════════════════════════════════════════════════
         PANEL DE FILTROS
    ══════════════════════════════════════════════════════ -->
    <div class="cl-filters-panel">
        <div class="cl-filters-row">

            <!-- Búsqueda -->
            <div class="cl-filter-field cl-field-search">
                <label class="cl-filter-label">
                    <i class="fas fa-search"></i> Búsqueda
                </label>
                <div class="cl-input-icon">
                    <i class="fas fa-search cl-input-icon-inner"></i>
                    <input type="text"
                           id="filtro"
                           class="cl-input"
                           placeholder="Nombre, documento, email…"
                           value="<?= htmlspecialchars($_GET['filtro'] ?? '') ?>">
                </div>
            </div>

            <!-- Tipo cliente -->
            <div class="cl-filter-field">
                <label class="cl-filter-label">
                    <i class="fas fa-tag"></i> Tipo
                </label>
                <select id="tipo_cliente" class="cl-select">
                    <option value="">Todos</option>
                    <option value="regular"   <?= ($_GET['tipo_cliente'] ?? '') === 'regular'   ? 'selected' : '' ?>>Regular</option>
                    <option value="frecuente" <?= ($_GET['tipo_cliente'] ?? '') === 'frecuente' ? 'selected' : '' ?>>Frecuente</option>
                    <option value="vip"       <?= ($_GET['tipo_cliente'] ?? '') === 'vip'       ? 'selected' : '' ?>>VIP</option>
                    <option value="mayorista" <?= ($_GET['tipo_cliente'] ?? '') === 'mayorista' ? 'selected' : '' ?>>Mayorista</option>
                </select>
            </div>

            <!-- Estado -->
            <div class="cl-filter-field">
                <label class="cl-filter-label">
                    <i class="fas fa-circle-dot"></i> Estado
                </label>
                <select id="estado" class="cl-select">
                    <option value="">Todos</option>
                    <option value="activo"   <?= ($_GET['estado'] ?? '') === 'activo'   ? 'selected' : '' ?>>Activo</option>
                    <option value="inactivo" <?= ($_GET['estado'] ?? '') === 'inactivo' ? 'selected' : '' ?>>Inactivo</option>
                </select>
            </div>

            <!-- Límite -->
            <div class="cl-filter-field cl-field-limit">
                <label class="cl-filter-label">
                    <i class="fas fa-list-ol"></i> Por página
                </label>
                <select id="limit" class="cl-select">
                    <?php foreach ([5,10,20,50,100] as $n): ?>
                    <option value="<?= $n ?>" <?= ($_GET['limit'] ?? 20) == $n ? 'selected' : '' ?>>
                        <?= $n ?> registros
                    </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <!-- Acciones -->
            <div class="cl-filter-actions">
                <button id="btnFiltrar" class="cl-btn cl-btn-primary">
                    <i class="fas fa-search"></i> Filtrar
                </button>
                <button id="btnLimpiar" class="cl-btn cl-btn-ghost">
                    <i class="fas fa-xmark"></i> Limpiar
                </button>
            </div>
        </div>
    </div>

    <!-- ══════════════════════════════════════════════════════
         TABLA
    ══════════════════════════════════════════════════════ -->
    <div class="cl-table-card">

        <!-- Barra superior de la tabla -->
        <div class="cl-table-toolbar">
            <span class="cl-table-count">
                <?php if ($totalRegistros > 0): ?>
                    Mostrando <strong><?= count($clientes) ?></strong>
                    de <strong><?= number_format($totalRegistros) ?></strong> clientes
                <?php else: ?>
                    Sin resultados
                <?php endif; ?>
            </span>
        </div>

        <div class="cl-table-scroll">
            <table class="cl-table">
                <thead>
                    <tr>
                        <th class="cl-th-id">#</th>
                        <th>Documento</th>
                        <th>Cliente</th>
                        <th>Contacto</th>
                        <th class="cl-th-center">Tipo</th>
                        <th class="cl-th-right">Compras</th>
                        <th class="cl-th-right">Gastado</th>
                        <th class="cl-th-center">Estado</th>
                        <th class="cl-th-center">Acciones</th>
                    </tr>
                </thead>
                <tbody>
                <?php if (empty($clientes)): ?>
                    <tr>
                        <td colspan="9" class="cl-empty">
                            <div class="cl-empty-inner">
                                <i class="fas fa-users-slash cl-empty-icon"></i>
                                <p class="cl-empty-title">No hay clientes registrados</p>
                                <p class="cl-empty-sub">Agrega el primero desde el botón <strong>Nuevo Cliente</strong>.</p>
                            </div>
                        </td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($clientes as $cli): ?>
                    <tr class="cl-row">

                        <!-- ID -->
                        <td class="cl-td-id"><?= (int)$cli['id_cliente'] ?></td>

                        <!-- Documento -->
                        <td>
                            <span class="cl-doc-type"><?= htmlspecialchars($cli['tipo_documento']) ?></span>
                            <span class="cl-doc-num"><?= htmlspecialchars($cli['numero_documento']) ?></span>
                        </td>

                        <!-- Nombre -->
                        <td>
                            <div class="cl-cliente-cell">
                                <div class="cl-avatar" data-tipo="<?= $cli['tipo_cliente'] ?>">
                                    <?= mb_strtoupper(mb_substr($cli['nombre_completo'], 0, 1)) ?>
                                </div>
                                <strong class="cl-cliente-nombre">
                                    <?= htmlspecialchars($cli['nombre_completo']) ?>
                                </strong>
                            </div>
                        </td>

                        <!-- Contacto -->
                        <td class="cl-contacto">
                            <?php if (!empty($cli['email'])): ?>
                                <span><i class="fas fa-envelope"></i> <?= htmlspecialchars($cli['email']) ?></span>
                            <?php endif; ?>
                            <?php if (!empty($cli['telefono'])): ?>
                                <span><i class="fas fa-phone"></i> <?= htmlspecialchars($cli['telefono']) ?></span>
                            <?php endif; ?>
                            <?php if (empty($cli['email']) && empty($cli['telefono'])): ?>
                                <span class="cl-no-data">—</span>
                            <?php endif; ?>
                        </td>

                        <!-- Tipo cliente -->
                        <td class="cl-th-center">
                            <span class="cl-pill cl-pill-<?= $cli['tipo_cliente'] ?>">
                                <?= ucfirst($cli['tipo_cliente']) ?>
                            </span>
                        </td>

                        <!-- Compras -->
                        <td class="cl-th-right cl-num">
                            <?= number_format($cli['total_compras'] ?? 0) ?>
                        </td>

                        <!-- Gastado -->
                        <td class="cl-th-right cl-money">
                            S/ <?= number_format($cli['total_gastado'] ?? 0, 2) ?>
                        </td>

                        <!-- Estado -->
                        <td class="cl-th-center">
                            <span class="cl-status <?= $cli['estado'] === 'activo' ? 'cl-status-activo' : 'cl-status-inactivo' ?>">
                                <i class="fas <?= $cli['estado'] === 'activo' ? 'fa-circle-check' : 'fa-circle-xmark' ?>"></i>
                                <?= $cli['estado'] === 'activo' ? 'Activo' : 'Inactivo' ?>
                            </span>
                        </td>

                        <!-- Acciones -->
                        <td class="cl-th-center">
                            <div class="cl-row-actions">
                                <a href="index.php?controller=cliente&action=ver&id=<?= (int)$cli['id_cliente'] ?>"
                                   class="cl-icon-btn cl-icon-view"
                                   title="Ver detalle">
                                    <i class="fas fa-eye"></i>
                                </a>
                                <a href="index.php?controller=cliente&action=editar&id=<?= (int)$cli['id_cliente'] ?>"
                                   class="cl-icon-btn cl-icon-edit"
                                   title="Editar cliente">
                                    <i class="fas fa-pen-to-square"></i>
                                </a>

                                <?php if ($cli['estado'] === 'activo'): ?>
                                <button type="button"
                                        class="cl-icon-btn cl-icon-delete btn-delete"
                                        data-url="index.php?controller=cliente&action=eliminar&id=<?= (int)$cli['id_cliente'] ?>"
                                        data-nombre="<?= htmlspecialchars($cli['nombre_completo'], ENT_QUOTES) ?>"
                                        title="Desactivar cliente">
                                    <i class="fas fa-ban"></i>
                                </button>
                                <?php else: ?>
                                <button type="button"
                                        class="cl-icon-btn cl-icon-activate btn-activate"
                                        data-url="index.php?controller=cliente&action=activar&id=<?= (int)$cli['id_cliente'] ?>"
                                        data-nombre="<?= htmlspecialchars($cli['nombre_completo'], ENT_QUOTES) ?>"
                                        title="Activar cliente">
                                    <i class="fas fa-circle-check"></i>
                                </button>
                                <?php endif; ?>
                            </div>
                        </td>

                    </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
                </tbody>
            </table>
        </div>

        <!-- ── Paginación ── -->
        <?php if ($totalPaginas > 1): ?>
        <div class="cl-pagination">
            <span class="cl-pag-info">
                Página <strong><?= $paginaActual ?></strong> de <strong><?= $totalPaginas ?></strong>
            </span>
            <div class="cl-pag-controls">
                <?php if ($paginaActual > 1): ?>
                <button class="cl-pag-btn" onclick="cambiarPagina(1)" title="Primera">
                    <i class="fas fa-angles-left"></i>
                </button>
                <button class="cl-pag-btn" onclick="cambiarPagina(<?= $paginaActual - 1 ?>)" title="Anterior">
                    <i class="fas fa-chevron-left"></i>
                </button>
                <?php endif; ?>

                <?php
                $start = max(1, $paginaActual - 2);
                $end   = min($totalPaginas, $paginaActual + 2);
                for ($i = $start; $i <= $end; $i++): ?>
                <button class="cl-pag-btn <?= $i === $paginaActual ? 'cl-pag-active' : '' ?>"
                        onclick="cambiarPagina(<?= $i ?>)">
                    <?= $i ?>
                </button>
                <?php endfor; ?>

                <?php if ($paginaActual < $totalPaginas): ?>
                <button class="cl-pag-btn" onclick="cambiarPagina(<?= $paginaActual + 1 ?>)" title="Siguiente">
                    <i class="fas fa-chevron-right"></i>
                </button>
                <button class="cl-pag-btn" onclick="cambiarPagina(<?= $totalPaginas ?>)" title="Última">
                    <i class="fas fa-angles-right"></i>
                </button>
                <?php endif; ?>
            </div>
        </div>
        <?php endif; ?>

    </div><!-- /cl-table-card -->
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