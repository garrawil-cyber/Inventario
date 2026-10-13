<?php 
$currentPage = 'compras';
include __DIR__ . '/../../layouts/inventario_layout.php'; 
?>

<link rel="stylesheet" href="<?= BASE_URL ?>assets/css/compras.css">

<div class="container-fluid px-3 py-2">
    <!-- Encabezado -->
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h1 class="h5 fw-bold mb-0">
                <i class="fas fa-shopping-cart me-2" style="color:var(--vt-primary);"></i>Detalle de Compra
            </h1>
            <ol class="breadcrumb mb-0 mt-1">
                <li class="breadcrumb-item small"><a href="index.php?controller=dashboard&action=index">Dashboard</a></li>
                <li class="breadcrumb-item small"><a href="index.php?controller=compra&action=index">Compras</a></li>
                <li class="breadcrumb-item small active">Ver Compra</li>
            </ol>
        </div>
        <div>
            <a href="index.php?controller=compra&action=index" class="btn btn-sm btn-outline-secondary">
                <i class="fas fa-arrow-left me-1"></i>Volver
            </a>
            <?php if ($cabecera['estado'] !== 'anulada'): ?>
            <button id="btnAnularCompra" data-id="<?= $cabecera['id'] ?>" class="btn btn-sm btn-danger ms-2">
                <i class="fas fa-ban me-1"></i>Anular Compra
            </button>
            <?php endif; ?>
        </div>
    </div>

    <div class="row">
        <div class="col-md-7">
            <div class="card shadow-sm">
                <div class="card-header bg-white">
                    <h5 class="mb-0">Información de la Compra</h5>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-6">
                            <table class="table table-sm">
                                <tr><th>Código:</th><td><code><?= htmlspecialchars($cabecera['codigo']) ?></code></td></tr>
                                <tr><th>Fecha:</th><td><?= date('d/m/Y H:i:s', strtotime($cabecera['fecha'])) ?></td></tr>
                                <tr><th>Comprobante:</th><td><?= strtoupper($cabecera['tipo_comprobante']) ?> <?= htmlspecialchars($cabecera['comprobante_completo']) ?></td></tr>
                                <tr><th>Forma de Pago:</th><td><?= ucfirst($cabecera['forma_pago']) ?></td></tr>
                            </table>
                        </div>
                        <div class="col-md-6">
                            <table class="table table-sm">
                                <tr><th>Estado:</th>
                                    <td>
                                        <span class="badge bg-<?= $cabecera['estado'] === 'completada' ? 'success' : ($cabecera['estado'] === 'anulada' ? 'danger' : 'warning') ?>">
                                            <?= ucfirst($cabecera['estado']) ?>
                                        </span>
                                    </td>
                                </tr>
                                <tr><th>Estado Pago:</th>
                                    <td>
                                        <span class="badge bg-<?= $cabecera['estado_pago'] === 'pagado' ? 'success' : ($cabecera['estado_pago'] === 'anulado' ? 'danger' : 'warning') ?>">
                                            <?= ucfirst($cabecera['estado_pago']) ?>
                                        </span>
                                    </td>
                                </tr>
                                <tr><th>Registrado por:</th><td><?= htmlspecialchars($cabecera['usuario_nombre']) ?></td></tr>
                            </table>
                        </div>
                    </div>
                </div>
            </div>

            <div class="card shadow-sm mt-3">
                <div class="card-header bg-white">
                    <h5 class="mb-0">Información del Proveedor</h5>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-6">
                            <table class="table table-sm">
                                <tr><th>Razón Social:</th><td><?= htmlspecialchars($cabecera['proveedor_nombre']) ?></td></tr>
                                <tr><th>RUC:</th><td><?= htmlspecialchars($cabecera['proveedor_ruc']) ?></td></tr>
                            </table>
                        </div>
                        <div class="col-md-6">
                            <table class="table table-sm">
                                <tr><th>Teléfono:</th><td><?= htmlspecialchars($cabecera['proveedor_telefono'] ?? 'N/A') ?></td></tr>
                                <tr><th>Email:</th><td><?= htmlspecialchars($cabecera['proveedor_email'] ?? 'N/A') ?></td></tr>
                            </table>
                        </div>
                    </div>
                    <?php if (!empty($cabecera['proveedor_direccion'])): ?>
                    <div class="mt-2">
                        <strong>Dirección:</strong> <?= htmlspecialchars($cabecera['proveedor_direccion']) ?>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <div class="col-md-5">
            <div class="resumen-compra">
                <h6>SUB TOTAL</h6>
                <div class="valor">S/ <?= number_format($cabecera['subtotal'], 2) ?></div>
                <hr class="my-2 bg-white">
                <h6>IGV (18%)</h6>
                <div class="valor">S/ <?= number_format($cabecera['igv'], 2) ?></div>
                <hr class="my-2 bg-white">
                <h6>DESCUENTO</h6>
                <div class="valor">S/ <?= number_format($cabecera['descuento'], 2) ?></div>
                <hr class="my-2 bg-white">
                <h6>TOTAL</h6>
                <div class="valor fs-2">S/ <?= number_format($cabecera['total'], 2) ?></div>
            </div>
        </div>
    </div>

    <!-- Detalle de productos -->
    <div class="card shadow-sm mt-4">
        <div class="card-header bg-white">
            <h5 class="mb-0"><i class="fas fa-boxes me-2"></i>Productos de la Compra</h5>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-bordered mb-0">
                    <thead class="table-light">
                        <tr>
                            <th>#</th>
                            <th>Código</th>
                            <th>Producto</th>
                            <th>Categoría</th>
                            <th class="text-center">Cantidad</th>
                            <th class="text-end">Precio Unit.</th>
                            <th class="text-end">Subtotal</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php $contador = 1; foreach ($detalle as $item): ?>
                        <tr class="detalle-producto">
                            <td><?= $contador++ ?></td>
                            <td><code><?= htmlspecialchars($item['producto_codigo']) ?></code></td>
                            <td>
                                <?= htmlspecialchars($item['producto_nombre']) ?>
                                <?php if ($item['lote']): ?>
                                <small class="text-muted d-block">Lote: <?= htmlspecialchars($item['lote']) ?></small>
                                <?php endif; ?>
                            </td>
                            <td><?= htmlspecialchars($item['categoria_nombre'] ?? '-') ?></td>
                            <td class="text-center"><?= number_format($item['cantidad']) ?></td>
                            <td class="text-end">S/ <?= number_format($item['precio_unitario'], 2) ?></td>
                            <td class="text-end">S/ <?= number_format($item['subtotal'], 2) ?></td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <?php if (!empty($cabecera['observaciones'])): ?>
    <div class="card shadow-sm mt-3">
        <div class="card-header bg-white">
            <h5 class="mb-0"><i class="fas fa-sticky-note me-2"></i>Observaciones</h5>
        </div>
        <div class="card-body">
            <p class="mb-0"><?= nl2br(htmlspecialchars($cabecera['observaciones'])) ?></p>
        </div>
    </div>
    <?php endif; ?>
</div>

<meta name="csrf-token" content="<?= SessionMiddleware::generateCSRFToken() ?>">
<script src="<?= BASE_URL ?>assets/js/compras.js"></script>

<?php include __DIR__ . '/../../layouts/footer.php'; ?>