<?php
$currentPage = 'marcas';
include __DIR__ . '/../../layouts/inventario_layout.php';
?>

<link rel="stylesheet" href="<?= BASE_URL ?>/views/inventario/marcas/css/marcas.css">
<meta name="csrf-token" content="<?= htmlspecialchars($csrfToken ?? '') ?>">

<div class="marca-wrap">
    <!-- Encabezado -->
    <div class="marca-header">
        <div>
            <h1 class="marca-title">
                <i class="fas fa-trademark"></i> Marcas
            </h1>
            <div class="marca-breadcrumb">
                <a href="index.php?controller=dashboard&action=index">Dashboard</a> / 
                <a href="index.php?controller=marca&action=index">Catálogos</a> / 
                <span>Marcas</span>
            </div>
        </div>
        <a href="index.php?controller=marca&action=crear" class="btn-marca-primary">
            <i class="fas fa-plus"></i> Nueva Marca
        </a>
    </div>

    <!-- Estadísticas -->
    <div class="marca-stats">
        <div class="marca-stat-card">
            <div class="marca-stat-label">Total Marcas</div>
            <div class="marca-stat-value"><?= number_format($totalRegistros) ?></div>
        </div>
        <div class="marca-stat-card">
            <div class="marca-stat-label">Activas</div>
            <div class="marca-stat-value activas">
                <?= number_format(array_filter($marcas, fn($m) => $m['estado'] === 'activo') ? count(array_filter($marcas, fn($m) => $m['estado'] === 'activo')) : 0) ?>
            </div>
        </div>
        <div class="marca-stat-card">
            <div class="marca-stat-label">Inactivas</div>
            <div class="marca-stat-value inactivas">
                <?= number_format(array_filter($marcas, fn($m) => $m['estado'] === 'inactivo') ? count(array_filter($marcas, fn($m) => $m['estado'] === 'inactivo')) : 0) ?>
            </div>
        </div>
    </div>

    <!-- Filtros -->
    <div class="marca-filters">
        <div class="marca-filter-group">
            <label><i class="fas fa-search"></i> Búsqueda</label>
            <input type="text" id="filtro" name="filtro" placeholder="Nombre, país..." 
                   value="<?= htmlspecialchars($_GET['filtro'] ?? '') ?>">
        </div>
        <div class="marca-filter-group">
            <label><i class="fas fa-filter"></i> Estado</label>
            <select id="estado" name="estado">
                <option value="">Todos</option>
                <option value="activo" <?= ($_GET['estado'] ?? '') === 'activo' ? 'selected' : '' ?>>Activas</option>
                <option value="inactivo" <?= ($_GET['estado'] ?? '') === 'inactivo' ? 'selected' : '' ?>>Inactivas</option>
            </select>
        </div>
        <div class="marca-filter-group">
            <label><i class="fas fa-list"></i> Mostrar</label>
            <select id="limit" onchange="cambiarLimit(this)">
                <option value="10" <?= ($_GET['limit'] ?? 20) == 10 ? 'selected' : '' ?>>10</option>
                <option value="20" <?= ($_GET['limit'] ?? 20) == 20 ? 'selected' : '' ?>>20</option>
                <option value="50" <?= ($_GET['limit'] ?? 20) == 50 ? 'selected' : '' ?>>50</option>
                <option value="100" <?= ($_GET['limit'] ?? 20) == 100 ? 'selected' : '' ?>>100</option>
            </select>
        </div>
        <div class="marca-filter-group" style="flex: 0 0 auto;">
            <button id="btnFiltrar" class="btn-marca-primary" style="width: 100%;">
                <i class="fas fa-search"></i> Filtrar
            </button>
        </div>
        <div class="marca-filter-group" style="flex: 0 0 auto;">
            <button id="btnLimpiar" class="btn-marca-secondary" style="width: 100%;">
                <i class="fas fa-eraser"></i> Limpiar
            </button>
        </div>
    </div>

    <!-- Tabla -->
    <div class="marca-table-container">
        <table class="marca-table">
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Nombre</th>
                    <th>Descripción</th>
                    <th>País de Origen</th>
                    <th>Sitio Web</th>
                    <th>Productos</th>
                    <th>Estado</th>
                    <th>Fecha Creación</th>
                    <th>Acciones</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($marcas)): ?>
                    <tr>
                        <td colspan="9" style="text-align: center; padding: 40px;">
                            <i class="fas fa-box-open fa-2x" style="color: #cbd5e1; margin-bottom: 10px; display: block;"></i>
                            No hay marcas registradas.
                        </td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($marcas as $marca): ?>
                        <tr>
                            <td><?= $marca['id_marca'] ?></td>
                            <td><strong><?= htmlspecialchars($marca['nombre']) ?></strong></td>
                            <td><?= htmlspecialchars($marca['descripcion'] ?? '-') ?></td>
                            <td><?= htmlspecialchars($marca['pais_origen'] ?? '-') ?></td>
                            <td>
                                <?php if (!empty($marca['sitio_web'])): ?>
                                    <a href="<?= htmlspecialchars($marca['sitio_web']) ?>" target="_blank" class="text-decoration-none">
                                        <i class="fas fa-external-link-alt"></i> Visitar
                                    </a>
                                <?php else: ?>
                                    -
                                <?php endif; ?>
                            </td>
                            <td class="text-center"><?= number_format($marca['total_productos_activos'] ?? 0) ?></td>
                            <td>
                                <span class="marca-badge <?= $marca['estado'] === 'activo' ? 'marca-badge-activo' : 'marca-badge-inactivo' ?>">
                                    <i class="fas <?= $marca['estado'] === 'activo' ? 'fa-check-circle' : 'fa-ban' ?>"></i>
                                    <?= ucfirst($marca['estado']) ?>
                                </span>
                            </td>
                            <td><?= $marca['fecha_creacion_format'] ?? date('d/m/Y', strtotime($marca['fecha_creacion'])) ?></td>
                            <td class="marca-actions">
                                <a href="index.php?controller=marca&action=editar&id=<?= $marca['id_marca'] ?>" 
                                   class="marca-action-btn edit" title="Editar">
                                    <i class="fas fa-edit"></i>
                                </a>
                                <?php if ($marca['estado'] === 'activo'): ?>
                                    <button class="marca-action-btn delete btn-eliminar-marca" 
                                            data-id="<?= $marca['id_marca'] ?>" 
                                            data-nombre="<?= htmlspecialchars($marca['nombre']) ?>"
                                            title="Desactivar">
                                        <i class="fas fa-trash-alt"></i>
                                    </button>
                                <?php else: ?>
                                    <button class="marca-action-btn activate btn-activar-marca" 
                                            data-id="<?= $marca['id_marca'] ?>" 
                                            data-nombre="<?= htmlspecialchars($marca['nombre']) ?>"
                                            title="Activar">
                                        <i class="fas fa-check-circle"></i>
                                    </button>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>

    <!-- Paginación -->
    <?php if ($totalPaginas > 1): ?>
    <div class="marca-pagination">
        <div class="page-info">
            Mostrando <?= count($marcas) ?> de <?= number_format($totalRegistros) ?> registros
        </div>
        <div class="pagination">
            <?php if ($paginaActual > 1): ?>
                <a href="javascript:void(0)" onclick="cambiarPagina(<?= $paginaActual - 1 ?>)" class="page-link">
                    <i class="fas fa-chevron-left"></i>
                </a>
            <?php endif; ?>
            
            <?php
            $start = max(1, $paginaActual - 2);
            $end = min($totalPaginas, $paginaActual + 2);
            for ($i = $start; $i <= $end; $i++):
            ?>
                <a href="javascript:void(0)" onclick="cambiarPagina(<?= $i ?>)" 
                   class="page-link <?= $i === $paginaActual ? 'active' : '' ?>">
                    <?= $i ?>
                </a>
            <?php endfor; ?>
            
            <?php if ($paginaActual < $totalPaginas): ?>
                <a href="javascript:void(0)" onclick="cambiarPagina(<?= $paginaActual + 1 ?>)" class="page-link">
                    <i class="fas fa-chevron-right"></i>
                </a>
            <?php endif; ?>
        </div>
    </div>
    <?php endif; ?>
</div>

<input type="hidden" id="ifCsrfToken" value="<?= htmlspecialchars($csrfToken ?? '') ?>">

<script>
const marcaSweetAlert = <?= isset($sweetAlert) && $sweetAlert ? json_encode([
    'title' => $sweetAlert['title'] ?? 'Aviso',
    'text' => $sweetAlert['text'] ?? ($sweetAlert['message'] ?? ''),
    'type' => $sweetAlert['type'] ?? 'info',
]) : 'null' ?>;
</script>

<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.all.min.js"></script>
<script src="<?= BASE_URL ?>/views/inventario/marcas/js/marcas.js"></script>

<?php include __DIR__ . '/../../layouts/footer.php'; ?>