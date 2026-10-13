<?php 
$currentPage = 'compras';
include __DIR__ . '/../../layouts/inventario_layout.php'; 
?>

<!-- CSS específico del módulo -->
<link rel="stylesheet" href="<?= BASE_URL ?>/views/inventario/compras/css/compras.css">

<div class="container-fluid px-3 py-2">
    <!-- Encabezado -->
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h1 class="h5 fw-bold mb-0">
                <i class="fas fa-shopping-cart me-2" style="color:var(--vt-primary);"></i>Gestión de Compras
            </h1>
            <ol class="breadcrumb mb-0 mt-1">
                <li class="breadcrumb-item small"><a href="index.php?controller=dashboard&action=index">Dashboard</a></li>
                <li class="breadcrumb-item small active">Compras</li>
            </ol>
        </div>
        <div>
            <a href="index.php?controller=compra&action=crear" class="btn btn-sm btn-primary">
                <i class="fas fa-plus me-1"></i>Nueva Compra
            </a>
        </div>
    </div>

    <!-- Filtros -->
    <div class="filtros-card">
        <form id="filter-form" method="GET" action="index.php">
            <input type="hidden" name="controller" value="compra">
            <input type="hidden" name="action" value="index">
            
            <div class="row g-3">
                <div class="col-md-3">
                    <label class="form-label">Buscar</label>
                    <input type="text" name="filtro" class="form-control form-control-sm filter-auto-submit" 
                           placeholder="Código, proveedor..." value="<?= htmlspecialchars($_GET['filtro'] ?? '') ?>">
                </div>
                <div class="col-md-2">
                    <label class="form-label">Proveedor</label>
                    <select name="proveedor_id" class="form-select form-select-sm filter-auto-submit">
                        <option value="">Todos</option>
                        <?php foreach ($proveedores as $p): ?>
                        <option value="<?= $p['id_proveedor'] ?>" <?= (($_GET['proveedor_id'] ?? '') == $p['id_proveedor']) ? 'selected' : '' ?>>
                            <?= htmlspecialchars($p['razon_social']) ?>
                        </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-2">
                    <label class="form-label">Estado</label>
                    <select name="estado" class="form-select form-select-sm filter-auto-submit">
                        <option value="">Todos</option>
                        <?php foreach ($estados as $e): ?>
                        <option value="<?= $e ?>" <?= (($_GET['estado'] ?? '') == $e) ? 'selected' : '' ?>>
                            <?= ucfirst($e) ?>
                        </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-2">
                    <label class="form-label">Estado Pago</label>
                    <select name="estado_pago" class="form-select form-select-sm filter-auto-submit">
                        <option value="">Todos</option>
                        <?php foreach ($estadosPago as $ep): ?>
                        <option value="<?= $ep ?>" <?= (($_GET['estado_pago'] ?? '') == $ep) ? 'selected' : '' ?>>
                            <?= ucfirst($ep) ?>
                        </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-2">
                    <label class="form-label">Fecha Desde</label>
                    <input type="date" name="fecha_desde" class="form-control form-control-sm filter-auto-submit" 
                           value="<?= htmlspecialchars($_GET['fecha_desde'] ?? '') ?>">
                </div>
                <div class="col-md-1">
                    <label class="form-label">Hasta</label>
                    <input type="date" name="fecha_hasta" class="form-control form-control-sm filter-auto-submit" 
                           value="<?= htmlspecialchars($_GET['fecha_hasta'] ?? '') ?>">
                </div>
            </div>
        </form>
    </div>

    <!-- Tabla de compras -->
    <div class="card shadow-sm">
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover table-compras mb-0">
                    <thead class="table-light">
                        <tr>
                            <th>ID</th>
                            <th>Código</th>
                            <th>Fecha</th>
                            <th>Proveedor</th>
                            <th>Comprobante</th>
                            <th class="text-end">Total</th>
                            <th>Estado</th>
                            <th>Estado Pago</th>
                            <th class="text-center">Items</th>
                            <th class="text-center">Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($compras)): ?>
                        <tr>
                            <td colspan="10" class="text-center py-4 text-muted">
                                <i class="fas fa-inbox fa-2x mb-2 d-block"></i>
                                No hay compras registradas
                            </td>
                        </tr>
                        <?php else: ?>
                        <?php foreach ($compras as $compra): ?>
                        <tr>
                            <td>#<?= $compra['id'] ?></td>
                            <td>
                                <code><?= htmlspecialchars($compra['codigo']) ?></code>
                            </td>
                            <td><?= date('d/m/Y', strtotime($compra['fecha'])) ?></td>
                            <td><?= htmlspecialchars($compra['proveedor_nombre']) ?></td>
                            <td>
                                <small><?= strtoupper($compra['tipo_comprobante']) ?> <?= htmlspecialchars($compra['comprobante']) ?></small>
                            </td>
                            <td class="text-end fw-bold">S/ <?= number_format($compra['total'], 2) ?></td>
                            <td>
                                <?php
                                $badgeClass = 'secondary';
                                if ($compra['estado'] === 'completada') $badgeClass = 'success';
                                elseif ($compra['estado'] === 'anulada') $badgeClass = 'danger';
                                elseif ($compra['estado'] === 'pendiente') $badgeClass = 'warning';
                                elseif ($compra['estado'] === 'procesando') $badgeClass = 'info';
                                ?>
                                <span class="badge bg-<?= $badgeClass ?>"><?= ucfirst($compra['estado']) ?></span>
                            </td>
                            <td>
                                <?php
                                $pagoClass = 'secondary';
                                if ($compra['estado_pago'] === 'pagado') $pagoClass = 'success';
                                elseif ($compra['estado_pago'] === 'pendiente') $pagoClass = 'warning';
                                elseif ($compra['estado_pago'] === 'parcial') $pagoClass = 'info';
                                elseif ($compra['estado_pago'] === 'anulado') $pagoClass = 'dark';
                                ?>
                                <span class="badge bg-<?= $pagoClass ?>"><?= ucfirst($compra['estado_pago']) ?></span>
                            </td>
                            <td class="text-center"><?= $compra['productos_count'] ?? 0 ?></td>
                            <td class="text-center">
                                <a href="index.php?controller=compra&action=ver&id=<?= $compra['id'] ?>" 
                                   class="btn btn-sm btn-outline-primary" title="Ver">
                                    <i class="fas fa-eye"></i>
                                </a>
                                <?php if ($compra['estado'] !== 'anulada'): ?>
                                <a href="index.php?controller=compra&action=editar&id=<?= $compra['id'] ?>" 
                                   class="btn btn-sm btn-outline-secondary" title="Editar">
                                    <i class="fas fa-edit"></i>
                                </a>
                                <?php endif; ?>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
        <?php if ($totalPaginas > 1): ?>
        <div class="card-footer bg-white">
            <nav>
                <ul class="pagination justify-content-center mb-0">
                    <li class="page-item <?= ($paginaActual <= 1) ? 'disabled' : '' ?>">
                        <a class="page-link" href="?controller=compra&action=index&pagina=<?= $paginaActual-1 ?>&<?= http_build_query(array_diff_key($_GET, ['pagina' => 0])) ?>">Anterior</a>
                    </li>
                    <?php for ($i = 1; $i <= $totalPaginas; $i++): ?>
                    <li class="page-item <?= ($i == $paginaActual) ? 'active' : '' ?>">
                        <a class="page-link" href="?controller=compra&action=index&pagina=<?= $i ?>&<?= http_build_query(array_diff_key($_GET, ['pagina' => 0])) ?>"><?= $i ?></a>
                    </li>
                    <?php endfor; ?>
                    <li class="page-item <?= ($paginaActual >= $totalPaginas) ? 'disabled' : '' ?>">
                        <a class="page-link" href="?controller=compra&action=index&pagina=<?= $paginaActual+1 ?>&<?= http_build_query(array_diff_key($_GET, ['pagina' => 0])) ?>">Siguiente</a>
                    </li>
                </ul>
            </nav>
        </div>
        <?php endif; ?>
    </div>
</div>

<!-- CSRF Token para JS -->
<meta name="csrf-token" content="<?= SessionMiddleware::generateCSRFToken() ?>">

<!-- JS específico del módulo -->
<script src="<?= BASE_URL ?>assets/js/compras.js"></script>

<?php include __DIR__ . '/../../layouts/footer.php'; ?>