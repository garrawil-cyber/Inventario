<?php
/**
 * Vista: Listado de Proveedores (Rediseñado)
 * Variables: $proveedores, $estadisticas, $sweetAlert
 */

$currentPage = 'proveedores';
$proveedores = $proveedores ?? [];
$estadisticas = $estadisticas ?? [];

// Contar filtros activos
$filtrosActivos = 0;
foreach (['estado', 'busqueda'] as $k) {
    if (!empty($_GET[$k])) $filtrosActivos++;
}

include __DIR__ . '/../../layouts/inventario_layout.php';
?>

<head>   
    <!-- Módulo Proveedores CSS/JS -->
    <link rel="stylesheet" href="<?= BASE_URL ?>/views/inventario/proveedores/css/proveedores.css">
</head>
<body class="page-proveedores-index">
<div class="container-fluid py-4">
    
    <!-- Header -->
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h1 class="display-6 fw-bold mb-1" style="font-size: 1.75rem;">
                <i class="fas fa-truck me-2" style="color: var(--pv-primary);"></i>
                Proveedores
            </h1>
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb mb-0">
                    <li class="breadcrumb-item"><a href="index.php?controller=dashboard&action=index">Dashboard</a></li>
                    <li class="breadcrumb-item active">Proveedores</li>
                </ol>
            </nav>
        </div>
        <div class="d-flex gap-2">
            <a href="index.php?controller=proveedor&action=exportar<?= !empty($_GET) ? '&'.http_build_query($_GET) : '' ?>" 
               class="btn btn-outline-secondary">
                <i class="fas fa-file-excel me-2 text-success"></i>Exportar
            </a>
            <a href="index.php?controller=proveedor&action=crear" class="btn btn-primary">
                <i class="fas fa-plus me-2"></i>Nuevo Proveedor
            </a>
        </div>
    </div>
    
    <!-- KPI Cards -->
    <div class="kpi-grid">
        <div class="kpi-card total">
            <div class="kpi-info">
                <p>Total Proveedores</p>
                <h3><?= number_format($estadisticas['total_proveedores'] ?? 0) ?></h3>
                <div class="kpi-sub">
                    <span class="text-success">▲ <?= $estadisticas['proveedores_activos'] ?? 0 ?> activos</span>
                    · <?= $estadisticas['proveedores_inactivos'] ?? 0 ?> inactivos
                </div>
            </div>
            <div class="kpi-icon"><i class="fas fa-building"></i></div>
        </div>
        
        <div class="kpi-card productos">
            <div class="kpi-info">
                <p>Productos Totales</p>
                <h3><?= number_format($estadisticas['total_productos'] ?? 0) ?></h3>
                <div class="kpi-sub"><?= number_format($estadisticas['productos_activos'] ?? 0) ?> activos</div>
            </div>
            <div class="kpi-icon"><i class="fas fa-boxes"></i></div>
        </div>
        
        <div class="kpi-card compras">
            <div class="kpi-info">
                <p>Valor Total Compras</p>
                <h3>S/ <?= number_format($estadisticas['valor_total_compras'] ?? 0, 2) ?></h3>
                <div class="kpi-sub"><?= number_format($estadisticas['total_movimientos'] ?? 0) ?> movimientos</div>
            </div>
            <div class="kpi-icon"><i class="fas fa-dollar-sign"></i></div>
        </div>
        
        <div class="kpi-card mes">
            <div class="kpi-info">
                <p>Compras del Mes</p>
                <h3>S/ <?= number_format($estadisticas['compras_mes_actual'] ?? 0, 2) ?></h3>
                <div class="kpi-sub"><?= number_format($estadisticas['movimientos_mes_actual'] ?? 0) ?> movimientos</div>
            </div>
            <div class="kpi-icon"><i class="fas fa-calendar-alt"></i></div>
        </div>
    </div>
    
    <!-- Analytics Section -->
    <div class="analytics-grid">
        <!-- Top Productos -->
        <div class="pv-card">
            <div class="pv-card-header">
                <i class="fas fa-star me-2" style="color: #d97706;"></i>
                Top Proveedores · Productos
            </div>
            <div class="pv-card-body">
                <?php if (!empty($estadisticas['top_proveedores_productos'])): ?>
                    <?php foreach ($estadisticas['top_proveedores_productos'] as $i => $p): ?>
                        <div class="top-list-item">
                            <div class="top-rank rank-<?= $i+1 ?>"><?= $i+1 ?></div>
                            <div class="pv-avatar" style="background: linear-gradient(135deg, #4f46e5, #818cf8);">
                                <?= strtoupper(substr($p['razon_social'], 0, 2)) ?>
                            </div>
                            <div style="flex:1">
                                <div class="fw-semibold"><?= htmlspecialchars($p['razon_social']) ?></div>
                                <small class="text-muted"><?= $p['total_productos'] ?> productos</small>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php else: ?>
                    <p class="text-center py-4 text-muted">Sin datos disponibles</p>
                <?php endif; ?>
            </div>
        </div>
        
        <!-- Top Compras -->
        <div class="pv-card">
            <div class="pv-card-header">
                <i class="fas fa-chart-line me-2" style="color: #059669;"></i>
                Top Proveedores · Compras
            </div>
            <div class="pv-card-body">
                <?php if (!empty($estadisticas['top_proveedores_compras'])): ?>
                    <?php foreach ($estadisticas['top_proveedores_compras'] as $i => $p): ?>
                        <div class="top-list-item">
                            <div class="top-rank rank-<?= $i+1 ?>"><?= $i+1 ?></div>
                            <div style="flex:1">
                                <div class="fw-semibold"><?= htmlspecialchars($p['razon_social']) ?></div>
                                <small class="text-muted"><?= $p['total_movimientos'] ?? 0 ?> movimientos</small>
                            </div>
                            <div class="fw-bold text-success">S/ <?= number_format($p['valor_total'] ?? 0, 0) ?></div>
                        </div>
                    <?php endforeach; ?>
                <?php else: ?>
                    <p class="text-center py-4 text-muted">Sin datos disponibles</p>
                <?php endif; ?>
            </div>
        </div>
    </div>
    
    <!-- Filtros -->
    <div class="pv-filters">
        <button class="btn w-100 text-start p-3" id="filterToggle" style="background: transparent;">
            <i class="fas fa-sliders-h me-2" style="color: var(--pv-primary);"></i>
            Filtros
            <?php if ($filtrosActivos > 0): ?>
                <span class="badge bg-primary ms-2"><?= $filtrosActivos ?></span>
            <?php endif; ?>
            <i class="fas fa-chevron-down float-end" id="filterChevron"></i>
        </button>
        <div class="collapse p-3" id="filterPanel" style="<?= $filtrosActivos > 0 ? 'display: block;' : '' ?>">
            <form method="get" action="">
                <input type="hidden" name="controller" value="proveedor">
                <input type="hidden" name="action" value="index">
                <div class="row g-3">
                    <div class="col-md-3">
                        <label class="pv-form-label">Estado</label>
                        <select name="estado" class="pv-form-control">
                            <option value="">Todos</option>
                            <option value="activo" <?= ($_GET['estado'] ?? '') === 'activo' ? 'selected' : '' ?>>Activos</option>
                            <option value="inactivo" <?= ($_GET['estado'] ?? '') === 'inactivo' ? 'selected' : '' ?>>Inactivos</option>
                            <option value="suspendido" <?= ($_GET['estado'] ?? '') === 'suspendido' ? 'selected' : '' ?>>Suspendidos</option>
                        </select>
                    </div>
                    <div class="col-md-6">
                        <label class="pv-form-label">Buscar</label>
                        <input type="text" name="busqueda" class="pv-form-control" 
                               placeholder="Razón social, RUC, nombre comercial..."
                               value="<?= htmlspecialchars($_GET['busqueda'] ?? '') ?>">
                    </div>
                    <div class="col-md-3 d-flex align-items-end gap-2">
                        <button type="submit" class="btn btn-primary w-100">
                            <i class="fas fa-search me-2"></i>Filtrar
                        </button>
                        <a href="index.php?controller=proveedor&action=index" class="btn btn-outline-secondary w-100">
                            <i class="fas fa-times me-2"></i>Limpiar
                        </a>
                    </div>
                </div>
            </form>
        </div>
    </div>
    
    <!-- Tabla -->
    <div class="pv-table-wrapper">
        <table class="pv-table" id="tablaProveedores">
            <thead>
                <tr>
                    <th>RUC</th>
                    <th>Razón Social</th>
                    <th>Ciudad</th>
                    <th>Contacto</th>
                    <th class="text-center">Productos</th>
                    <th class="text-end">Compras</th>
                    <th>Estado</th>
                    <th class="text-center">Acciones</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($proveedores)): ?>
                    <tr>
                        <td colspan="8" class="text-center py-5 text-muted">
                            <i class="fas fa-inbox fa-3x d-block mb-3 opacity-25"></i>
                            No hay proveedores registrados.
                            <br>
                            <a href="index.php?controller=proveedor&action=crear" class="btn btn-primary btn-sm mt-3">
                                <i class="fas fa-plus me-2"></i>Crear primer proveedor
                            </a>
                        </td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($proveedores as $prov): 
                        $estado = $prov['estado'] ?? 'inactivo';
                        $statusClass = $estado === 'activo' ? 'status-active' : ($estado === 'suspendido' ? 'status-suspended' : 'status-inactive');
                        $statusText = ucfirst($estado);
                    ?>
                        <tr>
                            <td><span class="ruc-chip"><?= htmlspecialchars($prov['ruc'] ?? '—') ?></span></td>
                            <td>
                                <div class="d-flex align-items-center gap-2">
                                    <div class="pv-avatar"><?= strtoupper(substr($prov['razon_social'], 0, 2)) ?></div>
                                    <div>
                                        <div class="fw-semibold"><?= htmlspecialchars($prov['razon_social']) ?></div>
                                        <?php if (!empty($prov['nombre_comercial'])): ?>
                                            <small class="text-muted"><?= htmlspecialchars($prov['nombre_comercial']) ?></small>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </td>
                            <td><?= htmlspecialchars($prov['ciudad'] ?? '—') ?></td>
                            <td>
                                <?php if (!empty($prov['telefono'])): ?>
                                    <div><i class="fas fa-phone me-1 text-muted"></i><?= htmlspecialchars($prov['telefono']) ?></div>
                                <?php endif; ?>
                                <?php if (!empty($prov['email'])): ?>
                                    <div><i class="fas fa-envelope me-1 text-muted"></i><?= htmlspecialchars($prov['email']) ?></div>
                                <?php endif; ?>
                            </td>
                            <td class="text-center"><span class="badge bg-primary"><?= $prov['total_productos'] ?? 0 ?></span></td>
                            <td class="text-end fw-semibold">S/ <?= number_format($prov['valor_compras'] ?? 0, 2) ?></td>
                            <td><span class="status-badge <?= $statusClass ?>"><?= $statusText ?></span></td>
                            <td class="text-center">
                                <div class="action-btns">
                                    <a href="index.php?controller=proveedor&action=ver&id=<?= $prov['id_proveedor'] ?>" class="action-btn view" title="Ver">
                                        <i class="fas fa-eye"></i>
                                    </a>
                                    <a href="index.php?controller=proveedor&action=editar&id=<?= $prov['id_proveedor'] ?>" class="action-btn edit" title="Editar">
                                        <i class="fas fa-pen"></i>
                                    </a>
                                    <?php if ($estado === 'activo'): ?>
<button class="action-btn delete btn-delete-proveedor" 
        data-id="<?= $prov['id_proveedor'] ?>" 
        data-nombre="<?= htmlspecialchars($prov['razon_social'], ENT_QUOTES, 'UTF-8') ?>"
        title="Desactivar">
    <i class="fas fa-ban"></i>
</button>
                                    <?php else: ?>
<button class="action-btn activate btn-activate-proveedor" 
        data-id="<?= $prov['id_proveedor'] ?>" 
        data-nombre="<?= htmlspecialchars($prov['razon_social'], ENT_QUOTES, 'UTF-8') ?>"
        title="Activar">
    <i class="fas fa-check-circle"></i>
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
</body>

<?php include __DIR__ . '/../../layouts/footer.php'; ?>

<!-- Pasar flash data a JavaScript -->
<script>
    window.proveedoresFlash = <?= json_encode($sweetAlert ?? null, JSON_UNESCAPED_UNICODE) ?>;
</script>

<script src="<?= BASE_URL ?>/views/inventario/proveedores/js/proveedores.js"></script>

