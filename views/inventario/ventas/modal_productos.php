<?php
/**
 * modal_productos.php
 * IMPORTANTE: modal.js se carga AL FINAL (antes del </body>)
 * para que window.agregarProducto (de crear.js) ya esté disponible.
 *
 * En crear.php el orden correcto es:
 *   1. <script src="crear.js">   ← define window.agregarProducto
 *   2. <script src="modal.js">   ← lo consume
 *
 * Por eso modal.js NO se carga aquí con <script src="">,
 * sino que crear.php lo incluye después de crear.js.
 */
?>
<link rel="stylesheet" href="<?= BASE_URL ?>/views/inventario/ventas/css/modal.css">

<!-- MODAL DE PRODUCTOS -->
<div class="modal fade" id="modalListaProductos" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-dialog-scrollable modal-xl">
        <div class="modal-content">

            <div class="modal-header">
                <h5 class="modal-title">
                    <i class="fas fa-boxes me-2"></i>Seleccionar Producto
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
            </div>

            <div class="modal-body p-3">
                <!-- Buscador interno del modal -->
                <div class="modal-search-box mb-3">
                    <i class="fas fa-search"></i>
                    <input type="text"
                           id="modalBuscarProducto"
                           class="form-control"
                           placeholder="Buscar por código, nombre o SKU..."
                           autocomplete="off">
                </div>

                <!-- Aquí se inyectan las tarjetas vía AJAX -->
                <div id="productosModalList" class="modal-products-grid">
                    <div class="loading-state text-center py-4">
                        <i class="fas fa-spinner fa-spin fa-2x text-muted"></i>
                        <p class="mt-2 text-muted">Cargando productos...</p>
                    </div>
                </div>
            </div>

        </div>
    </div>
</div>

<!-- ✅ modal.js se carga AQUÍ, al final, después de crear.js -->
<!-- En crear.php el include de este archivo debe ir DESPUÉS del <script src="crear.js"> -->