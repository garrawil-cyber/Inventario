/**
 * ============================================================
 * UBICACIONES MODULE JS
 * Sistema: RopaVirtual - Módulo de Ubicaciones
 * Requiere: SweetAlert2
 * ============================================================
 */

// Esperar a que el DOM esté listo
document.addEventListener('DOMContentLoaded', function() {
    
    // ============================================================
    // 1. ELIMINAR UBICACIÓN (SweetAlert confirmación)
    // ============================================================
    
    const btnEliminarUbicacion = document.getElementById('btnEliminarUbicacion');
    if (btnEliminarUbicacion) {
        btnEliminarUbicacion.addEventListener('click', function(e) {
            e.preventDefault();
            const idUbicacion = this.getAttribute('data-id');
            const codigoUbicacion = this.getAttribute('data-codigo');
            const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';
            
            Swal.fire({
                title: '¿Eliminar ubicación?',
                html: `Estás a punto de eliminar la ubicación <strong>${codigoUbicacion}</strong>.<br><br>⚠️ Solo se puede eliminar si no tiene productos asignados.<br><br>¿Deseas continuar?`,
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#dc3545',
                cancelButtonColor: '#6c757d',
                confirmButtonText: 'Sí, eliminar',
                cancelButtonText: 'Cancelar'
            }).then((result) => {
                if (result.isConfirmed) {
                    fetch('index.php?controller=ubicacion&action=eliminar', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                        },
                        body: JSON.stringify({
                            id: idUbicacion,
                            csrf_token: csrfToken
                        })
                    })
                    .then(response => response.json())
                    .then(data => {
                        if (data.success) {
                            Swal.fire({
                                title: '¡Eliminada!',
                                text: data.mensaje,
                                icon: 'success',
                                confirmButtonColor: '#3085d6'
                            }).then(() => {
                                window.location.href = 'index.php?controller=ubicacion&action=index';
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
    // 2. ASIGNAR PRODUCTO A UBICACIÓN (Modal)
    // ============================================================
    
    const btnAsignarProducto = document.getElementById('btnAsignarProducto');
    if (btnAsignarProducto) {
        btnAsignarProducto.addEventListener('click', function() {
            const idUbicacion = this.getAttribute('data-id');
            const codigoUbicacion = this.getAttribute('data-codigo');
            const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';
            
            Swal.fire({
                title: `Asignar producto a ${codigoUbicacion}`,
                html: `
                    <div class="text-start">
                        <div class="mb-3">
                            <label class="form-label">Seleccione un producto</label>
                            <select id="productoSelect" class="form-select">
                                <option value="">Cargando productos...</option>
                            </select>
                        </div>
                        <div class="alert alert-info small">
                            <i class="fas fa-info-circle me-1"></i>
                            La ubicación debe tener capacidad disponible para asignar el producto.
                        </div>
                    </div>
                `,
                showCancelButton: true,
                confirmButtonText: 'Asignar',
                cancelButtonText: 'Cancelar',
                preConfirm: () => {
                    const productoId = document.getElementById('productoSelect').value;
                    if (!productoId) {
                        Swal.showValidationMessage('Debe seleccionar un producto');
                        return false;
                    }
                    return { producto_id: productoId };
                },
                didOpen: () => {
                    // Cargar productos disponibles
                    fetch(`index.php?controller=producto&action=getForSelect&estado=activo&limit=100`)
                        .then(response => response.json())
                        .then(data => {
                            const select = document.getElementById('productoSelect');
                            if (data.success && data.data.length > 0) {
                                select.innerHTML = '<option value="">Seleccione un producto...</option>' +
                                    data.data.map(p => `<option value="${p.value}">${p.text}</option>`).join('');
                            } else {
                                select.innerHTML = '<option value="">No hay productos disponibles</option>';
                            }
                        })
                        .catch(error => {
                            document.getElementById('productoSelect').innerHTML = '<option value="">Error cargando productos</option>';
                        });
                }
            }).then((result) => {
                if (result.isConfirmed && result.value) {
                    fetch('index.php?controller=ubicacion&action=asignarProducto', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                        },
                        body: JSON.stringify({
                            id_ubicacion: idUbicacion,
                            id_producto: result.value.producto_id,
                            csrf_token: csrfToken
                        })
                    })
                    .then(response => response.json())
                    .then(data => {
                        if (data.success) {
                            Swal.fire({
                                title: '¡Asignado!',
                                text: data.mensaje,
                                icon: 'success',
                                confirmButtonColor: '#3085d6'
                            }).then(() => {
                                location.reload();
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
    // 3. ACTUALIZAR ESTADO DE UBICACIÓN (AJAX)
    // ============================================================
    
    const btnActualizarEstado = document.getElementById('btnActualizarEstado');
    if (btnActualizarEstado) {
        btnActualizarEstado.addEventListener('click', function() {
            const idUbicacion = this.getAttribute('data-id');
            const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';
            
            Swal.fire({
                title: 'Actualizar estado',
                text: '¿Deseas recalcular el estado de esta ubicación según los productos que contiene?',
                icon: 'question',
                showCancelButton: true,
                confirmButtonColor: '#3085d6',
                confirmButtonText: 'Sí, actualizar',
                cancelButtonText: 'Cancelar'
            }).then((result) => {
                if (result.isConfirmed) {
                    fetch('index.php?controller=ubicacion&action=actualizarEstado', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                        },
                        body: JSON.stringify({
                            id: idUbicacion,
                            csrf_token: csrfToken
                        })
                    })
                    .then(response => response.json())
                    .then(data => {
                        if (data.success) {
                            Swal.fire({
                                title: '¡Actualizado!',
                                text: data.mensaje,
                                icon: 'success',
                                confirmButtonColor: '#3085d6'
                            }).then(() => {
                                location.reload();
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
    // 4. FILTROS - Auto submit
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
    // 5. SELECCIÓN DE UBICACIÓN EN GRID (para asignación rápida)
    // ============================================================
    
    const ubicacionCards = document.querySelectorAll('.ubicacion-card');
    ubicacionCards.forEach(card => {
        card.addEventListener('click', function() {
            const id = this.getAttribute('data-id');
            const codigo = this.getAttribute('data-codigo');
            const estado = this.getAttribute('data-estado');
            
            // Mostrar información detallada en modal o SweetAlert
            Swal.fire({
                title: `Ubicación: ${codigo}`,
                html: `
                    <div class="text-start">
                        <p><strong>Estado:</strong> <span class="badge bg-${estado === 'disponible' ? 'success' : (estado === 'ocupado' ? 'danger' : 'warning')}">${estado}</span></p>
                        <p><strong>Capacidad:</strong> ${this.getAttribute('data-capacidad') || 'Sin límite'} productos</p>
                        <p><strong>Productos actuales:</strong> ${this.getAttribute('data-productos') || 0}</p>
                        <hr>
                        <div class="d-grid gap-2">
                            <a href="index.php?controller=ubicacion&action=ver&id=${id}" class="btn btn-primary btn-sm">
                                <i class="fas fa-eye me-1"></i>Ver detalles
                            </a>
                            <button onclick="asignarProductoRapido(${id}, '${codigo}')" class="btn btn-success btn-sm">
                                <i class="fas fa-box me-1"></i>Asignar producto
                            </button>
                        </div>
                    </div>
                `,
                icon: 'info',
                confirmButtonText: 'Cerrar',
                showCancelButton: false
            });
        });
    });
    
    // ============================================================
    // 6. CARGAR UBICACIONES DISPONIBLES (AJAX para formularios)
    // ============================================================
    
    const almacenSelect = document.getElementById('id_almacen');
    const ubicacionSelect = document.getElementById('id_ubicacion');
    
    if (almacenSelect && ubicacionSelect) {
        almacenSelect.addEventListener('change', function() {
            const idAlmacen = this.value;
            if (idAlmacen) {
                fetch(`index.php?controller=ubicacion&action=getForSelect&id_almacen=${idAlmacen}&estado=disponible`)
                    .then(response => response.json())
                    .then(data => {
                        if (data.success) {
                            ubicacionSelect.innerHTML = '<option value="">Seleccione una ubicación...</option>' +
                                data.data.map(u => `<option value="${u.value}">${u.text}</option>`).join('');
                        } else {
                            ubicacionSelect.innerHTML = '<option value="">No hay ubicaciones disponibles</option>';
                        }
                    })
                    .catch(error => {
                        ubicacionSelect.innerHTML = '<option value="">Error cargando ubicaciones</option>';
                    });
            } else {
                ubicacionSelect.innerHTML = '<option value="">Primero seleccione un almacén</option>';
            }
        });
    }
    
    // ============================================================
    // 7. TOOLTIPS
    // ============================================================
    
    const tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
    tooltipTriggerList.map(function(tooltipTriggerEl) {
        return new bootstrap.Tooltip(tooltipTriggerEl);
    });
});

// ============================================================
// FUNCIONES GLOBALES
// ============================================================

function asignarProductoRapido(idUbicacion, codigoUbicacion) {
    const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';
    
    Swal.fire({
        title: `Asignar producto a ${codigoUbicacion}`,
        html: `
            <div class="text-start">
                <div class="mb-3">
                    <label class="form-label">Seleccione un producto</label>
                    <select id="productoSelectRapido" class="form-select">
                        <option value="">Cargando productos...</option>
                    </select>
                </div>
            </div>
        `,
        showCancelButton: true,
        confirmButtonText: 'Asignar',
        cancelButtonText: 'Cancelar',
        preConfirm: () => {
            const productoId = document.getElementById('productoSelectRapido').value;
            if (!productoId) {
                Swal.showValidationMessage('Debe seleccionar un producto');
                return false;
            }
            return { producto_id: productoId };
        },
        didOpen: () => {
            fetch(`index.php?controller=producto&action=getForSelect&estado=activo&limit=100`)
                .then(response => response.json())
                .then(data => {
                    const select = document.getElementById('productoSelectRapido');
                    if (data.success && data.data.length > 0) {
                        select.innerHTML = '<option value="">Seleccione un producto...</option>' +
                            data.data.map(p => `<option value="${p.value}">${p.text}</option>`).join('');
                    } else {
                        select.innerHTML = '<option value="">No hay productos disponibles</option>';
                    }
                })
                .catch(error => {
                    document.getElementById('productoSelectRapido').innerHTML = '<option value="">Error cargando productos</option>';
                });
        }
    }).then((result) => {
        if (result.isConfirmed && result.value) {
            fetch('index.php?controller=ubicacion&action=asignarProducto', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({
                    id_ubicacion: idUbicacion,
                    id_producto: result.value.producto_id,
                    csrf_token: csrfToken
                })
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    Swal.fire({
                        title: '¡Asignado!',
                        text: data.mensaje,
                        icon: 'success',
                        confirmButtonColor: '#3085d6'
                    }).then(() => {
                        location.reload();
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
}

function verDetalleUbicacion(idUbicacion) {
    window.location.href = `index.php?controller=ubicacion&action=ver&id=${idUbicacion}`;
}