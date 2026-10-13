<?php 
$currentPage = 'almacenes';
include __DIR__ . '/../../layouts/inventario_layout.php'; 
?>

<link rel="stylesheet" href="<?= BASE_URL ?>assets/css/almacenes.css">

<div class="container-fluid px-3 py-2">
    <!-- Encabezado -->
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h1 class="h5 fw-bold mb-0">
                <i class="fas fa-warehouse me-2" style="color:var(--vt-primary);"></i>Gestión de Almacenes
            </h1>
            <ol class="breadcrumb mb-0 mt-1">
                <li class="breadcrumb-item small"><a href="index.php?controller=dashboard&action=index">Dashboard</a></li>
                <li class="breadcrumb-item small active">Almacenes</li>
            </ol>
        </div>
        <div>
            <a href="index.php?controller=almacen&action=crear" class="btn btn-sm btn-primary">
                <i class="fas fa-plus me-1"></i>Nuevo Almacén
            </a>
        </div>
    </div>

    <!-- Filtros -->
    <div class="almacen-filtros-card">
        <form id="filter-form" method="GET" action="index.php">
            <input type="hidden" name="controller" value="almacen">
            <input type="hidden" name="action" value="index">
            
            <div class="row g-3 align-items-end">
                <div class="col-md-4">
                    <label class="form-label">Buscar</label>
                    <input type="text" name="filtro" class="form-control form-control-sm filter-auto-submit" 
                           placeholder="Nombre, código, responsable..." value="<?= htmlspecialchars($_GET['filtro'] ?? '') ?>">
                </div>
                <div class="col-md-3">
                    <label class="form-label">Tipo</label>
                    <select name="tipo" class="form-select form-select-sm filter-auto-submit">
                        <option value="">Todos</option>
                        <option value="principal" <?= (($_GET['tipo'] ?? '') == 'principal') ? 'selected' : '' ?>>Principal</option>
                        <option value="secundario" <?= (($_GET['tipo'] ?? '') == 'secundario') ? 'selected' : '' ?>>Secundario</option>
                        <option value="transitorio" <?= (($_GET['tipo'] ?? '') == 'transitorio') ? 'selected' : '' ?>>Transitorio</option>
                    </select>
                </div>
                <div class="col-md-3">
                    <label class="form-label">Estado</label>
                    <select name="estado" class="form-select form-select-sm filter-auto-submit">
                        <option value="">Todos</option>
                        <option value="activo" <?= (($_GET['estado'] ?? '') == 'activo') ? 'selected' : '' ?>>Activo</option>
                        <option value="inactivo" <?= (($_GET['estado'] ?? '') == 'inactivo') ? 'selected' : '' ?>>Inactivo</option>
                    </select>
                </div>
                <div class="col-md-2">
                    <button type="submit" class="btn btn-sm btn-secondary w-100">
                        <i class="fas fa-search me-1"></i>Filtrar
                    </button>
                </div>
            </div>
        </form>
    </div>

    <!-- Tabla de almacenes -->
    <div class="card shadow-sm">
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover table-almacenes mb-0">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Código</th>
                            <th>Nombre</th>
                            <th>Ciudad</th>
                            <th>Responsable</th>
                            <th>Tipo</th>
                            <th>Estado</th>
                            <th>Ubicaciones</th>
                            <th class="text-center">Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($almacenes)): ?>
                        <tr>
                            <td colspan="9" class="text-center py-4 text-muted">
                                <i class="fas fa-warehouse fa-2x mb-2 d-block"></i>
                                No hay almacenes registrados
                            </td>
                        </tr>
                        <?php else: ?>
                        <?php foreach ($almacenes as $almacen): ?>
                        <tr>
                            <td class="fw-bold">#<?= $almacen['id_almacen'] ?></td>
                            <td><code><?= htmlspecialchars($almacen['codigo']) ?></code></td>
                            <td>
                                <strong><?= htmlspecialchars($almacen['nombre']) ?></strong>
                                <?php if (!empty($almacen['direccion'])): ?>
                                <small class="d-block text-muted"><?= htmlspecialchars($almacen['direccion']) ?></small>
                                <?php endif; ?>
                            </td>
                            <td><?= htmlspecialchars($almacen['ciudad'] ?? '-') ?></td>
                            <td>
                                <?= htmlspecialchars($almacen['responsable']) ?>
                                <?php if (!empty($almacen['telefono'])): ?>
                                <small class="d-block text-muted"><?= htmlspecialchars($almacen['telefono']) ?></small>
                                <?php endif; ?>
                            </td>
                            <td>
                                <span class="badge-almacen-<?= $almacen['tipo'] ?>">
                                    <?= ucfirst($almacen['tipo']) ?>
                                </span>
                            </td>
                            <td>
                                <span class="badge-almacen-<?= $almacen['estado'] ?>">
                                    <?= ucfirst($almacen['estado']) ?>
                                </span>
                            </td>
                            <td>
                                <span class="badge bg-secondary">
                                    <i class="fas fa-map-marker-alt me-1"></i><?= $almacen['total_ubicaciones'] ?? 0 ?>
                                </span>
                                <small class="text-muted d-block">
                                    <?= ($almacen['ubicaciones_ocupadas'] ?? 0) ?> ocupadas
                                </small>
                            </td>
                            <td class="text-center">
                                <div class="btn-group" role="group">
                                    <a href="index.php?controller=almacen&action=ver&id=<?= $almacen['id_almacen'] ?>" 
                                       class="btn btn-sm btn-outline-primary" title="Ver detalle">
                                        <i class="fas fa-eye"></i>
                                    </a>
                                    <a href="index.php?controller=almacen&action=editar&id=<?= $almacen['id_almacen'] ?>" 
                                       class="btn btn-sm btn-outline-secondary" title="Editar">
                                        <i class="fas fa-edit"></i>
                                    </a>
                                    <button type="button" 
                                            class="btn btn-sm btn-outline-danger btn-eliminar-almacen"
                                            data-id="<?= $almacen['id_almacen'] ?>"
                                            data-nombre="<?= htmlspecialchars($almacen['nombre']) ?>"
                                            title="Desactivar">
                                        <i class="fas fa-trash-alt"></i>
                                    </button>
                                </div>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
        
        <!-- Paginación -->
        <?php if ($totalPaginas > 1): ?>
        <div class="card-footer bg-white">
            <nav>
                <ul class="pagination justify-content-center mb-0">
                    <li class="page-item <?= ($paginaActual <= 1) ? 'disabled' : '' ?>">
                        <a class="page-link" href="?controller=almacen&action=index&pagina=<?= $paginaActual-1 ?>&<?= http_build_query(array_diff_key($_GET, ['pagina' => 0])) ?>">
                            <i class="fas fa-chevron-left"></i> Anterior
                        </a>
                    </li>
                    <?php 
                    $start = max(1, $paginaActual - 2);
                    $end = min($totalPaginas, $paginaActual + 2);
                    for ($i = $start; $i <= $end; $i++): ?>
                    <li class="page-item <?= ($i == $paginaActual) ? 'active' : '' ?>">
                        <a class="page-link" href="?controller=almacen&action=index&pagina=<?= $i ?>&<?= http_build_query(array_diff_key($_GET, ['pagina' => 0])) ?>"><?= $i ?></a>
                    </li>
                    <?php endfor; ?>
                    <li class="page-item <?= ($paginaActual >= $totalPaginas) ? 'disabled' : '' ?>">
                        <a class="page-link" href="?controller=almacen&action=index&pagina=<?= $paginaActual+1 ?>&<?= http_build_query(array_diff_key($_GET, ['pagina' => 0])) ?>">
                            Siguiente <i class="fas fa-chevron-right"></i>
                        </a>
                    </li>
                </ul>
            </nav>
        </div>
        <?php endif; ?>
    </div>
</div>

<meta name="csrf-token" content="<?= SessionMiddleware::generateCSRFToken() ?>">
<script src="<?= BASE_URL ?>assets/js/almacenes.js"></script>

<?php include __DIR__ . '/../../layouts/footer.php'; ?>