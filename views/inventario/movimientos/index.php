<?php
/**
 * views/inventario/movimientos/index.php
 * Variables del controlador:
 *   $movimientos  — array con joins (producto, almacén, usuario, tipo, proveedor)
 *   $productos    — lista activa para filtros
 *   $almacenes    — lista para filtros
 *   $estadisticas — agregados del período
 */

$currentPage = 'movimientos';
include __DIR__ . '/../../layouts/inventario_layout.php';

// ── KPIs desde los movimientos del período ──────────────────────────
$kpiEntradas = $kpiSalidas = $kpiAjustes = 0;
$kpiValE     = $kpiValS   = 0.0;
foreach ($movimientos as $m) {
    $val = (float)($m['costo_total'] ?? ($m['cantidad'] * $m['costo_unitario'] ?? 0));
    if (($m['tipo_movimiento'] ?? '') === 'entrada') { 
        $kpiEntradas++; 
        $kpiValE += $val; 
    } elseif (($m['tipo_movimiento'] ?? '') === 'salida') { 
        $kpiSalidas++;  
        $kpiValS += $val; 
    } else { 
        $kpiAjustes++; 
    }
}

// ── URL de exportar con filtros actuales ────────────────────────────
$exportUrl = 'index.php?' . http_build_query(array_merge($_GET, [
    'controller' => 'movimiento',
    'action'     => 'exportar',
]));

// ── Contar filtros activos ───────────────────────────────────────────
$filtrosActivos = 0;
foreach (['tipo_movimiento','id_producto','id_almacen','fecha_desde','fecha_hasta','documento_numero'] as $k) {
    if (!empty($_GET[$k])) $filtrosActivos++;
}

// ── Flash de sesión (SweetAlert) ─────────────────────────────────────
$sweetAlertFlash = null;
if (isset($_SESSION['sweet_alert'])) {
    $sweetAlertFlash = $_SESSION['sweet_alert'];
    unset($_SESSION['sweet_alert']);
}
?>

<link href="https://fonts.googleapis.com/css2?family=IBM+Plex+Sans:wght@300;400;500;600;700&family=IBM+Plex+Mono:wght@400;500&display=swap" rel="stylesheet">
<link rel="stylesheet" href="<?= BASE_URL ?>/views/inventario/movimientos/css/movimientos.css">

<div class="mv-shell">

    <!-- ══════════════════════════════════════════════════════
         TOPBAR
    ══════════════════════════════════════════════════════ -->
    <header class="mv-topbar">
        <div class="mv-topbar-left">
            <div class="mv-page-icon">
                <i class="fas fa-exchange-alt"></i>
            </div>
            <div>
                <h1 class="mv-page-title">Movimientos de Inventario</h1>
                <nav class="mv-breadcrumb">
                    <a href="index.php?controller=dashboard&action=index">Dashboard</a>
                    <span class="mv-breadcrumb-sep">/</span>
                    <span>Movimientos</span>
                </nav>
            </div>
        </div>
        <div class="mv-topbar-actions">
            <a href="index.php?controller=movimiento&action=entrada"
               class="mv-btn mv-btn-entrada">
                <i class="fas fa-arrow-down"></i> Entrada
            </a>
            <a href="index.php?controller=movimiento&action=salida"
               class="mv-btn mv-btn-salida">
                <i class="fas fa-arrow-up"></i> Salida
            </a>
            <a href="<?= htmlspecialchars($exportUrl) ?>" class="mv-btn mv-btn-ghost">
                <i class="fas fa-file-excel" style="color:#16a34a;"></i> Excel
            </a>
        </div>
    </header>

    <!-- ══════════════════════════════════════════════════════
         KPI STRIP
    ══════════════════════════════════════════════════════ -->
    <div class="mv-kpi-strip">

        <div class="mv-kpi mv-kpi-entrada">
            <div class="mv-kpi-icon"><i class="fas fa-arrow-circle-down"></i></div>
            <div class="mv-kpi-body">
                <span class="mv-kpi-val"><?= number_format($kpiEntradas) ?></span>
                <span class="mv-kpi-label">Entradas</span>
                <span class="mv-kpi-sub">S/ <?= number_format($kpiValE, 2) ?> ingresados</span>
            </div>
        </div>

        <div class="mv-kpi mv-kpi-salida">
            <div class="mv-kpi-icon"><i class="fas fa-arrow-circle-up"></i></div>
            <div class="mv-kpi-body">
                <span class="mv-kpi-val"><?= number_format($kpiSalidas) ?></span>
                <span class="mv-kpi-label">Salidas</span>
                <span class="mv-kpi-sub">S/ <?= number_format($kpiValS, 2) ?> retirados</span>
            </div>
        </div>

        <div class="mv-kpi mv-kpi-ajuste">
            <div class="mv-kpi-icon"><i class="fas fa-sliders-h"></i></div>
            <div class="mv-kpi-body">
                <span class="mv-kpi-val"><?= number_format($kpiAjustes) ?></span>
                <span class="mv-kpi-label">Ajustes</span>
                <span class="mv-kpi-sub">Correcciones de stock</span>
            </div>
        </div>

        <div class="mv-kpi mv-kpi-total">
            <div class="mv-kpi-icon"><i class="fas fa-layer-group"></i></div>
            <div class="mv-kpi-body">
                <span class="mv-kpi-val"><?= number_format(count($movimientos)) ?></span>
                <span class="mv-kpi-label">Total Registros</span>
                <span class="mv-kpi-sub">en el período filtrado</span>
            </div>
        </div>

    </div>

    <!-- ══════════════════════════════════════════════════════
         FILTROS
    ══════════════════════════════════════════════════════ -->
    <div class="mv-filter-card">
        <button class="mv-filter-toggle"
                id="mvFilterToggle"
                data-active="<?= $filtrosActivos ?>">
            <span>
                <i class="fas fa-filter me-2" style="color:#2563eb;"></i>
                Filtros
                <?php if ($filtrosActivos > 0): ?>
                <span class="mv-filter-badge"><?= $filtrosActivos ?></span>
                <?php endif; ?>
            </span>
            <i class="fas fa-chevron-down mv-filter-chevron <?= $filtrosActivos > 0 ? 'open' : '' ?>"
               id="mvFilterChevron"></i>
        </button>

        <div id="mvFilterBody" class="mv-filter-body">
            <form method="get" action="">
                <input type="hidden" name="controller" value="movimiento">
                <input type="hidden" name="action"     value="index">

                <div class="mv-filter-grid">

                    <div>
                        <label class="mv-filter-label">Tipo</label>
                        <select name="tipo_movimiento" class="mv-filter-select">
                            <option value="">Todos</option>
                            <option value="entrada" <?= ($_GET['tipo_movimiento'] ?? '') === 'entrada' ? 'selected' : '' ?>>↓ Entrada</option>
                            <option value="salida"  <?= ($_GET['tipo_movimiento'] ?? '') === 'salida'  ? 'selected' : '' ?>>↑ Salida</option>
                            <option value="ajuste"  <?= ($_GET['tipo_movimiento'] ?? '') === 'ajuste'  ? 'selected' : '' ?>>⇄ Ajuste</option>
                        </select>
                    </div>

                    <div>
                        <label class="mv-filter-label">Producto</label>
                        <select name="id_producto" class="mv-filter-select">
                            <option value="">Todos</option>
                            <?php foreach ($productos as $p): ?>
                            <option value="<?= $p['id_producto'] ?>"
                                <?= (int)($_GET['id_producto'] ?? 0) === (int)$p['id_producto'] ? 'selected' : '' ?>>
                                <?= htmlspecialchars(($p['codigo'] ?? '') . ' – ' . ($p['nombre'] ?? '')) ?>
                            </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div>
                        <label class="mv-filter-label">Almacén</label>
                        <select name="id_almacen" class="mv-filter-select">
                            <option value="">Todos</option>
                            <?php foreach ($almacenes as $al): ?>
                            <option value="<?= $al['id_almacen'] ?? '' ?>"
                                <?= (int)($_GET['id_almacen'] ?? 0) === (int)($al['id_almacen'] ?? 0) ? 'selected' : '' ?>>
                                <?= htmlspecialchars($al['almacen_nombre'] ?? 'Sin nombre') ?>
                            </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div>
                        <label class="mv-filter-label">Nº Documento</label>
                        <input type="text" name="documento_numero" class="mv-filter-input"
                               placeholder="Factura, guía…"
                               value="<?= htmlspecialchars($_GET['documento_numero'] ?? '') ?>">
                    </div>

                    <div>
                        <label class="mv-filter-label">Desde</label>
                        <input type="date" name="fecha_desde" class="mv-filter-input"
                               value="<?= htmlspecialchars($_GET['fecha_desde'] ?? '') ?>">
                    </div>

                    <div>
                        <label class="mv-filter-label">Hasta</label>
                        <input type="date" name="fecha_hasta" class="mv-filter-input"
                               value="<?= htmlspecialchars($_GET['fecha_hasta'] ?? '') ?>">
                    </div>

                    <div style="display:flex;gap:6px;align-items:flex-end;">
                        <button type="submit" class="mv-btn mv-btn-entrada" style="height:36px;padding:0 14px;">
                            <i class="fas fa-search"></i> Filtrar
                        </button>
                        <a href="index.php?controller=movimiento&action=index"
                           class="mv-btn mv-btn-ghost" style="height:36px;padding:0 12px;">
                            <i class="fas fa-xmark"></i>
                        </a>
                    </div>

                </div>
            </form>
        </div>
    </div>

    <!-- ══════════════════════════════════════════════════════
         TABLA
    ══════════════════════════════════════════════════════ -->
    <div class="mv-table-card">

        <!-- Toolbar de la tabla -->
        <div class="mv-table-toolbar">
            <span class="mv-table-count">
                <?php if (count($movimientos) > 0): ?>
                    Mostrando <strong><?= count($movimientos) ?></strong> movimientos
                    <?php if ($filtrosActivos > 0): ?>
                        <span style="color:#2563eb;font-size:.75rem;margin-left:8px;">
                            <i class="fas fa-filter"></i>
                            <?= $filtrosActivos ?> filtro<?= $filtrosActivos > 1 ? 's' : '' ?> activo<?= $filtrosActivos > 1 ? 's' : '' ?>
                        </span>
                    <?php endif; ?>
                <?php else: ?>
                    Sin resultados
                <?php endif; ?>
            </span>
        </div>

        <div class="mv-table-scroll">
            <table class="mv-table">
                <thead>
                    <tr>
                        <th>Código</th>
                        <th>Fecha</th>
                        <th>Producto</th>
                        <th>Tipo</th>
                        <th class="mv-table-center">Cant.</th>
                        <th class="mv-table-right">C.Unit.</th>
                        <th class="mv-table-right">Total</th>
                        <th>Almacén</th>
                        <th>Usuario</th>
                        <th>Documento</th>
                        <th class="mv-table-center">Det.</th>
                    </tr>
                </thead>
                <tbody>
                <?php if (empty($movimientos)): ?>
                    <tr>
                        <td colspan="11">
                            <div class="mv-empty">
                                <i class="fas fa-inbox mv-empty-icon"></i>
                                <p class="mv-empty-title">
                                    Sin movimientos<?= $filtrosActivos > 0 ? ' con los filtros aplicados' : '' ?>
                                </p>
                                <?php if ($filtrosActivos > 0): ?>
                                <p class="mv-empty-sub">
                                    <a href="index.php?controller=movimiento&action=index"
                                       class="mv-btn mv-btn-ghost"
                                       style="display:inline-flex;margin-top:10px;height:32px;padding:0 12px;font-size:.8rem;">
                                        <i class="fas fa-xmark"></i> Quitar filtros
                                    </a>
                                </p>
                                <?php endif; ?>
                            </div>
                        </td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($movimientos as $m):
                        $tipo  = $m['tipo_movimiento'] ?? 'otro';
                        $icon  = $tipo === 'entrada' ? 'fa-arrow-down' : ($tipo === 'salida' ? 'fa-arrow-up' : 'fa-sliders-h');
                        $qtyClass = 'mv-qty-' . ($tipo === 'otro' ? 'ajuste' : $tipo);
                        $moneyClass = $tipo === 'entrada' ? 'mv-money-entrada' : ($tipo === 'salida' ? 'mv-money-salida' : '');
                        $total = (float)($m['costo_total'] ?? ($m['cantidad'] * ($m['costo_unitario'] ?? 0)));
                        $signo = $tipo === 'entrada' ? '+' : ($tipo === 'salida' ? '−' : '±');
                    ?>
                    <tr>
                        <!-- Código -->
                        <td><span class="mv-code"><?= htmlspecialchars($m['codigo'] ?? '—') ?></span></td>

                        <!-- Fecha -->
                        <td>
                            <div class="mv-date-main"><?= date('d/m/Y', strtotime($m['fecha_movimiento'] ?? 'now')) ?></div>
                            <div class="mv-date-sub"><?= date('H:i', strtotime($m['fecha_movimiento'] ?? 'now')) ?></div>
                        </td>

                        <!-- Producto -->
                        <td>
                            <span class="mv-prod-name"><?= htmlspecialchars($m['producto_nombre'] ?? '') ?></span>
                            <span class="mv-prod-code"><?= htmlspecialchars($m['producto_codigo'] ?? '') ?></span>
                        </td>

                        <!-- Tipo -->
                        <td>
                            <span class="mv-pill mv-pill-<?= $tipo ?>">
                                <i class="fas <?= $icon ?>"></i>
                                <?= ucfirst($tipo) ?>
                            </span>
                        </td>

                        <!-- Cantidad -->
                        <td class="mv-table-center <?= $qtyClass ?>">
                            <?= $signo . number_format($m['cantidad'] ?? 0, 0) ?>
                        </td>

                        <!-- Costo unitario -->
                        <td class="mv-table-right" style="font-size:.8rem;">
                            S/ <?= number_format($m['costo_unitario'] ?? 0, 2) ?>
                        </td>

                        <!-- Total -->
                        <td class="mv-table-right mv-money <?= $moneyClass ?>">
                            S/ <?= number_format($total, 2) ?>
                        </td>

                        <!-- Almacén -->
                        <td style="font-size:.8rem;"><?= htmlspecialchars($m['almacen_nombre'] ?? '—') ?></td>

                        <!-- Usuario -->
                        <td style="font-size:.78rem;color:var(--mv-text-3);">
                            <?= htmlspecialchars($m['usuario_nombre'] ?? '—') ?>
                        </td>

                        <!-- Documento -->
                        <td>
                            <?php if (!empty($m['documento_numero'])): ?>
                                <span class="mv-doc"><?= htmlspecialchars($m['documento_numero']) ?></span>
                            <?php else: ?>
                                <span style="color:var(--mv-text-4);font-size:.78rem;">—</span>
                            <?php endif; ?>
                        </td>

                        <!-- Botón detalle -->
                        <td class="mv-table-center">
                            <button class="mv-detail-btn"
                                    title="Ver detalle"
                                    onclick="mvShowDetalle(<?= htmlspecialchars(json_encode($m), ENT_QUOTES) ?>)">
                                <i class="fas fa-eye"></i>
                            </button>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
                </tbody>
            </table>
        </div>

<!-- Resumen totales -->
<?php if (!empty($movimientos)): ?>
<div class="mv-summary-bar">
    <div class="mv-summary-item">
        <span class="mv-summary-val mv-qty-entrada"><?= number_format($kpiEntradas) ?></span>
        <span class="mv-summary-label">Entradas</span>
    </div>
    <div class="mv-summary-item">
        <span class="mv-summary-val mv-qty-salida"><?= number_format($kpiSalidas) ?></span>
        <span class="mv-summary-label">Salidas</span>
    </div>
    <div class="mv-summary-item">
        <span class="mv-summary-val mv-qty-ajuste"><?= number_format($kpiAjustes) ?></span>
        <span class="mv-summary-label">Ajustes</span>
    </div>
    <div class="mv-summary-item" style="margin-left:auto;border-right:none;border-left:1px solid var(--mv-border);">
        <span class="mv-summary-val mv-money-entrada">S/ <?= number_format($kpiValE, 2) ?></span>
        <span class="mv-summary-label">Val. Entradas</span>
    </div>
    <div class="mv-summary-item">
        <span class="mv-summary-val mv-money-salida">S/ <?= number_format($kpiValS, 2) ?></span>
        <span class="mv-summary-label">Val. Salidas</span>
    </div>
    <div class="mv-summary-item">
        <span class="mv-summary-val" style="color:#2563eb;">
            S/ <?= number_format(abs($kpiValE - $kpiValS), 2) ?>
        </span>
        <span class="mv-summary-label">Diferencia</span>
    </div>
</div>
<?php endif; ?>

<!-- ══════════════════════════════════════════════════════
     PAGINACIÓN
══════════════════════════════════════════════════════ -->
<?php if ($totalPaginas > 1): ?>
<div class="mv-pagination-wrapper">
    <div class="mv-pagination-info">
        Mostrando <strong><?= count($movimientos) ?></strong> de <strong><?= number_format($totalRegistros) ?></strong> registros
        <span class="mv-pagination-sep">|</span>
        Página <strong><?= $paginaActual ?></strong> de <strong><?= $totalPaginas ?></strong>
    </div>
    
    <div class="mv-pagination">
        <!-- Botón Anterior -->
        <?php if ($paginaActual > 1): ?>
            <a href="?<?= http_build_query(array_merge($_GET, ['page' => $paginaActual - 1])) ?>" 
               class="mv-pagination-btn" title="Página anterior">
                <i class="fas fa-chevron-left"></i>
            </a>
        <?php else: ?>
            <span class="mv-pagination-btn mv-pagination-btn-disabled">
                <i class="fas fa-chevron-left"></i>
            </span>
        <?php endif; ?>
        
        <!-- Números de página -->
        <?php
        $startPage = max(1, $paginaActual - 2);
        $endPage = min($totalPaginas, $paginaActual + 2);
        
        // Mostrar primera página si no está en el rango
        if ($startPage > 1): ?>
            <a href="?<?= http_build_query(array_merge($_GET, ['page' => 1])) ?>" 
               class="mv-pagination-btn">1</a>
            <?php if ($startPage > 2): ?>
                <span class="mv-pagination-ellipsis">…</span>
            <?php endif; ?>
        <?php endif; ?>
        
        <!-- Páginas del rango -->
        <?php for ($i = $startPage; $i <= $endPage; $i++): ?>
            <?php if ($i == $paginaActual): ?>
                <span class="mv-pagination-btn mv-pagination-btn-active"><?= $i ?></span>
            <?php else: ?>
                <a href="?<?= http_build_query(array_merge($_GET, ['page' => $i])) ?>" 
                   class="mv-pagination-btn"><?= $i ?></a>
            <?php endif; ?>
        <?php endfor; ?>
        
        <!-- Mostrar última página si no está en el rango -->
        <?php if ($endPage < $totalPaginas): ?>
            <?php if ($endPage < $totalPaginas - 1): ?>
                <span class="mv-pagination-ellipsis">…</span>
            <?php endif; ?>
            <a href="?<?= http_build_query(array_merge($_GET, ['page' => $totalPaginas])) ?>" 
               class="mv-pagination-btn"><?= $totalPaginas ?></a>
        <?php endif; ?>
        
        <!-- Botón Siguiente -->
        <?php if ($paginaActual < $totalPaginas): ?>
            <a href="?<?= http_build_query(array_merge($_GET, ['page' => $paginaActual + 1])) ?>" 
               class="mv-pagination-btn" title="Página siguiente">
                <i class="fas fa-chevron-right"></i>
            </a>
        <?php else: ?>
            <span class="mv-pagination-btn mv-pagination-btn-disabled">
                <i class="fas fa-chevron-right"></i>
            </span>
        <?php endif; ?>
        
        <!-- Selector de registros por página -->
        <div class="mv-pagination-perpage">
            <span>Mostrar</span>
            <select onchange="window.location.href='?<?= http_build_query(array_merge($_GET, ['per_page' => ''])) ?>' + this.value">
                <option value="5" <?= $registrosPorPagina == 5 ? 'selected' : '' ?>>5</option>
                <option value="10" <?= $registrosPorPagina == 10 ? 'selected' : '' ?>>10</option>
                <option value="20" <?= $registrosPorPagina == 20 ? 'selected' : '' ?>>20</option>
                <option value="50" <?= $registrosPorPagina == 50 ? 'selected' : '' ?>>50</option>
                <option value="100" <?= $registrosPorPagina == 100 ? 'selected' : '' ?>>100</option>
            </select>
            <span>por página</span>
        </div>
    </div>
</div>
<?php endif; ?>

    </div><!-- /mv-table-card -->
</div><!-- /mv-shell -->

<!-- ══════════════════════════════════════════════════════
     MODAL DETALLE (custom — sin Bootstrap)
══════════════════════════════════════════════════════ -->
<div id="mvModalOverlay" class="mv-modal-overlay">
    <div class="mv-modal" role="dialog" aria-modal="true">

        <div class="mv-modal-head">
            <div>
                <div class="mv-modal-title">
                    <i class="fas fa-file-alt me-2" style="color:#2563eb;"></i>
                    Detalle del Movimiento
                    — <span id="mvDTipoBadge"></span>
                </div>
                <div class="mv-modal-code" id="mvDCodigo"></div>
            </div>
            <button class="mv-modal-close" id="mvModalClose" title="Cerrar">
                <i class="fas fa-xmark"></i>
            </button>
        </div>

        <div class="mv-modal-body">

            <div class="mv-modal-grid">

                <!-- Producto -->
                <div>
                    <div class="mv-modal-section-title">
                        <i class="fas fa-box"></i> Producto
                    </div>
                    <div class="mv-modal-row">
                        <span class="mv-modal-dl">Nombre</span>
                        <span class="mv-modal-dv" id="mvDNombre"></span>
                    </div>
                    <div class="mv-modal-row">
                        <span class="mv-modal-dl">Código</span>
                        <span class="mv-modal-dv" id="mvDCodProd" style="font-family:var(--mv-mono);font-size:.8rem;"></span>
                    </div>
                    <div class="mv-modal-row">
                        <span class="mv-modal-dl">Almacén</span>
                        <span class="mv-modal-dv" id="mvDAlmacen"></span>
                    </div>
                    <div class="mv-modal-row">
                        <span class="mv-modal-dl">Proveedor</span>
                        <span class="mv-modal-dv" id="mvDProveedor"></span>
                    </div>
                </div>

                <!-- Movimiento -->
                <div>
                    <div class="mv-modal-section-title">
                        <i class="fas fa-info-circle"></i> Movimiento
                    </div>
                    <div class="mv-modal-row">
                        <span class="mv-modal-dl">Fecha / Hora</span>
                        <span class="mv-modal-dv" id="mvDFecha"></span>
                    </div>
                    <div class="mv-modal-row">
                        <span class="mv-modal-dl">Usuario</span>
                        <span class="mv-modal-dv" id="mvDUsuario"></span>
                    </div>
                    <div class="mv-modal-row">
                        <span class="mv-modal-dl">Documento</span>
                        <span class="mv-modal-dv" id="mvDDocumento"
                              style="font-family:var(--mv-mono);font-size:.8rem;"></span>
                    </div>
                </div>

            </div>

            <!-- Stats de stock -->
            <div class="mv-modal-stats">
                <div class="mv-modal-stat">
                    <div class="mv-modal-stat-val" id="mvDStockAnt">—</div>
                    <div class="mv-modal-stat-lbl">Stock Anterior</div>
                </div>
                <div class="mv-modal-stat">
                    <div class="mv-modal-stat-val" id="mvDCantidad" style="color:#2563eb;">—</div>
                    <div class="mv-modal-stat-lbl">Cantidad</div>
                </div>
                <div class="mv-modal-stat">
                    <div class="mv-modal-stat-val" id="mvDStockNvo" style="color:#059669;">—</div>
                    <div class="mv-modal-stat-lbl">Stock Nuevo</div>
                </div>
                <div class="mv-modal-stat">
                    <div class="mv-modal-stat-val" id="mvDTotal">—</div>
                    <div class="mv-modal-stat-lbl">Total</div>
                </div>
            </div>

            <!-- Observaciones -->
            <div class="mv-modal-obs" id="mvDObs">Sin observaciones.</div>

        </div>

        <div class="mv-modal-foot">
            <button class="mv-btn mv-btn-ghost" style="height:34px;padding:0 16px;font-size:.82rem;"
                    onclick="mvCloseDetalle()">
                <i class="fas fa-xmark"></i> Cerrar
            </button>
        </div>

    </div>
</div>

<!-- ── Datos para JS ──────────────────────────────────────── -->
<script>
/* Flash SweetAlert del servidor (registrado por setSweetAlert() del controlador) */
const mvFlashData = <?= $sweetAlertFlash
    ? json_encode($sweetAlertFlash)
    : 'null' ?>;
</script>

<script src="<?= BASE_URL ?>/views/inventario/movimientos/js/movimientos.js"></script>

<?php include __DIR__ . '/../../layouts/footer.php'; ?>