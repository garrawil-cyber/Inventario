/**
 * modal.js — Sistema RopaVirtual · Módulo de Ventas
 * Dependencias: Bootstrap 5, SweetAlert2, crear.js (window.agregarProducto)
 *
 * BUGS CORREGIDOS:
 *  1. renderProductosModal usaba p.id (undefined) → modelo devuelve p.id_producto
 *     Fix: se usa p.id_producto con fallback a p.id
 *  2. getTotalProductosActivos NO filtraba por stock_actual > 0,
 *     por lo que el total incluía productos sin stock → paginación errónea.
 *     Fix: se aplica el mismo filtro en el conteo (ver fix en Ventas.php).
 *  3. cargarProductosModal llamaba a escapeHtml() (función de crear.js),
 *     que puede no estar disponible al momento de ejecutarse modal.js.
 *     Fix: se usa la función local modalEscapeHtml() en todos los casos.
 */

'use strict';

/* ============================================================
   1. ESTADO DEL MODAL
   ============================================================ */
let modalPaginaActual   = 1;
let modalBusquedaActual = '';
let modalCargando       = false;

/* ============================================================
   2. UTILIDADES — todas locales, sin dependencias externas
   ============================================================ */
function modalEscapeHtml(text) {
    if (text == null) return '';
    const d = document.createElement('div');
    d.textContent = String(text);
    return d.innerHTML;
}

function modalEscapeAttr(text) {
    if (text == null) return '';
    return String(text)
        .replace(/&/g, '&amp;')
        .replace(/"/g, '&quot;')
        .replace(/'/g, '&#39;')
        .replace(/</g, '&lt;')
        .replace(/>/g, '&gt;');
}

function modalFormatMoney(amount) {
    return 'S/ ' + parseFloat(amount || 0).toFixed(2);
}

/* ============================================================
   3. CARGA DE PRODUCTOS VÍA AJAX
   ============================================================ */
function cargarProductosModal(page, search) {
    page   = (page   !== undefined) ? page   : 1;
    search = (search !== undefined) ? search : '';

    if (modalCargando) return;
    modalCargando       = true;
    modalPaginaActual   = page;
    modalBusquedaActual = search;

    const container = document.getElementById('productosModalList');
    if (!container) { modalCargando = false; return; }

    container.innerHTML = `
        <div class="loading-state">
            <i class="fas fa-spinner fa-spin fa-2x"></i>
            <p class="mt-2">Cargando productos...</p>
        </div>`;

    let url = 'index.php?controller=ventas&action=listarProductosModal&page=' + page;
    if (search && search.trim() !== '') {
        url += '&search=' + encodeURIComponent(search.trim());
    }

    fetch(url, {
        method: 'GET',
        headers: {
            'Accept': 'application/json',
            'X-Requested-With': 'XMLHttpRequest'
        }
    })
    .then(res => {
        if (!res.ok) throw new Error('HTTP ' + res.status);
        return res.json();
    })
    .then(data => {
        if (!data.success) {
            container.innerHTML = `
                <div class="empty-state">
                    <i class="fas fa-exclamation-triangle fa-2x mb-2" style="color:#ef4444;"></i>
                    <p>Error: ${modalEscapeHtml(data.error || 'No se pudieron cargar los productos')}</p>
                </div>`;
            return;
        }
        renderProductosModal(data.productos, data.pagination);
    })
    .catch(err => {
        console.error('[Modal] Error al cargar productos:', err);
        container.innerHTML = `
            <div class="empty-state">
                <i class="fas fa-exclamation-triangle fa-2x mb-2" style="color:#ef4444;"></i>
                <p>Error de conexión: ${modalEscapeHtml(err.message)}</p>
                <button onclick="cargarProductosModal(1, '${modalEscapeAttr(modalBusquedaActual)}')"
                        class="btn btn-sm btn-primary mt-2">
                    <i class="fas fa-sync-alt me-1"></i> Reintentar
                </button>
            </div>`;
    })
    .finally(() => {
        modalCargando = false;
    });
}

/* ============================================================
   4. RENDERIZADO DEL GRID
   ============================================================ */
function renderProductosModal(productos, pagination) {
    const container = document.getElementById('productosModalList');
    if (!container) return;

    if (!productos || productos.length === 0) {
        const termEscaped = modalEscapeHtml(modalBusquedaActual);
        container.innerHTML = `
            <div class="empty-state">
                <i class="fas fa-box-open fa-2x mb-2"></i>
                <p>${termEscaped
                    ? `No hay productos con stock para "<strong>${termEscaped}</strong>"`
                    : 'No hay productos con stock disponible'}</p>
            </div>`;
        return;
    }

    let html = '<div class="productos-grid">';

    productos.forEach(p => {
        // ── BUG 1 FIX: el modelo devuelve id_producto, no id ──────
        const idProducto = parseInt(p.id_producto || p.id) || 0;
        const stock      = parseInt(p.stock)        || 0;
        const stockMin   = parseInt(p.stock_minimo) || 0;
        const precio     = parseFloat(p.precio_venta) || 0;

        // Estado de stock
        let stockClass = '';
        let stockLabel = '';
        let cardClass  = '';

        if (stock === 0) {
            stockClass = 'stock-critico-label'; stockLabel = 'AGOTADO'; cardClass = 'stock-critico';
        } else if (stockMin > 0 && stock <= Math.floor(stockMin * 0.5)) {
            stockClass = 'stock-critico-label'; stockLabel = 'CRÍTICO'; cardClass = 'stock-critico';
        } else if (stockMin > 0 && stock <= stockMin) {
            stockClass = 'stock-bajo-label'; stockLabel = 'BAJO'; cardClass = 'stock-bajo';
        }

        const stockText = stock > 0 ? `Stock: ${stock}` : 'Sin stock';

        html += `
            <div class="producto-card ${cardClass}"
                 data-id="${idProducto}"
                 data-nombre="${modalEscapeAttr(p.nombre)}"
                 data-precio="${precio}"
                 data-stock="${stock}"
                 onclick="seleccionarProductoModal(
                     parseInt(this.dataset.id),
                     this.dataset.nombre,
                     parseFloat(this.dataset.precio),
                     parseInt(this.dataset.stock))">

                <div class="producto-nombre">${modalEscapeHtml(p.nombre)}</div>

                <div class="producto-codigo">
                    <i class="fas fa-barcode fa-xs"></i>
                    ${modalEscapeHtml(p.codigo || '—')}
                    ${p.sku
                        ? `<span class="badge-marca ms-1">${modalEscapeHtml(p.sku)}</span>`
                        : ''}
                </div>

                <div class="d-flex justify-content-between align-items-center mt-2">
                    <div class="producto-precio">${modalFormatMoney(precio)}</div>
                    <div class="producto-stock ${stockClass}">
                        ${stockLabel
                            ? `<span class="me-1">⚠️</span>${stockLabel}`
                            : stockText}
                    </div>
                </div>

                ${p.marca
                    ? `<div class="producto-codigo mt-1">
                           <i class="fas fa-tag fa-xs"></i>
                           ${modalEscapeHtml(p.marca)}
                       </div>`
                    : ''}
            </div>`;
    });

    html += '</div>';

    // Paginación
    if (pagination && pagination.total_pages > 1) {
        const cp = pagination.current_page;
        const tp = pagination.total_pages;
        html += `
            <div class="paginacion-modal">
                <button onclick="cargarProductosModal(1, modalBusquedaActual)"
                        ${cp === 1  ? 'disabled' : ''} title="Primera página">
                    <i class="fas fa-angle-double-left"></i>
                </button>
                <button onclick="cargarProductosModal(${cp - 1}, modalBusquedaActual)"
                        ${cp === 1  ? 'disabled' : ''} title="Anterior">
                    <i class="fas fa-angle-left"></i>
                </button>
                <span class="px-3 py-2 text-muted" style="font-size:0.8rem;">
                    Pág. <strong>${cp}</strong> / ${tp}
                    <small class="ms-1">(${pagination.total_records} productos)</small>
                </span>
                <button onclick="cargarProductosModal(${cp + 1}, modalBusquedaActual)"
                        ${cp === tp ? 'disabled' : ''} title="Siguiente">
                    <i class="fas fa-angle-right"></i>
                </button>
                <button onclick="cargarProductosModal(${tp}, modalBusquedaActual)"
                        ${cp === tp ? 'disabled' : ''} title="Última página">
                    <i class="fas fa-angle-double-right"></i>
                </button>
            </div>`;
    }

    container.innerHTML = html;
}

/* ============================================================
   5. SELECCIÓN DE PRODUCTO → delega al carrito de crear.js
   ============================================================ */
function seleccionarProductoModal(id, nombre, precio, stock) {
    if (stock <= 0) {
        Swal.fire({
            title: 'Sin stock',
            text: 'Este producto no tiene stock disponible.',
            icon: 'warning',
            confirmButtonColor: '#6366f1'
        });
        return;
    }

    // Cerrar el modal antes de agregar al carrito
    const modalEl = document.getElementById('modalListaProductos');
    if (modalEl) {
        const inst = bootstrap.Modal.getInstance(modalEl);
        if (inst) inst.hide();
    }

    // Delegar a crear.js — función expuesta globalmente
    if (typeof window.agregarProducto === 'function') {
        window.agregarProducto(id, nombre, precio, stock);
    } else {
        console.error('[Modal] window.agregarProducto() no está definida. ¿Se cargó crear.js antes que modal.js?');
        Swal.fire({
            title: 'Error',
            text: 'No se pudo agregar el producto. Recarga la página.',
            icon: 'error',
            confirmButtonColor: '#ef4444'
        });
    }
}

/* ============================================================
   6. EVENTOS DEL MODAL
   ============================================================ */
document.addEventListener('DOMContentLoaded', function () {

    // Búsqueda interna del modal con debounce 400 ms
    const inputModal = document.getElementById('modalBuscarProducto');
    if (inputModal) {
        let searchTimer;
        inputModal.addEventListener('input', function () {
            clearTimeout(searchTimer);
            const val = this.value;
            searchTimer = setTimeout(() => cargarProductosModal(1, val), 400);
        });

        // Enter también lanza búsqueda inmediata
        inputModal.addEventListener('keydown', function (e) {
            if (e.key === 'Enter') {
                e.preventDefault();
                clearTimeout(searchTimer);
                cargarProductosModal(1, this.value);
            }
        });
    }

    const modalEl = document.getElementById('modalListaProductos');
    if (!modalEl) return;

    // Cargar la primera vez que se abre (y sólo si el contenedor muestra el spinner)
    modalEl.addEventListener('show.bs.modal', function () {
        const lista = document.getElementById('productosModalList');
        if (lista && lista.querySelector('.loading-state')) {
            cargarProductosModal(1, '');
        }
    });

    // Al reabrirse desde un estado previo, recargar con búsqueda limpia
    modalEl.addEventListener('shown.bs.modal', function () {
        if (inputModal) inputModal.focus();
    });

    // Reset completo al cerrar
    modalEl.addEventListener('hidden.bs.modal', function () {
        if (inputModal) inputModal.value = '';
        modalBusquedaActual = '';
        modalPaginaActual   = 1;
    });
});