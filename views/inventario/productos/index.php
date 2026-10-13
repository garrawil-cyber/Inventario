<?php
$currentPage = 'productos';
include __DIR__ . '/../../layouts/inventario_layout.php';
require_once __DIR__ . '/../../../config/SessionMiddleware.php';

$sweetAlert         = SessionMiddleware::getSweetAlert();

// ── Estadísticas de los productos en la página actual ──────────────────────
// Cuando hay paginación, los conteos de KPI usan el total real del controlador
// (pasado en $paginacion) no el count() de la página actual.
$totalProductos     = $paginacion['total_registros'] ?? count($productos);
$productosActivos   = count(array_filter($productos, fn($p) => $p['estado'] === 'activo'));
$productosInactivos = count(array_filter($productos, fn($p) => $p['estado'] === 'inactivo'));
$productosEliminados = count(array_filter($productos, fn($p) => $p['estado'] === 'eliminado'));
$stockTotal         = array_sum(array_column($productos, 'stock_actual'));
$valorInventario    = array_sum(array_map(fn($p) => $p['stock_actual'] * $p['precio_compra'], $productos));
$productosStockBajo = count(array_filter($productos, fn($p) => in_array($p['estado_stock'] ?? '', ['bajo', 'critico'])));
$productosAgotados  = count(array_filter($productos, fn($p) => ($p['estado_stock'] ?? '') === 'agotado'));

// ── Normalizar estado recibido por GET (la vista enviaba 'activos','eliminados'…)
// Ahora el select envía los valores correctos sin 's', pero se normaliza
// también el valor que viene en $filtrosActivos del controlador si existe.
$estadoGet = $_GET['estado'] ?? 'activo';

// Contar filtros activos (estado='activo' es el default, no cuenta como filtro)
$filtrosActivos = 0;
if (!empty($_GET['categoria']))                                  $filtrosActivos++;
if (!empty($_GET['almacen']))                                    $filtrosActivos++;
if (!empty($_GET['estado']) && $_GET['estado'] !== 'activo')    $filtrosActivos++;
if (!empty($_GET['estado_stock']))                               $filtrosActivos++;
if (!empty($_GET['busqueda']))                                   $filtrosActivos++;

// Usar paginación del controlador si está disponible, o valores por defecto
$paginacion     = $paginacion     ?? ['pagina_actual'=>1,'total_paginas'=>1,'total_registros'=>count($productos),'por_pagina'=>50,'offset'=>0];
$filtrosActivos_ctrl = isset($filtrosActivos) ? $filtrosActivos : 0;
?>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;500;600;700;800&family=DM+Mono:wght@400;500&display=swap" rel="stylesheet">
<link rel="stylesheet" href="<?= BASE_URL ?>/views/inventario/productos/css/styles_index.css">

<div class="pg-wrap">

    <!-- ══ Page Header ══════════════════════════════════════ -->
    <div class="pg-head">
        <div class="pg-head-left">
            <div class="pg-eyebrow"><i class="fas fa-boxes me-1"></i>Inventario</div>
            <h1 class="pg-title">Catálogo de <span>Productos</span></h1>
            <p class="pg-sub"><?= $paginacion['total_registros'] ?> producto<?= $paginacion['total_registros'] != 1 ? 's' : '' ?> encontrado<?= $paginacion['total_registros'] != 1 ? 's' : '' ?> · Actualizado ahora</p>
        </div>
        <div class="pg-actions">
            <a href="index.php?controller=producto&action=crear" class="btn-primary">
                <i class="fas fa-plus"></i> Nuevo Producto
            </a>
            <button class="btn-ghost" onclick="exportarTabla()">
                <i class="fas fa-download"></i> Exportar
            </button>
        </div>
    </div>

    <!-- ══ Alertas de stock ══════════════════════════════════ -->
    <?php if ($productosAgotados > 0): ?>
    <div class="inline-alert danger" style="margin-bottom:.75rem;">
        <i class="fas fa-exclamation-circle"></i>
        <strong><?= $productosAgotados ?> producto<?= $productosAgotados > 1 ? 's' : '' ?> agotado<?= $productosAgotados > 1 ? 's' : '' ?></strong> — Requieren reposición inmediata.
    </div>
    <?php endif; ?>
    <?php if ($productosStockBajo > 0): ?>
    <div class="inline-alert warning" style="margin-bottom:.75rem;">
        <i class="fas fa-exclamation-triangle"></i>
        <strong><?= $productosStockBajo ?> producto<?= $productosStockBajo > 1 ? 's' : '' ?> con stock bajo o crítico</strong> — Por debajo del punto de reorden.
    </div>
    <?php endif; ?>

    <!-- ══ KPI Row ═══════════════════════════════════════════ -->
    <div class="kpi-row">
        <div class="kpi" style="--kpi-color: var(--accent);">
            <div class="kpi-label">Total productos</div>
            <div class="kpi-val"><?= $totalProductos ?></div>
            <div class="kpi-note"><?= $productosActivos ?> activos / <?= $productosInactivos ?> inactivos</div>
            <i class="fas fa-box kpi-icon"></i>
        </div>
        <div class="kpi" style="--kpi-color: var(--ok);">
            <div class="kpi-label">Stock total</div>
            <div class="kpi-val"><?= number_format($stockTotal) ?></div>
            <div class="kpi-note">unidades en inventario</div>
            <i class="fas fa-cubes kpi-icon"></i>
        </div>
        <div class="kpi" style="--kpi-color: var(--info);">
            <div class="kpi-label">Valor inventario</div>
            <div class="kpi-val">S/ <?= number_format($valorInventario, 0) ?></div>
            <div class="kpi-note">a precio de compra</div>
            <i class="fas fa-coins kpi-icon"></i>
        </div>
        <div class="kpi" style="--kpi-color: var(--warn);">
            <div class="kpi-label">Stock bajo / crítico</div>
            <div class="kpi-val" style="color: var(--warn)"><?= $productosStockBajo ?></div>
            <div class="kpi-note">por reponer</div>
            <i class="fas fa-chart-line kpi-icon"></i>
        </div>
        <div class="kpi" style="--kpi-color: var(--danger);">
            <div class="kpi-label">Agotados</div>
            <div class="kpi-val" style="color: var(--danger)"><?= $productosAgotados ?></div>
            <div class="kpi-note">sin existencias</div>
            <i class="fas fa-ban kpi-icon"></i>
        </div>
    </div>

    <!-- ══ Filtros ═══════════════════════════════════════════ -->
    <div class="card">
        <div class="card-hdr">
            <span class="card-ttl">
                <i class="fas fa-sliders-h"></i> Filtros
                <?php if ($filtrosActivos > 0): ?>
                    <span style="background:var(--accent);color:#fff;border-radius:99px;padding:.05rem .45rem;font-size:.6rem;"><?= $filtrosActivos ?></span>
                <?php endif; ?>
            </span>
            <?php if ($filtrosActivos > 0): ?>
                <a href="index.php?controller=producto&action=index&estado=activo" class="btn-ghost" style="padding:.35rem .6rem; font-size:.7rem;">
                    <i class="fas fa-times"></i> Limpiar
                </a>
            <?php endif; ?>
        </div>
        <div class="card-body">
            <form method="GET" action="index.php">
                <input type="hidden" name="controller" value="producto">
                <input type="hidden" name="action" value="index">
                <div class="filter-grid" style="grid-template-columns:1fr 1fr 1fr 1fr 1fr auto;">
                    <!-- Búsqueda libre -->
                    <div class="fld-group">
                        <label class="fld-label">Búsqueda</label>
                        <input type="text" name="busqueda" class="fld-select"
                               placeholder="Nombre, código, SKU…"
                               value="<?= htmlspecialchars($_GET['busqueda'] ?? '') ?>">
                    </div>
                    <div class="fld-group">
                        <label class="fld-label">Categoría</label>
                        <select name="categoria" class="fld-select">
                            <option value="">Todas las categorías</option>
                            <?php foreach ($categorias as $cat): ?>
                                <option value="<?= htmlspecialchars($cat['value']) ?>"
                                    <?= (isset($_GET['categoria']) && $_GET['categoria'] == $cat['value']) ? 'selected' : '' ?>>
                                    <?= htmlspecialchars($cat['text']) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="fld-group">
                        <label class="fld-label">Almacén</label>
                        <select name="almacen" class="fld-select">
                            <option value="">Todos los almacenes</option>
                            <?php foreach ($almacenes as $alm): ?>
                                <option value="<?= htmlspecialchars($alm['value']) ?>"
                                    <?= (isset($_GET['almacen']) && $_GET['almacen'] == $alm['value']) ? 'selected' : '' ?>>
                                    <?= htmlspecialchars($alm['text']) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="fld-group">
                        <label class="fld-label">Estado</label>
<select name="estado" class="fld-select">
    <option value="activo"    <?= ($estadoGet === 'activo'    || $estadoGet === '')          ? 'selected' : '' ?>>Activos</option>
    <option value="inactivo"  <?= ($estadoGet === 'inactivo')                                ? 'selected' : '' ?>>Inactivos</option>
    <option value="eliminado" <?= ($estadoGet === 'eliminado')                               ? 'selected' : '' ?>>Eliminados</option>
    <option value="todos"     <?= ($estadoGet === 'todos')                                   ? 'selected' : '' ?>>Todos</option>
</select>
                    </div>
                    <!-- FILTRO NUEVO: estado_stock -->
                    <div class="fld-group">
                        <label class="fld-label">Stock</label>
                        <select name="estado_stock" class="fld-select">
                            <option value="">Todos los estados</option>
                            <option value="normal"  <?= (isset($_GET['estado_stock']) && $_GET['estado_stock'] == 'normal')  ? 'selected' : '' ?>>Normal</option>
                            <option value="bajo"    <?= (isset($_GET['estado_stock']) && $_GET['estado_stock'] == 'bajo')    ? 'selected' : '' ?>>Bajo</option>
                            <option value="critico" <?= (isset($_GET['estado_stock']) && $_GET['estado_stock'] == 'critico') ? 'selected' : '' ?>>Crítico</option>
                            <option value="agotado" <?= (isset($_GET['estado_stock']) && $_GET['estado_stock'] == 'agotado') ? 'selected' : '' ?>>Agotado</option>
                        </select>
                    </div>
                    <div class="filter-actions">
                        <button type="submit" class="btn-primary">
                            <i class="fas fa-search"></i> Aplicar
                        </button>
                    </div>
                </div>
            </form>
        </div>
    </div>
<!-- ══ Tabla ══════════════════════════════════════════════ -->
<div class="card">
    <div class="card-hdr">
        <span class="card-ttl">
            <i class="fas fa-list"></i> Directorio de Productos
            <span class="tbl-count"><?= $paginacion['total_registros'] ?></span>
        </span>
        <div style="display:flex;gap:.4rem;">
            <button class="btn-ghost" onclick="toggleView('table')" id="btnTable" style="padding:.35rem .6rem;font-size:.7rem;">
                <i class="fas fa-th-list"></i>
            </button>
            <button class="btn-ghost" onclick="toggleView('grid')" id="btnGrid" style="padding:.35rem .6rem;font-size:.7rem;">
                <i class="fas fa-th"></i>
            </button>
        </div>
    </div>

    <!-- Table view -->
    <div id="viewTable" class="tbl-wrap">
        <table class="prod-table" id="tblProductos">
            <thead>
                <tr>
                    <th>Código</th>
                    <th>Producto</th>
                    <th>Categoría</th>
                    <th>Almacén</th>
                    <th>Stock</th>
                    <th>P. Compra</th>
                    <th>P. Venta</th>
                    <th>Estado</th>
                    <th style="width:120px; text-align:center;">Acciones</th>
                </tr>
            </thead>
            <tbody>
            <?php if (empty($productos)): ?>
                <tr>
                    <td colspan="9">
                        <div class="empty-state">
                            <div class="empty-icon"><i class="fas fa-box-open"></i></div>
                            <div class="empty-title">Sin productos</div>
                            <div class="empty-sub">No se encontraron productos<?= $filtrosActivos > 0 ? ' con los filtros aplicados' : '' ?>.</div>
                            <a href="index.php?controller=producto&action=crear" class="btn-primary">
                                <i class="fas fa-plus"></i> Crear primer producto
                            </a>
                        </div>
                    </td>
                </tr>
            <?php else: ?>
                <?php foreach ($productos as $p):
                    $stk    = (int)$p['stock_actual'];
                    $min    = (int)$p['stock_minimo'];
                    $max    = max((int)$p['stock_maximo'], 1);
                    $pct    = min(round(($stk / $max) * 100), 100);
                    $estStock = $p['estado_stock'] ?? ($stk === 0 ? 'agotado' : ($stk <= $min ? 'critico' : 'normal'));
                    $sClass   = match($estStock) { 'agotado','critico' => 'danger', 'bajo' => 'warn', default => 'ok' };
                    $sText    = match($estStock) { 'agotado' => 'Agotado', 'critico' => 'Crítico', 'bajo' => 'Bajo', default => 'Normal' };
                    $sColor   = match($estStock) { 'agotado','critico' => '#dc2626', 'bajo' => '#d97706', default => '#059669' };
                    $av       = mb_strtoupper(mb_substr($p['nombre'], 0, 2));
                ?>
                <tr>
                    <td><span class="code-chip"><?= htmlspecialchars($p['codigo']) ?></span></td>
                    <td>
                        <div class="prod-name-cell">
                            <div class="prod-av"><?= $av ?></div>
                            <div>
                                <div class="prod-name"><?= htmlspecialchars($p['nombre']) ?></div>
                                <?php if (!empty($p['descripcion'])): ?>
                                    <div class="prod-desc"><?= htmlspecialchars(mb_substr($p['descripcion'], 0, 40)) ?><?= mb_strlen($p['descripcion']) > 40 ? '…' : '' ?></div>
                                <?php endif; ?>
                            </div>
                        </div>
                    </td>
                    <td><span class="cat-badge"><?= htmlspecialchars($p['categoria_nombre'] ?? '—') ?></span></td>
                    <td style="font-size:.72rem; color:var(--tx3);">
                        <?= htmlspecialchars($p['almacen_nombre'] ?? '—') ?>
                    </td>
                    <td>
                        <div class="stock-cell">
                            <div class="stock-top">
                                <span class="stock-num <?= $sClass ?>"><?= $stk ?></span>
                                <span class="stock-pill <?= $sClass ?>"><?= $sText ?></span>
                            </div>
                            <div class="stock-bar">
                                <div class="stock-bar-fill" style="width:<?= $pct ?>%; background:<?= $sColor ?>;"></div>
                            </div>
                        </div>
                    </td>
                    <td><span class="price-buy">S/ <?= number_format($p['precio_compra'], 2) ?></span></td>
                    <td><span class="price-sell">S/ <?= number_format($p['precio_venta'], 2) ?></span></td>
                    <td>
                        <span class="status-dot <?= $p['estado'] ?>">
                            <?= ucfirst($p['estado']) ?>
                        </span>
                    </td>
                    <td>
                        <div class="act-row" style="justify-content:center;">
                            <a href="index.php?controller=producto&action=ver&id=<?= $p['id_producto'] ?>" class="btn-icon" title="Ver detalle">
                                <i class="fas fa-eye"></i>
                            </a>
                            <a href="index.php?controller=producto&action=editar&id=<?= $p['id_producto'] ?>" class="btn-icon" title="Editar">
                                <i class="fas fa-pen"></i>
                            </a>
                            <a href="index.php?controller=movimiento&action=entrada&id_producto=<?= $p['id_producto'] ?>" class="btn-icon success" title="Registrar entrada">
                                <i class="fas fa-arrow-down"></i>
                            </a>
                            <a href="index.php?controller=movimiento&action=salida&id_producto=<?= $p['id_producto'] ?>" class="btn-icon warn" title="Registrar salida">
                                <i class="fas fa-arrow-up"></i>
                            </a>
                            <button class="btn-icon danger" title="Eliminar"
                                onclick="confirmarEliminar(<?= $p['id_producto'] ?>, '<?= htmlspecialchars(addslashes($p['nombre'])) ?>')">
                                <i class="fas fa-trash"></i>
                            </button>
                        </div>
                    </td>
                </tr>
                <?php endforeach; ?>
            <?php endif; ?>
            </tbody>
        </table>
    </div><!-- /tbl-wrap -->

    <!-- ══ Paginación ══════════════════════════════════════════ -->
    <?php if ($paginacion['total_paginas'] > 1): ?>
    <div class="pag-wrap" style="display:flex;align-items:center;justify-content:space-between;padding:.75rem 1.25rem;border-top:1px solid var(--border);flex-wrap:wrap;gap:.5rem;">
        <div style="font-size:.75rem;color:var(--tx3);">
            Mostrando
            <strong><?= $paginacion['offset'] + 1 ?></strong> –
            <strong><?= min($paginacion['offset'] + $paginacion['por_pagina'], $paginacion['total_registros']) ?></strong>
            de <strong><?= $paginacion['total_registros'] ?></strong> productos
        </div>
        <div style="display:flex;gap:.25rem;flex-wrap:wrap;">
            <?php
            // Construir query string base preservando todos los filtros
            $qBase = http_build_query(array_filter([
                'controller'  => 'producto',
                'action'      => 'index',
                'busqueda'    => $_GET['busqueda']    ?? '',
                'categoria'   => $_GET['categoria']   ?? '',
                'almacen'     => $_GET['almacen']     ?? '',
                'estado'      => $_GET['estado']      ?? 'activo',
                'estado_stock'=> $_GET['estado_stock'] ?? '',
                'por_pagina'  => $paginacion['por_pagina'],
            ], fn($v) => $v !== ''));

            $pActual = $paginacion['pagina_actual'];
            $pTotal  = $paginacion['total_paginas'];

            // Anterior
            if ($pActual > 1): ?>
                <a href="index.php?<?= $qBase ?>&pagina=<?= $pActual - 1 ?>" class="btn-ghost" style="padding:.3rem .55rem;font-size:.75rem;">‹</a>
            <?php endif;

            // Páginas cercanas
            $rango = range(max(1, $pActual - 2), min($pTotal, $pActual + 2));
            if ($rango[0] > 1): ?>
                <a href="index.php?<?= $qBase ?>&pagina=1" class="btn-ghost" style="padding:.3rem .55rem;font-size:.75rem;">1</a>
                <?php if ($rango[0] > 2): ?><span style="padding:.3rem .3rem;font-size:.75rem;color:var(--tx3);">…</span><?php endif;
            endif;
            foreach ($rango as $pg): ?>
                <a href="index.php?<?= $qBase ?>&pagina=<?= $pg ?>"
                   class="btn-ghost<?= $pg === $pActual ? ' active' : '' ?>"
                   style="padding:.3rem .55rem;font-size:.75rem;<?= $pg === $pActual ? 'background:var(--accent);color:#fff;' : '' ?>">
                    <?= $pg ?>
                </a>
            <?php endforeach;
            if (end($rango) < $pTotal):
                if (end($rango) < $pTotal - 1): ?><span style="padding:.3rem .3rem;font-size:.75rem;color:var(--tx3);">…</span><?php endif; ?>
                <a href="index.php?<?= $qBase ?>&pagina=<?= $pTotal ?>" class="btn-ghost" style="padding:.3rem .55rem;font-size:.75rem;"><?= $pTotal ?></a>
            <?php endif;

            // Siguiente
            if ($pActual < $pTotal): ?>
                <a href="index.php?<?= $qBase ?>&pagina=<?= $pActual + 1 ?>" class="btn-ghost" style="padding:.3rem .55rem;font-size:.75rem;">›</a>
            <?php endif; ?>
        </div>
        <!-- Por página -->
        <div style="display:flex;align-items:center;gap:.4rem;font-size:.75rem;color:var(--tx3);">
            Filas:
            <?php foreach ([10,15,25,50,100] as $n): ?>
                <a href="index.php?<?= $qBase ?>&por_pagina=<?= $n ?>&pagina=1"
                   style="padding:.2rem .45rem;border-radius:4px;text-decoration:none;font-size:.72rem;
                          <?= $paginacion['por_pagina'] == $n ? 'background:var(--accent);color:#fff;' : 'color:var(--tx2);' ?>">
                    <?= $n ?>
                </a>
            <?php endforeach; ?>
        </div>
    </div>
    <?php endif; ?>

    <!-- Grid view (tarjetas) -->
    <div id="viewGrid" style="display:none; padding:1rem;">
        <div style="display:grid; grid-template-columns:repeat(auto-fill,minmax(220px,1fr)); gap:.75rem;">
        <?php foreach ($productos as $p):
            $stk      = (int)$p['stock_actual'];
            $min      = (int)$p['stock_minimo'];
            $estStock = $p['estado_stock'] ?? ($stk === 0 ? 'agotado' : ($stk <= $min ? 'critico' : 'normal'));
            $sClass   = match($estStock) { 'agotado','critico' => 'danger', 'bajo' => 'warn', default => 'ok' };
            $sText    = match($estStock) { 'agotado' => 'Agotado', 'critico' => 'Crítico', 'bajo' => 'Bajo', default => 'Normal' };
            $av       = mb_strtoupper(mb_substr($p['nombre'], 0, 2));
        ?>
        <div style="background:var(--surface); border:1px solid var(--border); border-radius:var(--r); padding:.9rem;">
            <div style="display:flex; justify-content:space-between; align-items:flex-start; margin-bottom:.65rem;">
                <div class="prod-av" style="width:40px;height:40px;font-size:.75rem;"><?= $av ?></div>
                <span class="status-dot <?= $p['estado'] ?>"><?= ucfirst($p['estado']) ?></span>
            </div>
            <div style="font-size:.82rem; font-weight:700; margin-bottom:.2rem;"><?= htmlspecialchars($p['nombre']) ?></div>
            <div style="margin-bottom:.65rem;"><span class="code-chip"><?= htmlspecialchars($p['codigo']) ?></span></div>
            <div style="display:flex; justify-content:space-between; font-size:.72rem; color:var(--tx3); margin-bottom:.5rem;">
                <span>Stock: <strong class="stock-num <?= $sClass ?>"><?= $stk ?></strong></span>
                <span class="stock-pill <?= $sClass ?>"><?= $sText ?></span>
            </div>
            <div style="font-size:.78rem; font-weight:600; margin-bottom:.65rem;">S/ <?= number_format($p['precio_venta'], 2) ?></div>
            <div class="act-row">
                <a href="index.php?controller=producto&action=ver&id=<?= $p['id_producto'] ?>" class="btn-icon" title="Ver"><i class="fas fa-eye"></i></a>
                <a href="index.php?controller=producto&action=editar&id=<?= $p['id_producto'] ?>" class="btn-icon" title="Editar"><i class="fas fa-pen"></i></a>
                <a href="index.php?controller=movimiento&action=entrada&id_producto=<?= $p['id_producto'] ?>" class="btn-icon success" title="Entrada"><i class="fas fa-arrow-down"></i></a>
            </div>
        </div>
        <?php endforeach; ?>
        </div>
    </div>
</div>


<script>
/* ── Toggle view ──────────────────────────────── */
function toggleView(v) {
    document.getElementById('viewTable').style.display = v === 'table' ? '' : 'none';
    document.getElementById('viewGrid').style.display  = v === 'grid'  ? '' : 'none';
}

/* ── Confirm delete ───────────────────────────── */
function confirmarEliminar(id, nombre) {
    if (typeof Swal !== 'undefined') {
        Swal.fire({
            title: '¿Eliminar producto?',
            html: `<p style="font-size:.9rem;color:#4a5568;">Estás a punto de eliminar <strong>${nombre}</strong>.<br>Esta acción no se puede deshacer.</p>`,
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#dc2626',
            cancelButtonColor: '#94a3b8',
            confirmButtonText: 'Sí, eliminar',
            cancelButtonText: 'Cancelar',
        }).then(result => {
            if (result.isConfirmed)
                window.location.href = `index.php?controller=producto&action=eliminar&id=${id}`;
        });
    } else {
        if (confirm('¿Eliminar ' + nombre + '?'))
            window.location.href = `index.php?controller=producto&action=eliminar&id=${id}`;
    }
}

/* ── Export CSV ───────────────────────────────── */
function exportarTabla() {
    const rows = [...document.querySelectorAll('#tblProductos tr')];
    const csv  = rows.map(r =>
        [...r.querySelectorAll('th,td')]
            .slice(0, -1)
            .map(c => '"' + c.innerText.replace(/"/g, '""') + '"')
            .join(',')
    ).join('\n');
    const a = document.createElement('a');
    a.href = 'data:text/csv;charset=utf-8,' + encodeURIComponent(csv);
    a.download = 'productos_' + new Date().toISOString().slice(0, 10) + '.csv';
    a.click();
}

/* ── Inicialización segura de DataTables ───────── */
function initDataTable() {
    const table = document.getElementById('tblProductos');
    if (!table) return;
    
    // Verificar si hay filas de datos reales (no el mensaje de "sin productos")
    const tbody = table.querySelector('tbody');
    if (!tbody) return;
    
    const rows = tbody.querySelectorAll('tr');
    let hasRealData = false;
    
    // Verificar si existen filas con datos reales (más de una celda sin colspan)
    for (let row of rows) {
        const cells = row.querySelectorAll('td');
        // Si la fila tiene más de una celda O tiene 1 celda sin colspan, hay datos reales
        if (cells.length > 1) {
            hasRealData = true;
            break;
        }
        // Si tiene 1 celda pero NO tiene colspan, también es dato real
        if (cells.length === 1 && !cells[0].hasAttribute('colspan')) {
            hasRealData = true;
            break;
        }
    }
    
    // Si no hay datos reales, destruir DataTables si existe y salir
    if (!hasRealData || rows.length === 0) {
        if (typeof $ !== 'undefined' && $.fn && $.fn.DataTable) {
            if ($.fn.dataTable.isDataTable('#tblProductos')) {
                $('#tblProductos').DataTable().destroy();
                console.log('DataTables destruida - sin datos');
            }
        }
        return;
    }
    
    // Hay datos reales, inicializar DataTables
    if (typeof $ !== 'undefined' && $.fn && $.fn.DataTable) {
        // Destruir instancia previa si existe
        if ($.fn.dataTable.isDataTable('#tblProductos')) {
            $('#tblProductos').DataTable().destroy();
        }
        
        $('#tblProductos').DataTable({
            language: { url: '//cdn.datatables.net/plug-ins/1.13.7/i18n/es-ES.json' },
            pageLength: 25,
            order: [],
            responsive: true,
            columnDefs: [{ orderable: false, targets: [8] }],
            dom: '<"dt-top d-flex justify-content-between align-items-center mb-2"lf>rtp',
        });
        console.log('DataTables inicializada correctamente');
    }
}

/* ── Ejecutar cuando el DOM esté listo ───────── */
document.addEventListener('DOMContentLoaded', function () {
    // Inicializar DataTables solo si hay datos
    initDataTable();
    
    // SweetAlert flash
    <?php if ($sweetAlert): ?>
    if (typeof Swal !== 'undefined') {
        const cfg = <?= json_encode([
            'title'             => $sweetAlert['title'],
            'text'              => $sweetAlert['text'] ?? ($sweetAlert['message'] ?? ''),
            'icon'              => $sweetAlert['type'],
            'confirmButtonText' => 'Aceptar',
            'confirmButtonColor'=> '#2563eb',
        ]) ?>;
        <?php if ($sweetAlert['type'] === 'success'): ?>
        cfg.timer = 3000;
        cfg.timerProgressBar = true;
        <?php endif; ?>
        Swal.fire(cfg);
    }
    <?php endif; ?>
});

// También ejecutar cuando se usen filtros (por si hay cambios con AJAX)
if (typeof $ !== 'undefined') {
    $(document).on('click', 'form button[type="submit"]', function() {
        // Pequeño delay para esperar que el DOM se actualice
        setTimeout(initDataTable, 100);
    });
}
</script>

<?php include __DIR__ . '/../../layouts/footer.php'; ?>