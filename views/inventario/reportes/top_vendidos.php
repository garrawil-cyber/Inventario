<?php 
$currentPage = 'reportes';
include __DIR__ . '/../../layouts/inventario_layout.php'; 
?>
<?php
/**
 * Vista: Top Productos Más Vendidos
 * Ruta:  views/inventario/reportes/top_vendidos.php
 * ─────────────────────────────────────────────────
 * Variables esperadas desde el controlador:
 *
 *  $topProductos  array  Lista de productos top. Cada elemento:
 *    [
 *      'nombre'      => string,
 *      'categoria'   => string,
 *      'imagen_url'  => string|null,
 *      'codigo'      => string,
 *      'unidades'    => int,
 *      'ingresos'    => float,
 *      'stock'       => int,
 *      'tendencia'   => 'up'|'down'|'stable',   // variación vs período anterior
 *      'variacion'   => float,                   // % de variación
 *    ]
 *
 *  $periodoActual  string   Ej: 'Abril 2026'
 *  $totalUnidades  int      Suma total de unidades vendidas en el período
 *  $totalIngresos  float    Suma total de ingresos en el período
 *  $categorias     array    Lista de categorías para el filtro
 *
 * Si las variables no vienen del controlador se usan datos de demo.
 */

// ── Datos de demo si el controlador no provee datos reales ───
if (!isset($topProductos)) {
    $periodoActual = 'Abril 2026';
    $totalUnidades = 1_247;
    $totalIngresos = 48_390.50;
    $categorias    = ['Todas', 'Camisetas', 'Pantalones', 'Calzado', 'Vestidos', 'Chaquetas', 'Accesorios'];

    $topProductos = [
        ['nombre'=>'Polo Classic Blanco','categoria'=>'Camisetas',  'codigo'=>'CAM-001','imagen_url'=>null,'unidades'=>312,'ingresos'=>9_048.00,'stock'=>45,'tendencia'=>'up',    'variacion'=>18.4],
        ['nombre'=>'Jean Slim Azul',     'categoria'=>'Pantalones', 'codigo'=>'PAN-003','imagen_url'=>null,'unidades'=>278,'ingresos'=>13_622.00,'stock'=>22,'tendencia'=>'up',    'variacion'=>9.1],
        ['nombre'=>'Zapatilla Urban Run','categoria'=>'Calzado',    'codigo'=>'CAL-007','imagen_url'=>null,'unidades'=>195,'ingresos'=>11_505.00,'stock'=>8, 'tendencia'=>'down',  'variacion'=>-4.2],
        ['nombre'=>'Vestido Floral','    categoria'=>'Vestidos',    'codigo'=>'VES-012','imagen_url'=>null,'unidades'=>167,'ingresos'=>7_515.00, 'stock'=>31,'tendencia'=>'up',    'variacion'=>31.7],
        ['nombre'=>'Chaqueta Bomber',    'categoria'=>'Chaquetas',  'codigo'=>'CHA-002','imagen_url'=>null,'unidades'=>134,'ingresos'=>9_112.00, 'stock'=>14,'tendencia'=>'stable','variacion'=>0.5],
        ['nombre'=>'Polo Rayas Navy',    'categoria'=>'Camisetas',  'codigo'=>'CAM-008','imagen_url'=>null,'unidades'=>98, 'ingresos'=>2_842.00, 'stock'=>57,'tendencia'=>'down',  'variacion'=>-11.3],
        ['nombre'=>'Short Deportivo',    'categoria'=>'Pantalones', 'codigo'=>'PAN-011','imagen_url'=>null,'unidades'=>63, 'ingresos'=>1_890.00, 'stock'=>89,'tendencia'=>'up',    'variacion'=>5.8],
    ];
}

// Máximo de unidades para las barras de progreso
$maxUnidades = !empty($topProductos) ? max(array_column($topProductos, 'unidades')) : 1;

// Moneda del sistema
$currency = defined('APP_CURRENCY') ? APP_CURRENCY : 'S/';
?>

<style>
/* ═══════════════════════════════════════════════════════════════
   TOP VENDIDOS — Design System
   Estética: Data-forward. Oscuro, sofisticado, con acentos ámbar.
   Tipografía: Syne (display) + DM Sans (body)
═══════════════════════════════════════════════════════════════ */
@import url('https://fonts.googleapis.com/css2?family=Syne:wght@600;700;800&family=DM+Sans:wght@300;400;500;600&display=swap');

:root {
    --tv-bg:          #0f1117;
    --tv-surface:     #181c27;
    --tv-surface2:    #1e2433;
    --tv-border:      rgba(255,255,255,.07);
    --tv-amber:       #f5a623;
    --tv-amber-dim:   rgba(245,166,35,.12);
    --tv-amber-glow:  rgba(245,166,35,.35);
    --tv-teal:        #2dd4bf;
    --tv-rose:        #fb7185;
    --tv-text:        #e8eaf0;
    --tv-muted:       #6b7280;
    --tv-rank-gold:   #fbbf24;
    --tv-rank-silver: #94a3b8;
    --tv-rank-bronze: #c2855a;
    --tv-radius:      14px;
    --tv-radius-sm:   8px;
}

/* ── Scope: todo dentro de .tv-page ── */
.tv-page {
    font-family: 'DM Sans', sans-serif;
    color: var(--tv-text);
    background: var(--tv-bg);
    min-height: 100vh;
    padding: 2rem 2.2rem 3rem;
}

/* ── Header ── */
.tv-header {
    display: flex;
    align-items: flex-start;
    justify-content: space-between;
    flex-wrap: wrap;
    gap: 1.2rem;
    margin-bottom: 2.4rem;
}
.tv-header-left {}
.tv-eyebrow {
    font-family: 'DM Sans', sans-serif;
    font-size: .72rem;
    font-weight: 600;
    letter-spacing: .18em;
    text-transform: uppercase;
    color: var(--tv-amber);
    display: flex;
    align-items: center;
    gap: .45rem;
    margin-bottom: .4rem;
}
.tv-eyebrow::before {
    content: '';
    width: 22px; height: 2px;
    background: var(--tv-amber);
    border-radius: 2px;
}
.tv-title {
    font-family: 'Syne', sans-serif;
    font-size: 2rem;
    font-weight: 800;
    line-height: 1.1;
    color: #fff;
    margin: 0 0 .3rem;
}
.tv-subtitle {
    font-size: .88rem;
    color: var(--tv-muted);
    margin: 0;
}

/* ── Acciones de cabecera ── */
.tv-header-actions {
    display: flex;
    align-items: center;
    gap: .75rem;
    flex-wrap: wrap;
}
.tv-period-badge {
    background: var(--tv-amber-dim);
    border: 1px solid rgba(245,166,35,.3);
    color: var(--tv-amber);
    font-size: .8rem;
    font-weight: 600;
    padding: .4rem .9rem;
    border-radius: 999px;
    display: flex;
    align-items: center;
    gap: .4rem;
}
.tv-btn {
    display: inline-flex;
    align-items: center;
    gap: .45rem;
    padding: .48rem 1.1rem;
    border-radius: var(--tv-radius-sm);
    font-family: 'DM Sans', sans-serif;
    font-size: .82rem;
    font-weight: 500;
    cursor: pointer;
    border: none;
    transition: all .18s ease;
    text-decoration: none;
}
.tv-btn-ghost {
    background: transparent;
    border: 1px solid var(--tv-border);
    color: var(--tv-muted);
}
.tv-btn-ghost:hover {
    border-color: rgba(255,255,255,.18);
    color: var(--tv-text);
    background: rgba(255,255,255,.04);
}
.tv-btn-amber {
    background: var(--tv-amber);
    color: #0f1117;
    font-weight: 600;
}
.tv-btn-amber:hover {
    background: #f0b84a;
    box-shadow: 0 0 18px var(--tv-amber-glow);
}

/* ── KPI Cards ── */
.tv-kpi-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
    gap: 1rem;
    margin-bottom: 2rem;
}
.tv-kpi {
    background: var(--tv-surface);
    border: 1px solid var(--tv-border);
    border-radius: var(--tv-radius);
    padding: 1.4rem 1.6rem;
    position: relative;
    overflow: hidden;
    transition: border-color .2s;
}
.tv-kpi:hover { border-color: rgba(245,166,35,.25); }
.tv-kpi::before {
    content: '';
    position: absolute;
    inset: 0;
    background: linear-gradient(135deg, rgba(245,166,35,.05) 0%, transparent 60%);
    pointer-events: none;
}
.tv-kpi-icon {
    width: 38px; height: 38px;
    border-radius: var(--tv-radius-sm);
    display: flex; align-items: center; justify-content: center;
    font-size: 1rem;
    margin-bottom: .9rem;
}
.tv-kpi-icon.amber  { background: var(--tv-amber-dim);       color: var(--tv-amber); }
.tv-kpi-icon.teal   { background: rgba(45,212,191,.1);        color: var(--tv-teal); }
.tv-kpi-icon.rose   { background: rgba(251,113,133,.1);       color: var(--tv-rose); }
.tv-kpi-icon.slate  { background: rgba(148,163,184,.1);       color: #94a3b8; }
.tv-kpi-label {
    font-size: .72rem;
    font-weight: 500;
    letter-spacing: .08em;
    text-transform: uppercase;
    color: var(--tv-muted);
    margin-bottom: .25rem;
}
.tv-kpi-value {
    font-family: 'Syne', sans-serif;
    font-size: 1.7rem;
    font-weight: 700;
    color: #fff;
    line-height: 1;
}
.tv-kpi-sub {
    font-size: .76rem;
    color: var(--tv-muted);
    margin-top: .35rem;
}

/* ── Filtros ── */
.tv-filters {
    background: var(--tv-surface);
    border: 1px solid var(--tv-border);
    border-radius: var(--tv-radius);
    padding: 1rem 1.4rem;
    display: flex;
    align-items: center;
    gap: 1rem;
    flex-wrap: wrap;
    margin-bottom: 1.6rem;
}
.tv-filters-label {
    font-size: .75rem;
    font-weight: 600;
    letter-spacing: .1em;
    text-transform: uppercase;
    color: var(--tv-muted);
    white-space: nowrap;
}
.tv-filter-pills {
    display: flex;
    gap: .5rem;
    flex-wrap: wrap;
}
.tv-pill {
    padding: .32rem .85rem;
    border-radius: 999px;
    font-size: .78rem;
    font-weight: 500;
    cursor: pointer;
    border: 1px solid var(--tv-border);
    background: transparent;
    color: var(--tv-muted);
    transition: all .15s ease;
    font-family: 'DM Sans', sans-serif;
}
.tv-pill:hover    { border-color: rgba(245,166,35,.4); color: var(--tv-amber); }
.tv-pill.active   { background: var(--tv-amber-dim); border-color: rgba(245,166,35,.5); color: var(--tv-amber); }

.tv-filter-sep { width: 1px; height: 24px; background: var(--tv-border); }

.tv-select {
    background: var(--tv-surface2);
    border: 1px solid var(--tv-border);
    color: var(--tv-text);
    border-radius: var(--tv-radius-sm);
    padding: .35rem .8rem;
    font-size: .8rem;
    font-family: 'DM Sans', sans-serif;
    cursor: pointer;
    outline: none;
}
.tv-select:focus { border-color: var(--tv-amber); }
.tv-select option { background: #1e2433; }

/* ── Tabla principal ── */
.tv-card {
    background: var(--tv-surface);
    border: 1px solid var(--tv-border);
    border-radius: var(--tv-radius);
    overflow: hidden;
}
.tv-table-header {
    padding: 1.2rem 1.6rem;
    display: flex;
    align-items: center;
    justify-content: space-between;
    border-bottom: 1px solid var(--tv-border);
    gap: 1rem;
    flex-wrap: wrap;
}
.tv-table-title {
    font-family: 'Syne', sans-serif;
    font-size: 1rem;
    font-weight: 700;
    color: #fff;
}
.tv-table-meta {
    font-size: .78rem;
    color: var(--tv-muted);
}

.tv-search-wrap {
    position: relative;
}
.tv-search-wrap i {
    position: absolute;
    left: .75rem;
    top: 50%;
    transform: translateY(-50%);
    color: var(--tv-muted);
    font-size: .8rem;
    pointer-events: none;
}
.tv-search {
    background: var(--tv-surface2);
    border: 1px solid var(--tv-border);
    color: var(--tv-text);
    border-radius: var(--tv-radius-sm);
    padding: .38rem .8rem .38rem 2rem;
    font-size: .8rem;
    font-family: 'DM Sans', sans-serif;
    width: 200px;
    outline: none;
    transition: border-color .15s;
}
.tv-search::placeholder { color: var(--tv-muted); }
.tv-search:focus { border-color: var(--tv-amber); }

/* Tabla */
.tv-table-wrap { overflow-x: auto; }
table.tv-table {
    width: 100%;
    border-collapse: collapse;
}
.tv-table thead th {
    padding: .75rem 1.2rem;
    font-size: .68rem;
    font-weight: 600;
    letter-spacing: .1em;
    text-transform: uppercase;
    color: var(--tv-muted);
    background: var(--tv-surface2);
    border-bottom: 1px solid var(--tv-border);
    white-space: nowrap;
    cursor: pointer;
    user-select: none;
    transition: color .15s;
}
.tv-table thead th:hover { color: var(--tv-amber); }
.tv-table thead th .sort-icon { margin-left: .3rem; opacity: .4; font-size: .6rem; }
.tv-table thead th.sorted .sort-icon { opacity: 1; color: var(--tv-amber); }

.tv-table tbody tr {
    border-bottom: 1px solid var(--tv-border);
    transition: background .15s;
    animation: tvRowIn .35s ease both;
}
.tv-table tbody tr:last-child { border-bottom: none; }
.tv-table tbody tr:hover { background: rgba(255,255,255,.027); }

@keyframes tvRowIn {
    from { opacity: 0; transform: translateX(-8px); }
    to   { opacity: 1; transform: translateX(0); }
}

.tv-table td {
    padding: .95rem 1.2rem;
    font-size: .85rem;
    vertical-align: middle;
}

/* Rank badge */
.tv-rank {
    font-family: 'Syne', sans-serif;
    font-size: .85rem;
    font-weight: 800;
    width: 32px; height: 32px;
    border-radius: 50%;
    display: flex; align-items: center; justify-content: center;
}
.tv-rank.gold   { background: rgba(251,191,36,.15); color: var(--tv-rank-gold); }
.tv-rank.silver { background: rgba(148,163,184,.12); color: var(--tv-rank-silver); }
.tv-rank.bronze { background: rgba(194,133,90,.12);  color: var(--tv-rank-bronze); }
.tv-rank.other  { background: rgba(255,255,255,.05); color: var(--tv-muted); }

/* Producto celda */
.tv-prod-cell {
    display: flex;
    align-items: center;
    gap: .9rem;
}
.tv-prod-avatar {
    width: 42px; height: 42px;
    border-radius: var(--tv-radius-sm);
    background: var(--tv-surface2);
    border: 1px solid var(--tv-border);
    display: flex; align-items: center; justify-content: center;
    font-size: 1.1rem;
    flex-shrink: 0;
    overflow: hidden;
}
.tv-prod-avatar img {
    width: 100%; height: 100%;
    object-fit: cover;
}
.tv-prod-name {
    font-weight: 600;
    color: var(--tv-text);
    font-size: .87rem;
    white-space: nowrap;
}
.tv-prod-code {
    font-size: .72rem;
    color: var(--tv-muted);
    margin-top: .1rem;
}

/* Categoría badge */
.tv-cat-badge {
    display: inline-block;
    padding: .22rem .7rem;
    border-radius: 999px;
    font-size: .72rem;
    font-weight: 500;
    background: rgba(255,255,255,.05);
    border: 1px solid var(--tv-border);
    color: var(--tv-muted);
    white-space: nowrap;
}

/* Barra de progreso */
.tv-bar-wrap {
    display: flex;
    align-items: center;
    gap: .75rem;
}
.tv-bar-track {
    flex: 1;
    height: 6px;
    background: rgba(255,255,255,.06);
    border-radius: 999px;
    min-width: 80px;
    overflow: hidden;
}
.tv-bar-fill {
    height: 100%;
    border-radius: 999px;
    background: linear-gradient(90deg, var(--tv-amber), #e8962a);
    transition: width .6s cubic-bezier(.22,1,.36,1);
    transform-origin: left;
}
.tv-bar-val {
    font-family: 'Syne', sans-serif;
    font-weight: 700;
    font-size: .9rem;
    color: #fff;
    min-width: 36px;
    text-align: right;
}

/* Ingresos */
.tv-ingreso {
    font-family: 'Syne', sans-serif;
    font-weight: 700;
    font-size: .92rem;
    color: var(--tv-teal);
}

/* Stock */
.tv-stock {
    font-weight: 600;
    font-size: .85rem;
}
.tv-stock.low  { color: var(--tv-rose); }
.tv-stock.mid  { color: var(--tv-amber); }
.tv-stock.high { color: var(--tv-teal); }

/* Tendencia */
.tv-trend {
    display: inline-flex;
    align-items: center;
    gap: .3rem;
    font-size: .78rem;
    font-weight: 600;
    padding: .25rem .65rem;
    border-radius: 999px;
}
.tv-trend.up     { background: rgba(45,212,191,.1);  color: var(--tv-teal); }
.tv-trend.down   { background: rgba(251,113,133,.1); color: var(--tv-rose); }
.tv-trend.stable { background: rgba(255,255,255,.05); color: var(--tv-muted); }

/* ── Empty state ── */
.tv-empty {
    padding: 4rem 2rem;
    text-align: center;
    color: var(--tv-muted);
}
.tv-empty i { font-size: 2.5rem; margin-bottom: .8rem; display: block; opacity: .4; }
.tv-empty p { font-size: .88rem; }

/* ── Pagination ── */
.tv-pagination {
    display: flex;
    align-items: center;
    justify-content: space-between;
    padding: 1rem 1.6rem;
    border-top: 1px solid var(--tv-border);
    flex-wrap: wrap;
    gap: .75rem;
}
.tv-pag-info { font-size: .78rem; color: var(--tv-muted); }
.tv-pag-btns { display: flex; gap: .4rem; }
.tv-pag-btn {
    width: 30px; height: 30px;
    border-radius: var(--tv-radius-sm);
    border: 1px solid var(--tv-border);
    background: transparent;
    color: var(--tv-muted);
    font-size: .78rem;
    font-family: 'DM Sans', sans-serif;
    cursor: pointer;
    display: flex; align-items: center; justify-content: center;
    transition: all .15s;
}
.tv-pag-btn:hover  { border-color: var(--tv-amber); color: var(--tv-amber); }
.tv-pag-btn.active { background: var(--tv-amber); border-color: var(--tv-amber); color: #0f1117; font-weight: 700; }
.tv-pag-btn:disabled { opacity: .3; cursor: not-allowed; }

/* ── Chart panel ── */
.tv-chart-panel {
    background: var(--tv-surface);
    border: 1px solid var(--tv-border);
    border-radius: var(--tv-radius);
    padding: 1.4rem 1.6rem;
    margin-top: 1.5rem;
}
.tv-chart-title {
    font-family: 'Syne', sans-serif;
    font-size: .95rem;
    font-weight: 700;
    color: #fff;
    margin-bottom: 1.2rem;
}
.tv-chart-bars {
    display: flex;
    align-items: flex-end;
    gap: .6rem;
    height: 140px;
    padding-bottom: .4rem;
}
.tv-chart-col {
    flex: 1;
    display: flex;
    flex-direction: column;
    align-items: center;
    gap: .4rem;
    height: 100%;
    justify-content: flex-end;
}
.tv-chart-bar {
    width: 100%;
    background: linear-gradient(180deg, var(--tv-amber) 0%, rgba(245,166,35,.4) 100%);
    border-radius: 5px 5px 0 0;
    min-height: 4px;
    transition: height .7s cubic-bezier(.22,1,.36,1);
    position: relative;
    cursor: pointer;
}
.tv-chart-bar:hover { filter: brightness(1.15); }
.tv-chart-bar-label {
    font-size: .64rem;
    color: var(--tv-muted);
    text-align: center;
    max-width: 100%;
    overflow: hidden;
    text-overflow: ellipsis;
    white-space: nowrap;
    width: 100%;
}

/* ── Animaciones stagger para KPIs ── */
.tv-kpi:nth-child(1) { animation: tvFadeUp .4s .05s both; }
.tv-kpi:nth-child(2) { animation: tvFadeUp .4s .12s both; }
.tv-kpi:nth-child(3) { animation: tvFadeUp .4s .19s both; }
.tv-kpi:nth-child(4) { animation: tvFadeUp .4s .26s both; }
@keyframes tvFadeUp {
    from { opacity: 0; transform: translateY(14px); }
    to   { opacity: 1; transform: translateY(0); }
}

/* ── Row delay stagger ── */
.tv-table tbody tr:nth-child(1)  { animation-delay: .04s; }
.tv-table tbody tr:nth-child(2)  { animation-delay: .09s; }
.tv-table tbody tr:nth-child(3)  { animation-delay: .14s; }
.tv-table tbody tr:nth-child(4)  { animation-delay: .19s; }
.tv-table tbody tr:nth-child(5)  { animation-delay: .24s; }
.tv-table tbody tr:nth-child(6)  { animation-delay: .29s; }
.tv-table tbody tr:nth-child(7)  { animation-delay: .34s; }
.tv-table tbody tr:nth-child(8)  { animation-delay: .39s; }
.tv-table tbody tr:nth-child(9)  { animation-delay: .44s; }
.tv-table tbody tr:nth-child(10) { animation-delay: .49s; }

/* ── Responsive ── */
@media (max-width: 768px) {
    .tv-page { padding: 1.2rem 1rem 2rem; }
    .tv-title { font-size: 1.5rem; }
    .tv-search { width: 150px; }
    .tv-table td, .tv-table th { padding: .7rem .8rem; }
}
</style>

<div class="tv-page">

    <!-- ── Header ──────────────────────────── -->
    <div class="tv-header">
        <div class="tv-header-left">
            <div class="tv-eyebrow">
                <i class="fas fa-chart-line"></i> Reportes
            </div>
            <h1 class="tv-title">Top Productos Vendidos</h1>
            <p class="tv-subtitle">Ranking de productos por volumen de ventas en el período seleccionado</p>
        </div>
        <div class="tv-header-actions">
            <div class="tv-period-badge">
                <i class="fas fa-calendar-alt"></i>
                <?= htmlspecialchars($periodoActual ?? 'Período actual') ?>
            </div>
            <button class="tv-btn tv-btn-ghost" onclick="window.print()">
                <i class="fas fa-print"></i> Imprimir
            </button>
            <a href="<?= BASE_URL ?>/index.php?controller=reporte&action=exportTopVendidos&format=excel"
               class="tv-btn tv-btn-ghost">
                <i class="fas fa-file-excel"></i> Excel
            </a>
            <a href="<?= BASE_URL ?>/index.php?controller=reporte&action=exportTopVendidos&format=pdf"
               class="tv-btn tv-btn-amber">
                <i class="fas fa-file-pdf"></i> PDF
            </a>
        </div>
    </div>

    <!-- ── KPI Cards ────────────────────────── -->
    <div class="tv-kpi-grid">
        <div class="tv-kpi">
            <div class="tv-kpi-icon amber"><i class="fas fa-trophy"></i></div>
            <div class="tv-kpi-label">Productos rankeados</div>
            <div class="tv-kpi-value"><?= count($topProductos) ?></div>
            <div class="tv-kpi-sub">en este período</div>
        </div>
        <div class="tv-kpi">
            <div class="tv-kpi-icon teal"><i class="fas fa-boxes"></i></div>
            <div class="tv-kpi-label">Total unidades</div>
            <div class="tv-kpi-value"><?= number_format($totalUnidades) ?></div>
            <div class="tv-kpi-sub">unidades vendidas</div>
        </div>
        <div class="tv-kpi">
            <div class="tv-kpi-icon amber"><i class="fas fa-coins"></i></div>
            <div class="tv-kpi-label">Ingresos totales</div>
            <div class="tv-kpi-value"><?= $currency . ' ' . number_format($totalIngresos, 0) ?></div>
            <div class="tv-kpi-sub">en el período</div>
        </div>
        <div class="tv-kpi">
            <div class="tv-kpi-icon rose"><i class="fas fa-fire-alt"></i></div>
            <div class="tv-kpi-label">Producto estrella</div>
            <div class="tv-kpi-value" style="font-size:1.05rem; padding-top:.1rem">
                <?= htmlspecialchars($topProductos[0]['nombre'] ?? '—') ?>
            </div>
            <div class="tv-kpi-sub"><?= number_format($topProductos[0]['unidades'] ?? 0) ?> uds. vendidas</div>
        </div>
    </div>

    <!-- ── Filtros ──────────────────────────── -->
    <div class="tv-filters">
        <span class="tv-filters-label"><i class="fas fa-sliders-h me-1"></i> Filtrar</span>

        <div class="tv-filter-pills" id="catPills">
            <?php foreach (($categorias ?? ['Todas']) as $i => $cat): ?>
                <button class="tv-pill <?= $i === 0 ? 'active' : '' ?>"
                        onclick="filterByCategory(this, '<?= htmlspecialchars($cat) ?>')">
                    <?= htmlspecialchars($cat) ?>
                </button>
            <?php endforeach; ?>
        </div>

        <div class="tv-filter-sep"></div>

        <select class="tv-select" id="limitSelect" onchange="applyLimit(this.value)">
            <option value="5">Top 5</option>
            <option value="10" selected>Top 10</option>
            <option value="20">Top 20</option>
            <option value="50">Top 50</option>
        </select>

        <select class="tv-select" id="orderSelect" onchange="applyOrder(this.value)">
            <option value="unidades">Por unidades</option>
            <option value="ingresos">Por ingresos</option>
            <option value="nombre">Por nombre</option>
        </select>
    </div>

    <!-- ── Tabla ─────────────────────────────── -->
    <div class="tv-card">
        <div class="tv-table-header">
            <div>
                <div class="tv-table-title">Ranking de ventas</div>
                <div class="tv-table-meta" id="tableCount">
                    Mostrando <?= count($topProductos) ?> productos
                </div>
            </div>
            <div class="tv-search-wrap">
                <i class="fas fa-search"></i>
                <input type="text"
                       class="tv-search"
                       id="tvSearch"
                       placeholder="Buscar producto…"
                       oninput="searchTable(this.value)">
            </div>
        </div>

        <div class="tv-table-wrap">
            <table class="tv-table" id="tvTable">
                <thead>
                    <tr>
                        <th onclick="sortTable('rank')"    data-col="rank">
                            # <i class="fas fa-sort sort-icon"></i>
                        </th>
                        <th>Producto</th>
                        <th onclick="sortTable('categoria')" data-col="categoria">
                            Categoría <i class="fas fa-sort sort-icon"></i>
                        </th>
                        <th onclick="sortTable('unidades')" data-col="unidades" class="sorted">
                            Unidades <i class="fas fa-sort-down sort-icon"></i>
                        </th>
                        <th onclick="sortTable('ingresos')" data-col="ingresos">
                            Ingresos <i class="fas fa-sort sort-icon"></i>
                        </th>
                        <th onclick="sortTable('stock')" data-col="stock">
                            Stock <i class="fas fa-sort sort-icon"></i>
                        </th>
                        <th>Tendencia</th>
                    </tr>
                </thead>
                <tbody id="tvBody">
                    <?php foreach ($topProductos as $i => $prod):
                        $rank       = $i + 1;
                        $rankClass  = $rank === 1 ? 'gold' : ($rank === 2 ? 'silver' : ($rank === 3 ? 'bronze' : 'other'));
                        $rankIcon   = $rank === 1 ? '🥇' : ($rank === 2 ? '🥈' : ($rank === 3 ? '🥉' : $rank));
                        $barPct     = round(($prod['unidades'] / $maxUnidades) * 100);
                        $stockClass = $prod['stock'] < 10 ? 'low' : ($prod['stock'] < 30 ? 'mid' : 'high');
                        $tendIcon   = $prod['tendencia'] === 'up' ? 'fa-arrow-trend-up' :
                                     ($prod['tendencia'] === 'down' ? 'fa-arrow-trend-down' : 'fa-minus');
                        $varSign    = $prod['variacion'] > 0 ? '+' : '';
                        // Icono por categoría
                        $catIcons   = ['Camisetas'=>'👕','Pantalones'=>'👖','Calzado'=>'👟',
                                       'Vestidos'=>'👗','Chaquetas'=>'🧥','Accesorios'=>'👜'];
                        $catIcon    = $catIcons[trim($prod['categoria'])] ?? '📦';
                    ?>
                    <tr data-nombre="<?= htmlspecialchars(strtolower($prod['nombre'])) ?>"
                        data-categoria="<?= htmlspecialchars(trim($prod['categoria'])) ?>"
                        data-unidades="<?= $prod['unidades'] ?>"
                        data-ingresos="<?= $prod['ingresos'] ?>"
                        data-stock="<?= $prod['stock'] ?>"
                        data-rank="<?= $rank ?>">
                        <td>
                            <div class="tv-rank <?= $rankClass ?>">
                                <?= $rankIcon ?>
                            </div>
                        </td>
                        <td>
                            <div class="tv-prod-cell">
                                <div class="tv-prod-avatar">
                                    <?php if (!empty($prod['imagen_url'])): ?>
                                        <img src="<?= htmlspecialchars($prod['imagen_url']) ?>"
                                             alt="<?= htmlspecialchars($prod['nombre']) ?>">
                                    <?php else: ?>
                                        <?= $catIcon ?>
                                    <?php endif; ?>
                                </div>
                                <div>
                                    <div class="tv-prod-name"><?= htmlspecialchars($prod['nombre']) ?></div>
                                    <div class="tv-prod-code"><?= htmlspecialchars($prod['codigo']) ?></div>
                                </div>
                            </div>
                        </td>
                        <td>
                            <span class="tv-cat-badge"><?= htmlspecialchars(trim($prod['categoria'])) ?></span>
                        </td>
                        <td>
                            <div class="tv-bar-wrap">
                                <div class="tv-bar-track">
                                    <div class="tv-bar-fill" style="width:<?= $barPct ?>%"></div>
                                </div>
                                <span class="tv-bar-val"><?= number_format($prod['unidades']) ?></span>
                            </div>
                        </td>
                        <td>
                            <span class="tv-ingreso">
                                <?= $currency ?> <?= number_format($prod['ingresos'], 2) ?>
                            </span>
                        </td>
                        <td>
                            <span class="tv-stock <?= $stockClass ?>">
                                <?= $prod['stock'] ?> uds
                            </span>
                        </td>
                        <td>
                            <span class="tv-trend <?= $prod['tendencia'] ?>">
                                <i class="fas <?= $tendIcon ?>"></i>
                                <?= $varSign . number_format(abs($prod['variacion']), 1) ?>%
                            </span>
                        </td>
                    </tr>
                    <?php endforeach; ?>

                    <?php if (empty($topProductos)): ?>
                    <tr>
                        <td colspan="7">
                            <div class="tv-empty">
                                <i class="fas fa-chart-bar"></i>
                                <p>No hay datos de ventas para el período seleccionado.</p>
                            </div>
                        </td>
                    </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>

        <div class="tv-pagination">
            <div class="tv-pag-info" id="pagInfo">
                Mostrando 1–<?= count($topProductos) ?> de <?= count($topProductos) ?> productos
            </div>
            <div class="tv-pag-btns">
                <button class="tv-pag-btn" disabled><i class="fas fa-chevron-left"></i></button>
                <button class="tv-pag-btn active">1</button>
                <button class="tv-pag-btn" disabled><i class="fas fa-chevron-right"></i></button>
            </div>
        </div>
    </div>

    <!-- ── Mini chart ─────────────────────────── -->
    <div class="tv-chart-panel">
        <div class="tv-chart-title">
            <i class="fas fa-chart-column me-2" style="color:var(--tv-amber)"></i>
            Comparativa visual — Unidades vendidas
        </div>
        <div class="tv-chart-bars" id="chartBars">
            <?php
            $maxBar = $maxUnidades ?: 1;
            foreach ($topProductos as $prod):
                $h = round(($prod['unidades'] / $maxBar) * 120);
                $nombre = htmlspecialchars(explode(' ', trim($prod['nombre']))[0]);
            ?>
            <div class="tv-chart-col">
                <div class="tv-chart-bar" style="height:<?= $h ?>px"
                     title="<?= htmlspecialchars($prod['nombre']) ?>: <?= number_format($prod['unidades']) ?> uds.">
                </div>
                <div class="tv-chart-bar-label"><?= $nombre ?></div>
            </div>
            <?php endforeach; ?>
        </div>
    </div>

</div><!-- /tv-page -->

<script>
/* ═══════════════════════════════════════════════
   Top Vendidos — JS interactivo
═══════════════════════════════════════════════ */

// ── Filtro por categoría ──
function filterByCategory(btn, cat) {
    document.querySelectorAll('#catPills .tv-pill').forEach(p => p.classList.remove('active'));
    btn.classList.add('active');

    const rows = document.querySelectorAll('#tvBody tr[data-categoria]');
    let visible = 0;
    rows.forEach(row => {
        const show = cat === 'Todas' || row.dataset.categoria === cat;
        row.style.display = show ? '' : 'none';
        if (show) visible++;
    });
    updateCount(visible);
}

// ── Búsqueda en tiempo real ──
function searchTable(q) {
    const term = q.toLowerCase().trim();
    const rows = document.querySelectorAll('#tvBody tr[data-nombre]');
    let visible = 0;
    rows.forEach(row => {
        const match = row.dataset.nombre.includes(term);
        row.style.display = match ? '' : 'none';
        if (match) visible++;
    });
    updateCount(visible);
}

// ── Ordenamiento ──
let sortDir = { col: 'rank', asc: false };
function sortTable(col) {
    const tbody  = document.getElementById('tvBody');
    const rows   = Array.from(tbody.querySelectorAll('tr[data-rank]'));
    const asc    = sortDir.col === col ? !sortDir.asc : true;
    sortDir      = { col, asc };

    rows.sort((a, b) => {
        let va = a.dataset[col] ?? '';
        let vb = b.dataset[col] ?? '';
        const numCols = ['rank','unidades','ingresos','stock'];
        if (numCols.includes(col)) { va = parseFloat(va); vb = parseFloat(vb); }
        if (va < vb) return asc ? -1 : 1;
        if (va > vb) return asc ? 1 : -1;
        return 0;
    });

    rows.forEach(r => tbody.appendChild(r));

    // Actualizar íconos de sort
    document.querySelectorAll('.tv-table thead th').forEach(th => {
        th.classList.remove('sorted');
        const ico = th.querySelector('.sort-icon');
        if (ico) { ico.className = 'fas fa-sort sort-icon'; }
    });
    const th = document.querySelector(`[data-col="${col}"]`);
    if (th) {
        th.classList.add('sorted');
        const ico = th.querySelector('.sort-icon');
        if (ico) ico.className = `fas ${asc ? 'fa-sort-up' : 'fa-sort-down'} sort-icon`;
    }
}

// ── Límite ──
function applyLimit(n) {
    const rows = document.querySelectorAll('#tvBody tr[data-rank]');
    let c = 0;
    rows.forEach(r => {
        c++;
        r.style.display = c <= parseInt(n) ? '' : 'none';
    });
    updateCount(Math.min(n, rows.length));
}

// ── Helper ──
function updateCount(n) {
    const el = document.getElementById('tableCount');
    if (el) el.textContent = `Mostrando ${n} producto${n !== 1 ? 's' : ''}`;
}
</script>