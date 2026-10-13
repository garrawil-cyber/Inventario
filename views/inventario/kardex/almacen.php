<?php
/**
 * views/inventario/kardex/almacen.php
 * Variables del controlador:
 *   $almacenes  — lista completa para el selector
 *   $almacenSel — almacén elegido (o null)
 *   $kardex     — movimientos de kardex del almacén en el rango de fechas
 */

$currentPage = 'kardex';
include __DIR__ . '/../../layouts/inventario_layout.php';

$fechaDesde = $_GET['fecha_desde'] ?? date('Y-m-01');
$fechaHasta = $_GET['fecha_hasta'] ?? date('Y-m-d');

$totalEntradas = $totalSalidas = 0;
$valorEntradas = $valorSalidas = 0.0;
foreach ($kardex as $k) {
    $totalEntradas += (int) ($k['entrada_cantidad'] ?? 0);
    $valorEntradas += (float) ($k['entrada_costo_total'] ?? 0);
    $totalSalidas  += (int) ($k['salida_cantidad'] ?? 0);
    $valorSalidas  += (float) ($k['salida_costo_total'] ?? 0);
}
?>

<link href="https://fonts.googleapis.com/css2?family=IBM+Plex+Sans:wght@300;400;500;600;700&family=IBM+Plex+Mono:wght@400;500&display=swap" rel="stylesheet">
<link rel="stylesheet" href="<?= BASE_URL ?>/views/inventario/kardex/css/kardex.css">

<div class="kx-shell">

    <header class="kx-topbar">
        <div class="kx-topbar-left">
            <div class="kx-page-icon"><i class="fas fa-warehouse"></i></div>
            <div>
                <h1 class="kx-page-title">Kardex por Almacén</h1>
                <nav class="kx-breadcrumb">
                    <a href="index.php?controller=dashboard&action=index">Dashboard</a>
                    <span class="kx-breadcrumb-sep">/</span>
                    <a href="index.php?controller=kardex&action=index">Kardex</a>
                    <span class="kx-breadcrumb-sep">/</span>
                    <span>Por almacén</span>
                </nav>
            </div>
        </div>
    </header>

    <!-- Selector de almacén + fechas -->
    <div class="kx-filter-card">
        <div class="kx-filter-body" style="padding-top:20px;">
            <form method="get" action="">
                <input type="hidden" name="controller" value="kardex">
                <input type="hidden" name="action" value="almacen">

                <div class="kx-filter-grid" style="grid-template-columns:1fr 160px 160px auto;">
                    <div>
                        <label class="kx-filter-label">Almacén</label>
                        <select name="id" class="kx-filter-select" onchange="this.form.submit()">
                            <option value="">Selecciona un almacén…</option>
                            <?php foreach ($almacenes as $al): ?>
                            <option value="<?= $al['id_almacen'] ?? '' ?>"
                                <?= isset($almacenSel) && (int) $almacenSel['id_almacen'] === (int) ($al['id_almacen'] ?? 0) ? 'selected' : '' ?>>
                                <?= htmlspecialchars($al['almacen_nombre'] ?? 'Sin nombre') ?>
                            </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div>
                        <label class="kx-filter-label">Desde</label>
                        <input type="date" name="fecha_desde" class="kx-filter-input" value="<?= htmlspecialchars($fechaDesde) ?>">
                    </div>
                    <div>
                        <label class="kx-filter-label">Hasta</label>
                        <input type="date" name="fecha_hasta" class="kx-filter-input" value="<?= htmlspecialchars($fechaHasta) ?>">
                    </div>
                    <div style="display:flex;align-items:flex-end;">
                        <button type="submit" class="kx-btn kx-btn-primary" style="height:36px;padding:0 14px;">
                            <i class="fas fa-search"></i> Consultar
                        </button>
                    </div>
                </div>
            </form>
        </div>
    </div>

    <?php if (!$almacenSel): ?>
        <div class="kx-table-card">
            <div class="kx-empty">
                <i class="fas fa-warehouse kx-empty-icon"></i>
                <p class="kx-empty-title">Selecciona un almacén para ver su kardex</p>
                <p class="kx-empty-sub">Se mostrarán todos los movimientos registrados en el rango de fechas elegido.</p>
            </div>
        </div>
    <?php else: ?>

        <div class="kx-kpi-strip">
            <div class="kx-kpi kx-kpi-entrada">
                <div class="kx-kpi-icon"><i class="fas fa-arrow-circle-down"></i></div>
                <div class="kx-kpi-body">
                    <span class="kx-kpi-val"><?= number_format($totalEntradas) ?></span>
                    <span class="kx-kpi-label">Entradas</span>
                    <span class="kx-kpi-sub">S/ <?= number_format($valorEntradas, 2) ?></span>
                </div>
            </div>
            <div class="kx-kpi kx-kpi-salida">
                <div class="kx-kpi-icon"><i class="fas fa-arrow-circle-up"></i></div>
                <div class="kx-kpi-body">
                    <span class="kx-kpi-val"><?= number_format($totalSalidas) ?></span>
                    <span class="kx-kpi-label">Salidas</span>
                    <span class="kx-kpi-sub">S/ <?= number_format($valorSalidas, 2) ?></span>
                </div>
            </div>
            <div class="kx-kpi kx-kpi-saldo">
                <div class="kx-kpi-icon"><i class="fas fa-boxes"></i></div>
                <div class="kx-kpi-body">
                    <span class="kx-kpi-val"><?= number_format(count($kardex)) ?></span>
                    <span class="kx-kpi-label">Movimientos</span>
                    <span class="kx-kpi-sub">en <?= htmlspecialchars($almacenSel['almacen_nombre'] ?? $almacenSel['nombre'] ?? '') ?></span>
                </div>
            </div>
        </div>

        <div class="kx-table-card">
            <div class="kx-table-toolbar">
                <span class="kx-table-count">
                    <?= count($kardex) > 0 ? 'Mostrando <strong>' . count($kardex) . '</strong> movimientos' : 'Sin movimientos en el rango seleccionado' ?>
                </span>
            </div>
            <div class="kx-table-scroll">
                <table class="kx-table">
                    <thead>
                        <tr>
                            <th>Fecha</th>
                            <th>Producto</th>
                            <th>Tipo</th>
                            <th class="kx-table-center">Entrada</th>
                            <th class="kx-table-center">Salida</th>
                            <th class="kx-table-right">Valor</th>
                            <th>Documento</th>
                            <th>Usuario</th>
                        </tr>
                    </thead>
                    <tbody>
                    <?php if (empty($kardex)): ?>
                        <tr><td colspan="8">
                            <div class="kx-empty">
                                <i class="fas fa-inbox kx-empty-icon"></i>
                                <p class="kx-empty-title">Sin movimientos en este almacén y período</p>
                            </div>
                        </td></tr>
                    <?php else: ?>
                        <?php foreach ($kardex as $k):
                            $tipo = $k['tipo_movimiento'] ?? 'ajuste';
                            $icon = $tipo === 'entrada' ? 'fa-arrow-down' : ($tipo === 'salida' ? 'fa-arrow-up' : 'fa-sliders-h');
                            $valor = $tipo === 'entrada' ? ($k['entrada_costo_total'] ?? 0) : ($k['salida_costo_total'] ?? 0);
                            $docTxt = trim(($k['documento_tipo'] ?? '') . ' ' . ($k['documento_numero'] ?? ''));
                        ?>
                        <tr class="kx-row-<?= $tipo ?>">
                            <td>
                                <div class="kx-date-main"><?= date('d/m/Y', strtotime($k['fecha_movimiento'])) ?></div>
                                <div class="kx-date-sub"><?= date('H:i', strtotime($k['fecha_movimiento'])) ?></div>
                            </td>
                            <td>
                                <a href="index.php?controller=kardex&action=producto&id=<?= $k['id_producto'] ?>" style="text-decoration:none;">
                                    <span class="kx-prod-name"><?= htmlspecialchars($k['producto_nombre']) ?></span>
                                    <span class="kx-prod-code"><?= htmlspecialchars($k['producto_codigo']) ?></span>
                                </a>
                            </td>
                            <td><span class="kx-pill kx-pill-<?= $tipo ?>"><i class="fas <?= $icon ?>"></i> <?= ucfirst($tipo) ?></span></td>
                            <td class="kx-table-center kx-qty-entrada"><?= $k['entrada_cantidad'] > 0 ? '+' . number_format($k['entrada_cantidad']) : '—' ?></td>
                            <td class="kx-table-center kx-qty-salida"><?= $k['salida_cantidad'] > 0 ? '−' . number_format($k['salida_cantidad']) : '—' ?></td>
                            <td class="kx-table-right kx-money <?= $tipo === 'entrada' ? 'kx-money-entrada' : 'kx-money-salida' ?>">S/ <?= number_format($valor, 2) ?></td>
                            <td><?= !empty($docTxt) ? '<span class="kx-doc">' . htmlspecialchars($docTxt) . '</span>' : '<span class="kx-cell-muted">—</span>' ?></td>
                            <td class="kx-cell-muted"><?= htmlspecialchars($k['usuario_nombre'] ?? '—') ?></td>
                        </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>

    <?php endif; ?>

</div><!-- /kx-shell -->

<script src="<?= BASE_URL ?>/views/inventario/kardex/js/kardex.js"></script>
<?php include __DIR__ . '/../../layouts/footer.php'; ?>