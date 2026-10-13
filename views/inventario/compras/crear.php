<?php 
$currentPage = 'compras';
include __DIR__ . '/../../layouts/inventario_layout.php'; 
?>

<link rel="stylesheet" href="<?= BASE_URL ?>assets/css/compras.css">

<div class="container-fluid px-3 py-2">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h1 class="h5 fw-bold mb-0">
                <i class="fas fa-plus-circle me-2" style="color:var(--vt-primary);"></i>Nueva Compra
            </h1>
            <ol class="breadcrumb mb-0 mt-1">
                <li class="breadcrumb-item small"><a href="index.php?controller=dashboard&action=index">Dashboard</a></li>
                <li class="breadcrumb-item small"><a href="index.php?controller=compra&action=index">Compras</a></li>
                <li class="breadcrumb-item small active">Nueva Compra</li>
            </ol>
        </div>
        <div>
            <a href="index.php?controller=compra&action=index" class="btn btn-sm btn-outline-secondary">
                <i class="fas fa-times me-1"></i>Cancelar
            </a>
        </div>
    </div>

    <form method="POST" action="index.php?controller=compra&action=guardar" id="compra-form">
        <input type="hidden" name="csrf_token" value="<?= $csrfToken ?>">
        <input type="hidden" id="subtotal" name="subtotal" value="0">
        <input type="hidden" id="igv" name="igv" value="0">
        <input type="hidden" id="total" name="total" value="0">

        <div class="row">
            <div class="col-md-7">
                <!-- Datos de la compra -->
                <div class="card shadow-sm mb-3">
                    <div class="card-header bg-white">
                        <h5 class="mb-0"><i class="fas fa-info-circle me-2"></i>Datos de la Compra</h5>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Proveedor *</label>
                                <select name="proveedor_id" id="proveedor_id" class="form-select" required>
                                    <option value="">Seleccione un proveedor</option>
                                    <?php foreach ($proveedores as $p): ?>
                                    <option value="<?= $p['id_proveedor'] ?>" <?= (($formData['proveedor_id'] ?? '') == $p['id_proveedor']) ? 'selected' : '' ?>>
                                        <?= htmlspecialchars($p['razon_social']) ?> (<?= $p['ruc'] ?>)
                                    </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="col-md-3 mb-3">
                                <label class="form-label">Fecha</label>
                                <input type="date" name="fecha" class="form-control" value="<?= htmlspecialchars($formData['fecha'] ?? date('Y-m-d')) ?>">
                            </div>
                            <div class="col-md-3 mb-3">
                                <label class="form-label">Código (opcional)</label>
                                <input type="text" name="codigo" class="form-control" placeholder="Auto-generado" value="<?= htmlspecialchars($formData['codigo'] ?? '') ?>">
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-4 mb-3">
                                <label class="form-label">Tipo Comprobante</label>
                                <select name="tipo_comprobante" class="form-select">
                                    <?php foreach ($tiposComprobante as $tc): ?>
                                    <option value="<?= $tc ?>" <?= (($formData['tipo_comprobante'] ?? 'factura') == $tc) ? 'selected' : '' ?>>
                                        <?= strtoupper($tc) ?>
                                    </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="col-md-4 mb-3">
                                <label class="form-label">Serie</label>
                                <input type="text" name="serie" class="form-control" placeholder="Ej: F001" value="<?= htmlspecialchars($formData['serie'] ?? '') ?>">
                            </div>
                            <div class="col-md-4 mb-3">
                                <label class="form-label">Número</label>
                                <input type="text" name="numero" class="form-control" placeholder="Ej: 000123" value="<?= htmlspecialchars($formData['numero'] ?? '') ?>">
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Forma de Pago</label>
                                <select name="forma_pago" class="form-select">
                                    <?php foreach ($formasPago as $fp): ?>
                                    <option value="<?= $fp ?>" <?= (($formData['forma_pago'] ?? 'credito') == $fp) ? 'selected' : '' ?>>
                                        <?= ucfirst($fp) ?>
                                    </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Estado de Pago</label>
                                <select name="estado_pago" class="form-select">
                                    <?php foreach ($estadosPago as $ep): ?>
                                    <option value="<?= $ep ?>" <?= (($formData['estado_pago'] ?? 'pendiente') == $ep) ? 'selected' : '' ?>>
                                        <?= ucfirst($ep) ?>
                                    </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Observaciones</label>
                            <textarea name="observaciones" class="form-control" rows="2" placeholder="Notas adicionales..."><?= htmlspecialchars($formData['observaciones'] ?? '') ?></textarea>
                        </div>
                    </div>
                </div>

                <!-- Productos -->
                <div class="card shadow-sm">
                    <div class="card-header bg-white d-flex justify-content-between align-items-center">
                        <h5 class="mb-0"><i class="fas fa-boxes me-2"></i>Productos</h5>
                        <button type="button" id="btnAgregarProducto" class="btn btn-sm btn-primary">
                            <i class="fas fa-plus me-1"></i>Agregar Producto
                        </button>
                    </div>
                    <div class="card-body">
                        <div id="productos-container">
                            <!-- Template oculto -->
                            <template id="producto-row-template">
                                <div class="producto-row" data-id="__INDEX__">
                                    <div class="row g-3">
                                        <div class="col-md-5">
                                            <label class="form-label small">Producto *</label>
                                            <select name="producto_id[__INDEX__]" class="form-select form-select-sm" required>
                                                <option value="">Seleccione...</option>
                                                <?php foreach ($productos as $prod): ?>
                                                <option value="<?= $prod['id_producto'] ?>"><?= htmlspecialchars($prod['codigo'] . ' - ' . $prod['nombre']) ?></option>
                                                <?php endforeach; ?>
                                            </select>
                                        </div>
                                        <div class="col-md-2">
                                            <label class="form-label small">Cantidad *</label>
                                            <input type="number" name="cantidad[__INDEX__]" class="form-control form-control-sm cantidad-input" step="1" min="1" value="1" required>
                                        </div>
                                        <div class="col-md-2">
                                            <label class="form-label small">Precio Unit. *</label>
                                            <input type="number" name="precio_unitario[__INDEX__]" class="form-control form-control-sm precio-input" step="0.01" min="0" value="0" required>
                                        </div>
                                        <div class="col-md-2">
                                            <label class="form-label small">Subtotal</label>
                                            <div class="item-subtotal fw-bold text-primary mt-2">S/ 0.00</div>
                                        </div>
                                        <div class="col-md-1 text-end">
                                            <button type="button" class="btn btn-sm btn-danger btn-remove-producto mt-4">
                                                <i class="fas fa-trash"></i>
                                            </button>
                                        </div>
                                    </div>
                                    <div class="row mt-2">
                                        <div class="col-md-4">
                                            <label class="form-label small">Lote</label>
                                            <input type="text" name="lote[__INDEX__]" class="form-control form-control-sm" placeholder="Opcional">
                                        </div>
                                        <div class="col-md-4">
                                            <label class="form-label small">Fecha Vencimiento</label>
                                            <input type="date" name="fecha_vencimiento[__INDEX__]" class="form-control form-control-sm">
                                        </div>
                                    </div>
                                </div>
                            </template>
                            
                            <!-- Productos existentes (si hay) -->
                            <?php if (!empty($formData['producto_id'])): ?>
                                <?php foreach ($formData['producto_id'] as $idx => $prodId): ?>
                                <div class="producto-row" data-id="<?= $idx ?>">
                                    <div class="row g-3">
                                        <div class="col-md-5">
                                            <select name="producto_id[<?= $idx ?>]" class="form-select form-select-sm" required>
                                                <option value="">Seleccione...</option>
                                                <?php foreach ($productos as $prod): ?>
                                                <option value="<?= $prod['id_producto'] ?>" <?= ($prodId == $prod['id_producto']) ? 'selected' : '' ?>>
                                                    <?= htmlspecialchars($prod['codigo'] . ' - ' . $prod['nombre']) ?>
                                                </option>
                                                <?php endforeach; ?>
                                            </select>
                                        </div>
                                        <div class="col-md-2">
                                            <input type="number" name="cantidad[<?= $idx ?>]" class="form-control form-control-sm cantidad-input" value="<?= $formData['cantidad'][$idx] ?? 1 ?>">
                                        </div>
                                        <div class="col-md-2">
                                            <input type="number" name="precio_unitario[<?= $idx ?>]" class="form-control form-control-sm precio-input" step="0.01" value="<?= $formData['precio_unitario'][$idx] ?? 0 ?>">
                                        </div>
                                        <div class="col-md-2">
                                            <div class="item-subtotal fw-bold text-primary mt-2">S/ 0.00</div>
                                        </div>
                                        <div class="col-md-1 text-end">
                                            <button type="button" class="btn btn-sm btn-danger btn-remove-producto mt-4">
                                                <i class="fas fa-trash"></i>
                                            </button>
                                        </div>
                                    </div>
                                    <div class="row mt-2">
                                        <div class="col-md-4">
                                            <input type="text" name="lote[<?= $idx ?>]" class="form-control form-control-sm" value="<?= htmlspecialchars($formData['lote'][$idx] ?? '') ?>">
                                        </div>
                                        <div class="col-md-4">
                                            <input type="date" name="fecha_vencimiento[<?= $idx ?>]" class="form-control form-control-sm" value="<?= $formData['fecha_vencimiento'][$idx] ?? '' ?>">
                                        </div>
                                    </div>
                                </div>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <!-- Producto por defecto -->
                                <div class="producto-row" data-id="0">
                                    <div class="row g-3">
                                        <div class="col-md-5">
                                            <select name="producto_id[0]" class="form-select form-select-sm" required>
                                                <option value="">Seleccione...</option>
                                                <?php foreach ($productos as $prod): ?>
                                                <option value="<?= $prod['id_producto'] ?>"><?= htmlspecialchars($prod['codigo'] . ' - ' . $prod['nombre']) ?></option>
                                                <?php endforeach; ?>
                                            </select>
                                        </div>
                                        <div class="col-md-2">
                                            <input type="number" name="cantidad[0]" class="form-control form-control-sm cantidad-input" value="1">
                                        </div>
                                        <div class="col-md-2">
                                            <input type="number" name="precio_unitario[0]" class="form-control form-control-sm precio-input" step="0.01" value="0">
                                        </div>
                                        <div class="col-md-2">
                                            <div class="item-subtotal fw-bold text-primary mt-2">S/ 0.00</div>
                                        </div>
                                        <div class="col-md-1 text-end">
                                            <button type="button" class="btn btn-sm btn-danger btn-remove-producto mt-4">
                                                <i class="fas fa-trash"></i>
                                            </button>
                                        </div>
                                    </div>
                                    <div class="row mt-2">
                                        <div class="col-md-4">
                                            <input type="text" name="lote[0]" class="form-control form-control-sm" placeholder="Opcional">
                                        </div>
                                        <div class="col-md-4">
                                            <input type="date" name="fecha_vencimiento[0]" class="form-control form-control-sm">
                                        </div>
                                    </div>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-md-5">
                <div class="resumen-compra sticky-top" style="top: 20px;">
                    <h6 class="text-white-50">RESUMEN DE LA COMPRA</h6>
                    <div class="mb-3">
                        <label class="form-label text-white-50 small">Descuento Global (S/)</label>
                        <input type="number" id="descuento_global" name="descuento_global" class="form-control form-control-sm bg-white" step="0.01" value="<?= $formData['descuento_global'] ?? 0 ?>">
                    </div>
                    <hr class="my-2 bg-white">
                    <h6>SUB TOTAL</h6>
                    <div class="valor" id="subtotal_display">S/ 0.00</div>
                    <hr class="my-2 bg-white">
                    <h6>IGV (18%)</h6>
                    <div class="valor" id="igv_display">S/ 0.00</div>
                    <hr class="my-2 bg-white">
                    <h6>DESCUENTO</h6>
                    <div class="valor" id="descuento_display">S/ 0.00</div>
                    <hr class="my-2 bg-white">
                    <h6>TOTAL</h6>
                    <div class="valor fs-2" id="total_display">S/ 0.00</div>
                    <hr class="my-3">
                    <button type="submit" class="btn btn-light w-100 fw-bold">
                        <i class="fas fa-save me-2"></i>Registrar Compra
                    </button>
                </div>
            </div>
        </div>
    </form>
</div>

<script src="<?= BASE_URL ?>assets/js/compras.js"></script>

<?php include __DIR__ . '/../../layouts/footer.php'; ?>