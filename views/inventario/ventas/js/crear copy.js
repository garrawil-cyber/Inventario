/**
 * crear.js - Lógica profesional para Nueva Venta
 * Sistema: RopaVirtual - Módulo de Ventas
 * Dependencias: SweetAlert2, Bootstrap
 */

/* ============================================================
   1. ESTADO GLOBAL
   ============================================================ */
let cart = [];
let searchTimeout = null;

/* ============================================================
   2. REFERENCIAS DOM
   ============================================================ */
let buscarInput, resultadosDiv, cartContainer, itemsCountSpan;
let subtotalSpan, igvSpan, descuentoMostrar, totalSpan;
let descuentoGlobal, tipoComprobante, serie, numeroComprobante;
let formaPago, observaciones, clienteSelect;

// Base URL para peticiones AJAX (ajusta según tu configuración)
const BASE_URL = window.location.origin + window.location.pathname.replace(/index\.php.*$/, '');

/* ============================================================
   3. UTILIDADES
   ============================================================ */
function formatMoney(amount) {
    return 'S/ ' + parseFloat(amount || 0).toFixed(2);
}

function escapeHtml(text) {
    if (!text) return '';
    const div = document.createElement('div');
    div.textContent = text;
    return div.innerHTML;
}

function mostrarFeedback(mensaje, tipo = 'success') {
    const toast = document.createElement('div');
    toast.className = `toast-notification toast-${tipo}`;
    toast.innerHTML = `
        <i class="fas ${tipo === 'success' ? 'fa-check-circle' : 'fa-exclamation-circle'}"></i>
        <span>${mensaje}</span>
    `;
    toast.style.cssText = `
        position: fixed;
        bottom: 20px;
        right: 20px;
        z-index: 10000;
        background: ${tipo === 'success' ? '#10b981' : '#ef4444'};
        color: white;
        padding: 12px 20px;
        border-radius: 12px;
        font-size: 0.875rem;
        font-weight: 500;
        box-shadow: 0 10px 25px -5px rgba(0,0,0,0.2);
        animation: slideIn 0.3s ease;
        display: flex;
        align-items: center;
        gap: 10px;
    `;
    document.body.appendChild(toast);
    setTimeout(() => toast.remove(), 2500);
}

/* ============================================================
   4. VERIFICAR SESIÓN
   ============================================================ */
function verificarSesionYRedirigir(response) {
    // Si la respuesta es HTML y contiene una redirección a login
    if (response.headers && response.headers.get('content-type')?.includes('text/html')) {
        // Posible redirección, verificar si estamos en login
        window.location.href = 'index.php?controller=auth&action=login';
        return false;
    }
    return true;
}

/* ============================================================
   5. BÚSQUEDA RÁPIDA DE PRODUCTOS (CORREGIDA)
   ============================================================ */
/**
 * buscarProductosVenta - Versión con depuración completa
 */
function initBusquedaProducto() {
    if (!buscarInput) return;
    
    buscarInput.addEventListener('input', function () {
        clearTimeout(searchTimeout);
        const term = this.value.trim();

        if (term.length < 2) {
            resultadosDiv.innerHTML = '';
            return;
        }

        resultadosDiv.innerHTML = `
            <div class="loading-state" style="padding:1rem;text-align:center;">
                <i class="fas fa-spinner fa-spin"></i> Buscando...
            </div>`;

        searchTimeout = setTimeout(() => {
            const url = `index.php?controller=ventas&action=buscarProductos&q=${encodeURIComponent(term)}`;
            
            fetch(url, {
                method: 'GET',
                headers: { 
                    'Accept': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest'
                },
                credentials: 'same-origin'
            })
            .then(async response => {
                const text = await response.text();
                try {
                    return JSON.parse(text);
                } catch (e) {
                    throw new Error(`Respuesta no es JSON: ${text.substring(0, 100)}`);
                }
            })
            .then(data => {
                if (data.error) throw new Error(data.error);
                if (!Array.isArray(data)) throw new Error('Formato inválido');
                
                if (data.length === 0) {
                    resultadosDiv.innerHTML = `
                        <div style="padding:1rem;text-align:center;color:#64748b;">
                            <i class="fas fa-search"></i> Sin resultados para "${escapeHtml(term)}"
                        </div>`;
                    return;
                }
                
                resultadosDiv.innerHTML = `
                    <div class="vs-results">
                        ${data.map(p => `
                            <div class="vs-result-item" onclick="agregarProducto(${p.id}, '${escapeHtml(p.nombre).replace(/'/g, "\\'")}', ${p.precio_venta}, ${p.stock})">
                                <div style="flex:1;">
                                    <div class="vs-product-name"><strong>${escapeHtml(p.nombre)}</strong></div>
                                    <div class="vs-product-code">
                                        <i class="fas fa-barcode fa-xs"></i> ${escapeHtml(p.codigo || p.sku || 'Sin código')}
                                    </div>
                                    <div class="vs-product-detail mt-1">
                                        <i class="fas fa-boxes fa-xs"></i> Stock: <strong>${p.stock}</strong> ${p.unidad_medida || ''}
                                    </div>
                                </div>
                                <div style="text-align:right;">
                                    <div class="vs-product-price" style="margin-bottom:0.5rem;">${formatMoney(p.precio_venta)}</div>
                                    <button class="vs-btn-add" onclick="event.stopPropagation();agregarProducto(${p.id}, '${escapeHtml(p.nombre).replace(/'/g, "\\'")}', ${p.precio_venta}, ${p.stock})">
                                        <i class="fas fa-cart-plus fa-xs"></i> Agregar
                                    </button>
                                </div>
                            </div>
                        `).join('')}
                    </div>`;
            })
            .catch(error => {
                console.error('Error búsqueda:', error);
                resultadosDiv.innerHTML = `
                    <div style="padding:1rem;text-align:center;color:#ef4444; background:#fee2e2; border-radius:8px;">
                        <i class="fas fa-exclamation-triangle"></i> Error: ${escapeHtml(error.message)}
                    </div>`;
            });
        }, 500);
    });
}

function realizarBusquedaReal(term) {
    const url = `index.php?controller=ventas&action=buscarProductos&q=${encodeURIComponent(term)}`;
    
    console.log('Realizando búsqueda real en:', url);
    
    fetch(url, {
        method: 'GET',
        headers: { 
            'Accept': 'application/json',
            'X-Requested-With': 'XMLHttpRequest'
        },
        credentials: 'same-origin'
    })
    .then(async response => {
        console.log('Búsqueda response status:', response.status);
        console.log('Búsqueda content-type:', response.headers.get('content-type'));
        
        const text = await response.text();
        console.log('Búsqueda response text (primeros 200 chars):', text.substring(0, 200));
        
        // Intentar parsear JSON
        try {
            const data = JSON.parse(text);
            return data;
        } catch (e) {
            console.error('Error parsing JSON:', e);
            throw new Error(`Respuesta no es JSON: ${text.substring(0, 100)}`);
        }
    })
    .then(data => {
        if (data.error) {
            throw new Error(data.error);
        }
        
        if (!Array.isArray(data)) {
            throw new Error('Formato de respuesta inválido');
        }
        
        if (data.length === 0) {
            resultadosDiv.innerHTML = `
                <div style="padding:1rem;text-align:center;color:#64748b;">
                    <i class="fas fa-search"></i> Sin resultados para "${escapeHtml(term)}"
                </div>`;
            return;
        }
        
        resultadosDiv.innerHTML = `
            <div class="vs-results">
                ${data.map(p => `
                    <div class="vs-result-item" onclick="agregarProducto(${p.id}, '${escapeHtml(p.nombre).replace(/'/g, "\\'")}', ${p.precio_venta}, ${p.stock})">
                        <div style="flex:1;">
                            <div class="vs-product-name"><strong>${escapeHtml(p.nombre)}</strong></div>
                            <div class="vs-product-code">
                                <i class="fas fa-barcode fa-xs"></i> ${escapeHtml(p.codigo || p.sku || 'Sin código')}
                            </div>
                            <div class="vs-product-detail mt-1">
                                <i class="fas fa-boxes fa-xs"></i> Stock: <strong>${p.stock}</strong> ${p.unidad_medida || ''}
                            </div>
                        </div>
                        <div style="text-align:right;">
                            <div class="vs-product-price" style="margin-bottom:0.5rem;">${formatMoney(p.precio_venta)}</div>
                            <button class="vs-btn-add" onclick="event.stopPropagation();agregarProducto(${p.id}, '${escapeHtml(p.nombre).replace(/'/g, "\\'")}', ${p.precio_venta}, ${p.stock})">
                                <i class="fas fa-cart-plus fa-xs"></i> Agregar
                            </button>
                        </div>
                    </div>
                `).join('')}
            </div>`;
    })
    .catch(error => {
        console.error('Error en búsqueda real:', error);
        resultadosDiv.innerHTML = `
            <div style="padding:1rem;text-align:center;color:#ef4444; background:#fee2e2; border-radius:8px;">
                <i class="fas fa-exclamation-triangle"></i> Error: ${escapeHtml(error.message)}<br>
                <button onclick="location.reload()" style="margin-top:8px; padding:4px 12px; background:#ef4444; color:white; border:none; border-radius:6px;">
                    <i class="fas fa-sync-alt"></i> Recargar
                </button>
            </div>`;
    });
}
/* ============================================================
   6. GESTIÓN DEL CARRITO
   ============================================================ */
window.agregarProducto = function (id, nombre, precio, stockMaximo) {
    if (stockMaximo <= 0) {
        Swal.fire({
            title: 'Sin stock',
            text: 'Este producto no tiene stock disponible',
            icon: 'warning',
            confirmButtonColor: '#6366f1'
        });
        return;
    }

    const existing = cart.find(item => item.id === id);
    if (existing) {
        if (existing.cantidad >= stockMaximo) {
            Swal.fire({
                title: 'Stock insuficiente',
                text: `Solo hay ${stockMaximo} unidades disponibles`,
                icon: 'warning',
                confirmButtonColor: '#ef4444'
            });
            return;
        }
        existing.cantidad++;
        mostrarFeedback('Cantidad actualizada', 'success');
    } else {
        cart.push({ id, nombre, precio: parseFloat(precio), cantidad: 1, stock_max: stockMaximo });
        mostrarFeedback('Producto agregado', 'success');
    }

    actualizarCarrito();

    // Limpiar búsqueda
    if (resultadosDiv) resultadosDiv.innerHTML = '';
    if (buscarInput) {
        buscarInput.value = '';
        buscarInput.focus();
    }
};

function actualizarCarrito() {
    if (!cartContainer) return;

    if (cart.length === 0) {
        cartContainer.innerHTML = `
            <div class="vs-empty-cart">
                <div class="vs-empty-cart-icon"><i class="fas fa-box-open"></i></div>
                <p>Sin productos agregados</p>
                <small>Busque y seleccione productos</small>
            </div>`;
        if (itemsCountSpan) itemsCountSpan.textContent = '0 items';
    } else {
        cartContainer.innerHTML = cart.map((item, index) => `
            <div class="vs-cart-item">
                <div style="display:flex;justify-content:space-between;align-items:flex-start;gap:0.75rem;">
                    <div style="flex:1;">
                        <div class="vs-cart-item-name">${escapeHtml(item.nombre)}</div>
                        <div class="vs-cart-item-price">${formatMoney(item.precio)} c/u</div>
                    </div>
                    <div style="text-align:right;">
                        <div class="vs-qty">
                            <button class="vs-qty-btn" onclick="cambiarCantidad(${index}, -1)">
                                <i class="fas fa-minus fa-xs"></i>
                            </button>
                            <input type="number" class="vs-qty-input"
                                   value="${item.cantidad}" min="1" max="${item.stock_max}"
                                   onchange="cambiarCantidadDirecta(${index}, this.value)">
                            <button class="vs-qty-btn" onclick="cambiarCantidad(${index}, 1)">
                                <i class="fas fa-plus fa-xs"></i>
                            </button>
                            <button class="vs-qty-btn remove" onclick="eliminarProducto(${index})">
                                <i class="fas fa-trash-alt fa-xs"></i>
                            </button>
                        </div>
                        <div class="vs-cart-subtotal mt-1">${formatMoney(item.precio * item.cantidad)}</div>
                    </div>
                </div>
            </div>`).join('');

        const totalItems = cart.reduce((s, i) => s + i.cantidad, 0);
        itemsCountSpan.textContent = totalItems + (totalItems === 1 ? ' item' : ' items');
    }

    calcularTotales();
}

window.cambiarCantidad = function (index, delta) {
    const item = cart[index];
    const nv = item.cantidad + delta;
    if (nv < 1) {
        eliminarProducto(index);
        return;
    }
    if (nv > item.stock_max) {
        Swal.fire({
            title: 'Stock insuficiente',
            text: `Solo hay ${item.stock_max} unidades disponibles`,
            icon: 'warning',
            confirmButtonColor: '#ef4444'
        });
        return;
    }
    item.cantidad = nv;
    actualizarCarrito();
};

window.cambiarCantidadDirecta = function (index, valor) {
    let cantidad = parseInt(valor);
    const item = cart[index];
    if (isNaN(cantidad) || cantidad < 1) cantidad = 1;
    if (cantidad > item.stock_max) {
        Swal.fire({
            title: 'Stock insuficiente',
            text: `Solo hay ${item.stock_max} unidades disponibles`,
            icon: 'warning',
            confirmButtonColor: '#ef4444'
        });
        cantidad = item.stock_max;
    }
    item.cantidad = cantidad;
    actualizarCarrito();
};

window.eliminarProducto = function (index) {
    cart.splice(index, 1);
    actualizarCarrito();
};

/* ============================================================
   7. CÁLCULO DE TOTALES
   ============================================================ */
function calcularTotales() {
    const subtotal = cart.reduce((s, i) => s + i.precio * i.cantidad, 0);
    const descuentoVal = parseFloat(descuentoGlobal?.value) || 0;
    const igv = subtotal * 0.18;
    const total = subtotal + igv - descuentoVal;

    if (subtotalSpan) subtotalSpan.textContent = formatMoney(subtotal);
    if (igvSpan) igvSpan.textContent = formatMoney(igv);
    if (descuentoMostrar) descuentoMostrar.textContent = formatMoney(descuentoVal);
    if (totalSpan) totalSpan.textContent = formatMoney(total);
}

/* ============================================================
   8. GENERADOR DE NÚMERO DE COMPROBANTE (CORREGIDO)
   ============================================================ */
/*function generarSiguienteNumero() {
    const tipo = tipoComprobante?.value || 'boleta';
    const serieVal = serie?.value || 'B001';
    const numeroInput = numeroComprobante;
    const btnGenerar = document.getElementById('btnGenerarNumero');

    if (!btnGenerar) return;

    const originalIcon = btnGenerar.innerHTML;
    btnGenerar.innerHTML = '<i class="fas fa-spinner fa-spin"></i>';
    btnGenerar.disabled = true;

    const url = `index.php?controller=ventas&action=getSiguienteNumero&tipo=${encodeURIComponent(tipo)}&serie=${encodeURIComponent(serieVal)}`;

    fetch(url, {
        headers: { 
            'Accept': 'application/json',
            'X-Requested-With': 'XMLHttpRequest'
        },
        credentials: 'same-origin'
    })
    .then(async response => {
        const contentType = response.headers.get('content-type');
        if (!response.ok || !contentType?.includes('application/json')) {
            throw new Error('Error al obtener número de comprobante');
        }
        return response.json();
    })
    .then(data => {
        if (data.success && numeroInput) {
            numeroInput.value = data.numero;
            mostrarFeedback('Número generado: ' + data.numero, 'success');
        } else {
            mostrarFeedback(data.error || 'Error al generar número', 'error');
        }
    })
    .catch(error => {
        console.error('Error generarNumero:', error);
        mostrarFeedback('Error de conexión al generar número', 'error');
    })
    .finally(() => {
        btnGenerar.innerHTML = originalIcon;
        btnGenerar.disabled = false;
    });
}*/

function generarSiguienteNumero() {
    const tipo = tipoComprobante?.value || 'boleta';
    const serieVal = serie?.value || 'B001';
    const numeroInput = numeroComprobante;
    const btnGenerar = document.getElementById('btnGenerarNumero');

    if (!btnGenerar) return;

    const originalIcon = btnGenerar.innerHTML;
    btnGenerar.innerHTML = '<i class="fas fa-spinner fa-spin"></i>';
    btnGenerar.disabled = true;

    const url = `index.php?controller=ventas&action=getSiguienteNumero&tipo=${encodeURIComponent(tipo)}&serie=${encodeURIComponent(serieVal)}`;

    fetch(url, {
        headers: { 
            'Accept': 'application/json',
            'X-Requested-With': 'XMLHttpRequest'
        },
        credentials: 'same-origin'
    })
    .then(response => response.json())
    .then(data => {
        if (data.success && numeroInput) {
            numeroInput.value = data.numero;
            mostrarFeedback('Número generado: ' + data.numero, 'success');
        } else {
            // Usar número por defecto si hay error
            if (numeroInput) numeroInput.value = '00000001';
            // No mostrar error al usuario, solo warning
            console.warn('Error generando número:', data.error);
        }
    })
    .catch(error => {
        console.error('Error generarNumero:', error);
        // Usar número por defecto en caso de error de red
        if (numeroInput) numeroInput.value = '00000001';
    })
    .finally(() => {
        btnGenerar.innerHTML = originalIcon;
        btnGenerar.disabled = false;
    });
}

function initComprobante() {
    const btnGenerar = document.getElementById('btnGenerarNumero');
    if (btnGenerar) {
        btnGenerar.addEventListener('click', generarSiguienteNumero);
    }

    if (tipoComprobante) {
        tipoComprobante.addEventListener('change', function () {
            const prefijos = { boleta: 'B001', factura: 'F001', ticket: 'T001', nota_venta: 'NV001' };
            if (serie) serie.value = prefijos[this.value] || 'B001';
            generarSiguienteNumero();
        });
    }

    if (serie) {
        serie.addEventListener('change', generarSiguienteNumero);
    }

    setTimeout(generarSiguienteNumero, 500);
}

/* ============================================================
   9. LIMPIAR CARRITO
   ============================================================ */
function initLimpiarCarrito() {
    const btn = document.getElementById('btnLimpiarCarrito');
    if (!btn) return;

    btn.addEventListener('click', () => {
        if (cart.length === 0) return;
        Swal.fire({
            title: '¿Limpiar carrito?',
            text: 'Se eliminarán todos los productos agregados',
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#ef4444',
            confirmButtonText: 'Sí, limpiar',
            cancelButtonText: 'Cancelar'
        }).then(result => {
            if (result.isConfirmed) {
                cart = [];
                actualizarCarrito();
                mostrarFeedback('Carrito limpiado', 'success');
            }
        });
    });
}

/* ============================================================
   10. REGISTRAR VENTA (CORREGIDO)
   ============================================================ */
function initRegistrarVenta() {
    const btn = document.getElementById('btnRegistrarVenta');
    if (!btn) return;

    btn.addEventListener('click', async () => {
        if (cart.length === 0) {
            Swal.fire({
                title: 'Carrito vacío',
                text: 'Agregue al menos un producto para continuar',
                icon: 'warning',
                confirmButtonColor: '#6366f1'
            });
            return;
        }

        // Validar stock final
        for (const item of cart) {
            if (item.cantidad > item.stock_max) {
                Swal.fire({
                    title: 'Stock insuficiente',
                    text: `"${item.nombre}" tiene ${item.stock_max} unidades disponibles pero se intentan vender ${item.cantidad}.`,
                    icon: 'error',
                    confirmButtonColor: '#ef4444'
                });
                return;
            }
        }

        const subtotal = cart.reduce((s, i) => s + i.precio * i.cantidad, 0);
        const descuentoVal = parseFloat(descuentoGlobal?.value) || 0;
        const igv = subtotal * 0.18;
        const total = subtotal + igv - descuentoVal;

        const clienteNombre = clienteSelect?.options[clienteSelect.selectedIndex]?.text || 'Cliente General';

        const resumenProductos = cart.map(item => `
            <div style="display:flex;justify-content:space-between;margin-bottom:8px;font-size:0.85rem;">
                <span>${escapeHtml(item.nombre)} x${item.cantidad}</span>
                <span style="font-weight:600;">${formatMoney(item.precio * item.cantidad)}</span>
            </div>`).join('');

        const confirmResult = await Swal.fire({
            title: 'Confirmar venta',
            html: `
                <div style="text-align:left;max-height:450px;overflow-y:auto;">
                    <div style="margin-bottom:15px;padding:12px;background:#f8fafc;border-radius:12px;">
                        <div style="font-size:0.7rem;color:#64748b;margin-bottom:5px;">CLIENTE</div>
                        <div style="font-weight:600;">${escapeHtml(clienteNombre)}</div>
                    </div>
                    <div style="margin-bottom:15px;">
                        <div style="font-size:0.7rem;color:#64748b;margin-bottom:8px;">PRODUCTOS</div>
                        ${resumenProductos}
                    </div>
                    <div style="border-top:1px solid #e2e8f0;margin:10px 0;"></div>
                    <div>
                        <div style="display:flex;justify-content:space-between;font-size:0.85rem;">
                            <span>Subtotal:</span><span>${formatMoney(subtotal)}</span>
                        </div>
                        <div style="display:flex;justify-content:space-between;font-size:0.85rem;">
                            <span>IGV (18%):</span><span>${formatMoney(igv)}</span>
                        </div>
                        ${descuentoVal > 0 ? `
                        <div style="display:flex;justify-content:space-between;font-size:0.85rem;color:#ef4444;">
                            <span>Descuento:</span><span>-${formatMoney(descuentoVal)}</span>
                        </div>` : ''}
                        <div style="display:flex;justify-content:space-between;margin-top:12px;padding-top:12px;border-top:2px solid #e2e8f0;">
                            <span style="font-weight:700;">TOTAL:</span>
                            <span style="font-weight:700;color:#6366f1;">${formatMoney(total)}</span>
                        </div>
                    </div>
                </div>`,
            icon: 'question',
            showCancelButton: true,
            confirmButtonColor: '#6366f1',
            cancelButtonColor: '#64748b',
            confirmButtonText: '<i class="fas fa-check-circle"></i> Registrar Venta',
            cancelButtonText: '<i class="fas fa-times-circle"></i> Cancelar',
            reverseButtons: true
        });

        if (!confirmResult.isConfirmed) return;

        Swal.fire({
            title: 'Procesando...',
            text: 'Registrando la venta',
            allowOutsideClick: false,
            didOpen: () => Swal.showLoading()
        });

        const ventaData = {
            id_cliente: clienteSelect?.value || null,
            tipo_comprobante: tipoComprobante?.value || 'boleta',
            serie: serie?.value || null,
            numero_comprobante: numeroComprobante?.value || null,
            forma_pago: formaPago?.value || 'efectivo',
            descuento: descuentoVal,
            observaciones: observaciones?.value || null,
            items: cart.map(item => ({
                id_producto: item.id,
                cantidad: item.cantidad,
                precio: item.precio,
                descuento: 0
            }))
        };

        try {
            const response = await fetch('index.php?controller=ventas&action=registrar', {
                method: 'POST',
                headers: { 
                    'Content-Type': 'application/json',
                    'Accept': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest'
                },
                credentials: 'same-origin',
                body: JSON.stringify(ventaData)
            });
            
            const result = await response.json();

            if (result.success) {
                await Swal.fire({
                    title: '¡Venta Registrada!',
                    text: result.mensaje || 'La venta se ha registrado exitosamente.',
                    icon: 'success',
                    confirmButtonColor: '#6366f1',
                    confirmButtonText: 'Ver detalle'
                });
                window.location.href = `index.php?controller=ventas&action=ver&id=${result.id_venta}`;
            } else {
                Swal.fire({
                    title: 'Error',
                    text: result.mensaje || 'Ocurrió un error al registrar la venta.',
                    icon: 'error',
                    confirmButtonColor: '#ef4444'
                });
            }
        } catch (error) {
            console.error('Error registrar venta:', error);
            Swal.fire({
                title: 'Error de conexión',
                text: 'No se pudo conectar con el servidor. Verifique su conexión.',
                icon: 'error',
                confirmButtonColor: '#ef4444'
            });
        }
    });
}

/* ============================================================
   11. BOTÓN "VER TODOS" → MODAL
   ============================================================ */
function initVerTodos() {
    const btn = document.getElementById('btnBuscarTodos');
    if (!btn) return;

    btn.addEventListener('click', function () {
        if (typeof cargarProductosModal === 'function') {
            cargarProductosModal(1, '');
            const modal = new bootstrap.Modal(document.getElementById('modalListaProductos'));
            modal.show();
        } else {
            console.error('modal.js no está cargado correctamente');
            Swal.fire({
                title: 'Error',
                text: 'No se pudo cargar el listado de productos',
                icon: 'error',
                confirmButtonColor: '#ef4444'
            });
        }
    });
}

/* ============================================================
   12. EVENTOS ADICIONALES
   ============================================================ */
function initDescuentoGlobal() {
    if (descuentoGlobal) {
        descuentoGlobal.addEventListener('input', calcularTotales);
    }
}

/* ============================================================
   13. INICIALIZACIÓN
   ============================================================ */
document.addEventListener('DOMContentLoaded', function () {
    console.log('Inicializando módulo de ventas...');
    
    // Referencias DOM
    buscarInput = document.getElementById('buscar_producto');
    resultadosDiv = document.getElementById('resultados_busqueda');
    cartContainer = document.getElementById('cart_items');
    itemsCountSpan = document.getElementById('items_count');
    subtotalSpan = document.getElementById('subtotal');
    igvSpan = document.getElementById('igv');
    descuentoMostrar = document.getElementById('descuento_mostrar');
    totalSpan = document.getElementById('total');
    descuentoGlobal = document.getElementById('descuento_global');
    tipoComprobante = document.getElementById('tipo_comprobante');
    serie = document.getElementById('serie');
    numeroComprobante = document.getElementById('numero_comprobante');
    formaPago = document.getElementById('forma_pago');
    observaciones = document.getElementById('observaciones');
    clienteSelect = document.getElementById('cliente_id');

    // Verificar elementos críticos
    if (!buscarInput) console.warn('Elemento buscar_producto no encontrado');
    if (!resultadosDiv) console.warn('Elemento resultados_busqueda no encontrado');
    if (!cartContainer) console.warn('Elemento cart_items no encontrado');

    // SweetAlert flash desde sesión
    if (typeof sweetAlertData !== 'undefined' && sweetAlertData) {
        Swal.fire({
            title: sweetAlertData.title || 'Aviso',
            text: sweetAlertData.message || '',
            icon: sweetAlertData.type || 'info',
            confirmButtonColor: '#6366f1',
            confirmButtonText: 'Entendido'
        });
    }

    // Inicializar módulos
    initBusquedaProducto();
    initComprobante();
    initLimpiarCarrito();
    initRegistrarVenta();
    initVerTodos();
    initDescuentoGlobal();
    
    console.log('Módulo de ventas inicializado correctamente');
});