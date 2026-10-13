<?php
/**
 * views/inventario/kardex/producto.php
 * Variables del controlador:
 *   $producto     — ficha del producto (con categoria_nombre, marca_nombre)
 *   $kardex       — filas del libro kardex en el rango filtrado
 *   $saldoActual  — última fila real de kardex (independiente del filtro)
 *   $kpi          — agregados del período filtrado
 *   $almacenes    — lista para el filtro de almacén
 */

$currentPage = 'kardex';
include __DIR__ . '/../../layouts/inventario_layout.php';

$sweetAlertFlash = null;
if (isset($_SESSION['sweet_alert'])) {
    $sweetAlertFlash = $_SESSION['sweet_alert'];
    unset($_SESSION['sweet_alert']);
}

$filtrosActivos = 0;
foreach (['fecha_desde', 'fecha_hasta', 'id_almacen', 'tipo_movimiento'] as $k) {
    if (!empty($_GET[$k])) $filtrosActivos++;
}

$exportUrl = 'index.php?' . http_build_query(array_merge($_GET, [
    'controller' => 'kardex', 'action' => 'exportar', 'id' => $producto['id_producto'],
]));
$pdfUrl = 'index.php?' . http_build_query(array_merge($_GET, [
    'controller' => 'kardex', 'action' => 'pdf', 'id' => $producto['id_producto'],
]));

$stockEstado = $producto['estado_stock'] ?? 'normal';
?>

<link href="https://fonts.googleapis.com/css2?family=IBM+Plex+Sans:wght@300;400;500;600;700&family=IBM+Plex+Mono:wght@400;500&display=swap" rel="stylesheet">
<link rel="stylesheet" href="<?= BASE_URL ?>/views/inventario/kardex/css/kardex.css">

<div class="kx-shell">

    <!-- ══════════════════════════════════════════════════════
         TOPBAR
    ══════════════════════════════════════════════════════ -->
    <header class="kx-topbar">
        <div class="kx-topbar-left">
            <div class="kx-page-icon"><i class="fas fa-book-open"></i></div>
            <div>
                <h1 class="kx-page-title">Libro Kardex</h1>
                <nav class="kx-breadcrumb">
                    <a href="index.php?controller=dashboard&action=index">Dashboard</a>
                    <span class="kx-breadcrumb-sep">/</span>
                    <a href="index.php?controller=kardex&action=index">Kardex</a>
                    <span class="kx-breadcrumb-sep">/</span>
                    <span><?= htmlspecialchars($producto['nombre']) ?></span>
                </nav>
            </div>
        </div>
        <div class="kx-topbar-actions">
            <a href="<?= htmlspecialchars($pdfUrl) ?>" target="_blank" class="kx-btn kx-btn-ghost">
                <i class="fas fa-file-pdf" style="color:#dc2626;"></i> PDF
            </a>
            <a href="<?= htmlspecialchars($exportUrl) ?>" class="kx-btn kx-btn-ghost">
                <i class="fas fa-file-excel" style="color:#16a34a;"></i> Excel
            </a>
        </div>
    </header>

    <!-- ══════════════════════════════════════════════════════
         FICHA DE PRODUCTO
    ══════════════════════════════════════════════════════ -->
    <div class="kx-product-card">
        <div class="kx-product-main">
            <div class="kx-product-avatar"><i class="fas fa-box"></i></div>
            <div>
                <div class="kx-product-name"><?= htmlspecialchars($producto['nombre']) ?></div>
                <div class="kx-product-meta">
                    <span class="kx-product-code"><?= htmlspecialchars($producto['codigo']) ?></span>
                    <span><?= htmlspecialchars($producto['categoria_nombre'] ?? 'Sin categoría') ?></span>
                    <?php if (!empty($producto['marca_nombre'])): ?>
                        <span><?= htmlspecialchars($producto['marca_nombre']) ?></span>
                    <?php endif; ?>
                    <?php if (!empty($producto['talla']) || !empty($producto['color'])): ?>
                        <span><?= htmlspecialchars(trim(($producto['talla'] ?? '') . ' ' . ($producto['color'] ?? ''))) ?></span>
                    <?php endif; ?>
                </div>
            </div>
        </div>
        <span class="kx-stock-badge kx-stock-<?= $stockEstado ?>">
            <i class="fas fa-cubes"></i>
            <?= number_format($producto['stock_actual'] ?? 0) ?> unidades en stock
        </span>
    </div>

    <!-- ══════════════════════════════════════════════════════
         KPI STRIP
    ══════════════════════════════════════════════════════ -->
    <div class="kx-kpi-strip">
        <div class="kx-kpi kx-kpi-entrada">
            <div class="kx-kpi-icon"><i class="fas fa-arrow-circle-down"></i></div>
            <div class="kx-kpi-body">
                <span class="kx-kpi-val"><?= number_format($kpi['entradas_cant']) ?></span>
                <span class="kx-kpi-label">Entradas (período)</span>
                <span class="kx-kpi-sub">S/ <?= number_format($kpi['entradas_val'], 2) ?> ingresados</span>
            </div>
        </div>

        <div class="kx-kpi kx-kpi-salida">
            <div class="kx-kpi-icon"><i class="fas fa-arrow-circle-up"></i></div>
            <div class="kx-kpi-body">
                <span class="kx-kpi-val"><?= number_format($kpi['salidas_cant']) ?></span>
                <span class="kx-kpi-label">Salidas (período)</span>
                <span class="kx-kpi-sub">S/ <?= number_format($kpi['salidas_val'], 2) ?> retirados</span>
            </div>
        </div>

        <div class="kx-kpi kx-kpi-saldo">
            <div class="kx-kpi-icon"><i class="fas fa-balance-scale"></i></div>
            <div class="kx-kpi-body">
                <span class="kx-kpi-val"><?= number_format($saldoActual['saldo_cantidad'] ?? $producto['stock_actual'] ?? 0) ?></span>
                <span class="kx-kpi-label">Saldo Actual</span>
                <span class="kx-kpi-sub">A costo unit. S/ <?= number_format($saldoActual['saldo_costo_unitario'] ?? $producto['precio_compra'] ?? 0, 2) ?></span>
            </div>
        </div>

        <div class="kx-kpi kx-kpi-alerta">
            <div class="kx-kpi-icon"><i class="fas fa-coins"></i></div>
            <div class="kx-kpi-body">
                <span class="kx-kpi-val">S/ <?= number_format($saldoActual['saldo_costo_total'] ?? 0, 2) ?></span>
                <span class="kx-kpi-label">Valor Total Actual</span>
                <span class="kx-kpi-sub">Costo promedio ponderado a hoy</span>
            </div>
        </div>
    </div>

    <!-- ══════════════════════════════════════════════════════
         FILTROS
    ══════════════════════════════════════════════════════ -->
    <div class="kx-filter-card">
        <button class="kx-filter-toggle" id="kxFilterToggle" data-active="<?= $filtrosActivos ?>">
            <span>
                <i class="fas fa-filter me-2" style="color:#2563eb;"></i>
                Filtros
                <?php if ($filtrosActivos > 0): ?>
                    <span class="kx-filter-badge"><?= $filtrosActivos ?></span>
                <?php endif; ?>
            </span>
            <i class="fas fa-chevron-down kx-filter-chevron <?= $filtrosActivos > 0 ? 'open' : '' ?>" id="kxFilterChevron"></i>
        </button>

        <div id="kxFilterBody" class="kx-filter-body">
            <form method="get" action="">
                <input type="hidden" name="controller" value="kardex">
                <input type="hidden" name="action" value="producto">
                <input type="hidden" name="id" value="<?= $producto['id_producto'] ?>">

                <div class="kx-filter-grid">
                    <div>
                        <label class="kx-filter-label">Desde</label>
                        <input type="date" name="fecha_desde" class="kx-filter-input"
                               value="<?= htmlspecialchars($_GET['fecha_desde'] ?? '') ?>">
                    </div>

                    <div>
                        <label class="kx-filter-label">Hasta</label>
                        <input type="date" name="fecha_hasta" class="kx-filter-input"
                               value="<?= htmlspecialchars($_GET['fecha_hasta'] ?? '') ?>">
                    </div>

                    <div>
                        <label class="kx-filter-label">Almacén</label>
                        <select name="id_almacen" class="kx-filter-select">
                            <option value="">Todos</option>
                            <?php foreach ($almacenes as $al): ?>
                            <option value="<?= $al['id_almacen'] ?? '' ?>"
                                <?= (int) ($_GET['id_almacen'] ?? 0) === (int) ($al['id_almacen'] ?? 0) ? 'selected' : '' ?>>
                                <?= htmlspecialchars($al['almacen_nombre'] ?? 'Sin nombre') ?>
                            </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div>
                        <label class="kx-filter-label">Tipo</label>
                        <select name="tipo_movimiento" class="kx-filter-select">
                            <option value="">Todos</option>
                            <option value="entrada" <?= ($_GET['tipo_movimiento'] ?? '') === 'entrada' ? 'selected' : '' ?>>↓ Entrada</option>
                            <option value="salida"  <?= ($_GET['tipo_movimiento'] ?? '') === 'salida'  ? 'selected' : '' ?>>↑ Salida</option>
                            <option value="ajuste"  <?= ($_GET['tipo_movimiento'] ?? '') === 'ajuste'  ? 'selected' : '' ?>>⇄ Ajuste</option>
                        </select>
                    </div>

                    <div style="display:flex;gap:6px;align-items:flex-end;">
                        <button type="submit" class="kx-btn kx-btn-primary" style="height:36px;padding:0 14px;">
                            <i class="fas fa-search"></i> Filtrar
                        </button>
                        <a href="index.php?controller=kardex&action=producto&id=<?= $producto['id_producto'] ?>"
                           class="kx-btn kx-btn-ghost" style="height:36px;padding:0 12px;">
                            <i class="fas fa-xmark"></i>
                        </a>
                    </div>
                </div>
            </form>
        </div>
    </div>

    <!-- ══════════════════════════════════════════════════════
         LIBRO KARDEX (tabla PEPS con columnas agrupadas)
    ══════════════════════════════════════════════════════ -->
    <div class="kx-table-card">
        <div class="kx-table-toolbar">
            <span class="kx-table-count">
                <?php if (count($kardex) > 0): ?>
                    Mostrando <strong><?= count($kardex) ?></strong> movimientos de kardex
                <?php else: ?>
                    Sin movimientos en el rango seleccionado
                <?php endif; ?>
            </span>
        </div>

        <div class="kx-table-scroll">
            <table class="kx-table">
                <thead>
                    <tr class="kx-thead-groups">
                        <th rowspan="2" style="vertical-align:middle;">Fecha</th>
                        <th rowspan="2" style="vertical-align:middle;">Tipo</th>
                        <th rowspan="2" style="vertical-align:middle;">Documento</th>
                        <th colspan="3" class="kx-th-group-entrada">Entrada</th>
                        <th colspan="3" class="kx-th-group-salida">Salida</th>
                        <th colspan="3" class="kx-th-group-saldo">Saldo</th>
                        <th rowspan="2" style="vertical-align:middle;" class="kx-table-center">Det.</th>
                    </tr>
                    <tr>
                        <th class="kx-table-center kx-th-group-entrada">Cant.</th>
                        <th class="kx-table-right kx-th-group-entrada">C.Unit.</th>
                        <th class="kx-table-right kx-th-group-entrada">Total</th>
                        <th class="kx-table-center kx-th-group-salida">Cant.</th>
                        <th class="kx-table-right kx-th-group-salida">C.Unit.</th>
                        <th class="kx-table-right kx-th-group-salida">Total</th>
                        <th class="kx-table-center kx-th-group-saldo">Cant.</th>
                        <th class="kx-table-right kx-th-group-saldo">C.Unit.</th>
                        <th class="kx-table-right kx-th-group-saldo">Total</th>
                    </tr>
                </thead>
                <tbody>
                <?php if (empty($kardex)): ?>
                    <tr>
                        <td colspan="12">
                            <div class="kx-empty">
                                <i class="fas fa-inbox kx-empty-icon"></i>
                                <p class="kx-empty-title">Sin movimientos de kardex<?= $filtrosActivos > 0 ? ' con los filtros aplicados' : ' para este producto' ?></p>
                                <?php if ($filtrosActivos > 0): ?>
                                <p class="kx-empty-sub">
                                    <a href="index.php?controller=kardex&action=producto&id=<?= $producto['id_producto'] ?>"
                                       class="kx-btn kx-btn-ghost" style="display:inline-flex;margin-top:10px;height:32px;padding:0 12px;font-size:.8rem;">
                                        <i class="fas fa-xmark"></i> Quitar filtros
                                    </a>
                                </p>
                                <?php endif; ?>
                            </div>
                        </td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($kardex as $k):
                        $tipo = $k['tipo_movimiento'] ?? 'ajuste';
                        $icon = $tipo === 'entrada' ? 'fa-arrow-down' : ($tipo === 'salida' ? 'fa-arrow-up' : 'fa-sliders-h');
                        $docTxt = trim(($k['documento_tipo'] ?? '') . ' ' . ($k['documento_numero'] ?? ''));
                    ?>
                    <tr class="kx-row-<?= $tipo ?>">
                        <!-- Fecha -->
                        <td>
                            <div class="kx-date-main"><?= date('d/m/Y', strtotime($k['fecha_movimiento'])) ?></div>
                            <div class="kx-date-sub"><?= date('H:i', strtotime($k['fecha_movimiento'])) ?></div>
                        </td>

                        <!-- Tipo -->
                        <td>
                            <span class="kx-pill kx-pill-<?= $tipo ?>">
                                <i class="fas <?= $icon ?>"></i> <?= ucfirst($tipo) ?>
                            </span>
                        </td>

                        <!-- Documento -->
                        <td>
                            <?php if (!empty($docTxt)): ?>
                                <span class="kx-doc"><?= htmlspecialchars($docTxt) ?></span>
                            <?php else: ?>
                                <span class="kx-cell-muted">—</span>
                            <?php endif; ?>
                        </td>

                        <!-- Entrada -->
                        <td class="kx-table-center <?= $k['entrada_cantidad'] > 0 ? 'kx-qty-entrada' : '' ?>">
                            <?= $k['entrada_cantidad'] > 0 ? number_format($k['entrada_cantidad']) : '—' ?>
                        </td>
                        <td class="kx-table-right" style="font-size:.8rem;">
                            <?= $k['entrada_cantidad'] > 0 ? 'S/ ' . number_format($k['entrada_costo_unitario'], 2) : '—' ?>
                        </td>
                        <td class="kx-table-right kx-money <?= $k['entrada_cantidad'] > 0 ? 'kx-money-entrada' : '' ?>">
                            <?= $k['entrada_cantidad'] > 0 ? 'S/ ' . number_format($k['entrada_costo_total'], 2) : '—' ?>
                        </td>

                        <!-- Salida -->
                        <td class="kx-table-center <?= $k['salida_cantidad'] > 0 ? 'kx-qty-salida' : '' ?>">
                            <?= $k['salida_cantidad'] > 0 ? number_format($k['salida_cantidad']) : '—' ?>
                        </td>
                        <td class="kx-table-right" style="font-size:.8rem;">
                            <?= $k['salida_cantidad'] > 0 ? 'S/ ' . number_format($k['salida_costo_unitario'], 2) : '—' ?>
                        </td>
                        <td class="kx-table-right kx-money <?= $k['salida_cantidad'] > 0 ? 'kx-money-salida' : '' ?>">
                            <?= $k['salida_cantidad'] > 0 ? 'S/ ' . number_format($k['salida_costo_total'], 2) : '—' ?>
                        </td>

                        <!-- Saldo -->
                        <td class="kx-table-center" style="font-weight:700;">
                            <?= number_format($k['saldo_cantidad']) ?>
                        </td>
                        <td class="kx-table-right" style="font-size:.8rem;">
                            S/ <?= number_format($k['saldo_costo_unitario'], 2) ?>
                        </td>
                        <td class="kx-table-right kx-money kx-money-saldo">
                            S/ <?= number_format($k['saldo_costo_total'], 2) ?>
                        </td>

                        <!-- Detalle -->
                        <td class="kx-table-center">
                            <button class="kx-detail-btn" title="Ver detalle"
                                    onclick="kxShowDetalle(<?= htmlspecialchars(json_encode($k), ENT_QUOTES) ?>)">
                                <i class="fas fa-eye"></i>
                            </button>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
                </tbody>
            </table>
        </div>

        <?php if (!empty($kardex)): ?>
        <div class="kx-summary-bar">
            <div class="kx-summary-item">
                <span class="kx-summary-val kx-qty-entrada">+<?= number_format($kpi['entradas_cant']) ?></span>
                <span class="kx-summary-label">Total Entradas</span>
            </div>
            <div class="kx-summary-item">
                <span class="kx-summary-val kx-qty-salida">−<?= number_format($kpi['salidas_cant']) ?></span>
                <span class="kx-summary-label">Total Salidas</span>
            </div>
            <div class="kx-summary-item" style="margin-left:auto;border-right:none;border-left:1px solid var(--kx-border);">
                <span class="kx-summary-val kx-money-entrada">S/ <?= number_format($kpi['entradas_val'], 2) ?></span>
                <span class="kx-summary-label">Val. Entradas</span>
            </div>
            <div class="kx-summary-item">
                <span class="kx-summary-val kx-money-salida">S/ <?= number_format($kpi['salidas_val'], 2) ?></span>
                <span class="kx-summary-label">Val. Salidas</span>
            </div>
            <div class="kx-summary-item">
                <span class="kx-summary-val kx-money-saldo">S/ <?= number_format($saldoActual['saldo_costo_total'] ?? 0, 2) ?></span>
                <span class="kx-summary-label">Saldo a Hoy</span>
            </div>
        </div>
        <?php endif; ?>
    </div>

</div><!-- /kx-shell -->

<!-- ══════════════════════════════════════════════════════
     MODAL DETALLE
══════════════════════════════════════════════════════ -->
<div id="kxModalOverlay" class="kx-modal-overlay">
    <div class="kx-modal" role="dialog" aria-modal="true">
        <div class="kx-modal-head">
            <div>
                <div class="kx-modal-title">
                    <i class="fas fa-file-alt me-2" style="color:#2563eb;"></i>
                    Detalle del Movimiento — <span id="kxDTipoBadge"></span>
                </div>
                <div class="kx-modal-code" id="kxDFecha"></div>
            </div>
            <button class="kx-modal-close" id="kxModalClose" title="Cerrar"><i class="fas fa-xmark"></i></button>
        </div>

        <div class="kx-modal-body">
            <div class="kx-modal-row">
                <span class="kx-modal-dl">Documento</span>
                <span class="kx-modal-dv" id="kxDDocumento" style="font-family:var(--kx-mono);font-size:.8rem;"></span>
            </div>
            <div class="kx-modal-row">
                <span class="kx-modal-dl">Almacén</span>
                <span class="kx-modal-dv" id="kxDAlmacen"></span>
            </div>
            <div class="kx-modal-row">
                <span class="kx-modal-dl">Proveedor</span>
                <span class="kx-modal-dv" id="kxDProveedor"></span>
            </div>
            <div class="kx-modal-row">
                <span class="kx-modal-dl">Usuario</span>
                <span class="kx-modal-dv" id="kxDUsuario"></span>
            </div>

            <div class="kx-modal-stats">
                <div class="kx-modal-stat">
                    <div class="kx-modal-stat-val" id="kxDEntrada" style="color:#059669;">—</div>
                    <div class="kx-modal-stat-lbl">Entrada</div>
                </div>
                <div class="kx-modal-stat">
                    <div class="kx-modal-stat-val" id="kxDSalida" style="color:#dc2626;">—</div>
                    <div class="kx-modal-stat-lbl">Salida</div>
                </div>
                <div class="kx-modal-stat">
                    <div class="kx-modal-stat-val" id="kxDSaldo" style="color:#2563eb;">—</div>
                    <div class="kx-modal-stat-lbl">Saldo</div>
                </div>
            </div>

            <div class="kx-modal-obs" id="kxDObs">Sin observaciones.</div>
        </div>

        <div class="kx-modal-foot">
            <button class="kx-btn kx-btn-ghost kx-btn-sm" onclick="kxCloseDetalle()">
                <i class="fas fa-xmark"></i> Cerrar
            </button>
        </div>
    </div>
</div>

<script>
const kxFlashData = <?= $sweetAlertFlash ? json_encode($sweetAlertFlash) : 'null' ?>;
</script>
<script src="<?= BASE_URL ?>/views/inventario/kardex/js/kardex.js"></script>

<?php include __DIR__ . '/../../layouts/footer.php'; ?>