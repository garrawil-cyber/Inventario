<?php 
$currentPage = 'inventario_fisico';
include __DIR__ . '/../../layouts/inventario_layout.php'; 
?>

<link rel="stylesheet" href="<?= BASE_URL ?>/views/inventario/inventario_fisico/css/inventario_fisico.css">

<div class="container-fluid px-3 py-2">
    <!-- Encabezado -->
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h1 class="h5 fw-bold mb-0">
                <i class="fas fa-clipboard-list me-2" style="color:var(--if-primary);"></i>Inventario Físico
            </h1>
            <ol class="breadcrumb mb-0 mt-1">
                <li class="breadcrumb-item small"><a href="index.php?controller=dashboard&action=index">Dashboard</a></li>
                <li class="breadcrumb-item small active">Inventario Físico</li>
            </ol>
        </div>
        <div>
            <a href="index.php?controller=inventario&action=crear" class="btn btn-primary btn-sm">
                <i class="fas fa-plus me-1"></i>Nuevo Inventario
            </a>
        </div>
    </div>

    <!-- Filtros -->
    <div class="card shadow-sm mb-4">
        <div class="card-body">
            <form method="GET" id="ifFormFiltros" class="row g-3">
                <input type="hidden" name="controller" value="inventario">
                <input type="hidden" name="action" value="index">
                
                <div class="col-md-3">
                    <label class="form-label small">Buscar</label>
                    <input type="text" name="filtro" id="ifFiltroInput" class="form-control form-control-sm" 
                           placeholder="Código o responsable..." value="<?= htmlspecialchars($_GET['filtro'] ?? '') ?>">
                </div>
                
                <div class="col-md-2">
                    <label class="form-label small">Estado</label>
                    <select name="estado" class="form-select form-select-sm">
                        <option value="">Todos</option>
                        <?php foreach ($estados as $est): ?>
                        <option value="<?= $est ?>" <?= ($_GET['estado'] ?? '') === $est ? 'selected' : '' ?>>
                            <?= ucfirst(str_replace('_', ' ', $est)) ?>
                        </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                
                <div class="col-md-2">
                    <label class="form-label small">Tipo</label>
                    <select name="tipo" class="form-select form-select-sm">
                        <option value="">Todos</option>
                        <?php foreach ($tipos as $t): ?>
                        <option value="<?= $t ?>" <?= ($_GET['tipo'] ?? '') === $t ? 'selected' : '' ?>>
                            <?= ucfirst($t) ?>
                        </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                
                <div class="col-md-2">
                    <label class="form-label small">Almacén</label>
                    <select name="id_almacen" class="form-select form-select-sm">
                        <option value="">Todos</option>
                        <?php foreach ($almacenes as $alm): ?>
                        <option value="<?= $alm['id_almacen'] ?>" <?= ((int)($_GET['id_almacen'] ?? 0)) === $alm['id_almacen'] ? 'selected' : '' ?>>
                            <?= htmlspecialchars($alm['nombre']) ?>
                        </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                
                <div class="col-md-2">
                    <label class="form-label small">Fecha desde</label>
                    <input type="date" name="fecha_desde" class="form-control form-control-sm" 
                           value="<?= htmlspecialchars($_GET['fecha_desde'] ?? '') ?>">
                </div>
                
                <div class="col-md-2">
                    <label class="form-label small">Fecha hasta</label>
                    <input type="date" name="fecha_hasta" class="form-control form-control-sm" 
                           value="<?= htmlspecialchars($_GET['fecha_hasta'] ?? '') ?>">
                </div>
                
                <div class="col-md-2 d-flex align-items-end">
                    <button type="submit" class="btn btn-secondary btn-sm w-100">
                        <i class="fas fa-search me-1"></i>Filtrar
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- Resumen rápido -->
    <div class="row mb-4">
        <div class="col-md-4">
            <div class="stat-card-inv p-3">
                <div class="stat-value"><?= $resumen['planificados'] ?></div>
                <div class="stat-label">Planificados</div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="stat-card-inv p-3">
                <div class="stat-value"><?= $resumen['en_proceso'] ?></div>
                <div class="stat-label">En Proceso</div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="stat-card-inv p-3">
                <div class="stat-value"><?= $resumen['finalizados'] ?></div>
                <div class="stat-label">Finalizados</div>
            </div>
        </div>
    </div>

    <!-- Tabla de inventarios -->
    <div class="card shadow-sm">
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover mb-0">
                    <thead class="table-light">
                        <tr>
                            <th>Código</th>
                            <th>Almacén</th>
                            <th>Tipo</th>
                            <th>Estado</th>
                            <th>Fecha Inicio</th>
                            <th>Responsable</th>
                            <th>Productos</th>
                            <th>Diferencias</th>
                            <th class="text-end">Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($inventarios)): ?>
                        <tr>
                            <td colspan="9" class="text-center py-4 text-muted">
                                <i class="fas fa-inbox fa-2x mb-2 d-block"></i>
                                No hay inventarios registrados.
                            </td>
                        </tr>
                        <?php else: ?>
                        <?php foreach ($inventarios as $inv): ?>
                        <tr>
                            <td><code><?= htmlspecialchars($inv['codigo']) ?></code></td>
                            <td><?= htmlspecialchars($inv['almacen_nombre']) ?></td>
                            <td><span class="badge bg-secondary"><?= ucfirst($inv['tipo']) ?></span></td>
                            <td>
                                <span class="badge-inv-<?= $inv['estado'] ?>">
                                    <?= ucfirst(str_replace('_', ' ', $inv['estado'])) ?>
                                </span>
                            </td>
                            <td><?= $inv['fecha_inicio_fmt'] ?></td>
                            <td><?= htmlspecialchars($inv['responsable'] ?? '-') ?></td>
                            <td class="text-center"><?= number_format($inv['total_productos'] ?? 0) ?></td>
                            <td class="text-center">
                                <?php if (($inv['total_diferencias'] ?? 0) > 0): ?>
                                <span class="diferencia-negativa"><?= number_format($inv['total_diferencias']) ?></span>
                                <?php else: ?>
                                <span class="diferencia-cero">0</span>
                                <?php endif; ?>
                            </td>
                            <td class="text-end">
                                <a href="index.php?controller=inventario&action=ver&id=<?= $inv['id_inventario'] ?>" 
                                   class="btn btn-sm btn-outline-primary" title="Ver detalles">
                                    <i class="fas fa-eye"></i>
                                </a>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- Paginación -->
    <?php if ($totalPaginas > 1): ?>
    <div class="d-flex justify-content-between align-items-center mt-4">
        <div class="text-muted small">Mostrando <?= count($inventarios) ?> de <?= number_format($totalRegistros) ?> registros</div>
        <nav>
            <ul class="pagination pagination-sm mb-0">
                <?php for ($i = 1; $i <= $totalPaginas; $i++): ?>
                <li class="page-item <?= $i == $paginaActual ? 'active' : '' ?>">
                    <a class="page-link" href="?controller=inventario&action=index&pagina=<?= $i ?>&<?= http_build_query(array_filter($_GET, fn($k) => $k !== 'pagina', ARRAY_FILTER_USE_KEY)) ?>">
                        <?= $i ?>
                    </a>
                </li>
                <?php endfor; ?>
            </ul>
        </nav>
    </div>
    <?php endif; ?>
</div>

<script>
const ifSweetAlert = <?= isset($sweetAlert) && $sweetAlert ? json_encode([
    'title' => $sweetAlert['title'] ?? 'Aviso',
    'text'  => $sweetAlert['text'] ?? ($sweetAlert['message'] ?? ''),
    'type'  => $sweetAlert['type'] ?? 'info',
]) : 'null' ?>;
</script>

<script src="<?= BASE_URL ?>/views/inventario/inventario_fisico/js/inventario_fisico.js"></script>
<?php include __DIR__ . '/../../layouts/footer.php'; ?>