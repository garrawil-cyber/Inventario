<?php
$currentPage = 'ventas';
require_once __DIR__ . '/../../../config/SessionMiddleware.php';

// ✅ startSession() ANTES del include del layout
SessionMiddleware::startSession();

include __DIR__ . '/../../layouts/inventario_layout.php';

// ✅ Usar el método oficial del middleware (clave correcta: 'sweetalert_message')
$sweetAlert = SessionMiddleware::getSweetAlert();
?>

<link rel="stylesheet" href="<?= BASE_URL ?>/views/inventario/ventas/css/crear.css">



<div class="vs-wrapper container-fluid px-3 py-3">

    <!-- Header -->
    <div class="vs-page-header">
        <div>
            <h1 class="vs-page-title">Nueva Venta</h1>
            <p class="vs-page-subtitle">Registrar una nueva transacción de venta</p>
        </div>
        <a href="index.php?controller=ventas&action=index" class="vs-btn-back">
            <i class="fas fa-arrow-left fa-xs"></i> Volver
        </a>
    </div>

    <div class="row g-4">

        <!-- COLUMNA IZQUIERDA -->
        <div class="col-lg-7">

            <!-- CLIENTE -->
            <div class="vs-card">
                <div class="vs-card-header"><i class="fas fa-user-circle"></i> Cliente</div>
                <div class="vs-card-body">
                    <label class="vs-label">Seleccionar cliente</label>
                    <select id="cliente_id" class="vs-input">
                        <option value="">— Cliente General (sin registro) —</option>
                        <?php foreach ($clientes as $cliente): ?>
                        <option value="<?= (int)$cliente['id_cliente'] ?>">
                            <?= htmlspecialchars($cliente['nombre_completo']) ?> — <?= htmlspecialchars($cliente['numero_documento']) ?>
                        </option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>

            <!-- COMPROBANTE -->
            <div class="vs-card">
                <div class="vs-card-header"><i class="fas fa-file-invoice"></i> Comprobante</div>
                <div class="vs-card-body">
                    <div class="row g-3">
                        <div class="col-md-4">
                            <label class="vs-label">Tipo</label>
                            <select id="tipo_comprobante" class="vs-input">
                                <?php foreach ($tiposComprobante as $tc): ?>
                                <option value="<?= htmlspecialchars($tc['valor']) ?>"><?= htmlspecialchars($tc['label']) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-3">
                            <label class="vs-label">Serie</label>
                            <input type="text" id="serie" class="vs-input" placeholder="B001" value="B001">
                        </div>
                        <div class="col-md-5">
                            <label class="vs-label">Número</label>
                            <div class="vs-input-group">
                                <input type="text" id="numero_comprobante" class="vs-input" placeholder="00000001" value="00000001">
                                <button type="button" id="btnGenerarNumero" class="vs-btn-generar" title="Generar número correlativo">
                                    <i class="fas fa-sync-alt"></i>
                                </button>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <label class="vs-label">Forma de Pago</label>
                            <select id="forma_pago" class="vs-input">
                                <?php foreach ($metodosPago as $mp): ?>
                                <option value="<?= htmlspecialchars($mp['valor']) ?>"><?= htmlspecialchars($mp['label']) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="vs-label">Descuento Global (S/)</label>
                            <div class="vs-input-group">
                                <span class="vs-addon">S/</span>
                                <input type="number" id="descuento_global" class="vs-input" value="0" step="0.01" min="0">
                            </div>
                        </div>
                        <div class="col-12">
                            <label class="vs-label">Observaciones</label>
                            <textarea id="observaciones" class="vs-input" rows="3" placeholder="Notas adicionales sobre la venta..."></textarea>
                        </div>
                    </div>
                </div>
            </div>

            <!-- BUSCAR PRODUCTOS -->
            <div class="vs-card">
                <div class="vs-card-header"><i class="fas fa-search"></i> Buscar Producto</div>
                <div class="vs-card-body">
                    <div class="vs-search-container">
                        <div class="vs-search-wrap">
                            <i class="fas fa-barcode vs-search-icon"></i>
                            <input type="text" id="buscar_producto" class="vs-input vs-search-input"
                                   placeholder="Código, nombre o SKU..." autocomplete="off">
                        </div>
                        <button type="button" id="btnBuscarTodos" class="vs-btn-secondary">
                            <i class="fas fa-th-large"></i> Ver Todos
                        </button>
                    </div>
                    <div id="resultados_busqueda" class="vs-search-results mt-3"></div>
                </div>
            </div>

        </div><!-- /col-lg-7 -->

        <!-- COLUMNA DERECHA: CARRITO -->
        <div class="col-lg-5">
            <div class="vs-sticky-card">
                <div class="vs-cart-header">
                    <div class="vs-cart-header-title">
                        <i class="fas fa-shopping-cart"></i> Carrito de Ventas
                    </div>
                    <span class="vs-cart-badge" id="items_count">0 items</span>
                </div>
                <div id="cart_items" class="vs-cart-items">
                    <div class="vs-empty-cart">
                        <div class="vs-empty-cart-icon"><i class="fas fa-box-open"></i></div>
                        <p>Sin productos agregados</p>
                        <small>Busque y seleccione productos</small>
                    </div>
                </div>
                <div class="vs-cart-footer">
                    <div class="vs-totals">
                        <div class="vs-total-row"><span>Subtotal</span><span id="subtotal">S/ 0.00</span></div>
                        <div class="vs-total-row"><span>IGV (18%)</span><span id="igv">S/ 0.00</span></div>
                        <div class="vs-total-row">
                            <span>Descuento</span>
                            <span id="descuento_mostrar" class="vs-discount-amount">S/ 0.00</span>
                        </div>
                        <div class="vs-total-final">
                            <span class="label">Total</span>
                            <span class="amount" id="total">S/ 0.00</span>
                        </div>
                    </div>
                    <div class="vs-cart-actions">
                        <button type="button" id="btnRegistrarVenta" class="vs-btn-primary btn-block">
                            <i class="fas fa-check-circle"></i> Registrar Venta
                        </button>
                        <button type="button" id="btnLimpiarCarrito" class="vs-btn-ghost btn-block">
                            <i class="fas fa-trash-alt"></i> Limpiar carrito
                        </button>
                    </div>
                </div>
            </div>
        </div><!-- /col-lg-5 -->

    </div><!-- /row -->
</div><!-- /vs-wrapper -->

<!-- ══════════════════════════════════════════════════════════
     ORDEN DE CARGA — CRÍTICO (no cambiar):
       1. crear.js   → define window.agregarProducto
       2. modal.js   → lo consume en seleccionarProductoModal()
       3. modal HTML → DOM del modal disponible para los listeners
     ══════════════════════════════════════════════════════════ -->
<script src="<?= BASE_URL ?>/views/inventario/ventas/js/crear.js"></script>
<script src="<?= BASE_URL ?>/views/inventario/ventas/js/modal.js"></script>

<?php include __DIR__ . '/modal_productos.php'; ?>

<?php include __DIR__ . '/../../layouts/footer.php'; ?>