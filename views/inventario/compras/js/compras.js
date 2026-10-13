/**
 * ============================================================
 * COMPRAS MODULE JS
 * Sistema: RopaVirtual - Módulo de Compras
 * Requiere: SweetAlert2, jQuery (opcional)
 * ============================================================
 */

// Esperar a que el DOM esté listo
document.addEventListener('DOMContentLoaded', function() {
    
    // ============================================================
    // 1. MANEJO DE PRODUCTOS DINÁMICOS (Formulario crear/editar)
    // ============================================================
    
    let productoCnt = document.querySelectorAll('.producto-row').length;
    
    // Botón para agregar producto
    const btnAgregarProducto = document.getElementById('btnAgregarProducto');
    if (btnAgregarProducto) {
        btnAgregarProducto.addEventListener('click', function() {
            agregarProductoRow();
        });
    }
    
    // Función para agregar una nueva fila de producto
    function agregarProductoRow() {
        productoCnt++;
        const template = document.getElementById('producto-row-template');
        if (!template) return;
        
        const newRow = template.content.cloneNode(true);
        const rowDiv = newRow.querySelector('.producto-row');
        rowDiv.setAttribute('data-id', productoCnt);
        
        // Actualizar IDs y names
        const selects = newRow.querySelectorAll('select, input');
        selects.forEach(el => {
            const name = el.getAttribute('name');
            if (name) {
                el.setAttribute('name', name.replace('__INDEX__', productoCnt));
                el.id = name.replace('__INDEX__', productoCnt);
            }
        });
        
        // Agregar evento al botón eliminar
        const btnRemove = newRow.querySelector('.btn-remove-producto');
        if (btnRemove) {
            btnRemove.addEventListener('click', function() {
                rowDiv.remove();
                recalcularTotales();
            });
        }
        
        // Agregar eventos de cambio para recalcular
        const cantidadInput = newRow.querySelector('.cantidad-input');
        const precioInput = newRow.querySelector('.precio-input');
        if (cantidadInput) cantidadInput.addEventListener('input', recalcularTotales);
        if (precioInput) precioInput.addEventListener('input', recalcularTotales);
        
        document.getElementById('productos-container').appendChild(newRow);
        recalcularTotales();
    }
    
    // Función para recalcular subtotales y totales
    window.recalcularTotales = function() {
        let subtotal = 0;
        let totalIGV = 0;
        
        document.querySelectorAll('.producto-row').forEach(row => {
            const cantidad = parseFloat(row.querySelector('.cantidad-input')?.value) || 0;
            const precio = parseFloat(row.querySelector('.precio-input')?.value) || 0;
            const subtotalItem = cantidad * precio;
            const igvItem = subtotalItem * 0.18;
            
            subtotal += subtotalItem;
            totalIGV += igvItem;
            
            // Mostrar subtotal del item
            const subtotalSpan = row.querySelector('.item-subtotal');
            if (subtotalSpan) {
                subtotalSpan.textContent = 'S/ ' + subtotalItem.toFixed(2);
            }
        });
        
        const descuento = parseFloat(document.getElementById('descuento_global')?.value) || 0;
        const total = subtotal + totalIGV - descuento;
        
        // Actualizar resumen
        const subtotalEl = document.getElementById('subtotal_display');
        const igvEl = document.getElementById('igv_display');
        const descuentoEl = document.getElementById('descuento_display');
        const totalEl = document.getElementById('total_display');
        
        if (subtotalEl) subtotalEl.textContent = 'S/ ' + subtotal.toFixed(2);
        if (igvEl) igvEl.textContent = 'S/ ' + totalIGV.toFixed(2);
        if (descuentoEl) descuentoEl.textContent = 'S/ ' + descuento.toFixed(2);
        if (totalEl) totalEl.textContent = 'S/ ' + total.toFixed(2);
        
        // Actualizar campos ocultos del formulario
        const subtotalInput = document.getElementById('subtotal');
        const igvInput = document.getElementById('igv');
        const totalInput = document.getElementById('total');
        
        if (subtotalInput) subtotalInput.value = subtotal.toFixed(2);
        if (igvInput) igvInput.value = totalIGV.toFixed(2);
        if (totalInput) totalInput.value = total.toFixed(2);
    };
    
    // Evento para descuento global
    const descuentoGlobal = document.getElementById('descuento_global');
    if (descuentoGlobal) {
        descuentoGlobal.addEventListener('input', recalcularTotales);
    }
    
    // ============================================================
    // 2. ANULAR COMPRA (SweetAlert confirmación)
    // ============================================================
    
    const btnAnularCompra = document.getElementById('btnAnularCompra');
    if (btnAnularCompra) {
        btnAnularCompra.addEventListener('click', function(e) {
            e.preventDefault();
            const idCompra = this.getAttribute('data-id');
            const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';
            
            Swal.fire({
                title: '¿Anular compra?',
                text: 'Esta acción revertirá el inventario y no se puede deshacer.',
                icon: 'warning',
                input: 'textarea',
                inputPlaceholder: 'Ingrese el motivo de la anulación...',
                inputAttributes: {
                    'aria-label': 'Motivo de anulación'
                },
                showCancelButton: true,
                confirmButtonColor: '#dc3545',
                cancelButtonColor: '#6c757d',
                confirmButtonText: 'Sí, anular compra',
                cancelButtonText: 'Cancelar',
                preConfirm: (motivo) => {
                    if (!motivo || motivo.trim() === '') {
                        Swal.showValidationMessage('Debe ingresar un motivo para anular la compra');
                        return false;
                    }
                    return motivo;
                }
            }).then((result) => {
                if (result.isConfirmed) {
                    // Realizar petición AJAX
                    fetch('index.php?controller=compra&action=anular', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                        },
                        body: JSON.stringify({
                            id_compra: idCompra,
                            motivo: result.value,
                            csrf_token: csrfToken
                        })
                    })
                    .then(response => response.json())
                    .then(data => {
                        if (data.success) {
                            Swal.fire({
                                title: '¡Anulada!',
                                text: data.mensaje,
                                icon: 'success',
                                confirmButtonColor: '#3085d6'
                            }).then(() => {
                                window.location.href = 'index.php?controller=compra&action=ver&id=' + idCompra;
                            });
                        } else {
                            Swal.fire({
                                title: 'Error',
                                text: data.mensaje,
                                icon: 'error',
                                confirmButtonColor: '#3085d6'
                            });
                        }
                    })
                    .catch(error => {
                        Swal.fire({
                            title: 'Error',
                            text: 'Error de conexión al servidor.',
                            icon: 'error'
                        });
                    });
                }
            });
        });
    }
    
    // ============================================================
    // 3. ELIMINAR FILA DE PRODUCTO (dinámico)
    // ============================================================
    
    document.querySelectorAll('.btn-remove-producto').forEach(btn => {
        btn.addEventListener('click', function() {
            this.closest('.producto-row')?.remove();
            recalcularTotales();
        });
    });
    
    // ============================================================
    // 4. FILTROS - Auto submit al cambiar valores
    // ============================================================
    
    const filterForm = document.getElementById('filter-form');
    const filterInputs = document.querySelectorAll('.filter-auto-submit');
    
    if (filterForm) {
        filterInputs.forEach(input => {
            input.addEventListener('change', function() {
                filterForm.submit();
            });
        });
    }
    
    // ============================================================
    // 5. SELECCIÓN DE PROVEEDOR (autocomplete básico)
    // ============================================================
    
    const proveedorSelect = document.getElementById('proveedor_id');
    if (proveedorSelect) {
        proveedorSelect.addEventListener('change', function() {
            const proveedorId = this.value;
            if (proveedorId && window.cargarCondicionesPago) {
                cargarCondicionesPago(proveedorId);
            }
        });
    }
    
    // ============================================================
    // 6. INICIALIZAR recalcularTotales si hay productos
    // ============================================================
    
    if (document.querySelector('.producto-row')) {
        recalcularTotales();
    }
});

// ============================================================
// FUNCIONES GLOBALES
// ============================================================

function cargarCondicionesPago(proveedorId) {
    // Implementar según necesidad - puede cargar condiciones de pago del proveedor
    console.log('Cargar condiciones para proveedor:', proveedorId);
}

function eliminarProducto(btn) {
    if (confirm('¿Eliminar este producto de la compra?')) {
        btn.closest('.producto-row')?.remove();
        if (typeof recalcularTotales === 'function') {
            recalcularTotales();
        }
    }
}