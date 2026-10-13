/**
 * inventario_fisico.js - RopaVirtual · Módulo Inventario Físico (CON CSRF)
 * 
 * Cambios:
 *   - Agregada función getCsrfToken() para obtener token del meta tag
 *   - ajaxPost() ahora incluye cabecera X-CSRF-Token
 *   - Soporte para CSRF en todas las peticiones POST/AJAX
 */

'use strict';

// =========================================================================
// UTILIDADES CSRF
// =========================================================================

/**
 * Obtiene el token CSRF desde el meta tag en el HTML
 */
function getCsrfToken() {
    // Buscar primero en el meta tag
    const meta = document.querySelector('meta[name="csrf-token"]');
    if (meta && meta.getAttribute('content')) {
        return meta.getAttribute('content');
    }
    
    // Fallback: buscar en el input hidden
    const input = document.getElementById('ifCsrfToken');
    if (input && input.value) {
        return input.value;
    }
    
    console.error('CSRF Token: No encontrado en meta tag ni en input hidden');
    return '';
}

/**
 * Valida que el token CSRF exista antes de hacer peticiones
 */
function hasCsrfToken() {
    const token = getCsrfToken();
    if (!token) {
        console.warn('CSRF Token no disponible. Recargue la página.');
        return false;
    }
    return true;
}

// =========================================================================
// PETICIONES AJAX
// =========================================================================

/**
 * Realiza petición POST con JSON incluyendo CSRF token
 */
async function ajaxPost(url, body) {
    // Verificar token CSRF antes de enviar
    if (!hasCsrfToken()) {
        throw new Error('Token CSRF no disponible. Recargue la página.');
    }
    
    const resp = await fetch(url, {
        method: 'POST',
        headers: { 
            'Content-Type': 'application/json',
            'X-Requested-With': 'XMLHttpRequest',
            'X-CSRF-Token': getCsrfToken()  // ← Token CSRF añadido
        },
        credentials: 'same-origin',
        body: JSON.stringify(body),
    });
    
    // Si el servidor responde 403 (CSRF inválido), mostrar mensaje específico
    if (resp.status === 403) {
        const errorData = await resp.json().catch(() => ({}));
        throw new Error(errorData.mensaje || 'Token CSRF inválido. Recargue la página.');
    }
    
    return resp.json();
}

/**
 * Realiza petición GET (útil para búsquedas - opcionalmente incluye token)
 */
async function ajaxGet(url, includeCsrf = false) {
    const headers = {
        'X-Requested-With': 'XMLHttpRequest'
    };
    
    if (includeCsrf && hasCsrfToken()) {
        headers['X-CSRF-Token'] = getCsrfToken();
    }
    
    const resp = await fetch(url, {
        method: 'GET',
        headers: headers,
        credentials: 'same-origin',
    });
    
    return resp.json();
}

// =========================================================================
// SWEETALERT HELPERS
// =========================================================================

function swalError(e) {
    console.error(e);
    Swal.fire({ 
        title: 'Error de conexión', 
        text: e.message || 'Error inesperado. Recargue la página e intente nuevamente.', 
        icon: 'error',
        confirmButtonText: 'Recargar',
        confirmButtonColor: '#0d9488',
    }).then((result) => {
        if (result.isConfirmed) {
            location.reload();
        }
    });
}

function toastOk(msg) {
    Swal.fire({ 
        icon: 'success', 
        text: msg, 
        toast: true, 
        position: 'top-end', 
        timer: 2800, 
        showConfirmButton: false 
    });
}

function escHtml(s) {
    if (!s) return '';
    const div = document.createElement('div');
    div.appendChild(document.createTextNode(String(s)));
    return div.innerHTML;
}

// =========================================================================
// EVENTOS PRINCIPALES
// =========================================================================

document.addEventListener('DOMContentLoaded', () => {
    
    // Verificar que el token CSRF existe al cargar la página
    if (!hasCsrfToken()) {
        console.warn('Advertencia: No se encontró meta tag "csrf-token". Las peticiones POST podrían fallar.');
    }
    
    // SweetAlert flash
    if (typeof ifSweetAlert !== 'undefined' && ifSweetAlert) {
        Swal.fire({
            title: ifSweetAlert.title || 'Aviso',
            html: ifSweetAlert.text || '',
            icon: ifSweetAlert.type || 'info',
            confirmButtonText: 'Entendido',
            confirmButtonColor: '#0d9488',
            timer: ifSweetAlert.type === 'success' ? 3200 : undefined,
        });
    }
    
    // ==============================================================
    // FINALIZAR INVENTARIO
    // ==============================================================
    const btnFinalizar = document.getElementById('btnFinalizar');
    if (btnFinalizar) {
        btnFinalizar.addEventListener('click', async function() {
            const id = this.dataset.id;
            
            const result = await Swal.fire({
                title: '¿Finalizar inventario?',
                html: '<p>Esta acción ajustará el stock de los productos con diferencias.</p>',
                icon: 'warning',
                showDenyButton: true,
                showCancelButton: true,
                confirmButtonText: 'Finalizar y ajustar stock',
                denyButtonText: 'Finalizar sin ajustar',
                cancelButtonText: 'Cancelar',
                confirmButtonColor: '#0d9488',
                denyButtonColor: '#0284c7',
            });
            
            if (result.isDismissed) return;
            const aplicar = result.isConfirmed;
            
            Swal.fire({ title: 'Finalizando...', allowOutsideClick: false, didOpen: () => Swal.showLoading() });
            
            try {
                const data = await ajaxPost('index.php?controller=inventario&action=finalizar', {
                    id_inventario: parseInt(id),
                    aplicar_ajustes: aplicar,
                });
                
                if (data.success) {
                    await Swal.fire({
                        title: '¡Inventario finalizado!',
                        html: `<p>${data.mensaje}</p>${aplicar ? `<p style="color:#0d9488;font-weight:700">${data.ajustados} producto(s) ajustados.</p>` : ''}`,
                        icon: 'success',
                    });
                    location.href = `index.php?controller=inventario&action=resultados&id=${id}`;
                } else {
                    Swal.fire({ title: 'Error', text: data.mensaje, icon: 'error' });
                }
            } catch(e) { 
                swalError(e); 
            }
        });
    }
    
    // ==============================================================
    // INICIAR CONTEO
    // ==============================================================
    const btnIniciar = document.getElementById('btnIniciarConteo');
    if (btnIniciar) {
        btnIniciar.addEventListener('click', async function() {
            const id = this.dataset.id;
            
            const result = await Swal.fire({
                title: '¿Iniciar conteo?',
                text: 'El inventario pasará a estado "En Proceso"',
                icon: 'question',
                showCancelButton: true,
                confirmButtonText: 'Iniciar',
                confirmButtonColor: '#0d9488',
            });
            if (!result.isConfirmed) return;
            
            Swal.fire({ title: 'Iniciando...', didOpen: () => Swal.showLoading() });
            
            try {
                const data = await ajaxPost('index.php?controller=inventario&action=iniciar', { 
                    id_inventario: parseInt(id)
                });
                if (data.success) {
                    await Swal.fire({ title: '¡Conteo iniciado!', text: data.mensaje, icon: 'success', timer: 2500 });
                    location.reload();
                } else {
                    Swal.fire({ title: 'Error', text: data.mensaje, icon: 'error' });
                }
            } catch(e) { 
                swalError(e); 
            }
        });
    }
    
    // ==============================================================
    // CANCELAR INVENTARIO
    // ==============================================================
    const btnCancelar = document.getElementById('btnCancelar');
    if (btnCancelar) {
        btnCancelar.addEventListener('click', async function() {
            const id = this.dataset.id;
            
            const result = await Swal.fire({
                title: '¿Cancelar inventario?',
                html: `<input id="sw-motivo" class="swal2-input" placeholder="Motivo de cancelación" style="margin:0;width:100%">`,
                icon: 'warning',
                showCancelButton: true,
                confirmButtonText: 'Sí, cancelar',
                cancelButtonText: 'No',
                confirmButtonColor: '#dc2626',
                preConfirm: () => document.getElementById('sw-motivo')?.value || '',
            });
            if (!result.isConfirmed) return;
            
            Swal.fire({ title: 'Cancelando...', didOpen: () => Swal.showLoading() });
            
            try {
                const data = await ajaxPost('index.php?controller=inventario&action=cancelar', {
                    id_inventario: parseInt(id),
                    motivo: result.value,
                });
                if (data.success) {
                    await Swal.fire({ title: 'Cancelado', text: data.mensaje, icon: 'success' });
                    location.reload();
                } else {
                    Swal.fire({ title: 'Error', text: data.mensaje, icon: 'error' });
                }
            } catch(e) { 
                swalError(e); 
            }
        });
    }
    
    // ==============================================================
    // AGREGAR PRODUCTO (modal)
    // ==============================================================
    const btnAgregar = document.getElementById('btnAgregarProducto');
    if (btnAgregar) {
        btnAgregar.addEventListener('click', () => {
            const modal = document.getElementById('modalAgregarProducto');
            if (modal) new bootstrap.Modal(modal).show();
        });
    }
    
    // ==============================================================
    // GUARDAR DETALLE (agregar producto)
    // ==============================================================
    const btnGuardarDetalle = document.getElementById('ifBtnGuardarDetalle');
    if (btnGuardarDetalle) {
        btnGuardarDetalle.addEventListener('click', async () => {
            const idInventario = parseInt(document.getElementById('ifIdInventario')?.value || '0');
            const idProducto = parseInt(document.getElementById('ifModalIdProducto')?.value || '0');
            const stockFisico = parseInt(document.getElementById('ifModalStockFisico')?.value || '0');
            const costo = parseFloat(document.getElementById('ifModalCosto')?.value || '0') || null;
            const obs = document.getElementById('ifModalObs')?.value || null;
            
            if (!idProducto) {
                Swal.fire({ title: 'Selecciona un producto', icon: 'warning' });
                return;
            }
            
            btnGuardarDetalle.disabled = true;
            btnGuardarDetalle.innerHTML = '<span class="spinner-border spinner-border-sm me-1"></span> Guardando...';
            
            try {
                const data = await ajaxPost('index.php?controller=inventario&action=agregarProducto', {
                    id_inventario: idInventario,
                    id_producto: idProducto,
                    stock_fisico: stockFisico,
                    costo_unitario: costo,
                    observaciones: obs,
                });
                if (data.success) {
                    bootstrap.Modal.getInstance(document.getElementById('modalAgregarProducto'))?.hide();
                    toastOk(data.mensaje || 'Producto agregado');
                    setTimeout(() => location.reload(), 900);
                } else {
                    Swal.fire({ title: 'Error', text: data.mensaje, icon: 'error' });
                }
            } catch(e) { 
                swalError(e); 
            }
            finally {
                btnGuardarDetalle.disabled = false;
                btnGuardarDetalle.innerHTML = '<i class="fas fa-plus"></i> Agregar';
            }
        });
    }
    
    // ==============================================================
    // ELIMINAR PRODUCTO
    // ==============================================================
    document.querySelectorAll('.btn-eliminar-producto').forEach(btn => {
        btn.addEventListener('click', async function() {
            const idDetalle = this.dataset.id;
            const nombre = this.dataset.producto || 'este producto';
            
            const result = await Swal.fire({
                title: '¿Eliminar producto?',
                html: `Se eliminará <strong>${escHtml(nombre)}</strong> del inventario.`,
                icon: 'warning',
                showCancelButton: true,
                confirmButtonText: 'Sí, eliminar',
                confirmButtonColor: '#dc2626',
            });
            if (!result.isConfirmed) return;
            
            Swal.fire({ title: 'Eliminando...', didOpen: () => Swal.showLoading() });
            
            try {
                const data = await ajaxPost('index.php?controller=inventario&action=eliminarProducto', {
                    id_detalle: parseInt(idDetalle),
                });
                if (data.success) {
                    toastOk(data.mensaje);
                    setTimeout(() => location.reload(), 1000);
                } else {
                    Swal.fire({ title: 'Error', text: data.mensaje, icon: 'error' });
                }
            } catch(e) { 
                swalError(e); 
            }
        });
    });
    
    // ==============================================================
    // BUSCAR PRODUCTOS (modal)
    // ==============================================================
    const inputBuscar = document.getElementById('ifBuscarProducto');
    const resultsDiv = document.getElementById('ifResultadosProducto');
    
    if (inputBuscar && resultsDiv) {
        let timer;
        inputBuscar.addEventListener('input', () => {
            clearTimeout(timer);
            const q = inputBuscar.value.trim();
            if (q.length < 2) {
                resultsDiv.innerHTML = '';
                resultsDiv.style.display = 'none';
                return;
            }
            resultsDiv.innerHTML = '<div class="text-center p-2"><i class="fas fa-spinner fa-spin"></i> Buscando...</div>';
            resultsDiv.style.display = 'block';
            timer = setTimeout(() => buscarProductos(q), 350);
        });
        
        async function buscarProductos(q) {
            try {
                // GET no requiere CSRF por defecto, pero si quieres puedes habilitarlo
                const resp = await fetch(`index.php?controller=inventario&action=getProductos&q=${encodeURIComponent(q)}`, {
                    headers: { 
                        'X-Requested-With': 'XMLHttpRequest'
                    }
                });
                const data = await resp.json();
                
                if (!data.success || !data.data.length) {
                    resultsDiv.innerHTML = '<div class="text-center p-2 text-muted">Sin resultados</div>';
                    return;
                }
                
                resultsDiv.innerHTML = data.data.map(p => `
                    <div class="p-2 border-bottom" style="cursor:pointer" onclick="seleccionarProducto(${p.id_producto},'${escHtml(p.nombre).replace(/'/g,"\\'")}',${p.stock_actual || 0},${p.precio_compra || 0})">
                        <div class="fw-bold">${escHtml(p.nombre)}</div>
                        <div class="small text-muted">${escHtml(p.codigo || '')} · ${escHtml(p.categoria || '')}</div>
                        <div class="small text-success">Stock: ${p.stock_actual || 0}</div>
                    </div>
                `).join('');
            } catch(e) {
                console.error(e);
                resultsDiv.innerHTML = '<div class="text-center p-2 text-danger">Error al buscar</div>';
            }
        }
    }
});

// =========================================================================
// FUNCIONES GLOBALES
// =========================================================================

window.seleccionarProducto = function(id, nombre, stock, costo) {
    const idInput = document.getElementById('ifModalIdProducto');
    const nombreSpan = document.getElementById('ifModalNombre');
    const stockSpan = document.getElementById('ifModalStock');
    const costoInput = document.getElementById('ifModalCosto');
    const stockFisicoInput = document.getElementById('ifModalStockFisico');
    const buscarInput = document.getElementById('ifBuscarProducto');
    const resultsDiv = document.getElementById('ifResultadosProducto');
    const selectedDiv = document.getElementById('ifProductoSeleccionado');
    
    if (idInput) idInput.value = id;
    if (nombreSpan) nombreSpan.textContent = nombre;
    if (stockSpan) stockSpan.textContent = stock;
    if (costoInput) costoInput.value = costo > 0 ? costo : '';
    if (stockFisicoInput) stockFisicoInput.value = stock;
    if (buscarInput) buscarInput.value = nombre;
    if (resultsDiv) {
        resultsDiv.innerHTML = '';
        resultsDiv.style.display = 'none';
    }
    if (selectedDiv) selectedDiv.style.display = 'block';
    
    // Pequeña animación de éxito
    const selectRow = document.querySelector('#ifResultadosProducto .border-bottom');
    if (selectRow) {
        selectRow.style.backgroundColor = '#d1fae5';
        setTimeout(() => {
            selectRow.style.backgroundColor = '';
        }, 300);
    }
};

// Función auxiliar para regenerar token (útil si expira)
window.refreshCsrfToken = function() {
    location.reload();
};