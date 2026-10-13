<?php 
$currentPage = 'alertas';
include __DIR__ . '/../../layouts/inventario_layout.php'; 
?>

<div class="container-fluid">
    <!-- Encabezado -->
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1 class="h3 mb-0">Detalle de Alerta</h1>
        <div>
            <a href="index.php?controller=alerta&action=index" class="btn btn-secondary me-2">
                <i class="fas fa-arrow-left me-2"></i>Volver
            </a>
            <?php if ($alerta['estado'] == 'activa'): ?>
                <a href="index.php?controller=alerta&action=atender&id=<?= $alerta['id_alerta'] ?>" 
                   class="btn btn-success me-2" onclick="return confirm('¿Marcar como atendida?')">
                    <i class="fas fa-check me-2"></i>Marcar como Atendida
                </a>
            <?php endif; ?>
        </div>
    </div>

    <!-- Información Principal -->
    <div class="row">
        <!-- Columna izquierda: Detalles de la alerta -->
        <div class="col-lg-8">
            <div class="card mb-4">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h5 class="mb-0">Información de la Alerta</h5>
                    <span class="badge 
                        <?= $alerta['nivel_urgencia'] == 'critica' ? 'bg-danger' : 
                           ($alerta['nivel_urgencia'] == 'alta' ? 'bg-warning' : 
                           ($alerta['nivel_urgencia'] == 'media' ? 'bg-info' : 'bg-success')) ?>">
                        <?= strtoupper($alerta['nivel_urgencia']) ?>
                    </span>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <small class="text-muted d-block">Producto</small>
                            <h5><?= htmlspecialchars($alerta['producto_nombre']) ?></h5>
                            <small class="text-muted">Código: <?= htmlspecialchars($alerta['producto_codigo']) ?></small>
                        </div>
                        <div class="col-md-6 mb-3">
                            <small class="text-muted d-block">Tipo de Alerta</small>
                            <span class="badge bg-secondary">
                                <?= $alerta['tipo_alerta'] == 'stock_bajo' ? 'Stock Bajo' : 
                                   ($alerta['tipo_alerta'] == 'vencimiento' ? 'Vencimiento' : 'Manual') ?>
                            </span>
                        </div>
                    </div>
                    
                    <div class="mb-4">
                        <small class="text-muted d-block">Mensaje</small>
                        <div class="alert 
                            <?= $alerta['nivel_urgencia'] == 'critica' ? 'alert-danger' : 
                               ($alerta['nivel_urgencia'] == 'alta' ? 'alert-warning' : 
                               ($alerta['nivel_urgencia'] == 'media' ? 'alert-info' : 'alert-success')) ?>">
                            <p class="mb-0"><?= nl2br(htmlspecialchars($alerta['mensaje'])) ?></p>
                        </div>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <small class="text-muted d-block">Estado</small>
                            <span class="badge 
                                <?= $alerta['estado'] == 'activa' ? 'bg-warning' : 
                                   ($alerta['estado'] == 'atendida' ? 'bg-success' : 'bg-secondary') ?>">
                                <?= $alerta['estado'] == 'activa' ? 'Activa' : 
                                   ($alerta['estado'] == 'atendida' ? 'Atendida' : 'Ignorada') ?>
                            </span>
                        </div>
                        <div class="col-md-6 mb-3">
                            <small class="text-muted d-block">Fecha de Creación</small>
                            <p class="mb-0"><?= date('d/m/Y H:i', strtotime($alerta['fecha_creacion'])) ?></p>
                        </div>
                    </div>
                    
                    <?php if ($alerta['estado'] == 'atendida'): ?>
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <small class="text-muted d-block">Atendida por</small>
                                <p class="mb-0"><?= htmlspecialchars($alerta['atendido_por_nombre'] ?? 'Usuario') ?></p>
                            </div>
                            <div class="col-md-6 mb-3">
                                <small class="text-muted d-block">Fecha de Atención</small>
                                <p class="mb-0"><?= date('d/m/Y H:i', strtotime($alerta['fecha_atencion'])) ?></p>
                            </div>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
            
            <!-- Información del Producto -->
            <div class="card">
                <div class="card-header">
                    <h6 class="mb-0">Información del Producto</h6>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-4 mb-3">
                            <small class="text-muted d-block">Stock Actual</small>
                            <h4 class="<?= $alerta['stock_actual'] <= $alerta['producto_stock_minimo'] ? 'text-danger' : 'text-success' ?>">
                                <?= $alerta['stock_actual'] ?>
                            </h4>
                        </div>
                        <div class="col-md-4 mb-3">
                            <small class="text-muted d-block">Stock Mínimo</small>
                            <h4><?= $alerta['producto_stock_minimo'] ?></h4>
                        </div>
                        <div class="col-md-4 mb-3">
                            <small class="text-muted d-block">Diferencia</small>
                            <h4 class="text-danger">-<?= max(0, $alerta['producto_stock_minimo'] - $alerta['stock_actual']) ?></h4>
                        </div>
                    </div>
                    
                    <div class="mb-3">
                        <div class="d-flex justify-content-between mb-1">
                            <span>Stock Actual: <?= $alerta['stock_actual'] ?></span>
                            <span>Stock Mínimo: <?= $alerta['producto_stock_minimo'] ?></span>
                        </div>
                        <div class="progress" style="height: 15px;">
                            <?php
                            $porcentaje = ($alerta['stock_actual'] / $alerta['producto_stock_minimo']) * 100;
                            $claseProgreso = $porcentaje < 30 ? 'bg-danger' : 
                                            ($porcentaje < 50 ? 'bg-warning' : 'bg-success');
                            ?>
                            <div class="progress-bar <?= $claseProgreso ?>" 
                                 style="width: <?= min($porcentaje, 100) ?>%">
                                <span class="progress-text"><?= number_format($porcentaje, 1) ?>%</span>
                            </div>
                        </div>
                    </div>
                    
                    <div class="alert alert-warning">
                        <i class="fas fa-exclamation-triangle me-2"></i>
                        El producto está al <strong><?= number_format($porcentaje, 1) ?>%</strong> de su stock mínimo.
                        <?php if ($porcentaje < 100): ?>
                            Se requieren <strong><?= max(0, $alerta['producto_stock_minimo'] - $alerta['stock_actual']) ?></strong> unidades adicionales.
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Columna derecha: Acciones -->
        <div class="col-lg-4">
            <!-- Acciones -->
            <div class="card mb-4">
                <div class="card-header">
                    <h6 class="mb-0">Acciones</h6>
                </div>
                <div class="card-body">
                    <div class="d-grid gap-2">
                        <?php if ($alerta['estado'] == 'activa'): ?>
                            <a href="index.php?controller=movimiento&action=entrada&id_producto=<?= $alerta['id_producto'] ?>" 
                               class="btn btn-success">
                                <i class="fas fa-arrow-down me-2"></i>Reponer Stock
                            </a>
                            
                            <a href="index.php?controller=alerta&action=atender&id=<?= $alerta['id_alerta'] ?>" 
                               class="btn btn-primary"
                               onclick="return confirm('¿Marcar esta alerta como atendida?')">
                                <i class="fas fa-check me-2"></i>Marcar como Atendida
                            </a>
                            
                            <a href="index.php?controller=alerta&action=ignorar&id=<?= $alerta['id_alerta'] ?>" 
                               class="btn btn-secondary"
                               onclick="return confirm('¿Ignorar esta alerta?')">
                                <i class="fas fa-times me-2"></i>Ignorar Alerta
                            </a>
                        <?php endif; ?>
                        
                        <a href="index.php?controller=producto&action=ver&id=<?= $alerta['id_producto'] ?>" 
                           class="btn btn-info">
                            <i class="fas fa-eye me-2"></i>Ver Producto
                        </a>
                        
                        <a href="index.php?controller=kardex&action=producto&id_producto=<?= $alerta['id_producto'] ?>" 
                           class="btn btn-warning">
                            <i class="fas fa-book me-2"></i>Ver Kardex
                        </a>
                    </div>
                </div>
            </div>
            
            <!-- Historial de Alertas para este Producto -->
            <div class="card">
                <div class="card-header">
                    <h6 class="mb-0">Alertas Recientes para este Producto</h6>
                </div>
                <div class="card-body">
                    <div class="list-group list-group-flush">
                        <?php for ($i = 1; $i <= 3; $i++): ?>
                            <div class="list-group-item px-0">
                                <div class="d-flex justify-content-between">
                                    <div>
                                        <small class="text-muted"><?= date('d/m/Y', strtotime("-$i days")) ?></small>
                                        <div>Stock bajo</div>
                                    </div>
                                    <span class="badge bg-warning">Activa</span>
                                </div>
                            </div>
                        <?php endfor; ?>
                    </div>
                    <div class="text-center mt-2">
                        <a href="index.php?controller=alerta&action=index&producto_id=<?= $alerta['id_producto'] ?>" 
                           class="btn btn-sm btn-outline-primary">
                            Ver Todas
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Comentarios o Historial -->
    <?php if ($alerta['estado'] == 'atendida'): ?>
        <div class="card mt-4">
            <div class="card-header">
                <h6 class="mb-0">Historial de Atención</h6>
            </div>
            <div class="card-body">
                <div class="timeline">
                    <div class="timeline-item">
                        <div class="timeline-marker bg-success"></div>
                        <div class="timeline-content">
                            <h6 class="mb-1">Alerta Atendida</h6>
                            <small class="text-muted"><?= date('d/m/Y H:i', strtotime($alerta['fecha_atencion'])) ?></small>
                            <p class="mb-0">Alerta marcada como atendida por <?= htmlspecialchars($alerta['atendido_por_nombre'] ?? 'Usuario') ?>.</p>
                        </div>
                    </div>
                    <div class="timeline-item">
                        <div class="timeline-marker bg-primary"></div>
                        <div class="timeline-content">
                            <h6 class="mb-1">Alerta Creada</h6>
                            <small class="text-muted"><?= date('d/m/Y H:i', strtotime($alerta['fecha_creacion'])) ?></small>
                            <p class="mb-0">Alerta creada automáticamente por el sistema.</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    <?php endif; ?>
</div>

<style>
.timeline {
    position: relative;
    padding-left: 30px;
}

.timeline-item {
    position: relative;
    margin-bottom: 20px;
}

.timeline-marker {
    position: absolute;
    left: -30px;
    width: 12px;
    height: 12px;
    border-radius: 50%;
    top: 5px;
}

.timeline-content {
    padding-left: 10px;
}

.progress-text {
    position: absolute;
    right: 10px;
    color: white;
    font-weight: bold;
    text-shadow: 1px 1px 2px rgba(0,0,0,0.5);
}
</style>

<?php include __DIR__ . '/../../layouts/footer.php'; ?>