<?php 
$currentPage = 'ventas';
include __DIR__ . '/../../layouts/inventario_layout.php'; 
?>

<div class="container-fluid px-3 py-2">
    <!-- Encabezado -->
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h1 class="h5 fw-bold mb-0">
                <i class="fas fa-file-invoice me-2" style="color:var(--vt-primary);"></i>Detalle de Venta
            </h1>
            <ol class="breadcrumb mb-0 mt-1">
                <li class="breadcrumb-item small"><a href="index.php?controller=dashboard&action=index">Dashboard</a></li>
                <li class="breadcrumb-item small"><a href="index.php?controller=ventas&action=index">Ventas</a></li>
                <li class="breadcrumb-item small active">Ver Venta</li>
            </ol>
        </div>
        <div>
            <a href="index.php?controller=ventas&action=index" class="btn btn-sm btn-outline-secondary">
                <i class="fas fa-arrow-left me-1"></i>Volver
            </a>
        </div>
    </div>

    <div class="row">
        <div class="col-md-12">
            <div class="card shadow-sm">
                <div class="card-header bg-white">
                    <h5 class="mb-0">Venta #<?= htmlspecialchars($cabecera['codigo'] ?? 'N/A') ?></h5>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-6">
                            <h6>Información de la Venta</h6>
                            <table class="table table-sm">
                                <tr>
                                    <th>Fecha:</th>
                                    <td><?= date('d/m/Y H:i:s', strtotime($cabecera['fecha_venta'] ?? 'now')) ?></td>
                                </tr>
                                <tr>
                                    <th>Comprobante:</th>
                                    <td><?= strtoupper($cabecera['tipo_comprobante'] ?? 'N/A') ?> - <?= htmlspecialchars($cabecera['comprobante'] ?? '') ?></td>
                                </tr>
                                <tr>
                                    <th>Forma de Pago:</th>
                                    <td><?= ucfirst($cabecera['forma_pago'] ?? 'N/A') ?></td>
                                </tr>
                                <tr>
                                    <th>Estado:</th>
                                    <td>
                                        <span class="badge bg-<?= ($cabecera['estado'] ?? '') === 'completada' ? 'success' : 'danger' ?>">
                                            <?= ucfirst($cabecera['estado'] ?? 'N/A') ?>
                                        </span>
                                    </td>
                                </tr>
                            </table>
                        </div>
                        <div class="col-md-6">
                            <h6>Información del Cliente</h6>
                            <table class="table table-sm">
                                <tr>
                                    <th>Cliente:</th>
                                    <td><?= htmlspecialchars($cabecera['cliente_nombre'] ?? 'Cliente General') ?></td>
                                </tr>
                                <tr>
                                    <th>Documento:</th>
                                    <td><?= htmlspecialchars($cabecera['cliente_documento'] ?? 'N/A') ?></td>
                                </tr>
                                <tr>
                                    <th>Vendedor:</th>
                                    <td><?= htmlspecialchars($cabecera['vendedor_nombre'] ?? 'N/A') ?></td>
                                </tr>
                            </table>
                        </div>
                    </div>

                    <h6 class="mt-3">Productos</h6>
                    <div class="table-responsive">
                        <table class="table table-bordered">
                            <thead class="table-light">
                                <tr>
                                    <th>Producto</th>
                                    <th>Código</th>
                                    <th class="text-center">Cantidad</th>
                                    <th class="text-end">Precio</th>
                                    <th class="text-end">Subtotal</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($detalle as $item): ?>
                                <tr>
                                    <td><?= htmlspecialchars($item['nombre']) ?>
                                        <?php if ($item['talla']): ?>
                                            <small class="text-muted"> (Talla: <?= $item['talla'] ?>)</small>
                                        <?php endif; ?>
                                    </td>
                                    <td><?= htmlspecialchars($item['codigo']) ?></td>
                                    <td class="text-center"><?= number_format($item['cantidad']) ?></td>
                                    <td class="text-end">S/ <?= number_format($item['precio'], 2) ?></td>
                                    <td class="text-end">S/ <?= number_format($item['subtotal'], 2) ?></td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                            <tfoot>
                                <tr class="table-light">
                                    <th colspan="4" class="text-end">Subtotal:</th>
                                    <th class="text-end">S/ <?= number_format($cabecera['subtotal'] ?? 0, 2) ?></th>
                                </tr>
                                <tr class="table-light">
                                    <th colspan="4" class="text-end">IGV (18%):</th>
                                    <th class="text-end">S/ <?= number_format($cabecera['igv'] ?? 0, 2) ?></th>
                                </tr>
                                <tr class="table-light">
                                    <th colspan="4" class="text-end">Descuento:</th>
                                    <th class="text-end">S/ <?= number_format($cabecera['descuento'] ?? 0, 2) ?></th>
                                </tr>
                                <tr class="table-success">
                                    <th colspan="4" class="text-end">TOTAL:</th>
                                    <th class="text-end">S/ <?= number_format($cabecera['total'] ?? 0, 2) ?></th>
                                </tr>
                            </tfoot>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include __DIR__ . '/../../layouts/footer.php'; ?>