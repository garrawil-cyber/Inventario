<?php
/**
 * Vista: Detalle de Proveedor
 * Variables: $proveedor, $totalProductos, $sweetAlert
 */

$currentPage = 'proveedores';
$proveedor = $proveedor ?? [];
$totalProductos = $totalProductos ?? 0;
?>

<head>
    <link rel="stylesheet" href="<?= BASE_URL ?>/views/inventario/proveedores/css/proveedores.css">
</head>
<body class="page-proveedores-ver">

<?php include __DIR__ . '/../../layouts/inventario_layout.php'; ?>

<div class="container-fluid py-4">
    <div class="row">
        <div class="col-12">
            
            <!-- Header -->
            <div class="d-flex justify-content-between align-items-center mb-4">
                <div>
                    <h1 class="display-6 fw-bold mb-1" style="font-size: 1.5rem;">
                        <i class="fas fa-building me-2" style="color: var(--pv-primary);"></i>
                        Detalle del Proveedor
                    </h1>
                    <p class="text-muted mb-0">Información completa del proveedor</p>
                </div>
                <div class="d-flex gap-2">
                    <a href="index.php?controller=proveedor&action=index" class="btn btn-outline-secondary">
                        <i class="fas fa-arrow-left me-2"></i>Volver
                    </a>
                    <a href="index.php?controller=proveedor&action=editar&id=<?= $proveedor['id_proveedor'] ?? 0 ?>" class="btn btn-primary">
                        <i class="fas fa-edit me-2"></i>Editar
                    </a>
                </div>
            </div>
            
            <div class="row">
                <!-- Columna Principal -->
                <div class="col-lg-8">
                    <!-- Información General -->
                    <div class="pv-card mb-4">
                        <div class="pv-card-header">
                            <i class="fas fa-info-circle me-2"></i>
                            Información General
                        </div>
                        <div class="pv-card-body">
                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <small class="text-muted d-block text-uppercase fw-bold">RUC</small>
                                    <h5><span class="ruc-chip"><?= htmlspecialchars($proveedor['ruc'] ?? '—') ?></span></h5>
                                </div>
                                <div class="col-md-6 mb-3">
                                    <small class="text-muted d-block text-uppercase fw-bold">Razón Social</small>
                                    <h5><?= htmlspecialchars($proveedor['razon_social'] ?? '—') ?></h5>
                                </div>
                                <?php if (!empty($proveedor['nombre_comercial'])): ?>
                                <div class="col-md-6 mb-3">
                                    <small class="text-muted d-block text-uppercase fw-bold">Nombre Comercial</small>
                                    <p class="mb-0"><?= htmlspecialchars($proveedor['nombre_comercial']) ?></p>
                                </div>
                                <?php endif; ?>
                                <div class="col-md-6 mb-3">
                                    <small class="text-muted d-block text-uppercase fw-bold">Estado</small>
                                    <span class="status-badge <?= ($proveedor['estado'] ?? '') === 'activo' ? 'status-active' : 'status-inactive' ?>">
                                        <?= ucfirst($proveedor['estado'] ?? 'inactivo') ?>
                                    </span>
                                </div>
                            </div>
                            
                            <div class="row">
                                <div class="col-md-12 mb-3">
                                    <small class="text-muted d-block text-uppercase fw-bold">Dirección</small>
                                    <p class="mb-0"><?= htmlspecialchars($proveedor['direccion'] ?? 'Sin dirección registrada') ?></p>
                                </div>
                            </div>
                            
                            <div class="row">
                                <div class="col-md-4 mb-3">
                                    <small class="text-muted d-block text-uppercase fw-bold">Ciudad</small>
                                    <p class="mb-0"><?= htmlspecialchars($proveedor['ciudad'] ?? '—') ?></p>
                                </div>
                                <div class="col-md-4 mb-3">
                                    <small class="text-muted d-block text-uppercase fw-bold">País</small>
                                    <p class="mb-0"><?= htmlspecialchars($proveedor['pais'] ?? '—') ?></p>
                                </div>
                                <div class="col-md-4 mb-3">
                                    <small class="text-muted d-block text-uppercase fw-bold">Tiempo Entrega</small>
                                    <p class="mb-0"><?= $proveedor['tiempo_entrega_dias'] ?? 0 ?> días</p>
                                </div>
                            </div>
                            
                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <small class="text-muted d-block text-uppercase fw-bold">Teléfono</small>
                                    <p class="mb-0"><?= htmlspecialchars($proveedor['telefono'] ?? '—') ?></p>
                                </div>
                                <div class="col-md-6 mb-3">
                                    <small class="text-muted d-block text-uppercase fw-bold">Email</small>
                                    <p class="mb-0"><?= htmlspecialchars($proveedor['email'] ?? '—') ?></p>
                                </div>
                            </div>
                            
                            <?php if (!empty($proveedor['condiciones_pago'])): ?>
                            <div class="row">
                                <div class="col-md-12">
                                    <small class="text-muted d-block text-uppercase fw-bold">Condiciones de Pago</small>
                                    <p class="mb-0"><?= htmlspecialchars($proveedor['condiciones_pago']) ?></p>
                                </div>
                            </div>
                            <?php endif; ?>
                        </div>
                    </div>
                    
                    <!-- Contacto -->
                    <div class="pv-card mb-4">
                        <div class="pv-card-header">
                            <i class="fas fa-user-tie me-2"></i>
                            Persona de Contacto
                        </div>
                        <div class="pv-card-body">
                            <div class="row">
                                <div class="col-md-4 mb-3">
                                    <small class="text-muted d-block text-uppercase fw-bold">Nombre</small>
                                    <p class="mb-0"><?= htmlspecialchars($proveedor['contacto_nombre'] ?? '—') ?></p>
                                </div>
                                <div class="col-md-4 mb-3">
                                    <small class="text-muted d-block text-uppercase fw-bold">Teléfono</small>
                                    <p class="mb-0"><?= htmlspecialchars($proveedor['contacto_telefono'] ?? '—') ?></p>
                                </div>
                                <div class="col-md-4 mb-3">
                                    <small class="text-muted d-block text-uppercase fw-bold">Email</small>
                                    <p class="mb-0"><?= htmlspecialchars($proveedor['contacto_email'] ?? '—') ?></p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                
                
                <!-- Columna Lateral - Estadísticas y Acciones -->
<div class="col-lg-4">
    
    <!-- ESTADÍSTICAS -->
    <div class="pv-card mb-4">
        <div class="pv-card-header">
            <i class="fas fa-chart-bar me-2"></i>
            Estadísticas
        </div>
        <div class="pv-card-body">
            <div class="row g-3">
                <!-- Productos -->
                <div class="col-6">
                    <div class="stat-card">
                        <div class="stat-number"><?= number_format($totalProductos) ?></div>
                        <div class="stat-label">
                            <i class="fas fa-boxes me-1"></i> Productos
                        </div>
                        <?php if ($totalProductos > 0): ?>
                            <small class="text-muted d-block mt-1">
                                <a href="index.php?controller=producto&action=index&proveedor=<?= $proveedor['id_proveedor'] ?>" 
                                   class="text-primary text-decoration-none small">
                                    Ver productos →
                                </a>
                            </small>
                        <?php endif; ?>
                    </div>
                </div>
                
                <!-- Movimientos -->
                <div class="col-6">
                    <div class="stat-card">
                        <div class="stat-number"><?= number_format($totalMovimientos) ?></div>
                        <div class="stat-label">
                            <i class="fas fa-exchange-alt me-1"></i> Movimientos
                        </div>
                        <?php if ($totalMovimientos > 0): ?>
                            <small class="text-muted d-block mt-1">
                                <a href="index.php?controller=compras&action=index&proveedor=<?= $proveedor['id_proveedor'] ?>" 
                                   class="text-primary text-decoration-none small">
                                    Ver compras →
                                </a>
                            </small>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
            
            <!-- Valor Compras -->
            <div class="mt-3 p-3 text-center" style="background: #f8fafc; border-radius: 12px;">
                <small class="text-muted d-block text-uppercase fw-bold mb-1">Valor Compras</small>
                <h4 class="mb-0" style="color: #059669; font-weight: 800;">
                    S/ <?= number_format($valorCompras, 2) ?>
                </h4>
            </div>
        </div>
    </div>
    
    <!-- ACCIONES RÁPIDAS -->
    <div class="pv-card">
        <div class="pv-card-header">
            <i class="fas fa-bolt me-2"></i>
            Acciones Rápidas
        </div>
        <div class="pv-card-body">
            <div class="d-grid gap-2">
                <a href="index.php?controller=movimiento&action=entrada&proveedor=<?= $proveedor['id_proveedor'] ?? 0 ?>" 
                   class="btn btn-outline-success">
                    <i class="fas fa-arrow-down me-2"></i>Registrar Compra
                </a>
                <a href="index.php?controller=producto&action=index&proveedor=<?= $proveedor['id_proveedor'] ?? 0 ?>" 
                   class="btn btn-outline-primary">
                    <i class="fas fa-boxes me-2"></i>Ver Productos
                </a>
                <a href="index.php?controller=compras&action=index&proveedor=<?= $proveedor['id_proveedor'] ?? 0 ?>" 
                   class="btn btn-outline-info">
                    <i class="fas fa-history me-2"></i>Historial de Compras
                </a>
            </div>
        </div>
    </div>
    
</div>


            </div>
            
        </div>
    </div>
</div>
</body>

<?php include __DIR__ . '/../../layouts/footer.php'; ?>

<script src="<?= BASE_URL ?>/views/inventario/proveedores/js/proveedores.js"></script>


