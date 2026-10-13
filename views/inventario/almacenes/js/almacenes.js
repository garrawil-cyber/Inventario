/**
 * ============================================================
 * ALMACENES MODULE JS
 * Sistema: RopaVirtual - Módulo de Almacenes
 * Requiere: SweetAlert2
 * ============================================================
 */

// Esperar a que el DOM esté listo
document.addEventListener('DOMContentLoaded', function() {
    
    // ============================================================
    // 1. ELIMINAR ALMACÉN (SweetAlert confirmación)
    // ============================================================
    
    const btnEliminarAlmacen = document.getElementById('btnEliminarAlmacen');
    if (btnEliminarAlmacen) {
        btnEliminarAlmacen.addEventListener('click', function(e) {
            e.preventDefault();
            const idAlmacen = this.getAttribute('data-id');
            const nombreAlmacen = this.getAttribute('data-nombre');
            const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';
            
            Swal.fire({
                title: '¿Desactivar almacén?',
                html: `Estás a punto de desactivar <strong>${nombreAlmacen}</strong>.<br><br>Esta acción no se puede deshacer y también desactivará las ubicaciones asociadas.<br><br>¿Deseas continuar?`,
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#dc3545',
                cancelButtonColor: '#6c757d',
                confirmButtonText: 'Sí, desactivar',
                cancelButtonText: 'Cancelar'
            }).then((result) => {
                if (result.isConfirmed) {
                    fetch('index.php?controller=almacen&action=eliminar', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                        },
                        body: JSON.stringify({
                            id: idAlmacen,
                            csrf_token: csrfToken
                        })
                    })
                    .then(response => response.json())
                    .then(data => {
                        if (data.success) {
                            Swal.fire({
                                title: '¡Desactivado!',
                                text: data.mensaje,
                                icon: 'success',
                                confirmButtonColor: '#3085d6'
                            }).then(() => {
                                window.location.href = 'index.php?controller=almacen&action=index';
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
    // 2. FILTROS - Auto submit al cambiar valores
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
    // 3. TOOLTIPS
    // ============================================================
    
    const tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
    tooltipTriggerList.map(function(tooltipTriggerEl) {
        return new bootstrap.Tooltip(tooltipTriggerEl);
    });
    
    // ============================================================
    // 4. VER UBICACIONES DEL ALMACÉN (modal o redirección)
    // ============================================================
    
    const btnVerUbicaciones = document.querySelectorAll('.btn-ver-ubicaciones');
    btnVerUbicaciones.forEach(btn => {
        btn.addEventListener('click', function(e) {
            const idAlmacen = this.getAttribute('data-id');
            const nombreAlmacen = this.getAttribute('data-nombre');
            
            Swal.fire({
                title: `Ubicaciones de ${nombreAlmacen}`,
                text: '¿Deseas ver todas las ubicaciones de este almacén?',
                icon: 'info',
                showCancelButton: true,
                confirmButtonColor: '#3085d6',
                confirmButtonText: 'Ver ubicaciones',
                cancelButtonText: 'Cancelar'
            }).then((result) => {
                if (result.isConfirmed) {
                    window.location.href = `index.php?controller=almacen&action=ubicaciones&id=${idAlmacen}`;
                }
            });
        });
    });
    
    // ============================================================
    // 5. ACTUALIZAR CAPACIDAD EN TIEMPO REAL (formulario)
    // ============================================================
    
    const capacidadInput = document.getElementById('capacidad_maxima');
    const capacidadDisplay = document.getElementById('capacidad_display');
    
    if (capacidadInput && capacidadDisplay) {
        capacidadInput.addEventListener('input', function() {
            const valor = this.value;
            if (valor && valor > 0) {
                capacidadDisplay.textContent = `${valor} unidades`;
                capacidadDisplay.style.color = '#28a745';
            } else {
                capacidadDisplay.textContent = 'Sin límite';
                capacidadDisplay.style.color = '#6c757d';
            }
        });
    }
    
    // ============================================================
    // 6. CARGAR ESTADÍSTICAS DEL DASHBOARD (AJAX)
    // ============================================================
    
    const dashboardContainer = document.getElementById('dashboard-stats-container');
    if (dashboardContainer) {
        fetch('index.php?controller=almacen&action=dashboardStats')
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    actualizarDashboardStats(data);
                }
            })
            .catch(error => console.error('Error cargando estadísticas:', error));
    }
    
    function actualizarDashboardStats(data) {
        const general = data.resumen_general;
        const capacidadData = data.capacidad_por_almacen;
        
        // Actualizar resumen general
        const totalAlmacenes = document.getElementById('total_almacenes');
        const activos = document.getElementById('almacenes_activos');
        const inactivos = document.getElementById('almacenes_inactivos');
        const principales = document.getElementById('almacenes_principales');
        
        if (totalAlmacenes) totalAlmacenes.textContent = general.total_almacenes || 0;
        if (activos) activos.textContent = general.activos || 0;
        if (inactivos) inactivos.textContent = general.inactivos || 0;
        if (principales) principales.textContent = general.principales || 0;
        
        // Actualizar gráfico de capacidad (si existe)
        const capacidadChart = document.getElementById('capacidad-chart');
        if (capacidadChart && capacidadData.length > 0) {
            // Aquí puedes integrar un gráfico con Chart.js
            console.log('Datos de capacidad por almacén:', capacidadData);
        }
    }
});

// ============================================================
// FUNCIONES GLOBALES
// ============================================================

function cargarEstadisticasAlmacen(idAlmacen) {
    fetch(`index.php?controller=almacen&action=getEstadisticas&id=${idAlmacen}`)
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                // Actualizar estadísticas en la vista
                const ubicacionesDisponibles = document.getElementById('ubicaciones_disponibles');
                const ubicacionesOcupadas = document.getElementById('ubicaciones_ocupadas');
                const productosTotales = document.getElementById('productos_totales');
                const stockTotal = document.getElementById('stock_total');
                
                if (ubicacionesDisponibles) ubicacionesDisponibles.textContent = data.ubicaciones_disponibles || 0;
                if (ubicacionesOcupadas) ubicacionesOcupadas.textContent = data.ubicaciones_ocupadas || 0;
                if (productosTotales) productosTotales.textContent = data.productos_totales || 0;
                if (stockTotal) stockTotal.textContent = data.stock_total || 0;
            }
        })
        .catch(error => console.error('Error:', error));
}