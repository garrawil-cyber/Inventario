<?php
/**
 * views/inventario/kardex/index.php
 * Variables del controlador:
 *   $resumen       — array de productos con saldo valorizado actual
 *   $estadisticas  — KPIs agregados (valor total, unidades, etc.)
 */

$currentPage = 'kardex';
include __DIR__ . '/../../layouts/inventario_layout.php';

$sweetAlertFlash = null;
if (isset($_SESSION['sweet_alert'])) {
    $sweetAlertFlash = $_SESSION['sweet_alert'];
    unset($_SESSION['sweet_alert']);
}

$filtrosActivos = 0;
foreach (['q', 'id_categoria', 'sin_movimiento'] as $k) {
    if (!empty($_GET[$k])) $filtrosActivos++;
}
?>

<link href="https://fonts.googleapis.com/css2?family=IBM+Plex+Sans:wght@300;400;500;600;700&family=IBM+Plex+Mono:wght@400;500&display=swap" rel="stylesheet">
<link rel="stylesheet" href="<?= BASE_URL ?>/views/inventario/kardex/css/kardex.css">

<div class="kx-shell">

    <!-- ══════════════════════════════════════════════════════
         TOPBAR
    ══════════════════════════════════════════════════════ -->
    <header class="kx-topbar">
        <div class="kx-topbar-left">
            <div class="kx-page-icon"><i class="fas fa-book"></i></div>
            <div>
                <h1 class="kx-page-title">Kardex Valorizado</h1>
                <nav class="kx-breadcrumb">
                    <a href="index.php?controller=dashboard&action=index">Dashboard</a>
                    <span class="kx-breadcrumb-sep">/</span>
                    <span>Kardex</span>
                </nav>
            </div>
        </div>
        <div class="kx-topbar-actions">
            <a href="index.php?controller=movimiento&action=entrada" class="kx-btn kx-btn-ghost">
                <i class="fas fa-arrow-down" style="color:#059669;"></i> Registrar entrada
            </a>
            <a href="index.php?controller=movimiento&action=salida" class="kx-btn kx-btn-ghost">
                <i class="fas fa-arrow-up" style="color:#dc2626;"></i> Registrar salida
            </a>
        </div>
    </header>

    <!-- ══════════════════════════════════════════════════════
         KPI STRIP
    ══════════════════════════════════════════════════════ -->
    <div class="kx-kpi-strip">
        <div class="kx-kpi kx-kpi-saldo">
            <div class="kx-kpi-icon"><i class="fas fa-coins"></i></div>
            <div class="kx-kpi-body">
                <span class="kx-kpi-val">S/ <?= number_format($estadisticas['valor_total_inventario'] ?? 0, 2) ?></span>
                <span class="kx-kpi-label">Valor Total Inventario</span>
                <span class="kx-kpi-sub">Costo promedio ponderado</span>
            </div>
        </div>

        <div class="kx-kpi kx-kpi-entrada">
            <div class="kx-kpi-icon"><i class="fas fa-cubes"></i></div>
            <div class="kx-kpi-body">
                <span class="kx-kpi-val"><?= number_format($estadisticas['unidades_totales'] ?? 0) ?></span>
                <span class="kx-kpi-label">Unidades en Stock</span>
                <span class="kx-kpi-sub">En todos los productos activos</span>
            </div>
        </div>

        <div class="kx-kpi kx-kpi-salida">
            <div class="kx-kpi-icon"><i class="fas fa-box-open"></i></div>
            <div class="kx-kpi-body">
                <span class="kx-kpi-val"><?= number_format($estadisticas['productos_con_movimiento'] ?? 0) ?></span>
                <span class="kx-kpi-label">Productos con Kardex</span>
                <span class="kx-kpi-sub">de <?= number_format($estadisticas['total_productos'] ?? 0) ?> productos activos</span>
            </div>
        </div>

        <div class="kx-kpi kx-kpi-alerta">
            <div class="kx-kpi-icon"><i class="fas fa-exclamation-triangle"></i></div>
            <div class="kx-kpi-body">
                <span class="kx-kpi-val"><?= number_format($estadisticas['productos_sin_movimiento'] ?? 0) ?></span>
                <span class="kx-kpi-label">Sin Movimientos</span>
                <span class="kx-kpi-sub">Aún no tienen historial de kardex</span>
            </div>
        </div>
    </div>

    <!-- ══════════════════════════════════════════════════════
         BUSCADOR RÁPIDO (autocomplete AJAX)
    ══════════════════════════════════════════════════════ -->
    <div class="kx-search-card">
        <label class="kx-search-label"><i class="fas fa-search me-1"></i> Ir directo al kardex de un producto</label>
        <div class="kx-search-wrap">
            <i class="fas fa-search kx-search-icon"></i>
            <input type="text" id="kxSearchInput" class="kx-search-input"
                   placeholder="Escribe el nombre, código o SKU del producto…"
                   autocomplete="off"
                   data-search-url="index.php?controller=kardex&action=buscar_productos"
                   data-goto-url="index.php?controller=kardex&action=producto">
            <div id="kxSearchResults" class="kx-search-results"></div>
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
                <input type="hidden" name="action" value="index">

                <div class="kx-filter-grid">
                    <div>
                        <label class="kx-filter-label">Ordenar por</label>
                        <select name="orden" class="kx-filter-select">
                            <option value="valor_desc" <?= ($_GET['orden'] ?? '') === 'valor_desc' ? 'selected' : '' ?>>Mayor valor</option>
                            <option value="valor_asc"  <?= ($_GET['orden'] ?? '') === 'valor_asc'  ? 'selected' : '' ?>>Menor valor</option>
                            <option value="nombre"     <?= ($_GET['orden'] ?? '') === 'nombre'     ? 'selected' : '' ?>>Nombre (A-Z)</option>
                            <option value="stock_bajo" <?= ($_GET['orden'] ?? '') === 'stock_bajo' ? 'selected' : '' ?>>Stock más bajo</option>
                        </select>
                    </div>

                    <div>
                        <label class="kx-filter-label">Solo sin movimiento</label>
                        <select name="sin_movimiento" class="kx-filter-select">
                            <option value="">Todos</option>
                            <option value="1" <?= ($_GET['sin_movimiento'] ?? '') === '1' ? 'selected' : '' ?>>Sin kardex</option>
                        </select>
                    </div>

                    <div>
                        <label class="kx-filter-label">Buscar</label>
                        <input type="text" name="q" class="kx-filter-input"
                               placeholder="Nombre, código o SKU…"
                               value="<?= htmlspecialchars($_GET['q'] ?? '') ?>">
                    </div>

                    <div>
                        <label class="kx-filter-label">&nbsp;</label>
                        <a href="index.php?controller=kardex&action=almacen" class="kx-btn kx-btn-ghost" style="height:36px;width:100%;justify-content:center;">
                            <i class="fas fa-warehouse"></i> Por almacén
                        </a>
                    </div>

                    <div style="display:flex;gap:6px;align-items:flex-end;">
                        <button type="submit" class="kx-btn kx-btn-primary" style="height:36px;padding:0 14px;">
                            <i class="fas fa-search"></i> Filtrar
                        </button>
                        <a href="index.php?controller=kardex&action=index" class="kx-btn kx-btn-ghost" style="height:36px;padding:0 12px;">
                            <i class="fas fa-xmark"></i>
                        </a>
                    </div>
                </div>
            </form>
        </div>
    </div>

    <!-- ══════════════════════════════════════════════════════
         TABLA RESUMEN
    ══════════════════════════════════════════════════════ -->
    <div class="kx-table-card">
        <div class="kx-table-toolbar">
            <span class="kx-table-count">
                <?php if (count($resumen) > 0): ?>
                    Mostrando <strong><?= count($resumen) ?></strong> productos
                <?php else: ?>
                    Sin resultados
                <?php endif; ?>
            </span>
        </div>

        <div class="kx-table-scroll">
            <table class="kx-table">
                <thead>
                    <tr>
                        <th>Producto</th>
                        <th>Categoría / Marca</th>
                        <th class="kx-table-center">Stock</th>
                        <th class="kx-table-right">Costo Promedio</th>
                        <th class="kx-table-right">Valor Total</th>
                        <th>Último Movimiento</th>
                        <th class="kx-table-center">Kardex</th>
                    </tr>
                </thead>
                <tbody>
                <?php if (empty($resumen)): ?>
                    <tr>
                        <td colspan="7">
                            <div class="kx-empty">
                                <i class="fas fa-inbox kx-empty-icon"></i>
                                <p class="kx-empty-title">Sin productos<?= $filtrosActivos > 0 ? ' con los filtros aplicados' : '' ?></p>
                                <?php if ($filtrosActivos > 0): ?>
                                <p class="kx-empty-sub">
                                    <a href="index.php?controller=kardex&action=index" class="kx-btn kx-btn-ghost"
                                       style="display:inline-flex;margin-top:10px;height:32px;padding:0 12px;font-size:.8rem;">
                                        <i class="fas fa-xmark"></i> Quitar filtros
                                    </a>
                                </p>
                                <?php endif; ?>
                            </div>
                        </td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($resumen as $r):
                        $stockEstado = $r['estado_stock'] ?? 'normal';
                    ?>
                    <tr>
                        <td>
                            <span class="kx-prod-name"><?= htmlspecialchars($r['nombre']) ?></span>
                            <span class="kx-prod-code"><?= htmlspecialchars($r['codigo']) ?></span>
                        </td>
                        <td style="font-size:.8rem;">
                            <?= htmlspecialchars($r['categoria_nombre'] ?? '—') ?>
                            <?php if (!empty($r['marca_nombre'])): ?>
                                <div class="kx-cell-muted"><?= htmlspecialchars($r['marca_nombre']) ?></div>
                            <?php endif; ?>
                        </td>
                        <td class="kx-table-center">
                            <span class="kx-stock-badge kx-stock-<?= $stockEstado ?>">
                                <?= number_format($r['saldo_cantidad'] ?? 0) ?>
                            </span>
                        </td>
                        <td class="kx-table-right">S/ <?= number_format($r['saldo_costo_unitario'] ?? 0, 2) ?></td>
                        <td class="kx-table-right kx-money kx-money-saldo">S/ <?= number_format($r['saldo_costo_total'] ?? 0, 2) ?></td>
                        <td class="kx-cell-muted">
                            <?= !empty($r['ultimo_movimiento'])
                                ? date('d/m/Y H:i', strtotime($r['ultimo_movimiento']))
                                : 'Sin movimientos' ?>
                        </td>
                        <td class="kx-table-center">
                            <a href="index.php?controller=kardex&action=producto&id=<?= $r['id_producto'] ?>"
                               class="kx-detail-btn" title="Ver libro kardex">
                                <i class="fas fa-book-open"></i>
                            </a>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>

</div><!-- /kx-shell -->

<script>
const kxFlashData = <?= $sweetAlertFlash ? json_encode($sweetAlertFlash) : 'null' ?>;
</script>
<script src="<?= BASE_URL ?>/views/inventario/kardex/js/kardex.js"></script>

<?php include __DIR__ . '/../../layouts/footer.php'; ?>