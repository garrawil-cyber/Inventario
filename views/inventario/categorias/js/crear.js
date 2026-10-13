/* ═══════════════════════════════════════════════════════════════════════
   SISTEMA DE CATEGORÍAS — MÓDULO COMPLETO
   Funciones: CRUD, Validación en tiempo real, Dashboard, Gráficos
   ═══════════════════════════════════════════════════════════════════════ */

'use strict';

// ============================================================
// VARIABLES GLOBALES
// ============================================================
let nombreValido = false;
let debounceTimer = null;
let verificandoNombre = false;

// Variables para gráficos (globales)
let stockChart = null;
let distribucionChart = null;
let topProductosChart = null;
let topValorChart = null;

// ============================================================
// UTILIDADES
// ============================================================
function escapeHtml(str) {
    if (!str) return '';
    return str.replace(/[&<>]/g, m =>
        m === '&' ? '&amp;' : m === '<' ? '&lt;' : '&gt;'
    );
}

function formatNumber(num) {
    if (num === null || num === undefined) return '0';
    return new Intl.NumberFormat('es-PE').format(num);
}

// ============================================================
// SWEETALERT
// ============================================================
function showWarning(message, title = 'Advertencia') {
    return Swal.fire({
        icon: 'warning',
        title: title,
        text: message,
        confirmButtonColor: '#f59e0b',
        confirmButtonText: 'Aceptar'
    });
}

function showError(message, title = 'Error') {
    return Swal.fire({
        icon: 'error',
        title: title,
        text: message,
        confirmButtonColor: '#ef4444',
        confirmButtonText: 'Entendido'
    });
}

// ============================================================
// VERIFICAR NOMBRE (VALIDACIÓN EN TIEMPO REAL)
// ============================================================
async function verificarNombre(nombre, excludeId = 0) {
    const input = document.getElementById('inputNombre');
    const feedback = document.getElementById('nombreFeedback');
    const icon = document.getElementById('iconNombreStatus');
    
    if (!input || !feedback || !icon) return;
    
    if (debounceTimer) clearTimeout(debounceTimer);
    
    if (!nombre || nombre.trim() === '') {
        feedback.innerHTML = '';
        feedback.className = 'form-text mt-1';
        icon.className = 'fas fa-circle text-secondary';
        icon.style.fontSize = '.65rem';
        input.classList.remove('is-valid', 'is-invalid');
        nombreValido = false;
        verificandoNombre = false;
        return;
    }
    
    verificandoNombre = true;
    
    feedback.innerHTML = '<span class="text-muted"><i class="fas fa-spinner fa-spin"></i> Verificando...</span>';
    feedback.className = 'form-text mt-1 text-muted';
    icon.className = 'fas fa-spinner fa-spin text-muted';
    icon.style.fontSize = '1rem';
    input.classList.remove('is-valid', 'is-invalid');
    nombreValido = false;
    
    debounceTimer = setTimeout(async () => {
        try {
            const url = `index.php?controller=categoria&action=checkNombre&nombre=${encodeURIComponent(nombre.trim())}&excluir=${excludeId}`;
            
            const response = await fetch(url, {
                headers: { 'X-Requested-With': 'XMLHttpRequest' },
                credentials: 'same-origin'
            });
            
            if (!response.ok) throw new Error(`HTTP ${response.status}`);
            
            const data = await response.json();
            
            if (data.disponible === true) {
                feedback.innerHTML = '<span class="text-success"><i class="fas fa-check-circle"></i> Nombre disponible</span>';
                feedback.className = 'form-text mt-1 text-success';
                icon.className = 'fas fa-check-circle text-success';
                icon.style.fontSize = '1rem';
                input.classList.add('is-valid');
                input.classList.remove('is-invalid');
                nombreValido = true;
            } else {
                const mensaje = data.mensaje || 'Ya existe una categoría con ese nombre';
                feedback.innerHTML = `<span class="text-danger"><i class="fas fa-times-circle"></i> ${escapeHtml(mensaje)}</span>`;
                feedback.className = 'form-text mt-1 text-danger';
                icon.className = 'fas fa-times-circle text-danger';
                icon.style.fontSize = '1rem';
                input.classList.add('is-invalid');
                input.classList.remove('is-valid');
                nombreValido = false;
            }
        } catch (error) {
            console.error('Error en verificación:', error);
            feedback.innerHTML = '<span class="text-warning"><i class="fas fa-exclamation-triangle"></i> Error de conexión</span>';
            feedback.className = 'form-text mt-1 text-warning';
            icon.className = 'fas fa-exclamation-triangle text-warning';
            icon.style.fontSize = '1rem';
            nombreValido = false;
        } finally {
            verificandoNombre = false;
        }
    }, 500);
}

// ============================================================
// INICIALIZAR VALIDACIÓN
// ============================================================
function initValidacionNombre(excludeId = 0) {
    const inputNombre = document.getElementById('inputNombre');
    const textarea = document.getElementById('descripcion');
    const descCount = document.getElementById('descCount');
    
    if (!inputNombre) {
        console.error('No se encontró el campo inputNombre');
        return;
    }
    
    if (textarea && descCount) {
        const updateCharCount = () => {
            descCount.textContent = `${textarea.value.length} / 500`;
        };
        textarea.addEventListener('input', updateCharCount);
        updateCharCount();
    }
    
    inputNombre.addEventListener('input', function() {
        verificarNombre(this.value, excludeId);
    });
    
    inputNombre.addEventListener('blur', function() {
        if (this.value.trim() !== '' && !nombreValido && !verificandoNombre) {
            verificarNombre(this.value, excludeId);
        }
    });
    
    if (inputNombre.value.trim() !== '') {
        verificarNombre(inputNombre.value, excludeId);
    } else {
        nombreValido = false;
    }
    
    console.log('✅ Validación inicializada', excludeId > 0 ? '(edición)' : '(creación)');
}

// ============================================================
// FLASH ALERT (mensajes del servidor)
// ============================================================
function initFlashAlert() {
    if (typeof categoriaSweetAlert !== 'undefined' && categoriaSweetAlert && categoriaSweetAlert.text) {
        Swal.fire({
            title: categoriaSweetAlert.title || 'Aviso',
            text: categoriaSweetAlert.text,
            icon: categoriaSweetAlert.type || 'info',
            confirmButtonColor: '#3b82f6',
            confirmButtonText: 'Aceptar',
            timer: categoriaSweetAlert.type === 'success' ? 3000 : undefined,
            timerProgressBar: categoriaSweetAlert.type === 'success'
        });
    }
}

// ============================================================
// FORMULARIO CREAR
// ============================================================
function initFormCrear() {
    const form = document.getElementById('formCrearCategoria');
    if (!form) return;
    
    form.addEventListener('submit', async function(e) {
        e.preventDefault();
        
        const inputNombre = document.getElementById('inputNombre');
        const nombre = inputNombre?.value.trim() || '';
        
        if (!nombre) {
            inputNombre?.classList.add('is-invalid');
            inputNombre?.focus();
            Swal.fire({
                icon: 'warning',
                title: 'Campo requerido',
                text: 'El nombre de la Categoría es obligatorio.',
                confirmButtonText: 'Entendido',
                confirmButtonColor: '#0d9488',
            });
            return;
        }
        
        if (verificandoNombre) {
            await showWarning('Espera a que se verifique el nombre...', 'Verificando');
            return;
        }
        
        if (!nombreValido) {
            await showWarning('El nombre ingresado no está disponible. Por favor, elige otro nombre.', 'Nombre no válido');
            inputNombre?.focus();
            return;
        }
        
        const result = await Swal.fire({
            icon: 'question',
            title: '¿Crear categoría?',
            html: `Se creará <strong>${escapeHtml(nombre)}</strong> con estado <strong>Activo</strong>.`,
            showCancelButton: true,
            confirmButtonColor: '#3b82f6',
            cancelButtonColor: '#64748b',
            confirmButtonText: '<i class="fas fa-save me-2"></i>Sí, crear',
            cancelButtonText: 'Cancelar'
        });

        if (!result.isConfirmed) return;

        const btn = document.getElementById('btnGuardar');
        if (btn) {
            btn.disabled = true;
            btn.innerHTML = '<span class="spinner-border spinner-border-sm me-1"></span> Guardando...';
        }

        form.submit();
    });
}

// ============================================================
// FORMULARIO EDITAR
// ============================================================
function initFormEditar() {
    const form = document.getElementById('formEditarCategoria');
    if (!form) return;
    
    form.addEventListener('submit', async (e) => {
        e.preventDefault();
        
        const inputNombre = document.getElementById('inputNombre');
        const nombre = inputNombre?.value.trim() || '';
        
        if (nombre === '') {
            await showWarning('El nombre de la categoría es obligatorio.', 'Campo requerido');
            inputNombre?.focus();
            return;
        }
        
        if (verificandoNombre) {
            await showWarning('Espera a que se verifique el nombre...', 'Verificando');
            return;
        }
        
        if (!nombreValido) {
            await showWarning('El nombre ingresado no está disponible. Elige otro.', 'Nombre no válido');
            inputNombre?.focus();
            return;
        }
        
        const confirm = await Swal.fire({
            icon: 'question',
            title: '¿Actualizar categoría?',
            html: `Se guardarán los cambios en <strong>${escapeHtml(nombre)}</strong>.`,
            showCancelButton: true,
            confirmButtonColor: '#3b82f6',
            cancelButtonColor: '#64748b',
            confirmButtonText: '<i class="fas fa-save me-2"></i>Sí, actualizar',
            cancelButtonText: 'Cancelar'
        });
        
        if (!confirm.isConfirmed) return;
        
        form.submit();
    });
    
}

// ============================================================
// MENSAJES FLASH EN INDEX
// ============================================================
function initFlashMessages() {
    if (typeof flashMessage !== 'undefined' && flashMessage && flashMessage !== 'null') {
        Swal.fire({
            icon: flashType || 'success',
            title: flashTitle || (flashType === 'success' ? '¡Éxito!' : 'Aviso'),
            text: flashMessage,
            confirmButtonColor: '#3b82f6',
            confirmButtonText: 'Aceptar',
            timer: flashType === 'success' ? 3000 : undefined,
            timerProgressBar: flashType === 'success'
        });
    }
}

// ============================================================
// BOTONES DE ACCIÓN (Eliminar/Activar)
// ============================================================
function initActionButtons() {
    document.querySelectorAll('.btn-delete').forEach(btn => {
        btn.addEventListener('click', (e) => {
            e.preventDefault();
            Swal.fire({
                title: '¿Desactivar categoría?',
                text: `¿Desactivar "${btn.dataset.nombre}"?`,
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#ef4444',
                cancelButtonColor: '#64748b',
                confirmButtonText: '<i class="fas fa-trash me-2"></i>Sí, desactivar',
                cancelButtonText: 'Cancelar'
            }).then(result => {
                if (result.isConfirmed) window.location.href = btn.dataset.url;
            });
        });
    });
    
    document.querySelectorAll('.btn-activate').forEach(btn => {
        btn.addEventListener('click', (e) => {
            e.preventDefault();
            Swal.fire({
                title: '¿Activar categoría?',
                text: `¿Activar "${btn.dataset.nombre}"?`,
                icon: 'question',
                showCancelButton: true,
                confirmButtonColor: '#3b82f6',
                confirmButtonText: '<i class="fas fa-check me-2"></i>Sí, activar',
                cancelButtonText: 'Cancelar'
            }).then(result => {
                if (result.isConfirmed) window.location.href = btn.dataset.url;
            });
        });
    });
}

// ============================================================
// FUNCIONES DEL DASHBOARD (exportadas globalmente)
// ============================================================

// Cargar estadísticas del dashboard
window.cargarEstadisticas = function() {
    console.log('🔄 Cargando estadísticas del dashboard...');
    
    fetch('index.php?controller=categoria&action=getDashboardStats')
        .then(res => res.json())
        .then(data => {
            if (data.success) {
                console.log('✅ Datos recibidos:', data);
                window.actualizarTarjetas(data.general);
                window.actualizarContadores(data.general);
                window.actualizarGraficosPrincipales(data);
                window.actualizarAlertas(data.alertas);
            } else {
                console.error('Error en datos:', data);
            }
        })
        .catch(err => console.error('Error al cargar estadísticas:', err));
};

// Actualizar tarjetas de estadísticas
window.actualizarTarjetas = function(general) {
    if (!general) return;
    
    const totalCategorias = document.getElementById('totalCategorias');
    const totalProductos = document.getElementById('totalProductos');
    const stockTotal = document.getElementById('stockTotal');
    const valorInventario = document.getElementById('valorInventario');
    
    if (totalCategorias) totalCategorias.textContent = general.categorias_activas || 0;
    if (totalProductos) totalProductos.textContent = general.total_productos || 0;
    if (stockTotal) stockTotal.textContent = formatNumber(general.stock_total || 0);
    if (valorInventario) valorInventario.innerHTML = 'S/ ' + formatNumber(general.valor_inventario || 0);
    
    console.log('📊 Tarjetas actualizadas');
};

// Actualizar contadores de activas/inactivas
window.actualizarContadores = function(general) {
    if (!general) return;
    
    const countActivas = document.getElementById('countActivas');
    const countInactivas = document.getElementById('countInactivas');
    
    if (countActivas) countActivas.textContent = general.categorias_activas || 0;
    if (countInactivas) countInactivas.textContent = general.categorias_inactivas || 0;
};

// Actualizar gráficos principales
window.actualizarGraficosPrincipales = function(data) {
    console.log('📈 Actualizando gráficos...');
    
    // Stock por categoría (gráfico de barras)
    if (data.stock && data.stock.length > 0) {
        const canvas = document.getElementById('stockChart');
        if (canvas) {
            if (stockChart) stockChart.destroy();
            
            const ctx = canvas.getContext('2d');
            stockChart = new Chart(ctx, {
                type: 'bar',
                data: {
                    labels: data.stock.map(i => i.nombre),
                    datasets: [{
                        label: 'Stock Total',
                        data: data.stock.map(i => parseInt(i.stock_total) || 0),
                        backgroundColor: 'rgba(59, 130, 246, 0.7)',
                        borderRadius: 8
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: true,
                    scales: {
                        y: { beginAtZero: true, title: { display: true, text: 'Unidades' } }
                    }
                }
            });
        }
    }
    
    // Distribución de productos (gráfico de donut)
    if (data.distribucion && data.distribucion.length > 0) {
        const canvas = document.getElementById('distribucionChart');
        if (canvas) {
            if (distribucionChart) distribucionChart.destroy();
            
            const ctx = canvas.getContext('2d');
            const colores = ['#3b82f6', '#10b981', '#f59e0b', '#ef4444', '#8b5cf6', '#06b6d4', '#ec4899'];
            
            distribucionChart = new Chart(ctx, {
                type: 'doughnut',
                data: {
                    labels: data.distribucion.map(i => i.nombre),
                    datasets: [{
                        data: data.distribucion.map(i => i.productos_activos || 0),
                        backgroundColor: colores.slice(0, data.distribucion.length),
                        borderWidth: 2,
                        borderColor: '#fff'
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: true,
                    plugins: {
                        legend: { position: 'bottom' },
                        tooltip: { callbacks: { label: ctx => `${ctx.label}: ${ctx.raw} productos` } }
                    }
                }
            });
        }
    }
    
    // Top 5 - Más Productos
    if (data.top_productos && data.top_productos.length > 0) {
        const canvas = document.getElementById('topProductosChart');
        if (canvas) {
            if (topProductosChart) topProductosChart.destroy();
            
            const ctx = canvas.getContext('2d');
            topProductosChart = new Chart(ctx, {
                type: 'bar',
                data: {
                    labels: data.top_productos.map(i => i.nombre),
                    datasets: [{
                        label: 'Productos',
                        data: data.top_productos.map(i => i.total_productos || 0),
                        backgroundColor: 'rgba(245, 158, 11, 0.7)',
                        borderRadius: 8
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: true,
                    indexAxis: 'y',
                    plugins: { legend: { position: 'top' } }
                }
            });
        }
    }
    
    // Top 5 - Mayor Valor
    if (data.top_valor && data.top_valor.length > 0) {
        const canvas = document.getElementById('topValorChart');
        if (canvas) {
            if (topValorChart) topValorChart.destroy();
            
            const ctx = canvas.getContext('2d');
            topValorChart = new Chart(ctx, {
                type: 'bar',
                data: {
                    labels: data.top_valor.map(i => i.nombre),
                    datasets: [{
                        label: 'Valor (S/)',
                        data: data.top_valor.map(i => parseFloat(i.valor_inventario) || 0),
                        backgroundColor: 'rgba(16, 185, 129, 0.7)',
                        borderRadius: 8
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: true,
                    indexAxis: 'y',
                    plugins: {
                        tooltip: { callbacks: { label: ctx => `S/ ${formatNumber(ctx.raw)}` } }
                    }
                }
            });
        }
    }
    
    console.log('✅ Gráficos actualizados');
};

// Actualizar tabla de alertas
window.actualizarAlertas = function(alertas) {
    const tbody = document.getElementById('alertasBody');
    if (!tbody) return;
    
    if (!alertas || alertas.length === 0) {
        tbody.innerHTML = '<tr><td colspan="5" class="text-center py-4 text-muted">No hay alertas de stock</td></tr>';
        return;
    }
    
    tbody.innerHTML = alertas.map(a => `
        <tr>
            <td><strong>${escapeHtml(a.nombre)}</strong></td>
            <td class="text-center">${a.stock_bajo > 0 ? `<span class="badge bg-warning">${a.stock_bajo} productos</span>` : '-'}</td>
            <td class="text-center">${a.sin_stock > 0 ? `<span class="badge bg-danger">${a.sin_stock} productos</span>` : '-'}</td>
            <td class="text-center">${a.stock_alto > 0 ? `<span class="badge bg-info">${a.stock_alto} productos</span>` : '-'}</td>
            <td class="text-center">
                <a href="index.php?controller=categoria&action=ver&id=${a.id_categoria}" class="btn btn-sm btn-outline-primary">
                    <i class="fas fa-eye"></i> Ver
                </a>
            </td>
        </tr>
    `).join('');
};

// Toggle del dashboard
window.toggleDashboard = function() {
    const panel = document.getElementById('dashboardPanel');
    if (!panel) return;
    
    const isVisible = panel.style.display === 'block';
    panel.style.display = isVisible ? 'none' : 'block';
    
    if (!isVisible) {
        console.log('📊 Mostrando dashboard, cargando datos...');
        setTimeout(() => {
            if (typeof window.cargarEstadisticas === 'function') {
                window.cargarEstadisticas();
            }
        }, 100);
    }
};

// ============================================================
// INICIALIZACIÓN PRINCIPAL
// ============================================================
document.addEventListener('DOMContentLoaded', () => {
    console.log('🚀 Inicializando módulo de categorías...');
    
    initFlashAlert();
    initActionButtons();
    initFlashMessages();
    
    // Vista CREAR
    if (document.getElementById('formCrearCategoria')) {
        console.log('📝 Vista: CREAR categoría');
        initValidacionNombre(0);
        initFormCrear();
    }
    
    // Vista EDITAR
    if (document.getElementById('formEditarCategoria')) {
        console.log('✏️ Vista: EDITAR categoría');
        const categoriaId = typeof CATEGORIA_ID !== 'undefined' ? CATEGORIA_ID : 0;
        initValidacionNombre(categoriaId);
        initFormEditar();
    }
    
    // Vista INDEX (con dashboard)
    if (document.getElementById('dashboardPanel')) {
        console.log('📊 Vista: INDEX con dashboard');
        // Los contadores se actualizarán cuando se abra el dashboard
    }
    
    console.log('✅ Módulo de categorías listo');
});