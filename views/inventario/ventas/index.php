<?php 
$currentPage = 'ventas';
include __DIR__ . '/../../layouts/inventario_layout.php'; 

// Estadísticas
$totalVentas = $estadisticas['total_ventas'] ?? 0;
$ingresosTotales = $estadisticas['ingresos_totales'] ?? 0;
$ingresosHoy = $estadisticas['ingresos_hoy'] ?? 0;
$ingresosMes = $estadisticas['ingresos_mes'] ?? 0;
$ventasHoy = $estadisticas['ventas_hoy'] ?? 0;
$ticketPromedio = $estadisticas['ticket_promedio'] ?? 0;
$ventasEfectivo = $estadisticas['ventas_efectivo'] ?? 0;
$ventasTarjeta = $estadisticas['ventas_tarjeta'] ?? 0;
$topProductos = $estadisticas['top_productos'] ?? [];

// Contar filtros activos
$filtrosActivos = 0;
foreach (['estado', 'tipo_pago', 'fecha_desde', 'fecha_hasta', 'busqueda'] as $k) {
    if (!empty($_GET[$k])) $filtrosActivos++;
}
?>

<style>
:root {
    --vt-bg:      #f8fafc;
    --vt-card:    #ffffff;
    --vt-bd:      #e2e8f0;
    --vt-tx:      #0f172a;
    --vt-mu:      #64748b;
    --vt-primary: #4f46e5;
    --vt-p-lt:    #eef2ff;
    --vt-success: #059669;
    --vt-s-lt:    #d1fae5;
    --vt-warning: #d97706;
    --vt-w-lt:    #fef3c7;
    --vt-danger:  #dc2626;
    --vt-d-lt:    #fee2e2;
    --vt-info:    #0891b2;
    --vt-i-lt:    #cffafe;
    --vt-r:       10px;
    --vt-sh:      0 1px 2px rgba(0,0,0,.04);
    --f-body:     'DM Sans', system-ui, sans-serif;
    --f-mono:     'DM Mono', monospace;
}

/* KPI Grid */
.kpi-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(180px, 1fr));
    gap: 0.75rem;
    margin-bottom: 1.25rem;
}
.kpi-card {
    background: var(--vt-card);
    border-radius: var(--vt-r);
    border: 1px solid var(--vt-bd);
    padding: 0.75rem 1rem;
    transition: all 0.15s;
}
.kpi-card:hover { box-shadow: var(--vt-sh); border-color: #cbd5e1; }
.kpi-label {
    font-size: 0.65rem;
    font-weight: 600;
    text-transform: uppercase;
    letter-spacing: 0.05em;
    color: var(--vt-mu);
    margin-bottom: 0.25rem;
}
.kpi-value {
    font-size: 1.35rem;
    font-weight: 700;
    color: var(--vt-tx);
    line-height: 1.2;
}
.kpi-sub {
    font-size: 0.65rem;
    color: var(--vt-mu);
    margin-top: 0.2rem;
}
.kpi-badge {
    font-size: 0.6rem;
    padding: 0.1rem 0.45rem;
    border-radius: 4px;
    font-weight: 600;
}

/* Análisis cards */
.an-card {
    background: var(--vt-card);
    border-radius: var(--vt-r);
    border: 1px solid var(--vt-bd);
    margin-bottom: 1.25rem;
}
.an-header {
    padding: 0.6rem 1rem;
    border-bottom: 1px solid var(--vt-bd);
    font-size: 0.7rem;
    font-weight: 700;
    text-transform: uppercase;
    letter-spacing: 0.05em;
    color: var(--vt-mu);
    display: flex;
    align-items: center;
    gap: 0.5rem;
}
.an-body { padding: 0.75rem 1rem; }

/* Filtros */
.fc {
    background: var(--vt-card);
    border-radius: var(--vt-r);
    border: 1px solid var(--vt-bd);
    margin-bottom: 1.25rem;
}
.ft {
    cursor: pointer;
    padding: 0.6rem 1rem;
    display: flex;
    align-items: center;
    justify-content: space-between;
    font-size: 0.75rem;
    font-weight: 600;
    width: 100%;
    background: none;
    border: none;
}
.ft .bc {
    background: var(--vt-primary);
    color: #fff;
    border-radius: 99px;
    padding: 0.05rem 0.4rem;
    font-size: 0.6rem;
}
.fb { padding: 0 1rem 1rem; }

/* Tabla */
.tw {
    background: var(--vt-card);
    border-radius: var(--vt-r);
    border: 1px solid var(--vt-bd);
    overflow: hidden;
}
.tw-header {
    padding: 0.6rem 1rem;
    display: flex;
    align-items: center;
    justify-content: space-between;
    border-bottom: 1px solid var(--vt-bd);
    background: #fafcff;
}
.tw-title {
    font-size: 0.75rem;
    font-weight: 700;
    text-transform: uppercase;
    letter-spacing: 0.05em;
    color: var(--vt-mu);
}
#tblVentas th {
    font-size: 0.6rem;
    font-weight: 700;
    text-transform: uppercase;
    letter-spacing: 0.05em;
    color: var(--vt-mu);
    background: #fafcff;
    padding: 0.6rem 0.75rem;
    border-bottom: 1px solid var(--vt-bd);
}
#tblVentas td {
    font-size: 0.75rem;
    padding: 0.55rem 0.75rem;
    vertical-align: middle;
    border-bottom: 1px solid var(--vt-bd);
}
#tblVentas tbody tr:hover { background: #f8fafc; }

/* Badges */
.status-badge {
    display: inline-block;
    padding: 0.2rem 0.55rem;
    border-radius: 20px;
    font-size: 0.65rem;
    font-weight: 600;
}
.status-completada { background: #d1fae5; color: #065f46; }
.status-pendiente { background: #fef3c7; color: #92400e; }
.status-cancelada { background: #fee2e2; color: #991b1b; }
.status-anulada { background: #f1f5f9; color: #475569; }

.payment-badge {
    display: inline-block;
    padding: 0.2rem 0.55rem;
    border-radius: 20px;
    font-size: 0.65rem;
    font-weight: 600;
}
.payment-efectivo { background: #e0f2fe; color: #0369a1; }
.payment-tarjeta { background: #fae8ff; color: #86198f; }
.payment-transferencia { background: #d1fae5; color: #065f46; }
.payment-credito { background: #fef3c7; color: #92400e; }

.act-btn-sm {
    background: transparent;
    border: 1px solid var(--vt-bd);
    border-radius: 6px;
    color: var(--vt-mu);
    padding: 0.2rem 0.45rem;
    font-size: 0.7rem;
    transition: all 0.15s;
}
.act-btn-sm:hover { background: #f1f5f9; border-color: #cbd5e1; }

/* Top productos */
.top-item {
    display: flex;
    align-items: center;
    justify-content: space-between;
    padding: 0.5rem 0;
    border-bottom: 1px solid var(--vt-bd);
}
.top-item:last-child { border-bottom: none; }
.top-rank {
    width: 22px;
    height: 22px;
    border-radius: 6px;
    background: var(--vt-p-lt);
    color: var(--vt-primary);
    font-size: 0.65rem;
    font-weight: 700;
    display: flex;
    align-items: center;
    justify-content: center;
}
</style>
<br>
<div class="container-fluid px-3 py-2">

    <!-- Encabezado -->
    <div class="d-flex justify-content-between align-items-center mb-3">
        <div>
            <h1 class="h5 fw-bold mb-0" style="color:var(--vt-tx);">
                <i class="fas fa-chart-line me-2" style="color:var(--vt-primary);"></i>Gestión de Ventas
            </h1>
            <ol class="breadcrumb mb-0 mt-1">
                <li class="breadcrumb-item small"><a href="index.php?controller=dashboard&action=index">Dashboard</a></li>
                <li class="breadcrumb-item small active">Ventas</li>
            </ol>
        </div>
        <div class="d-flex gap-2">
            <a href="index.php?controller=ventas&action=exportar<?= !empty($_GET) ? '&' . http_build_query($_GET) : '' ?>" 
               class="btn btn-sm btn-outline-secondary">
                <i class="fas fa-file-excel me-1 text-success"></i>Exportar
            </a>
            <a href="index.php?controller=ventas&action=crear" class="btn btn-sm fw-semibold px-3"
               style="background:var(--vt-primary);color:#fff;border:none;border-radius:8px;">
                <i class="fas fa-plus me-1"></i>Nueva Venta
            </a>
        </div>
    </div>

    <!-- KPI Cards -->
    <div class="kpi-grid">
        <div class="kpi-card">
            <div class="kpi-label">Total Ventas</div>
            <div class="kpi-value"><?= number_format($totalVentas) ?></div>
            <div class="kpi-sub">
                <span class="kpi-badge" style="background:var(--vt-s-lt);color:var(--vt-success);"><?= $ventasHoy ?> hoy</span>
            </div>
        </div>
        <div class="kpi-card">
            <div class="kpi-label">Ingresos Totales</div>
            <div class="kpi-value" style="font-size:1.1rem;">S/ <?= number_format($ingresosTotales, 2) ?></div>
            <div class="kpi-sub">S/ <?= number_format($ingresosMes, 2) ?> este mes</div>
        </div>
        <div class="kpi-card">
            <div class="kpi-label">Ingresos Hoy</div>
            <div class="kpi-value" style="font-size:1rem;">S/ <?= number_format($ingresosHoy, 2) ?></div>
            <div class="kpi-sub">Ticket promedio: S/ <?= number_format($ticketPromedio, 2) ?></div>
        </div>
        <div class="kpi-card">
            <div class="kpi-label">Métodos de Pago</div>
            <div class="kpi-value" style="font-size:0.9rem;">
                <span class="kpi-badge" style="background:#e0f2fe;">E: S/ <?= number_format($ventasEfectivo, 0) ?></span>
                <span class="kpi-badge" style="background:#fae8ff;">T: S/ <?= number_format($ventasTarjeta, 0) ?></span>
            </div>
            <div class="kpi-sub">Efectivo · Tarjeta · Transferencia</div>
        </div>
    </div>

    <!-- Panel de Análisis -->
    <div class="row g-3 mb-3">
        <!-- Top Productos -->
        <div class="col-md-6">
            <div class="an-card">
                <div class="an-header">
                    <i class="fas fa-trophy" style="color:var(--vt-warning);"></i> Top 5 Productos Más Vendidos
                </div>
                <div class="an-body">
                    <?php if (!empty($topProductos)): ?>
                        <?php foreach ($topProductos as $idx => $p): ?>
                            <div class="top-item">
                                <div class="d-flex align-items-center gap-2">
                                    <div class="top-rank <?= $idx === 0 ? 'r1' : '' ?>"><?= $idx + 1 ?></div>
                                    <div>
                                        <div class="fw-semibold" style="font-size:0.75rem;"><?= htmlspecialchars($p['nombre']) ?></div>
                                        <small class="text-muted" style="font-size:0.6rem;"><?= htmlspecialchars($p['codigo']) ?></small>
                                    </div>
                                </div>
                                <div class="text-end">
                                    <div class="fw-bold" style="font-size:0.75rem;"><?= $p['total_vendido'] ?> und</div>
                                    <small class="text-muted">S/ <?= number_format($p['total_ingresos'], 2) ?></small>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <p class="text-center py-3 text-muted small mb-0">Sin datos disponibles</p>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <!-- Resumen Rápido -->
        <div class="col-md-6">
            <div class="an-card">
                <div class="an-header">
                    <i class="fas fa-chart-pie" style="color:var(--vt-info);"></i> Resumen de Ventas
                </div>
                <div class="an-body">
                    <div class="row text-center g-2">
                        <div class="col-4">
                            <div class="p-2" style="background:#f8fafc; border-radius:10px;">
                                <div class="fw-bold text-success" style="font-size:1rem;"><?= $estadisticas['ventas_completadas'] ?? 0 ?></div>
                                <small class="text-muted">Completadas</small>
                            </div>
                        </div>
                        <div class="col-4">
                            <div class="p-2" style="background:#fef3c7; border-radius:10px;">
                                <div class="fw-bold text-warning" style="font-size:1rem;"><?= $estadisticas['ventas_pendientes'] ?? 0 ?></div>
                                <small class="text-muted">Pendientes</small>
                            </div>
                        </div>
                        <div class="col-4">
                            <div class="p-2" style="background:#fee2e2; border-radius:10px;">
                                <div class="fw-bold text-danger" style="font-size:1rem;"><?= $estadisticas['ventas_anuladas'] ?? 0 ?></div>
                                <small class="text-muted">Anuladas</small>
                            </div>
                        </div>
                    </div>
                    <div class="mt-3 pt-2 border-top">
                        <div class="d-flex justify-content-between">
                            <span class="small text-muted">IGV total recaudado:</span>
                            <span class="fw-semibold small">S/ <?= number_format($estadisticas['total_igv'] ?? 0, 2) ?></span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Filtros -->
    <div class="fc">
        <button class="ft" type="button" data-bs-toggle="collapse" data-bs-target="#colFiltros">
            <span><i class="fas fa-sliders-h me-2" style="color:var(--vt-primary);"></i> Filtros</span>
            <i class="fas fa-chevron-down small chev"></i>
        </button>
        <div class="collapse" id="colFiltros">
            <div class="fb">
                <form method="get" action="">
                    <input type="hidden" name="controller" value="venta">
                    <input type="hidden" name="action" value="index">
                    <div class="row g-2">
                        <div class="col-md-2">
                            <label class="form-label small fw-semibold mb-1">Estado</label>
                            <select name="estado" class="form-select form-select-sm">
                                <option value="">Todos</option>
                                <option value="completada" <?= ($_GET['estado'] ?? '') === 'completada' ? 'selected' : '' ?>>Completada</option>
                                <option value="pendiente" <?= ($_GET['estado'] ?? '') === 'pendiente' ? 'selected' : '' ?>>Pendiente</option>
                                <option value="anulada" <?= ($_GET['estado'] ?? '') === 'anulada' ? 'selected' : '' ?>>Anulada</option>
                            </select>
                        </div>
                        <div class="col-md-2">
                            <label class="form-label small fw-semibold mb-1">Tipo Pago</label>
                            <select name="tipo_pago" class="form-select form-select-sm">
                                <option value="">Todos</option>
                                <option value="efectivo" <?= ($_GET['tipo_pago'] ?? '') === 'efectivo' ? 'selected' : '' ?>>Efectivo</option>
                                <option value="tarjeta" <?= ($_GET['tipo_pago'] ?? '') === 'tarjeta' ? 'selected' : '' ?>>Tarjeta</option>
                                <option value="transferencia" <?= ($_GET['tipo_pago'] ?? '') === 'transferencia' ? 'selected' : '' ?>>Transferencia</option>
                            </select>
                        </div>
                        <div class="col-md-2">
                            <label class="form-label small fw-semibold mb-1">Fecha Desde</label>
                            <input type="date" name="fecha_desde" class="form-control form-control-sm" 
                                   value="<?= htmlspecialchars($_GET['fecha_desde'] ?? '') ?>">
                        </div>
                        <div class="col-md-2">
                            <label class="form-label small fw-semibold mb-1">Fecha Hasta</label>
                            <input type="date" name="fecha_hasta" class="form-control form-control-sm"
                                   value="<?= htmlspecialchars($_GET['fecha_hasta'] ?? '') ?>">
                        </div>
                        <div class="col-md-3">
                            <label class="form-label small fw-semibold mb-1">Buscar</label>
                            <input type="text" name="busqueda" class="form-control form-control-sm"
                                   placeholder="Cliente, comprobante, documento..."
                                   value="<?= htmlspecialchars($_GET['busqueda'] ?? '') ?>">
                        </div>
                        <div class="col-md-1 d-flex align-items-end gap-2">
                            <button type="submit" class="btn btn-sm btn-primary px-3">
                                <i class="fas fa-search"></i>
                            </button>
                            <a href="index.php?controller=ventas&action=index" class="btn btn-sm btn-outline-secondary">
                                <i class="fas fa-times"></i>
                            </a>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Tabla de Ventas -->
    <div class="tw">
        <div class="tw-header">
            <div class="d-flex align-items-center gap-2">
                <span class="tw-title"><i class="fas fa-list me-1"></i> Historial de Ventas</span>
                <span class="badge bg-secondary bg-opacity-10 text-secondary" style="font-size:0.6rem;">
                    <?= count($ventas) ?> registros
                </span>
            </div>
        </div>
        <div class="table-responsive">
            <table class="table table-hover mb-0" id="tblVentas">
                <thead>
                    <tr>
                        <th>Comprobante</th>
                        <th>Cliente</th>
                        <th>Fecha</th>
                        <th>Total</th>
                        <th>Pago</th>
                        <th>Estado</th>
                        <th>Usuario</th>
                        <th style="width:90px;">Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($ventas)): ?>
                        <tr>
                            <td colspan="8" class="text-center py-4 text-muted small">
                                <i class="fas fa-inbox fa-2x d-block mb-2 opacity-25"></i>
                                No hay ventas registradas.
                                <br>
                                <a href="index.php?controller=ventas&action=crear" class="btn btn-sm mt-2"
                                   style="background:var(--vt-primary);color:#fff;border:none;border-radius:6px;">
                                    <i class="fas fa-plus me-1"></i>Registrar primera venta
                                </a>
                            </td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($ventas as $v): ?>
                            <tr>
                                <td>
                                    <div class="fw-semibold" style="font-size:0.75rem;"><?= htmlspecialchars($v['numero_comprobante']) ?></div>
                                    <small class="text-muted" style="font-size:0.6rem;"><?= strtoupper($v['tipo_comprobante']) ?></small>
                                </td>
                                <td>
                                    <div style="font-size:0.75rem;"><?= htmlspecialchars($v['cliente_nombre'] ?? 'Cliente General') ?></div>
                                    <small class="text-muted" style="font-size:0.6rem;"><?= htmlspecialchars($v['cliente_documento'] ?? '—') ?></small>
                                </td>
                                <td style="font-size:0.7rem;"><?= date('d/m/Y H:i', strtotime($v['fecha_venta'])) ?></td>
                                <td class="fw-semibold" style="font-size:0.75rem;">S/ <?= number_format($v['total'], 2) ?></td>
                                <td>
                                    <span class="payment-badge payment-<?= $v['tipo_comprobante'] ?>">
                                        <?= ucfirst($v['tipo_comprobante']) ?>
                                    </span>
                                </td>
                                <td>
                                    <span class="status-badge status-<?= $v['estado'] ?>">
                                        <?= ucfirst($v['estado']) ?>
                                    </span>
                                </td>
                                <td style="font-size:0.7rem;"><?= htmlspecialchars($v['usuario_nombre'] ?? '—') ?></td>
                                <td>
                                    <div class="d-flex gap-1">
                                        <a href="index.php?controller=ventas&action=ver&id=<?= $v['id_venta'] ?>" 
                                           class="act-btn-sm" title="Ver">
                                            <i class="fas fa-eye"></i>
                                        </a>
                                        <?php if ($v['estado'] !== 'anulada'): ?>
                                            <button type="button" class="act-btn-sm text-danger" 
                                                    title="Anular"
                                                    onclick="confirmarAnular(<?= $v['id_venta'] ?>, '<?= addslashes($v['numero_comprobante']) ?>')">
                                                <i class="fas fa-ban"></i>
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
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    if (typeof $.fn.DataTable !== 'undefined' && $('#tblVentas tbody tr').length > 5) {
        $('#tblVentas').DataTable({
            language: { url: '//cdn.datatables.net/plug-ins/1.13.7/i18n/es-ES.json' },
            pageLength: 25,
            order: [[2, 'desc']],
            responsive: true,
            columnDefs: [{ orderable: false, targets: [7] }],
            dom: '<"d-flex justify-content-between align-items-center mb-2"lf>rtp',
        });
    }
});

function confirmarAnular(id, comprobante) {
    Swal.fire({
        title: '¿Anular venta?',
        html: `¿Está seguro de anular el comprobante <strong>${comprobante}</strong>?<br>
               <small class="text-muted">Esta acción restaurará el stock de los productos y no se puede deshacer.</small>`,
        icon: 'warning',
        input: 'text',
        inputPlaceholder: 'Motivo de anulación (opcional)',
        showCancelButton: true,
        confirmButtonColor: '#dc2626',
        cancelButtonColor: '#6b7280',
        confirmButtonText: 'Sí, anular',
        cancelButtonText: 'Cancelar',
        preConfirm: (motivo) => {
            return motivo;
        }
    }).then((result) => {
        if (result.isConfirmed) {
            let url = 'index.php?controller=ventas&action=anular&id=' + id;
            if (result.value) {
                url += '&motivo=' + encodeURIComponent(result.value);
            }
            window.location.href = url;
        }
    });
}

<?php if ($sweetAlert): ?>
document.addEventListener('DOMContentLoaded', function() {
    if (typeof Swal !== 'undefined') {
        var config = <?= json_encode([
            'title' => $sweetAlert['title'],
            'text' => $sweetAlert['text'],
            'icon' => $sweetAlert['type'],
            'confirmButtonText' => 'Aceptar',
            'confirmButtonColor' => '#4f46e5'
        ]) ?>;
        <?php if ($sweetAlert['type'] === 'success'): ?>
        config.timer = 3000;
        config.timerProgressBar = true;
        <?php endif; ?>
        Swal.fire(config);
    }
});
<?php endif; ?>
</script>

<?php include __DIR__ . '/../../layouts/footer.php'; ?>