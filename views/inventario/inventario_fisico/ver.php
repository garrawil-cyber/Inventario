<?php 
$currentPage = 'inventario_fisico';
include __DIR__ . '/../../layouts/inventario_layout.php'; 
?>

<link rel="stylesheet" href="<?= BASE_URL ?>/views/inventario/inventario_fisico/css/inventario_fisico.css">
<meta name="csrf-token" content="<?= htmlspecialchars($csrfToken ?? '') ?>">

<div class="container-fluid px-3 py-2">
    <!-- Encabezado -->
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h1 class="h5 fw-bold mb-0">
                <i class="fas fa-clipboard-list me-2" style="color:var(--if-primary);"></i>Inventario Físico
            </h1>
            <ol class="breadcrumb mb-0 mt-1">
                <li class="breadcrumb-item small"><a href="index.php?controller=dashboard&action=index">Dashboard</a></li>
                <li class="breadcrumb-item small"><a href="index.php?controller=inventario&action=index">Inventario Físico</a></li>
                <li class="breadcrumb-item small active"><?= htmlspecialchars($cabecera['codigo']) ?></li>
            </ol>
        </div>
        <div>
            <a href="index.php?controller=inventario&action=index" class="btn btn-sm btn-outline-secondary">
                <i class="fas fa-arrow-left me-1"></i>Volver
            </a>
            <a href="index.php?controller=inventario&action=exportar&id=<?= $cabecera['id_inventario'] ?>" 
               class="btn btn-sm btn-success ms-2">
                <i class="fas fa-file-excel me-1"></i>Exportar
            </a>
            <?php if ($cabecera['estado'] == 'planificado'): ?>
            <button id="btnAgregarProducto" class="btn btn-sm btn-primary ms-2" data-id="<?= $cabecera['id_inventario'] ?>">
                <i class="fas fa-plus me-1"></i>Agregar Producto
            </button>
            <?php endif; ?>
            <?php if ($cabecera['estado'] == 'planificado'): ?>
            <button id="btnIniciarConteo" class="btn btn-sm btn-warning ms-2" data-id="<?= $cabecera['id_inventario'] ?>">
                <i class="fas fa-play me-1"></i>Iniciar Conteo
            </button>
            <?php endif; ?>
            <?php if ($cabecera['estado'] == 'en_proceso'): ?>
            <button id="btnFinalizar" class="btn btn-sm btn-success ms-2" data-id="<?= $cabecera['id_inventario'] ?>">
                <i class="fas fa-check me-1"></i>Finalizar Inventario
            </button>
            <?php endif; ?>
            <?php if (in_array($cabecera['estado'], ['planificado', 'en_proceso'])): ?>
            <button id="btnCancelar" class="btn btn-sm btn-danger ms-2" data-id="<?= $cabecera['id_inventario'] ?>">
                <i class="fas fa-ban me-1"></i>Cancelar
            </button>
            <?php endif; ?>
        </div>
    </div>

    <!-- Información del inventario -->
    <div class="row mb-4">
        <div class="col-md-7">
            <div class="card shadow-sm">
                <div class="card-header bg-white">
                    <h5 class="mb-0"><i class="fas fa-info-circle me-2"></i>Información General</h5>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-4">
                            <small class="text-muted">Código</small>
                            <div><code><?= htmlspecialchars($cabecera['codigo']) ?></code></div>
                        </div>
                        <div class="col-md-4">
                            <small class="text-muted">Almacén</small>
                            <div><?= htmlspecialchars($cabecera['almacen_nombre']) ?></div>
                        </div>
                        <div class="col-md-4">
                            <small class="text-muted">Tipo</small>
                            <div><span class="badge bg-secondary"><?= ucfirst($cabecera['tipo']) ?></span></div>
                        </div>
                        <div class="col-md-4 mt-2">
                            <small class="text-muted">Estado</small>
                            <div>
                                <span class="badge-inv-<?= $cabecera['estado'] ?>">
                                    <?= ucfirst(str_replace('_', ' ', $cabecera['estado'])) ?>
                                </span>
                            </div>
                        </div>
                        <div class="col-md-4 mt-2">
                            <small class="text-muted">Fecha Inicio</small>
                            <div><?= $cabecera['fecha_inicio_fmt'] ?></div>
                        </div>
                        <div class="col-md-4 mt-2">
                            <small class="text-muted">Fecha Fin</small>
                            <div><?= $cabecera['fecha_fin_fmt'] ?? 'En curso' ?></div>
                        </div>
                        <div class="col-md-6 mt-2">
                            <small class="text-muted">Responsable</small>
                            <div><?= htmlspecialchars($cabecera['responsable']) ?></div>
                        </div>
                        <div class="col-md-6 mt-2">
                            <small class="text-muted">Registrado por</small>
                            <div><?= htmlspecialchars($cabecera['registrado_por']) ?></div>
                        </div>
                        <?php if (!empty($cabecera['observaciones'])): ?>
                        <div class="col-12 mt-2">
                            <small class="text-muted">Observaciones</small>
                            <div class="p-2 bg-light rounded"><?= nl2br(htmlspecialchars($cabecera['observaciones'])) ?></div>
                        </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="col-md-5">
            <div class="row g-3">
                <div class="col-6">
                    <div class="stat-card-inv p-3 position-relative">
                        <i class="fas fa-boxes stat-icon"></i>
                        <div class="stat-value"><?= number_format($stats['total_productos'] ?? 0) ?></div>
                        <div class="stat-label">Total Productos</div>
                    </div>
                </div>
                <div class="col-6">
                    <div class="stat-card-inv p-3 position-relative">
                        <i class="fas fa-exchange-alt stat-icon"></i>
                        <div class="stat-value text-danger"><?= number_format($stats['con_diferencia'] ?? 0) ?></div>
                        <div class="stat-label">Con Diferencia</div>
                        <small>Sobrantes: <?= $stats['sobrantes'] ?? 0 ?> | Faltantes: <?= $stats['faltantes'] ?? 0 ?></small>
                    </div>
                </div>
                <div class="col-12">
                    <div class="stat-card-inv p-3 position-relative">
                        <i class="fas fa-dollar-sign stat-icon"></i>
                        <div class="stat-value">S/ <?= number_format($stats['valor_diferencia_total'] ?? 0, 2) ?></div>
                        <div class="stat-label">Valor de Diferencias</div>
                        <small>Valor inventario físico: S/ <?= number_format($stats['valor_inventario_fisico'] ?? 0, 2) ?></small>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Resumen de diferencias (top 10) -->
    <?php if (!empty($topProductos)): ?>
    <div class="card shadow-sm mb-4">
        <div class="card-header bg-white">
            <h5 class="mb-0"><i class="fas fa-chart-line me-2"></i>Top 10 Productos con Mayor Diferencia</h5>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-sm mb-0">
                    <thead class="table-light">
                        <tr>
                            <th>Código</th>
                            <th>Producto</th>
                            <th class="text-center">Stock Sistema</th>
                            <th class="text-center">Stock Físico</th>
                            <th class="text-center">Diferencia</th>
                            <th class="text-end">Impacto Económico</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($topProductos as $prod): ?>
                        <tr>
                            <td><code><?= htmlspecialchars($prod['producto_codigo']) ?></code></td>
                            <td><?= htmlspecialchars($prod['producto_nombre']) ?></td>
                            <td class="text-center"><?= number_format($prod['stock_sistema']) ?></td>
                            <td class="text-center"><?= number_format($prod['stock_fisico']) ?></td>
                            <td class="text-center">
                                <span class="<?= $prod['diferencia'] > 0 ? 'diferencia-positiva' : ($prod['diferencia'] < 0 ? 'diferencia-negativa' : 'diferencia-cero') ?>">
                                    <?= ($prod['diferencia'] > 0 ? '+' : '') . number_format($prod['diferencia']) ?>
                                </span>
                            </td>
                            <td class="text-end">S/ <?= number_format($prod['impacto_economico'], 2) ?></td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
    <?php endif; ?>

    <!-- Detalle de productos -->
    <div class="card shadow-sm">
        <div class="card-header bg-white">
            <h5 class="mb-0"><i class="fas fa-list me-2"></i>Productos del Inventario</h5>
        </div>
        <div class="card-body p-0">
            <?php if (empty($detalle)): ?>
            <div class="text-center py-5">
                <i class="fas fa-box-open fa-3x text-muted mb-3 d-block"></i>
                <p class="text-muted">No hay productos agregados a este inventario.</p>
                <?php if ($cabecera['estado'] == 'planificado'): ?>
                <button class="btn btn-sm btn-primary" id="btnAgregarProductoEmpty" data-id="<?= $cabecera['id_inventario'] ?>">
                    <i class="fas fa-plus me-1"></i>Agregar primer producto
                </button>
                <?php endif; ?>
            </div>
            <?php else: ?>
            <div class="table-responsive">
                <table class="table table-hover table-productos-inv mb-0">
                    <thead>
                        <tr>
                            <th>Código</th>
                            <th>Producto</th>
                            <th>Categoría</th>
                            <th class="text-center">Stock Sistema</th>
                            <th class="text-center">Stock Físico</th>
                            <th class="text-center">Diferencia</th>
                            <th class="text-end">Valor Diferencia</th>
                            <th>Contado por</th>
                            <?php if ($cabecera['estado'] == 'planificado' || $cabecera['estado'] == 'en_proceso'): ?>
                            <th class="text-center">Acciones</th>
                            <?php endif; ?>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($detalle as $item): ?>
                        <tr>
                            <td><code><?= htmlspecialchars($item['producto_codigo']) ?></code></td>
                            <td>
                                <strong><?= htmlspecialchars($item['producto_nombre']) ?></strong>
                                <?php if (!empty($item['talla'])): ?>
                                <small class="text-muted d-block">Talla: <?= htmlspecialchars($item['talla']) ?></small>
                                <?php endif; ?>
                            </td>
                            <td><?= htmlspecialchars($item['categoria'] ?? '-') ?></td>
                            <td class="text-center"><?= number_format($item['stock_sistema']) ?></td>
                            <td class="text-center fw-bold"><?= number_format($item['stock_fisico']) ?></td>
                            <td class="text-center">
                                <span class="<?= $item['diferencia'] > 0 ? 'diferencia-positiva' : ($item['diferencia'] < 0 ? 'diferencia-negativa' : 'diferencia-cero') ?>">
                                    <?= ($item['diferencia'] > 0 ? '+' : '') . number_format($item['diferencia']) ?>
                                </span>
                            </td>
                            <td class="text-end">
                                S/ <?= number_format($item['valor_diferencia'] ?? 0, 2) ?>
                            </td>
                            <td>
                                <?= htmlspecialchars($item['contado_por'] ?? '-') ?>
                                <?php if (!empty($item['verificado_por'])): ?>
                                <small class="text-muted d-block">Verif: <?= htmlspecialchars($item['verificado_por']) ?></small>
                                <?php endif; ?>
                            </td>
                            <?php if ($cabecera['estado'] == 'planificado' || $cabecera['estado'] == 'en_proceso'): ?>
                            <td class="text-center">
                                <button class="btn btn-sm btn-outline-secondary btn-editar-producto" 
                                        data-id="<?= $item['id_detalle'] ?>"
                                        data-stock="<?= $item['stock_fisico'] ?>"
                                        title="Editar stock contado">
                                    <i class="fas fa-edit"></i>
                                </button>
                                <?php if ($cabecera['estado'] == 'planificado'): ?>
                                <button class="btn btn-sm btn-outline-danger btn-eliminar-producto ms-1"
                                        data-id="<?= $item['id_detalle'] ?>"
                                        data-producto="<?= htmlspecialchars($item['producto_nombre']) ?>"
                                        title="Eliminar producto">
                                    <i class="fas fa-trash-alt"></i>
                                </button>
                                <?php endif; ?>
                            </td>
                            <?php endif; ?>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<!-- MODAL: Agregar Producto al Inventario -->
<div class="modal fade" id="modalAgregarProducto" tabindex="-1" aria-labelledby="modalAgregarProductoLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content border-0 shadow-lg rounded-4">
            <div class="modal-header border-0 pb-0 pt-4 px-4">
                <h5 class="modal-title fw-bold" id="modalAgregarProductoLabel">
                    <i class="fas fa-plus-circle me-2" style="color:#0d9488;"></i>Agregar Producto al Inventario
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
            </div>
            <div class="modal-body px-4 py-3">
                <input type="hidden" id="ifIdInventario" value="<?= $cabecera['id_inventario'] ?>">
                
                <div class="mb-3">
                    <label class="form-label fw-semibold">Buscar producto</label>
                    <div class="input-group">
                        <span class="input-group-text bg-light border-end-0">
                            <i class="fas fa-search" style="color:#64748b;"></i>
                        </span>
                        <input type="text" id="ifBuscarProducto" class="form-control border-start-0 ps-0" placeholder="Nombre, código o SKU…" autocomplete="off">
                    </div>
                    <div id="ifResultadosProducto" style="border:1px solid #e2e8f0;border-radius:8px;max-height:220px;overflow-y:auto;margin-top:4px;background:#fff;display:none;"></div>
                </div>

                <div id="ifProductoSeleccionado" style="display:none; background:#f8fafc; border:1px solid #e2e8f0; border-radius:8px;" class="mb-3 p-3">
                    <div class="d-flex justify-content-between align-items-center mb-2">
                        <span class="fw-bold" id="ifModalNombre">—</span>
                        <span class="badge bg-light">Stock sistema: <strong id="ifModalStock">—</strong></span>
                    </div>
                    <input type="hidden" id="ifModalIdProducto" value="">
                </div>

                <div class="row g-3">
                    <div class="col-md-4">
                        <label class="form-label fw-semibold">Stock Físico Contado <span class="text-danger">*</span></label>
                        <input type="number" id="ifModalStockFisico" class="form-control text-center fw-bold" min="0" step="1" placeholder="0">
                    </div>
                    <div class="col-md-4">
                        <label class="form-label fw-semibold">Costo Unitario (S/)</label>
                        <input type="number" id="ifModalCosto" class="form-control" min="0" step="0.01" placeholder="0.00">
                    </div>
                    <div class="col-md-4">
                        <label class="form-label fw-semibold">Observaciones</label>
                        <input type="text" id="ifModalObs" class="form-control" placeholder="Opcional…" maxlength="255">
                    </div>
                </div>
            </div>
            <div class="modal-footer border-0 px-4 pb-4 pt-2">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                <button type="button" id="ifBtnGuardarDetalle" class="btn btn-primary">Agregar</button>
            </div>
        </div>
    </div>
</div>

<!-- ═══════════════════════════════════════════════════════════════════ -->
<!-- CSRF TOKEN - CRÍTICO: ESTE ES EL ÚNICO TOKEN QUE DEBE USAR EL JS   -->
<!-- ═══════════════════════════════════════════════════════════════════ -->
<input type="hidden" id="ifCsrfToken" value="<?= htmlspecialchars($csrfToken ?? '') ?>">

<!-- SweetAlert flash -->
<script>
const ifSweetAlert = <?= (isset($sweetAlert) && $sweetAlert) ? json_encode([
    'title' => $sweetAlert['title'] ?? 'Aviso',
    'text'  => $sweetAlert['text']  ?? ($sweetAlert['message'] ?? ''),
    'type'  => $sweetAlert['type']  ?? 'info',
]) : 'null' ?>;
</script>

<script src="<?= BASE_URL ?>/views/inventario/inventario_fisico/js/inventario_fisico.js"></script>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Verificar token CSRF
    const tokenInput = document.getElementById('ifCsrfToken');
    if (tokenInput && tokenInput.value) {
        console.log("✅ CSRF Token cargado en página:", tokenInput.value.substring(0, 30) + "...");
    } else {
        console.error("❌ CSRF Token NO encontrado en la página");
    }
    
    // Botón agregar primer producto
    const btnEmpty = document.getElementById('btnAgregarProductoEmpty');
    if (btnEmpty) {
        btnEmpty.addEventListener('click', function() {
            const btnOriginal = document.getElementById('btnAgregarProducto');
            if (btnOriginal) btnOriginal.click();
        });
    }
    
    // Observador resultados búsqueda
    const inputBuscar = document.getElementById('ifBuscarProducto');
    const resultsDiv = document.getElementById('ifResultadosProducto');
    if (inputBuscar && resultsDiv) {
        const observer = new MutationObserver(function() {
            resultsDiv.style.display = resultsDiv.innerHTML.trim() ? 'block' : 'none';
        });
        observer.observe(resultsDiv, { childList: true, subtree: true });
        
        window._origSeleccionarProducto = window.seleccionarProducto;
        window.seleccionarProducto = function(id, nombre, stock, costo) {
            if (window._origSeleccionarProducto) window._origSeleccionarProducto(id, nombre, stock, costo);
            const panel = document.getElementById('ifProductoSeleccionado');
            if (panel) panel.style.display = 'block';
            if (resultsDiv) resultsDiv.style.display = 'none';
        };
    }
});
</script>

<?php include __DIR__ . '/../../layouts/footer.php'; ?>