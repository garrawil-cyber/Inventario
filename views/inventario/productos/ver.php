<?php
$currentPage = 'productos';
require_once __DIR__ . '/../../../config/SessionMiddleware.php';
SessionMiddleware::startSession();

$sweetAlert = null;
if (!empty($_SESSION['sweet_alert'])) {
    $sweetAlert = $_SESSION['sweet_alert'];
    unset($_SESSION['sweet_alert']);
}

include __DIR__ . '/../../layouts/inventario_layout.php';

$p = $producto;
$idProd = (int)$p['id_producto'];

// Calcular utilidad y margen
$utilidad = ($p['precio_venta'] ?? 0) - ($p['precio_compra'] ?? 0);
$margen   = ($p['precio_compra'] ?? 0) > 0 ? ($utilidad / $p['precio_compra']) * 100 : 0;

// Stock porcentaje para la barra
$stockMax = max((int)($p['stock_maximo'] ?? 100), 1);
$stockAct = (int)($p['stock_actual'] ?? 0);
$stockPct = min(100, round(($stockAct / $stockMax) * 100));

$estadoStock = $p['estado_stock'] ?? 'normal';
$stockColors = [
    'normal'  => ['bar' => '#22c55e', 'bg' => '#dcfce7', 'text' => '#16a34a'],
    'bajo'    => ['bar' => '#f59e0b', 'bg' => '#fef3c7', 'text' => '#d97706'],
    'critico' => ['bar' => '#ef4444', 'bg' => '#fee2e2', 'text' => '#dc2626'],
    'agotado' => ['bar' => '#94a3b8', 'bg' => '#f1f5f9', 'text' => '#64748b'],
];
$sc = $stockColors[$estadoStock] ?? $stockColors['normal'];
?>

<link rel="preconnect" href="https://fonts.googleapis.com">
<link href="https://fonts.googleapis.com/css2?family=Syne:wght@400;500;600;700;800&family=IBM+Plex+Mono:wght@400;500&family=Nunito:wght@300;400;500;600;700&display=swap" rel="stylesheet">
<link rel="stylesheet" href="views/inventario/productos/css/styles.css">

<style>
/* ── Reset & Base ── */
*, *::before, *::after { box-sizing: border-box; }

.vp-root {
    --ink:        #0f172a;
    --ink2:       #334155;
    --ink3:       #64748b;
    --surface:    #ffffff;
    --surface2:   #f8fafc;
    --surface3:   #f1f5f9;
    --border:     #e2e8f0;
    --border2:    #cbd5e1;
    --accent:     #3b82f6;
    --accent2:    #1d4ed8;
    --accentlt:   #eff6ff;
    --ok:         #22c55e;
    --oklt:       #dcfce7;
    --warn:       #f59e0b;
    --warnlt:     #fef3c7;
    --danger:     #ef4444;
    --dangerlt:   #fee2e2;
    --r:          10px;
    --r2:         14px;
    --shadow:     0 1px 3px rgba(0,0,0,.07), 0 4px 16px rgba(0,0,0,.05);
    --shadowmd:   0 4px 12px rgba(0,0,0,.08), 0 12px 40px rgba(0,0,0,.06);
    font-family: 'Nunito', sans-serif;
    color: var(--ink);
    padding: 0 0 3rem;
}

/* ── Breadcrumb ── */
.vp-breadcrumb {
    display: flex;
    align-items: center;
    gap: .4rem;
    font-size: .75rem;
    color: var(--ink3);
    padding: 1rem 0 1.5rem;
}
.vp-breadcrumb a { color: var(--ink3); text-decoration: none; transition: color .15s; }
.vp-breadcrumb a:hover { color: var(--accent); }
.vp-breadcrumb .sep { color: var(--border2); }
.vp-breadcrumb .cur { color: var(--ink2); font-weight: 600; }

/* ── Hero Header ── */
.vp-hero {
    background: var(--surface);
    border: 1px solid var(--border);
    border-radius: var(--r2);
    padding: 1.75rem 2rem;
    margin-bottom: 1.5rem;
    box-shadow: var(--shadow);
    display: flex;
    align-items: flex-start;
    gap: 1.5rem;
}

.vp-hero-icon {
    width: 64px;
    height: 64px;
    border-radius: 14px;
    background: linear-gradient(135deg, var(--accentlt) 0%, #dbeafe 100%);
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 1.6rem;
    color: var(--accent);
    flex-shrink: 0;
    border: 1px solid #bfdbfe;
}

.vp-hero-body { flex: 1; min-width: 0; }

.vp-eyebrow {
    font-size: .65rem;
    font-weight: 700;
    text-transform: uppercase;
    letter-spacing: 1.5px;
    color: var(--accent);
    margin-bottom: .3rem;
    font-family: 'IBM Plex Mono', monospace;
}

.vp-title {
    font-family: 'Syne', sans-serif;
    font-size: 1.75rem;
    font-weight: 800;
    color: var(--ink);
    margin: 0 0 .6rem;
    line-height: 1.2;
}

.vp-chips {
    display: flex;
    flex-wrap: wrap;
    gap: .4rem;
    align-items: center;
}

.chip {
    display: inline-flex;
    align-items: center;
    gap: .25rem;
    padding: .2rem .65rem;
    border-radius: 99px;
    font-size: .7rem;
    font-weight: 600;
    border: 1px solid var(--border);
    background: var(--surface2);
    color: var(--ink2);
}
.chip.mono { font-family: 'IBM Plex Mono', monospace; font-size: .65rem; }
.chip.accent { background: var(--accentlt); color: var(--accent); border-color: #bfdbfe; }
.chip.ok { background: var(--oklt); color: #15803d; border-color: #bbf7d0; }
.chip.warn { background: var(--warnlt); color: #b45309; border-color: #fde68a; }
.chip.danger { background: var(--dangerlt); color: #b91c1c; border-color: #fecaca; }
.chip.muted { background: #f1f5f9; color: var(--ink3); border-color: var(--border); }

.vp-hero-actions {
    display: flex;
    flex-direction: column;
    gap: .5rem;
    flex-shrink: 0;
}

/* ── Buttons ── */
.btn {
    display: inline-flex;
    align-items: center;
    gap: .4rem;
    padding: .55rem 1.1rem;
    border-radius: 8px;
    font-size: .8rem;
    font-weight: 600;
    font-family: 'Nunito', sans-serif;
    cursor: pointer;
    text-decoration: none;
    border: 1px solid transparent;
    transition: all .15s ease;
    white-space: nowrap;
}
.btn-primary { background: var(--accent); color: #fff; border-color: var(--accent); }
.btn-primary:hover { background: var(--accent2); border-color: var(--accent2); transform: translateY(-1px); box-shadow: 0 4px 12px rgba(59,130,246,.35); }
.btn-ghost { background: var(--surface); color: var(--ink2); border-color: var(--border2); }
.btn-ghost:hover { background: var(--surface3); border-color: var(--border2); }
.btn-ok { background: var(--ok); color: #fff; border-color: var(--ok); }
.btn-ok:hover { background: #16a34a; border-color: #16a34a; transform: translateY(-1px); box-shadow: 0 4px 12px rgba(34,197,94,.35); }
.btn-warn { background: var(--warn); color: #fff; border-color: var(--warn); }
.btn-warn:hover { background: #d97706; border-color: #d97706; transform: translateY(-1px); }
.btn-danger { background: var(--surface); color: var(--danger); border-color: #fca5a5; }
.btn-danger:hover { background: var(--dangerlt); }

/* ── Stat Strip ── */
.vp-stats {
    display: grid;
    grid-template-columns: repeat(4, 1fr);
    gap: 1rem;
    margin-bottom: 1.5rem;
}

.stat-card {
    background: var(--surface);
    border: 1px solid var(--border);
    border-radius: var(--r2);
    padding: 1.25rem 1.5rem;
    box-shadow: var(--shadow);
    position: relative;
    overflow: hidden;
    transition: transform .15s, box-shadow .15s;
}
.stat-card:hover { transform: translateY(-2px); box-shadow: var(--shadowmd); }

.stat-card::before {
    content: '';
    position: absolute;
    top: 0; left: 0; right: 0;
    height: 3px;
    border-radius: 3px 3px 0 0;
}
.stat-card.blue::before { background: linear-gradient(90deg, #3b82f6, #60a5fa); }
.stat-card.green::before { background: linear-gradient(90deg, #22c55e, #4ade80); }
.stat-card.amber::before { background: linear-gradient(90deg, #f59e0b, #fbbf24); }
.stat-card.violet::before { background: linear-gradient(90deg, #8b5cf6, #a78bfa); }

.stat-label {
    font-size: .65rem;
    font-weight: 700;
    text-transform: uppercase;
    letter-spacing: 1px;
    color: var(--ink3);
    margin-bottom: .5rem;
}
.stat-value {
    font-family: 'Syne', sans-serif;
    font-size: 1.6rem;
    font-weight: 800;
    color: var(--ink);
    line-height: 1;
    margin-bottom: .3rem;
}
.stat-sub { font-size: .72rem; color: var(--ink3); }
.stat-icon {
    position: absolute;
    right: 1.25rem; top: 50%;
    transform: translateY(-50%);
    font-size: 1.8rem;
    opacity: .07;
}

/* ── Main Grid ── */
.vp-grid {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 1rem;
    margin-bottom: 1rem;
}
.vp-grid.cols3 { grid-template-columns: repeat(3, 1fr); }
.vp-grid.full  { grid-template-columns: 1fr; }

/* ── Section Cards ── */
.sec-card {
    background: var(--surface);
    border: 1px solid var(--border);
    border-radius: var(--r2);
    overflow: hidden;
    box-shadow: var(--shadow);
    transition: box-shadow .15s;
}
.sec-card:hover { box-shadow: var(--shadowmd); }

.sec-head {
    display: flex;
    align-items: center;
    gap: .6rem;
    padding: .9rem 1.25rem;
    border-bottom: 1px solid var(--border);
    background: var(--surface2);
}
.sec-icon {
    width: 30px; height: 30px;
    border-radius: 8px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: .75rem;
    flex-shrink: 0;
}
.sec-icon.blue   { background: var(--accentlt); color: var(--accent); }
.sec-icon.green  { background: var(--oklt); color: #15803d; }
.sec-icon.amber  { background: var(--warnlt); color: #b45309; }
.sec-icon.violet { background: #ede9fe; color: #7c3aed; }
.sec-icon.slate  { background: var(--surface3); color: var(--ink2); }
.sec-icon.rose   { background: #fff1f2; color: #e11d48; }

.sec-title {
    font-size: .78rem;
    font-weight: 700;
    color: var(--ink2);
    text-transform: uppercase;
    letter-spacing: .8px;
}

.sec-body { padding: 1.25rem; }

/* ── Row List ── */
.row-list { display: flex; flex-direction: column; gap: .6rem; }

.row-item {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: .5rem .75rem;
    background: var(--surface2);
    border-radius: 8px;
    border: 1px solid var(--border);
    font-size: .8rem;
}
.row-item-label { color: var(--ink3); font-weight: 500; }
.row-item-value { color: var(--ink); font-weight: 600; text-align: right; }
.row-item-value.mono { font-family: 'IBM Plex Mono', monospace; font-size: .75rem; }

/* ── Stock Bar ── */
.stock-wrap { margin: .75rem 0 0; }
.stock-top {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: .4rem;
    font-size: .75rem;
}
.stock-track {
    height: 8px;
    background: var(--surface3);
    border-radius: 99px;
    overflow: hidden;
    border: 1px solid var(--border);
}
.stock-fill {
    height: 100%;
    border-radius: 99px;
    transition: width .6s ease;
}
.stock-legend {
    display: flex;
    justify-content: space-between;
    font-size: .65rem;
    color: var(--ink3);
    margin-top: .3rem;
}

/* ── Price Breakdown ── */
.price-row {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: .6rem .75rem;
    border-radius: 8px;
    margin-bottom: .4rem;
    font-size: .82rem;
}
.price-row.compra  { background: #f8fafc; border: 1px solid var(--border); }
.price-row.venta   { background: var(--oklt); border: 1px solid #bbf7d0; }
.price-row.mayor   { background: #eff6ff; border: 1px solid #bfdbfe; }
.price-row.divider { background: transparent; border-top: 1px dashed var(--border2); padding: .3rem .75rem; margin-top: .2rem; }
.price-lbl { color: var(--ink3); font-weight: 500; }
.price-val { font-family: 'Syne', sans-serif; font-weight: 700; font-size: .9rem; color: var(--ink); }
.price-val.ok    { color: #15803d; }
.price-val.blue  { color: var(--accent); }

/* ── Margin Gauge ── */
.margin-gauge {
    text-align: center;
    padding: .5rem 0;
}
.margin-pct {
    font-family: 'Syne', sans-serif;
    font-size: 2.2rem;
    font-weight: 800;
    color: var(--ok);
    line-height: 1;
}
.margin-pct.warn { color: var(--warn); }
.margin-pct.danger { color: var(--danger); }
.margin-sub { font-size: .7rem; color: var(--ink3); margin-top: .2rem; }

/* ── Ubicacion Chips ── */
.ubic-list { display: flex; flex-wrap: wrap; gap: .5rem; margin-top: .25rem; }
.ubic-chip {
    display: inline-flex;
    align-items: center;
    gap: .3rem;
    background: var(--accentlt);
    border: 1px solid #bfdbfe;
    color: var(--accent2);
    border-radius: 7px;
    padding: .3rem .7rem;
    font-size: .72rem;
    font-weight: 600;
    font-family: 'IBM Plex Mono', monospace;
    transition: background .15s;
}
.ubic-chip:hover { background: #dbeafe; }

/* ── Audit Row ── */
.audit-row {
    display: flex;
    gap: 1rem;
    padding: .6rem .75rem;
    background: var(--surface2);
    border-radius: 8px;
    border: 1px solid var(--border);
    font-size: .78rem;
    align-items: center;
}
.audit-icon { color: var(--accent); font-size: .85rem; flex-shrink: 0; }
.audit-lbl  { color: var(--ink3); }
.audit-val  { color: var(--ink); font-weight: 600; margin-top: .1rem; font-family: 'IBM Plex Mono', monospace; font-size: .72rem; }

/* ── Action Footer ── */
.vp-footer {
    background: var(--surface);
    border: 1px solid var(--border);
    border-radius: var(--r2);
    padding: 1rem 1.5rem;
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 1rem;
    box-shadow: var(--shadow);
    margin-top: 1.5rem;
    flex-wrap: wrap;
}
.vp-footer-left { font-size: .8rem; color: var(--ink3); }
.vp-footer-right { display: flex; gap: .5rem; flex-wrap: wrap; align-items: center; }

/* ── Responsive ── */
@media (max-width: 900px) {
    .vp-stats { grid-template-columns: repeat(2, 1fr); }
    .vp-grid  { grid-template-columns: 1fr; }
    .vp-grid.cols3 { grid-template-columns: 1fr; }
    .vp-hero  { flex-direction: column; }
    .vp-hero-actions { flex-direction: row; }
}
@media (max-width: 560px) {
    .vp-stats { grid-template-columns: 1fr 1fr; }
    .vp-title { font-size: 1.3rem; }
    .stat-value { font-size: 1.3rem; }
}

/* ── Fade-in animation ── */
@keyframes fadeUp {
    from { opacity: 0; transform: translateY(12px); }
    to   { opacity: 1; transform: translateY(0); }
}
.vp-hero    { animation: fadeUp .3s ease both; }
.vp-stats   { animation: fadeUp .35s .05s ease both; }
.vp-grid    { animation: fadeUp .35s .1s ease both; }
.vp-footer  { animation: fadeUp .35s .15s ease both; }
</style>

<div class="vp-root">

<!-- Breadcrumb -->
<nav class="vp-breadcrumb">
    <a href="index.php?controller=dashboard&action=index"><i class="fas fa-home"></i></a>
    <span class="sep">/</span>
    <a href="index.php?controller=producto&action=index">Productos</a>
    <span class="sep">/</span>
    <span class="cur"><?= htmlspecialchars($p['nombre']) ?></span>
</nav>

<!-- Hero Header -->
<div class="vp-hero">
    <div class="vp-hero-icon"><i class="fas fa-box-open"></i></div>

    <div class="vp-hero-body">
        <div class="vp-eyebrow">Detalle de Producto · ID #<?= $idProd ?></div>
        <h1 class="vp-title"><?= htmlspecialchars($p['nombre']) ?></h1>
        <div class="vp-chips">
            <span class="chip mono accent"><i class="fas fa-barcode"></i><?= htmlspecialchars($p['codigo']) ?></span>
            <?php if (!empty($p['sku'])): ?>
                <span class="chip mono">SKU: <?= htmlspecialchars($p['sku']) ?></span>
            <?php endif; ?>
            <?php if (!empty($p['categoria_nombre'])): ?>
                <span class="chip"><i class="fas fa-tag"></i><?= htmlspecialchars($p['categoria_nombre']) ?></span>
            <?php endif; ?>
            <?php if (!empty($p['almacen_nombre'])): ?>
                <span class="chip"><i class="fas fa-warehouse"></i><?= htmlspecialchars($p['almacen_nombre']) ?></span>
            <?php endif; ?>
            <?php
                $estadoClass = match($p['estado']) {
                    'activo'   => 'ok',
                    'inactivo' => 'warn',
                    default    => 'danger'
                };
                $estadoIcon = match($p['estado']) {
                    'activo'   => 'fa-circle-check',
                    'inactivo' => 'fa-pause-circle',
                    default    => 'fa-circle-xmark'
                };
            ?>
            <span class="chip <?= $estadoClass ?>">
                <i class="fas <?= $estadoIcon ?>"></i><?= ucfirst($p['estado']) ?>
            </span>
        </div>
    </div>

    <div class="vp-hero-actions">
        <a href="index.php?controller=producto&action=editar&id=<?= $idProd ?>" class="btn btn-primary">
            <i class="fas fa-pen"></i> Editar
        </a>
        <a href="index.php?controller=producto&action=index" class="btn btn-ghost">
            <i class="fas fa-arrow-left"></i> Volver
        </a>
    </div>
</div>

<!-- Stat Strip -->
<div class="vp-stats">
    <div class="stat-card blue">
        <div class="stat-label">Stock Actual</div>
        <div class="stat-value"><?= number_format($p['stock_actual'] ?? 0) ?></div>
        <div class="stat-sub"><?= ucfirst($estadoStock) ?> · Máx <?= number_format($p['stock_maximo'] ?? 100) ?></div>
        <div class="stat-icon"><i class="fas fa-layer-group"></i></div>
    </div>
    <div class="stat-card green">
        <div class="stat-label">Precio de Venta</div>
        <div class="stat-value" style="font-size:1.35rem">S/ <?= number_format($p['precio_venta'] ?? 0, 2) ?></div>
        <div class="stat-sub">Compra: S/ <?= number_format($p['precio_compra'] ?? 0, 2) ?></div>
        <div class="stat-icon"><i class="fas fa-tags"></i></div>
    </div>
    <div class="stat-card amber">
        <div class="stat-label">Margen de Utilidad</div>
        <div class="stat-value" style="font-size:1.35rem"><?= number_format($margen, 1) ?>%</div>
        <div class="stat-sub">Utilidad: S/ <?= number_format($utilidad, 2) ?></div>
        <div class="stat-icon"><i class="fas fa-chart-line"></i></div>
    </div>
    <div class="stat-card violet">
        <div class="stat-label">Punto de Reorden</div>
        <div class="stat-value"><?= number_format($p['punto_reorden'] ?? 0) ?></div>
        <div class="stat-sub">Mínimo: <?= number_format($p['stock_minimo'] ?? 0) ?> uds</div>
        <div class="stat-icon"><i class="fas fa-bell"></i></div>
    </div>
</div>

<!-- Main Grid Row 1 -->
<div class="vp-grid">

    <!-- Información básica -->
    <div class="sec-card">
        <div class="sec-head">
            <div class="sec-icon blue"><i class="fas fa-info-circle"></i></div>
            <div class="sec-title">Información Básica</div>
        </div>
        <div class="sec-body">
            <div class="row-list">
                <div class="row-item">
                    <span class="row-item-label">Código</span>
                    <span class="row-item-value mono"><?= htmlspecialchars($p['codigo']) ?></span>
                </div>
                <div class="row-item">
                    <span class="row-item-label">SKU</span>
                    <span class="row-item-value mono"><?= htmlspecialchars($p['sku'] ?? '—') ?></span>
                </div>
                <div class="row-item">
                    <span class="row-item-label">Nombre</span>
                    <span class="row-item-value"><?= htmlspecialchars($p['nombre']) ?></span>
                </div>
                <div class="row-item">
                    <span class="row-item-label">Unidad de Medida</span>
                    <span class="row-item-value"><?= htmlspecialchars($p['unidad_medida'] ?? '—') ?></span>
                </div>
                <?php if (!empty($p['codigo_barras'])): ?>
                <div class="row-item">
                    <span class="row-item-label">Cód. Barras</span>
                    <span class="row-item-value mono"><?= htmlspecialchars($p['codigo_barras']) ?></span>
                </div>
                <?php endif; ?>
            </div>
            <?php if (!empty($p['descripcion'])): ?>
            <div style="margin-top:.75rem; padding:.7rem; background:var(--surface2); border-radius:8px; border:1px solid var(--border); font-size:.8rem; color:var(--ink2); line-height:1.5;">
                <?= nl2br(htmlspecialchars($p['descripcion'])) ?>
            </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- Clasificación -->
    <div class="sec-card">
        <div class="sec-head">
            <div class="sec-icon violet"><i class="fas fa-sitemap"></i></div>
            <div class="sec-title">Clasificación</div>
        </div>
        <div class="sec-body">
            <div class="row-list">
                <div class="row-item">
                    <span class="row-item-label">Categoría</span>
                    <span class="row-item-value"><?= htmlspecialchars($p['categoria_nombre'] ?? '—') ?></span>
                </div>
                <div class="row-item">
                    <span class="row-item-label">Marca</span>
                    <span class="row-item-value"><?= htmlspecialchars($p['marca_nombre'] ?? '—') ?></span>
                </div>
                <div class="row-item">
                    <span class="row-item-label">Proveedor</span>
                    <span class="row-item-value"><?= htmlspecialchars($p['proveedor_nombre'] ?? '—') ?></span>
                </div>
                <div class="row-item">
                    <span class="row-item-label">Género</span>
                    <span class="row-item-value"><?= htmlspecialchars($p['genero'] ?? 'Unisex') ?></span>
                </div>
                <div class="row-item">
                    <span class="row-item-label">Temporada</span>
                    <span class="row-item-value"><?= htmlspecialchars(str_replace('_', ' ', $p['temporada'] ?? 'Todo el año')) ?></span>
                </div>
            </div>
        </div>
    </div>

</div>

<!-- Main Grid Row 2 -->
<div class="vp-grid">

    <!-- Control de Stock -->
    <div class="sec-card">
        <div class="sec-head">
            <div class="sec-icon green"><i class="fas fa-layer-group"></i></div>
            <div class="sec-title">Control de Stock</div>
        </div>
        <div class="sec-body">
            <div class="row-list">
                <div class="row-item" style="background:<?= $sc['bg'] ?>; border-color:<?= $sc['bar'] ?>33;">
                    <span class="row-item-label" style="color:<?= $sc['text'] ?>; font-weight:700;">Stock Actual</span>
                    <span style="display:flex;align-items:center;gap:.5rem;">
                        <span style="font-family:'Syne',sans-serif; font-size:1.05rem; font-weight:800; color:<?= $sc['text'] ?>"><?= number_format($stockAct) ?></span>
                        <span class="chip" style="background:<?= $sc['bg'] ?>; color:<?= $sc['text'] ?>; border-color:<?= $sc['bar'] ?>55; font-size:.6rem;"><?= ucfirst($estadoStock) ?></span>
                    </span>
                </div>
                <div class="row-item">
                    <span class="row-item-label">Stock Mínimo</span>
                    <span class="row-item-value"><?= number_format($p['stock_minimo'] ?? 0) ?> uds</span>
                </div>
                <div class="row-item">
                    <span class="row-item-label">Stock Máximo</span>
                    <span class="row-item-value"><?= number_format($p['stock_maximo'] ?? 100) ?> uds</span>
                </div>
                <div class="row-item">
                    <span class="row-item-label">Punto de Reorden</span>
                    <span class="row-item-value"><?= number_format($p['punto_reorden'] ?? 0) ?> uds</span>
                </div>
            </div>
            <!-- Stock Progress Bar -->
            <div class="stock-wrap">
                <div class="stock-top">
                    <span style="font-size:.72rem; color:var(--ink3);">Ocupación del almacén</span>
                    <span style="font-size:.72rem; font-weight:700; color:<?= $sc['text'] ?>"><?= $stockPct ?>%</span>
                </div>
                <div class="stock-track">
                    <div class="stock-fill" style="width:<?= $stockPct ?>%; background:<?= $sc['bar'] ?>;"></div>
                </div>
                <div class="stock-legend">
                    <span>0</span>
                    <span>Mín: <?= number_format($p['stock_minimo'] ?? 0) ?></span>
                    <span>Máx: <?= number_format($p['stock_maximo'] ?? 100) ?></span>
                </div>
            </div>
        </div>
    </div>

    <!-- Precios -->
    <div class="sec-card">
        <div class="sec-head">
            <div class="sec-icon amber"><i class="fas fa-tags"></i></div>
            <div class="sec-title">Estructura de Precios</div>
        </div>
        <div class="sec-body">
            <div class="price-row compra">
                <span class="price-lbl"><i class="fas fa-arrow-down" style="color:var(--ink3); margin-right:.3rem;"></i> Precio de Compra</span>
                <span class="price-val">S/ <?= number_format($p['precio_compra'] ?? 0, 2) ?></span>
            </div>
            <div class="price-row venta">
                <span class="price-lbl"><i class="fas fa-arrow-up" style="color:#15803d; margin-right:.3rem;"></i> Precio de Venta</span>
                <span class="price-val ok">S/ <?= number_format($p['precio_venta'] ?? 0, 2) ?></span>
            </div>
            <?php if (!empty($p['precio_mayorista'])): ?>
            <div class="price-row mayor">
                <span class="price-lbl"><i class="fas fa-boxes-stacked" style="color:var(--accent); margin-right:.3rem;"></i> Precio Mayorista</span>
                <span class="price-val blue">S/ <?= number_format($p['precio_mayorista'], 2) ?></span>
            </div>
            <?php endif; ?>
            <div class="price-row divider"></div>
            <div style="display:flex; gap:.75rem; margin-top:.25rem;">
                <div style="flex:1; text-align:center; padding:.75rem; background:var(--surface2); border-radius:10px; border:1px solid var(--border);">
                    <div style="font-size:.65rem; font-weight:700; text-transform:uppercase; letter-spacing:.8px; color:var(--ink3); margin-bottom:.25rem;">Utilidad</div>
                    <div style="font-family:'Syne',sans-serif; font-size:1.15rem; font-weight:800; color:<?= $utilidad >= 0 ? '#15803d' : 'var(--danger)' ?>;">S/ <?= number_format($utilidad, 2) ?></div>
                </div>
                <div style="flex:1; text-align:center; padding:.75rem; background:var(--surface2); border-radius:10px; border:1px solid var(--border);">
                    <div style="font-size:.65rem; font-weight:700; text-transform:uppercase; letter-spacing:.8px; color:var(--ink3); margin-bottom:.25rem;">Margen</div>
                    <div style="font-family:'Syne',sans-serif; font-size:1.15rem; font-weight:800; color:<?= $margen >= 20 ? '#15803d' : ($margen >= 10 ? 'var(--warn)' : 'var(--danger)') ?>;"><?= number_format($margen, 1) ?>%</div>
                </div>
            </div>
        </div>
    </div>

</div>

<!-- Row 3 -->
<div class="vp-grid cols3">

    <!-- Atributos físicos -->
    <div class="sec-card">
        <div class="sec-head">
            <div class="sec-icon slate"><i class="fas fa-tshirt"></i></div>
            <div class="sec-title">Atributos</div>
        </div>
        <div class="sec-body">
            <div class="row-list">
                <div class="row-item">
                    <span class="row-item-label">Talla</span>
                    <span class="row-item-value"><?= htmlspecialchars($p['talla'] ?? '—') ?></span>
                </div>
                <div class="row-item">
                    <span class="row-item-label">Color</span>
                    <span class="row-item-value"><?= htmlspecialchars($p['color'] ?? '—') ?></span>
                </div>
                <div class="row-item">
                    <span class="row-item-label">Material</span>
                    <span class="row-item-value"><?= htmlspecialchars($p['material'] ?? '—') ?></span>
                </div>
            </div>
        </div>
    </div>

    <!-- Ubicaciones -->
    <div class="sec-card">
        <div class="sec-head">
            <div class="sec-icon rose"><i class="fas fa-map-marker-alt"></i></div>
            <div class="sec-title">Ubicaciones</div>
        </div>
        <div class="sec-body">
            <?php if (!empty($p['ubicaciones'])): ?>
                <div class="ubic-list">
                    <?php foreach ($p['ubicaciones'] as $ubic): ?>
                        <span class="ubic-chip" title="<?= htmlspecialchars($ubic['descripcion'] ?? '') ?>">
                            <i class="fas fa-location-dot"></i>
                            <?= htmlspecialchars($ubic['codigo']) ?>
                        </span>
                    <?php endforeach; ?>
                </div>
                <?php if (!empty($p['almacen_ciudad'])): ?>
                    <div style="margin-top:.75rem; font-size:.72rem; color:var(--ink3);">
                        <i class="fas fa-city" style="margin-right:.3rem;"></i><?= htmlspecialchars($p['almacen_ciudad']) ?>
                        <?php if (!empty($p['almacen_direccion'])): ?>
                            — <?= htmlspecialchars($p['almacen_direccion']) ?>
                        <?php endif; ?>
                    </div>
                <?php endif; ?>
            <?php else: ?>
                <div style="text-align:center; padding:1.5rem; color:var(--ink3); font-size:.8rem;">
                    <i class="fas fa-map-marker-alt" style="font-size:1.5rem; opacity:.25; display:block; margin-bottom:.5rem;"></i>
                    Sin ubicaciones registradas
                </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- Auditoría -->
    <div class="sec-card">
        <div class="sec-head">
            <div class="sec-icon slate"><i class="fas fa-clock-rotate-left"></i></div>
            <div class="sec-title">Auditoría</div>
        </div>
        <div class="sec-body">
            <div style="display:flex; flex-direction:column; gap:.6rem;">
                <div class="audit-row">
                    <div class="audit-icon"><i class="fas fa-calendar-plus"></i></div>
                    <div>
                        <div class="audit-lbl">Fecha de Creación</div>
                        <div class="audit-val"><?= date('d/m/Y H:i', strtotime($p['fecha_registro'] ?? 'now')) ?></div>
                    </div>
                </div>
                <div class="audit-row">
                    <div class="audit-icon"><i class="fas fa-calendar-pen"></i></div>
                    <div>
                        <div class="audit-lbl">Última Actualización</div>
                        <div class="audit-val"><?= date('d/m/Y H:i', strtotime($p['fecha_actualizacion'] ?? 'now')) ?></div>
                    </div>
                </div>
                <?php if (!empty($p['lote'])): ?>
                <div class="audit-row">
                    <div class="audit-icon"><i class="fas fa-box"></i></div>
                    <div>
                        <div class="audit-lbl">Lote</div>
                        <div class="audit-val"><?= htmlspecialchars($p['lote']) ?></div>
                    </div>
                </div>
                <?php endif; ?>
                <?php if (!empty($p['fecha_vencimiento'])): ?>
                <div class="audit-row">
                    <div class="audit-icon" style="color:var(--warn);"><i class="fas fa-calendar-xmark"></i></div>
                    <div>
                        <div class="audit-lbl">Vencimiento</div>
                        <div class="audit-val"><?= date('d/m/Y', strtotime($p['fecha_vencimiento'])) ?></div>
                    </div>
                </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

</div>

<!-- Observaciones (si existen) -->
<?php if (!empty($p['observaciones'])): ?>
<div class="vp-grid full" style="margin-top:0;">
    <div class="sec-card">
        <div class="sec-head">
            <div class="sec-icon slate"><i class="fas fa-note-sticky"></i></div>
            <div class="sec-title">Observaciones</div>
        </div>
        <div class="sec-body">
            <p style="font-size:.82rem; color:var(--ink2); line-height:1.6; margin:0;"><?= nl2br(htmlspecialchars($p['observaciones'])) ?></p>
        </div>
    </div>
</div>
<?php endif; ?>

<!-- Action Footer -->
<div class="vp-footer">
    <div class="vp-footer-left">
        <i class="fas fa-hashtag" style="color:var(--accent); margin-right:.3rem;"></i>
        Producto <strong style="font-family:'IBM Plex Mono',monospace;"><?= htmlspecialchars($p['codigo']) ?></strong>
        &nbsp;·&nbsp; Almacén: <strong><?= htmlspecialchars($p['almacen_nombre'] ?? '—') ?></strong>
    </div>
    <div class="vp-footer-right">
        <a href="index.php?controller=movimiento&action=entrada&id_producto=<?= $idProd ?>" class="btn btn-ok">
            <i class="fas fa-arrow-down"></i> Registrar Entrada
        </a>
        <a href="index.php?controller=movimiento&action=salida&id_producto=<?= $idProd ?>" class="btn btn-warn">
            <i class="fas fa-arrow-up"></i> Registrar Salida
        </a>
        <button class="btn btn-danger" onclick="confirmarEliminar()">
            <i class="fas fa-trash"></i> Eliminar
        </button>
    </div>
</div>

</div><!-- /vp-root -->

<script>
function confirmarEliminar() {
    Swal.fire({
        title: '¿Eliminar producto?',
        html: '<p style="margin:0;">Esta acción no se puede deshacer.<br>El producto quedará marcado como eliminado.</p>',
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#ef4444',
        cancelButtonColor: '#94a3b8',
        confirmButtonText: '<i class="fas fa-trash"></i> Sí, eliminar',
        cancelButtonText: 'Cancelar'
    }).then(r => {
        if (r.isConfirmed)
            window.location.href = 'index.php?controller=producto&action=eliminar&id=<?= $idProd ?>';
    });
}

<?php if ($sweetAlert): ?>
document.addEventListener('DOMContentLoaded', function() {
    Swal.fire({
        title: <?= json_encode($sweetAlert['title'] ?? 'Aviso') ?>,
        text: <?= json_encode($sweetAlert['message'] ?? '') ?>,
        icon: <?= json_encode($sweetAlert['type'] ?? 'info') ?>,
        confirmButtonColor: '#3b82f6'
    });
});
<?php endif; ?>
</script>

<?php include __DIR__ . '/../../layouts/footer.php'; ?>