<?php 
$currentPage = 'compras';
include __DIR__ . '/../../layouts/inventario_layout.php'; 
?>

<link rel="stylesheet" href="<?= BASE_URL ?>assets/css/compras.css">

<div class="container-fluid px-3 py-2">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h1 class="h5 fw-bold mb-0">
                <i class="fas fa-edit me-2" style="color:var(--vt-primary);"></i>Editar Compra
            </h1>
            <ol class="breadcrumb mb-0 mt-1">
                <li class="breadcrumb-item small"><a href="index.php?controller=dashboard&action=index">Dashboard</a></li>
                <li class="breadcrumb-item small"><a href="index.php?controller=compra&action=index">Compras</a></li>
                <li class="breadcrumb-item small active">Editar Compra</li>
            </ol>
        </div>
        <div>
            <a href="index.php?controller=compra&action=ver&id=<?= $cabecera['id'] ?>" class="btn btn-sm btn-outline-secondary">
                <i class="fas fa-arrow-left me-1"></i>Volver
            </a>
        </div>
    </div>

    <div class="row">
        <div class="col-md-12">
            <div class="card shadow-sm">
                <div class="card-header bg-white">
                    <h5 class="mb-0">Editar Compra #<?= htmlspecialchars($cabecera['codigo']) ?></h5>
                    <small class="text-muted">Solo se pueden editar campos no críticos (comprobante, pago, observaciones)</small>
                </div>
                <div class="card-body">
                    <form method="POST" action="index.php?controller=compra&action=actualizar">
                        <input type="hidden" name="csrf_token" value="<?= $csrfToken ?>">
                        <input type="hidden" name="id_compra" value="<?= $cabecera['id'] ?>">

                        <div class="row">
                            <div class="col-md-4 mb-3">
                                <label class="form-label">Tipo Comprobante</label>
                                <select name="tipo_comprobante" class="form-select">
                                    <?php foreach ($tiposComprobante as $tc): ?>
                                    <option value="<?= $tc ?>" <?= ($cabecera['tipo_comprobante'] == $tc) ? 'selected' : '' ?>>
                                        <?= strtoupper($tc) ?>
                                    </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="col-md-4 mb-3">
                                <label class="form-label">Serie</label>
                                <input type="text" name="serie" class="form-control" value="<?= htmlspecialchars($cabecera['serie'] ?? '') ?>">
                            </div>
                            <div class="col-md-4 mb-3">
                                <label class="form-label">Número</label>
                                <input type="text" name="numero" class="form-control" value="<?= htmlspecialchars($cabecera['numero'] ?? '') ?>">
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Forma de Pago</label>
                                <select name="forma_pago" class="form-select">
                                    <?php foreach ($formasPago as $fp): ?>
                                    <option value="<?= $fp ?>" <?= ($cabecera['forma_pago'] == $fp) ? 'selected' : '' ?>>
                                        <?= ucfirst($fp) ?>
                                    </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Estado de Pago</label>
                                <select name="estado_pago" class="form-select">
                                    <?php foreach ($estadosPago as $ep): ?>
                                    <option value="<?= $ep ?>" <?= ($cabecera['estado_pago'] == $ep) ? 'selected' : '' ?>>
                                        <?= ucfirst($ep) ?>
                                    </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Observaciones</label>
                            <textarea name="observaciones" class="form-control" rows="3" placeholder="Notas adicionales..."><?= htmlspecialchars($cabecera['observaciones'] ?? '') ?></textarea>
                            <small class="text-muted">Las nuevas observaciones se agregarán al final de las existentes.</small>
                        </div>

                        <div class="alert alert-warning">
                            <i class="fas fa-exclamation-triangle me-2"></i>
                            <strong>Nota:</strong> No se pueden modificar los productos, cantidades ni precios de una compra ya registrada. 
                            Si necesita corregir estos datos, anule esta compra y cree una nueva.
                        </div>

                        <div class="text-end">
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-save me-1"></i>Guardar Cambios
                            </button>
                            <a href="index.php?controller=compra&action=ver&id=<?= $cabecera['id'] ?>" class="btn btn-secondary">
                                <i class="fas fa-times me-1"></i>Cancelar
                            </a>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <!-- Información de referencia -->
    <div class="card shadow-sm mt-3">
        <div class="card-header bg-white">
            <h6 class="mb-0">Información de referencia</h6>
        </div>
        <div class="card-body">
            <div class="row">
                <div class="col-md-3">
                    <small class="text-muted">Proveedor:</small><br>
                    <strong><?= htmlspecialchars($cabecera['proveedor_nombre']) ?></strong>
                </div>
                <div class="col-md-3">
                    <small class="text-muted">Fecha de compra:</small><br>
                    <strong><?= date('d/m/Y', strtotime($cabecera['fecha'])) ?></strong>
                </div>
                <div class="col-md-3">
                    <small class="text-muted">Total:</small><br>
                    <strong class="text-success">S/ <?= number_format($cabecera['total'], 2) ?></strong>
                </div>
                <div class="col-md-3">
                    <small class="text-muted">Registrado por:</small><br>
                    <strong><?= htmlspecialchars($cabecera['usuario_nombre']) ?></strong>
                </div>
            </div>
        </div>
    </div>
</div>

<script src="<?= BASE_URL ?>assets/js/compras.js"></script>

<?php include __DIR__ . '/../../layouts/footer.php'; ?>