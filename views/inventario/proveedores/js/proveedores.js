/**
 * PROVEEDORES MODULE - VERSIÓN SIMPLIFICADA Y CORREGIDA
 */

const ProveedoresModule = (function() {
    'use strict';

    // Estado
    let rucValido = false;
    let rucOriginal = '';
    let excludeId = null;

    // ============================================================
    // Utilidades
    // ============================================================
    function showLoading(button) {
        if (button) {
            button.disabled = true;
            button.innerHTML = '<i class="fas fa-spinner fa-spin me-2"></i>Procesando...';
        }
    }

    function hideLoading(button, originalText) {
        if (button) {
            button.disabled = false;
            button.innerHTML = originalText;
        }
    }

    function redirect(url) {
        window.location.href = url;
    }

    function escapeHtml(str) {
        if (!str) return '';
        const map = {
            '&': '&amp;',
            '<': '&lt;',
            '>': '&gt;',
            '"': '&quot;',
            "'": '&#39;'
        };
        return String(str).replace(/[&<>"']/g, c => map[c]);
    }

    // ============================================================
    // SweetAlert Helpers
    // ============================================================
    function showSuccess(message, redirectUrl = null) {
        Swal.fire({
            title: '¡Éxito!',
            text: message,
            icon: 'success',
            confirmButtonColor: '#4f46e5',
            confirmButtonText: 'Aceptar',
            timer: redirectUrl ? 2000 : undefined,
            showConfirmButton: true
        }).then(() => {
            if (redirectUrl) redirect(redirectUrl);
        });
    }

    function showError(message) {
        Swal.fire({
            title: 'Error',
            text: message,
            icon: 'error',
            confirmButtonColor: '#dc2626',
            confirmButtonText: 'Entendido'
        });
    }

    // ============================================================
    // Confirmaciones previas
    // ============================================================
    function confirmarMutacion(tipo, nombre = null) {
        let title = '';
        let text = '';
        let confirmText = '';
        
        if (tipo === 'create') {
            title = '¿Guardar nuevo proveedor?';
            text = 'Verifique que los datos sean correctos antes de guardar.';
            confirmText = 'Sí, guardar';
        } else if (tipo === 'update') {
            title = '¿Actualizar proveedor?';
            text = `¿Seguro de actualizar los datos de <strong>${escapeHtml(nombre)}</strong>?`;
            confirmText = 'Sí, actualizar';
        }
        
        return Swal.fire({
            title: title,
            html: text,
            icon: 'question',
            showCancelButton: true,
            confirmButtonColor: '#4f46e5',
            cancelButtonColor: '#6b7280',
            confirmButtonText: confirmText,
            cancelButtonText: 'Cancelar',
            focusCancel: true
        });
    }

    function confirmarDelete(nombre) {
        return Swal.fire({
            title: '¿Desactivar proveedor?',
            html: `¿Seguro de desactivar a <strong>${escapeHtml(nombre)}</strong>?<br>
                   <small class="text-muted">Si tiene productos, solo quedará inactivo.</small>`,
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#dc2626',
            cancelButtonColor: '#6b7280',
            confirmButtonText: 'Sí, desactivar',
            cancelButtonText: 'Cancelar',
            focusCancel: true
        });
    }

    function confirmarActivar(nombre) {
        return Swal.fire({
            title: '¿Activar proveedor?',
            html: `¿Seguro de activar a <strong>${escapeHtml(nombre)}</strong>?`,
            icon: 'question',
            showCancelButton: true,
            confirmButtonColor: '#059669',
            cancelButtonColor: '#6b7280',
            confirmButtonText: 'Sí, activar',
            cancelButtonText: 'Cancelar',
            focusCancel: true
        });
    }

    // ============================================================
    // Mutaciones API
    // ============================================================
    async function postForm(url, formData) {
        const response = await fetch(url, {
            method: 'POST',
            headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
            body: new URLSearchParams(formData)
        });
        return response.json();
    }

    async function getJson(url) {
        const response = await fetch(url);
        return response.json();
    }

    // ============================================================
    // VALIDACIÓN DE RUC (CORREGIDA - SIN CLONAR INPUT)
    // ============================================================
    function initRUCValidation() {
        const rucInput = document.getElementById('ruc');
        if (!rucInput) return;

        // Eliminar event listener anterior si existe
        const oldListener = rucInput._blurListener;
        if (oldListener) {
            rucInput.removeEventListener('blur', oldListener);
        }

        // Crear nueva función de validación
        const validateRUC = async function() {
            const ruc = this.value.trim();
            const mensajeDiv = document.getElementById('mensajeRUC');
            
            if (!ruc) {
                if (mensajeDiv) mensajeDiv.innerHTML = '';
                rucValido = false;
                return;
            }

            const rucRegex = /^\d{11}$/;
            if (!rucRegex.test(ruc)) {
                if (mensajeDiv) {
                    mensajeDiv.innerHTML = '<span class="text-danger"><i class="fas fa-times-circle"></i> El RUC debe tener 11 dígitos</span>';
                }
                rucValido = false;
                return;
            }

            // 🔑 Si el RUC no cambió en edición, no validar contra BD
            if (excludeId && ruc === rucOriginal) {
                if (mensajeDiv) {
                    mensajeDiv.innerHTML = '<span class="text-success"><i class="fas fa-check-circle"></i> RUC válido (sin cambios)</span>';
                }
                rucValido = true;
                return;
            }

            try {
                let url = `index.php?controller=proveedor&action=verificar_ruc&ruc=${encodeURIComponent(ruc)}`;
                if (excludeId) {
                    url += `&exclude_id=${excludeId}`;
                }
                
                const data = await getJson(url);
                
                if (mensajeDiv) {
                    if (data.disponible) {
                        mensajeDiv.innerHTML = '<span class="text-success"><i class="fas fa-check-circle"></i> RUC disponible</span>';
                        rucValido = true;
                    } else {
                        mensajeDiv.innerHTML = `<span class="text-danger"><i class="fas fa-times-circle"></i> ${data.mensaje}</span>`;
                        rucValido = false;
                    }
                }
            } catch (error) {
                console.error('Error verificando RUC:', error);
                if (mensajeDiv) {
                    mensajeDiv.innerHTML = '<span class="text-danger"><i class="fas fa-exclamation-triangle"></i> Error al verificar RUC</span>';
                }
                rucValido = false;
            }
        };

        // Guardar referencia y agregar listener
        rucInput._blurListener = validateRUC;
        rucInput.addEventListener('blur', validateRUC);
        
        // Trigger inicial si ya hay valor
        if (rucInput.value) {
            setTimeout(() => rucInput.dispatchEvent(new Event('blur')), 100);
        }
    }

    // ============================================================
    // Validación de formulario
    // ============================================================
    function validateForm() {
        const ruc = document.getElementById('ruc')?.value.trim();
        
        if (!ruc) {
            showError('El RUC es obligatorio');
            return false;
        }
        
        const rucRegex = /^\d{11}$/;
        if (!rucRegex.test(ruc)) {
            showError('El RUC debe tener 11 dígitos numéricos');
            return false;
        }
        
        if (!rucValido) {
            showError('El RUC no es válido o ya está registrado');
            return false;
        }
        
        return true;
    }

    // ============================================================
    // CREATE
    // ============================================================
    function initCreateForm() {
        const form = document.getElementById('proveedorForm');
        if (!form) return;

        form.addEventListener('submit', async (e) => {
            e.preventDefault();
            
            if (!validateForm()) return;
            
            const result = await confirmarMutacion('create');
            if (!result.isConfirmed) return;
            
            const submitBtn = form.querySelector('button[type="submit"]');
            const originalText = submitBtn.innerHTML;
            showLoading(submitBtn);
            
            try {
                const formData = new FormData(form);
                const data = await postForm('index.php?controller=proveedor&action=guardar', formData);
                
                if (data.success) {
                    showSuccess(data.mensaje, data.redirect);
                } else {
                    showError(data.mensaje);
                    hideLoading(submitBtn, originalText);
                }
            } catch (error) {
                console.error('Error:', error);
                showError('Error al guardar el proveedor');
                hideLoading(submitBtn, originalText);
            }
        });
    }

    // ============================================================
    // UPDATE
    // ============================================================
    function initUpdateForm(proveedorId, proveedorNombre) {
        const form = document.getElementById('proveedorForm');
        if (!form) return;

        form.addEventListener('submit', async (e) => {
            e.preventDefault();
            
            if (!validateForm()) return;
            
            const result = await confirmarMutacion('update', proveedorNombre);
            if (!result.isConfirmed) return;
            
            const submitBtn = form.querySelector('button[type="submit"]');
            const originalText = submitBtn.innerHTML;
            showLoading(submitBtn);
            
            try {
                const formData = new FormData(form);
                const data = await postForm(`index.php?controller=proveedor&action=actualizar&id=${proveedorId}`, formData);
                
                if (data.success) {
                    showSuccess(data.mensaje, data.redirect);
                } else {
                    showError(data.mensaje);
                    hideLoading(submitBtn, originalText);
                }
            } catch (error) {
                console.error('Error:', error);
                showError('Error al actualizar el proveedor');
                hideLoading(submitBtn, originalText);
            }
        });
    }

    // ============================================================
    // DELETE
    // ============================================================
    function initDeleteButtons() {
        document.querySelectorAll('.btn-delete-proveedor').forEach(btn => {
            btn.addEventListener('click', async (e) => {
                e.preventDefault();
                const id = btn.dataset.id;
                const nombre = btn.dataset.nombre;
                
                const result = await confirmarDelete(nombre);
                if (!result.isConfirmed) return;
                
                try {
                    const data = await getJson(`index.php?controller=proveedor&action=eliminar&id=${id}`);
                    if (data.success) {
                        showSuccess(data.mensaje);
                        setTimeout(() => location.reload(), 2000);
                    } else {
                        showError(data.mensaje);
                    }
                } catch (error) {
                    console.error('Error:', error);
                    showError('Error al desactivar el proveedor');
                }
            });
        });
    }

    // ============================================================
    // ACTIVATE
    // ============================================================
    function initActivateButtons() {
        document.querySelectorAll('.btn-activate-proveedor').forEach(btn => {
            btn.addEventListener('click', async (e) => {
                e.preventDefault();
                const id = btn.dataset.id;
                const nombre = btn.dataset.nombre;
                
                const result = await confirmarActivar(nombre);
                if (!result.isConfirmed) return;
                
                try {
                    const data = await getJson(`index.php?controller=proveedor&action=activar&id=${id}`);
                    if (data.success) {
                        showSuccess(data.mensaje);
                        setTimeout(() => location.reload(), 2000);
                    } else {
                        showError(data.mensaje);
                    }
                } catch (error) {
                    console.error('Error:', error);
                    showError('Error al activar el proveedor');
                }
            });
        });
    }

    // ============================================================
    // Flash Messages
    // ============================================================
    function showFlashMessage() {
        const flashData = window.proveedoresFlash;
        if (flashData && flashData.type && flashData.title) {
            Swal.fire({
                title: flashData.title,
                text: flashData.text || '',
                icon: flashData.type === 'error' ? 'error' : (flashData.type === 'success' ? 'success' : 'info'),
                confirmButtonColor: '#4f46e5',
                confirmButtonText: 'Aceptar'
            });
        }
    }

    // ============================================================
    // DataTable
    // ============================================================
    function initDataTable() {
        if (typeof $.fn.DataTable !== 'undefined' && $('#tablaProveedores').length && $('#tablaProveedores tbody tr').length > 10) {
            $('#tablaProveedores').DataTable({
                language: { url: '//cdn.datatables.net/plug-ins/1.13.7/i18n/es-ES.json' },
                pageLength: 25,
                responsive: true,
                order: [],
                columnDefs: [{ orderable: false, targets: [-1] }]
            });
        }
    }

    // ============================================================
    // Filtros
    // ============================================================
    function initFiltersCollapse() {
        const filterBtn = document.getElementById('filterToggle');
        const filterPanel = document.getElementById('filterPanel');
        const chevron = document.getElementById('filterChevron');
        
        if (filterBtn && filterPanel) {
            filterBtn.addEventListener('click', () => {
                filterPanel.classList.toggle('show');
                if (chevron) {
                    chevron.style.transform = filterPanel.classList.contains('show') ? 'rotate(180deg)' : 'rotate(0)';
                }
            });
        }
    }

    // ============================================================
    // Public API
    // ============================================================
    return {
        initCreate: function() {
            excludeId = null;
            rucOriginal = '';
            initRUCValidation();
            initCreateForm();
        },
        initEdit: function(proveedorId, proveedorNombre, proveedorRuc) {
            excludeId = proveedorId;
            rucOriginal = proveedorRuc;
            initRUCValidation();
            initUpdateForm(proveedorId, proveedorNombre);
        },
        initIndex: function() {
            initDeleteButtons();
            initActivateButtons();
            initDataTable();
            initFiltersCollapse();
            showFlashMessage();
        },
        initVer: function() {}
    };
})();

// Inicialización según página
document.addEventListener('DOMContentLoaded', () => {
    const body = document.body;
    
    if (body.classList.contains('page-proveedores-index')) {
        ProveedoresModule.initIndex();
    } else if (body.classList.contains('page-proveedores-crear')) {
        ProveedoresModule.initCreate();
    } else if (body.classList.contains('page-proveedores-editar')) {
        const proveedorId = window.proveedorId;
        const proveedorNombre = window.proveedorNombre;
        const proveedorRuc = window.proveedorRucOriginal;
        ProveedoresModule.initEdit(proveedorId, proveedorNombre, proveedorRuc);
    } else if (body.classList.contains('page-proveedores-ver')) {
        ProveedoresModule.initVer();
    }
});