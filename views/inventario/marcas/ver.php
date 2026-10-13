<?php 
$currentPage = 'marcas';
include __DIR__ . '/../../layouts/inventario_layout.php'; 
?>

<style>
.detail-card {
    background: #fff;
    border-radius: 12px;
    border: 1px solid #e2e8f0;
    margin-bottom: 1rem;
}
.detail-header {
    padding: 0.75rem 1rem;
    border-bottom: 1px solid #e2e8f0;
    background: #f8fafc;
    font-weight: 600;
    font-size: 0.8rem;
}
.detail-body {
    padding: 1rem;
}
.info-row {
    display: flex;
    padding: 0.5rem 0;
    border-bottom: 1px solid #f1f5f9;
}
.info-label {
    width: 120px;
    font-size: 0.7rem;
    font-weight: 600;
    color: #64748b;
    text-transform: uppercase;
    letter-spacing: 0.05em;
}
.info-value {
    flex: 1;
    font-size: 0.8rem;
    color: #0f172a;
}
.logo-large {
    width: 120px;
    height: 120px;
    border-radius: 16px;
    object-fit: cover;
    border: 1px solid #e2e8f0;
    background: #f8fafc;
}
</style>

<div class="container-fluid px-3 py-2">

    <!-- Encabezado -->
    <div class="d-flex justify-content-between align-items-center mb-3">
        <div>
            <h1 class="h5 fw-bold mb-0">
                <i class="fas fa-trademark me-2" style="color:#4f46e5;"></i>Detalle de Marca
            </h1>
            <ol class="breadcrumb mb-0 mt-1">
                <li class="breadcrumb-item small"><a href="index.php?controller=dashboard&action=index">Dashboard</a></li>
                <li class="breadcrumb-item small"><a href="index.php?controller=marca&action=index">Marcas</a></li>
                <li class="breadcrumb-item small active"><?= htmlspecialchars($marca['nombre']) ?></li>
            </ol>
        </div>
        <div class="d-flex gap-2">
            <a href="index.php?controller=marca&action=editar&id=<?= $marca['id_marca'] ?>" 
               class="btn btn-sm" style="background:#4f46e5; color:#fff;">
                <i class="fas fa-edit me-1"></i>Editar
            </a>
            <a href="index.php?controller=marca&action=index" class="btn btn-sm btn-outline-secondary">
                <i class="fas fa-arrow-left me-1"></i>Volver
            </a>
        </div>
    </div>

    <div class="row">
        <div class="col-md-4">
            <div class="detail-card text-center">
                <div class="detail-header">
                    <i class="fas fa-image me-2"></i>Logo
                </div>
                <div class="detail-body">
                    <?php if (!empty($marca['logo_url'])): ?>
                        <img src="<?= htmlspecialchars($marca['logo_url']) ?>" class="logo-large mb-3">
                    <?php else: ?>
                        <div class="logo-large d-flex align-items-center justify-content-center mx-auto mb-3 bg-light">
                            <i class="fas fa-building fa-3x text-muted"></i>
                        </div>
                    <?php endif; ?>
                    <div class="small text-muted">
                        <i class="fas fa-calendar-alt me-1"></i>
                        Registrada: <?= date('d/m/Y', strtotime($marca['fecha_creacion'] ?? 'now')) ?>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-md-8">
            <div class="detail-card">
                <div class="detail-header">
                    <i class="fas fa-info-circle me-2"></i>Información de la Marca
                </div>
                <div class="detail-body">
                    <div class="info-row">
                        <div class="info-label">Nombre</div>
                        <div class="info-value fw-semibold"><?= htmlspecialchars($marca['nombre']) ?></div>
                    </div>
                    <div class="info-row">
                        <div class="info-label">Descripción</div>
                        <div class="info-value"><?= nl2br(htmlspecialchars($marca['descripcion'] ?? '—')) ?></div>
                    </div>
                    <div class="info-row">
                        <div class="info-label">Sitio Web</div>
                        <div class="info-value">
                            <?php if (!empty($marca['sitio_web'])): ?>
                                <a href="<?= htmlspecialchars($marca['sitio_web']) ?>" target="_blank">
                                    <?= htmlspecialchars($marca['sitio_web']) ?>
                                    <i class="fas fa-external-link-alt ms-1 small"></i>
                                </a>
                            <?php else: ?>
                                —
                            <?php endif; ?>
                        </div>
                    </div>
                    <div class="info-row">
                        <div class="info-label">Estado</div>
                        <div class="info-value">
                            <span class="status-badge status-<?= $marca['estado'] ?>">
                                <?= ucfirst($marca['estado']) ?>
                            </span>
                        </div>
                    </div>
                </div>
            </div>

            <div class="detail-card">
                <div class="detail-header">
                    <i class="fas fa-chart-line me-2"></i>Estadísticas
                </div>
                <div class="detail-body">
                    <div class="row text-center">
                        <div class="col-6">
                            <div class="p-3" style="background:#f8fafc; border-radius:10px;">
                                <div class="h4 fw-bold" style="color:#4f46e5;"><?= $totalProductos ?></div>
                                <div class="small text-muted">Productos Asociados</div>
                            </div>
                        </div>
                        <div class="col-6">
                            <div class="p-3" style="background:#f8fafc; border-radius:10px;">
                                <div class="h4 fw-bold text-muted">—</div>
                                <div class="small text-muted">Próximamente</div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include __DIR__ . '/../../layouts/footer.php'; ?>