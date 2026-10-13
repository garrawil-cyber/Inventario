<?php 
$currentPage = 'alertas';
include __DIR__ . '/../../layouts/inventario_layout.php'; 
?>

<div class="container-fluid">
    <!-- Encabezado -->
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1 class="h3 mb-0">Alertas de Inventario</h1>
        <div>
            <button class="btn btn-success me-2" onclick="generarAlertasAutomaticas()">
                <i class="fas fa-sync-alt me-2"></i>Generar Alertas
            </button>
            <a href="index.php?controller=alerta&action=crear" class="btn btn-primary me-2">
                <i class="fas fa-plus me-2"></i>Nueva Alerta
            </a>
            <a href="index.php?controller=alerta&action=limpiar" class="btn btn-warning"
               onclick="return confirm('¿Está seguro de limpiar las alertas antiguas?')">
                <i class="fas fa-trash me-2"></i>Limpiar Antiguas
            </a>
        </div>
    </div>

    <!-- Estadísticas -->
    <div class="row mb-4">
        <div class="col-xl-3 col-md-6 mb-4">
            <div class="card border-left-danger shadow h-100 py-2">
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                            <div class="text-xs font-weight-bold text-danger text-uppercase mb-1">
                                Críticas
                            </div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800">
                                <?= array_reduce($estadisticasUrgencia, function($carry, $item) {
                                    return $carry + ($item['nivel_urgencia'] == 'critica' ? $item['cantidad'] : 0);
                                }, 0) ?>
                            </div>
                        </div>
                        <div class="col-auto">
                            <i class="fas fa-exclamation-circle fa-2x text-gray-300"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="col-xl-3 col-md-6 mb-4">
            <div class="card border-left-warning shadow h-100 py-2">
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                            <div class="text-xs font-weight-bold text-warning text-uppercase mb-1">
                                Altas
                            </div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800">
                                <?= array_reduce($estadisticasUrgencia, function($carry, $item) {
                                    return $carry + ($item['nivel_urgencia'] == 'alta' ? $item['cantidad'] : 0);
                                }, 0) ?>
                            </div>
                        </div>
                        <div class="col-auto">
                            <i class="fas fa-exclamation-triangle fa-2x text-gray-300"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="col-xl-3 col-md-6 mb-4">
            <div class="card border-left-info shadow h-100 py-2">
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                            <div class="text-xs font-weight-bold text-info text-uppercase mb-1">
                                Medias
                            </div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800">
                                <?= array_reduce($estadisticasUrgencia, function($carry, $item) {
                                    return $carry + ($item['nivel_urgencia'] == 'media' ? $item['cantidad'] : 0);
                                }, 0) ?>
                            </div>
                        </div>
                        <div class="col-auto">
                            <i class="fas fa-info-circle fa-2x text-gray-300"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="col-xl-3 col-md-6 mb-4">
            <div class="card border-left-success shadow h-100 py-2">
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                            <div class="text-xs font-weight-bold text-success text-uppercase mb-1">
                                Bajas
                            </div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800">
                                <?= array_reduce($estadisticasUrgencia, function($carry, $item) {
                                    return $carry + ($item['nivel_urgencia'] == 'baja' ? $item['cantidad'] : 0);
                                }, 0) ?>
                            </div>
                        </div>
                        <div class="col-auto">
                            <i class="fas fa-check-circle fa-2x text-gray-300"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Filtros -->
    <div class="card mb-4">
        <div class="card-body">
            <form method="get" action="" class="row g-3">
                <input type="hidden" name="controller" value="alerta">
                <input type="hidden" name="action" value="index">
                
                <div class="col-md-4">
                    <label class="form-label">Tipo de Alerta</label>
                    <select name="tipo_alerta" class="form-select">
                        <option value="">Todos</option>
                        <option value="stock_bajo" <?= (isset($_GET['tipo_alerta']) && $_GET['tipo_alerta'] == 'stock_bajo') ? 'selected' : '' ?>>Stock Bajo</option>
                        <option value="vencimiento" <?= (isset($_GET['tipo_alerta']) && $_GET['tipo_alerta'] == 'vencimiento') ? 'selected' : '' ?>>Vencimiento</option>
                        <option value="manual" <?= (isset($_GET['tipo_alerta']) && $_GET['tipo_alerta'] == 'manual') ? 'selected' : '' ?>>Manual</option>
                    </select>
                </div>
                
                <div class="col-md-4">
                    <label class="form-label">Nivel de Urgencia</label>
                    <select name="nivel_urgencia" class="form-select">
                        <option value="">Todos</option>
                        <option value="critica" <?= (isset($_GET['nivel_urgencia']) && $_GET['nivel_urgencia'] == 'critica') ? 'selected' : '' ?>>Crítica</option>
                        <option value="alta" <?= (isset($_GET['nivel_urgencia']) && $_GET['nivel_urgencia'] == 'alta') ? 'selected' : '' ?>>Alta</option>
                        <option value="media" <?= (isset($_GET['nivel_urgencia']) && $_GET['nivel_urgencia'] == 'media') ? 'selected' : '' ?>>Media</option>
                        <option value="baja" <?= (isset($_GET['nivel_urgencia']) && $_GET['nivel_urgencia'] == 'baja') ? 'selected' : '' ?>>Baja</option>
                    </select>
                </div>
                
                <div class="col-md-4">
                    <label class="form-label">Producto</label>
                    <select name="producto_id" class="form-select">
                        <option value="">Todos</option>
                        <?php foreach ($productos as $producto): ?>
                            <option value="<?= $producto['id_producto'] ?>" <?= (isset($_GET['producto_id']) && $_GET['producto_id'] == $producto['id_producto']) ? 'selected' : '' ?>>
                                <?= htmlspecialchars($producto['codigo'] . ' - ' . $producto['nombre']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                
                <div class="col-12 d-flex justify-content-end">
                    <button type="submit" class="btn btn-primary me-2">
                        <i class="fas fa-filter"></i> Filtrar
                    </button>
                    <a href="index.php?controller=alerta&action=index" class="btn btn-secondary">
                        <i class="fas fa-redo"></i>
                    </a>
                </div>
            </form>
        </div>
    </div>

    <!-- Lista de Alertas -->
    <div class="card">
        <div class="card-body">
            <?php if (empty($alertas)): ?>
                <div class="text-center py-5">
                    <i class="fas fa-check-circle fa-4x text-success mb-3"></i>
                    <h4>¡Excelente! No hay alertas pendientes</h4>
                    <p class="text-muted">El inventario se encuentra en buen estado.</p>
                </div>
            <?php else: ?>
                <div class="list-group">
                    <?php foreach ($alertas as $alerta): ?>
                        <div class="list-group-item list-group-item-action">
                            <div class="d-flex w-100 justify-content-between align-items-start">
                                <div class="flex-grow-1">
                                    <div class="d-flex align-items-center mb-2">
                                        <span class="badge 
                                            <?= $alerta['nivel_urgencia'] == 'critica' ? 'bg-danger' : 
                                               ($alerta['nivel_urgencia'] == 'alta' ? 'bg-warning' : 
                                               ($alerta['nivel_urgencia'] == 'media' ? 'bg-info' : 'bg-success')) ?> me-2">
                                            <?= ucfirst($alerta['nivel_urgencia']) ?>
                                        </span>
                                        <span class="badge bg-secondary me-2">
                                            <?= $alerta['tipo_alerta'] == 'stock_bajo' ? 'Stock Bajo' : 
                                               ($alerta['tipo_alerta'] == 'vencimiento' ? 'Vencimiento' : 'Manual') ?>
                                        </span>
                                        <h6 class="mb-0"><?= htmlspecialchars($alerta['producto_nombre']) ?></h6>
                                        <small class="text-muted ms-2">(<?= htmlspecialchars($alerta['producto_codigo']) ?>)</small>
                                    </div>
                                    
                                    <p class="mb-2"><?= htmlspecialchars($alerta['mensaje']) ?></p>
                                    
                                    <div class="d-flex align-items-center">
                                        <small class="text-muted me-3">
                                            <i class="fas fa-calendar me-1"></i>
                                            <?= date('d/m/Y H:i', strtotime($alerta['fecha_creacion'])) ?>
                                        </small>
                                        
                                        <?php if ($alerta['tipo_alerta'] == 'stock_bajo'): ?>
                                            <small class="text-danger me-3">
                                                <i class="fas fa-box me-1"></i>
                                                Stock: <?= $alerta['stock_actual'] ?> / Mín: <?= $alerta['producto_stock_minimo'] ?>
                                            </small>
                                        <?php endif; ?>
                                    </div>
                                </div>
                                
                                <div class="btn-group btn-group-sm">
                                    <?php if ($alerta['tipo_alerta'] == 'stock_bajo'): ?>
                                        <a href="index.php?controller=movimiento&action=entrada&id_producto=<?= $alerta['id_producto'] ?>" 
                                           class="btn btn-outline-success" title="Reponer Stock">
                                            <i class="fas fa-arrow-down"></i>
                                        </a>
                                    <?php endif; ?>
                                    
                                    <a href="index.php?controller=alerta&action=atender&id=<?= $alerta['id_alerta'] ?>" 
                                       class="btn btn-outline-primary" title="Marcar como Atendida"
                                       onclick="return confirm('¿Marcar esta alerta como atendida?')">
                                        <i class="fas fa-check"></i>
                                    </a>
                                    
                                    <a href="index.php?controller=alerta&action=ignorar&id=<?= $alerta['id_alerta'] ?>" 
                                       class="btn btn-outline-secondary" title="Ignorar"
                                       onclick="return confirm('¿Ignorar esta alerta?')">
                                        <i class="fas fa-times"></i>
                                    </a>
                                    
                                    <a href="index.php?controller=alerta&action=ver&id=<?= $alerta['id_alerta'] ?>" 
                                       class="btn btn-outline-info" title="Ver Detalles">
                                        <i class="fas fa-eye"></i>
                                    </a>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
                
                <!-- Paginación (simplificada) -->
                <nav class="mt-4">
                    <ul class="pagination justify-content-center">
                        <li class="page-item disabled">
                            <a class="page-link" href="#" tabindex="-1">Anterior</a>
                        </li>
                        <li class="page-item active"><a class="page-link" href="#">1</a></li>
                        <li class="page-item"><a class="page-link" href="#">2</a></li>
                        <li class="page-item"><a class="page-link" href="#">3</a></li>
                        <li class="page-item">
                            <a class="page-link" href="#">Siguiente</a>
                        </li>
                    </ul>
                </nav>
            <?php endif; ?>
        </div>
    </div>
</div>

<script>
function generarAlertasAutomaticas() {
    if (confirm('¿Generar alertas automáticas por stock bajo?')) {
        window.location.href = 'index.php?controller=alerta&action=generar_automaticas';
    }
}

// Actualizar estadísticas periódicamente
setInterval(function() {
    fetch('index.php?controller=alerta&action=estadisticas')
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                // Actualizar contadores en la interfaz
                console.log('Estadísticas actualizadas:', data.estadisticas);
            }
        })
        .catch(error => console.error('Error:', error));
}, 30000); // Cada 30 segundos
</script>

<?php include __DIR__ . '/../../layouts/footer.php'; ?>