<?php 
$currentPage = 'alertas';
include __DIR__ . '/../../layouts/inventario_layout.php'; 
?>

<div class="container-fluid">
    <!-- Encabezado -->
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1 class="h3 mb-0">Crear Alerta Manual</h1>
        <div>
            <a href="index.php?controller=alerta&action=index" class="btn btn-secondary">
                <i class="fas fa-arrow-left me-2"></i>Volver
            </a>
        </div>
    </div>

    <!-- Formulario -->
    <div class="row">
        <div class="col-lg-8 mx-auto">
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0">Información de la Alerta</h5>
                </div>
                <div class="card-body">
                    <form method="post" action="index.php?controller=alerta&action=guardar">
                        <div class="row g-3">
                            <div class="col-md-6">
                                <label class="form-label">Producto <span class="text-danger">*</span></label>
                                <select name="id_producto" class="form-select" required>
                                    <option value="">Seleccione un producto</option>
                                    <?php foreach ($productos as $producto): ?>
                                        <option value="<?= $producto['id_producto'] ?>">
                                            <?= htmlspecialchars($producto['codigo'] . ' - ' . $producto['nombre']) ?>
                                            (Stock: <?= $producto['stock_actual'] ?>)
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            
                            <div class="col-md-6">
                                <label class="form-label">Tipo de Alerta <span class="text-danger">*</span></label>
                                <select name="tipo_alerta" class="form-select" required>
                                    <option value="">Seleccione tipo</option>
                                    <option value="stock_bajo">Stock Bajo</option>
                                    <option value="vencimiento">Vencimiento</option>
                                    <option value="calidad">Problema de Calidad</option>
                                    <option value="seguridad">Seguridad</option>
                                    <option value="otro">Otro</option>
                                </select>
                            </div>
                            
                            <div class="col-md-6">
                                <label class="form-label">Nivel de Urgencia <span class="text-danger">*</span></label>
                                <select name="nivel_urgencia" class="form-select" required>
                                    <option value="">Seleccione nivel</option>
                                    <option value="baja">Baja</option>
                                    <option value="media">Media</option>
                                    <option value="alta">Alta</option>
                                    <option value="critica">Crítica</option>
                                </select>
                            </div>
                            
                            <div class="col-12">
                                <label class="form-label">Mensaje <span class="text-danger">*</span></label>
                                <textarea name="mensaje" class="form-control" rows="4" required 
                                          placeholder="Describa la alerta..."></textarea>
                                <div class="form-text">Sea claro y específico en el mensaje de la alerta.</div>
                            </div>
                            
                            <div class="col-12 mt-4">
                                <div class="d-flex justify-content-end gap-2">
                                    <a href="index.php?controller=alerta&action=index" class="btn btn-secondary">
                                        Cancelar
                                    </a>
                                    <button type="submit" class="btn btn-primary">
                                        <i class="fas fa-bell me-2"></i>Crear Alerta
                                    </button>
                                </div>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
            
            <!-- Guía de niveles de urgencia -->
            <div class="card mt-3">
                <div class="card-header">
                    <h6 class="mb-0"><i class="fas fa-info-circle me-2"></i>Guía de Niveles de Urgencia</h6>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-3 mb-3">
                            <div class="border-start border-danger border-4 ps-3">
                                <h6 class="text-danger">Crítica</h6>
                                <small>Acción inmediata requerida. Ej: Stock agotado, producto defectuoso crítico.</small>
                            </div>
                        </div>
                        <div class="col-md-3 mb-3">
                            <div class="border-start border-warning border-4 ps-3">
                                <h6 class="text-warning">Alta</h6>
                                <small>Acción en 24 horas. Ej: Stock muy bajo, vencimiento próximo.</small>
                            </div>
                        </div>
                        <div class="col-md-3 mb-3">
                            <div class="border-start border-info border-4 ps-3">
                                <h6 class="text-info">Media</h6>
                                <small>Acción en 3 días. Ej: Stock bajo, observación de calidad.</small>
                            </div>
                        </div>
                        <div class="col-md-3 mb-3">
                            <div class="border-start border-success border-4 ps-3">
                                <h6 class="text-success">Baja</h6>
                                <small>Acción en 7 días. Ej: Stock por debajo del óptimo, mantenimiento programado.</small>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include __DIR__ . '/../../layouts/footer.php'; ?>