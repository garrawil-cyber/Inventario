/**
 * marcas.js — Módulo Marcas
 * Flujo SweetAlert idéntico al módulo Categorías:
 *   1. Submit interceptado → confirmación  "¿Crear marca?"
 *   2. Confirmar → submit real del form
 *   3. Servidor redirige con flash → "¡Marca creada!" (éxito)
 *   4. Flash de error → SweetAlert de advertencia/error
 *
 * Acciones AJAX (eliminar, activar) con token en header X-CSRF-Token.
 */

'use strict';

// =========================================================================
// CSRF
// =========================================================================

function getCsrfToken() {
    const meta = document.querySelector('meta[name="csrf-token"]');
    if (meta && meta.getAttribute('content')) return meta.getAttribute('content');
    console.error('CSRF Token: meta[name="csrf-token"] no encontrado');
    return '';
}

function hasCsrfToken() {
    return !!getCsrfToken();
}

// =========================================================================
// AJAX
// =========================================================================

async function ajaxPost(url, body) {
    if (!hasCsrfToken()) throw new Error('Token CSRF no disponible. Recargue la página.');

    const resp = await fetch(url, {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-Requested-With': 'XMLHttpRequest',
            'X-CSRF-Token': getCsrfToken(),
        },
        credentials: 'same-origin',
        body: JSON.stringify(body),
    });

    if (resp.status === 403) {
        const err = await resp.json().catch(() => ({}));
        throw new Error(err.mensaje || 'Token CSRF inválido. Recargue la página.');
    }
    return resp.json();
}

// =========================================================================
// SWEETALERT HELPERS
// =========================================================================

function toastSuccess(message) {
    Swal.fire({
        icon: 'success',
        text: message,
        toast: true,
        position: 'top-end',
        timer: 3000,
        showConfirmButton: false,
        timerProgressBar: true,
    });
}

function showError(message) {
    Swal.fire({
        icon: 'error',
        title: 'Error',
        text: message,
        confirmButtonText: 'Aceptar',
        confirmButtonColor: '#0d9488',
    });
}

function escHtml(str) {
    if (!str) return '';
    const d = document.createElement('div');
    d.appendChild(document.createTextNode(String(str)));
    return d.innerHTML;
}

// =========================================================================
// FORMULARIO CREAR — intercepción + confirmación SweetAlert
// =========================================================================

/**
 * Adjunta el comportamiento SweetAlert al formulario de creación.
 * Patrón espejo del módulo Categorías:
 *   submit → preventDefault → confirmar → submit real.
 */
function initFormCrear() {
    const form = document.getElementById('formCrearMarca');
    if (!form) return;

    form.addEventListener('submit', async function (e) {
        e.preventDefault(); // ← interceptar; aún NO se envía

        // Validación básica del campo requerido
        const nombreInput = document.getElementById('inputNombre');
        const nombre = nombreInput?.value.trim() || '';

        if (!nombre) {
            nombreInput?.classList.add('is-invalid');
            nombreInput?.focus();
            Swal.fire({
                icon: 'warning',
                title: 'Campo requerido',
                text: 'El nombre de la marca es obligatorio.',
                confirmButtonText: 'Entendido',
                confirmButtonColor: '#0d9488',
            });
            return;
        }

        // ── Confirmación estilo Categorías ────────────────────────────
        const result = await Swal.fire({
            title: '¿Crear marca?',
            html: `Se creará <strong>${escHtml(nombre)}</strong> con estado <em>Activo</em>.`,
            icon: 'question',
            showCancelButton: true,
            confirmButtonText: '<i class="fas fa-save me-1"></i> Sí, crear',
            cancelButtonText: 'Cancelar',
            confirmButtonColor: '#0d9488',
            cancelButtonColor: '#6b7280',
            reverseButtons: false,
        });

        if (!result.isConfirmed) return; // usuario canceló

        // ── Enviar el formulario realmente ────────────────────────────
        // Deshabilitar botón para evitar doble submit
        const btn = document.getElementById('btnCrearMarca');
        if (btn) {
            btn.disabled = true;
            btn.innerHTML = '<span class="spinner-border spinner-border-sm me-1"></span> Guardando...';
        }

        form.submit(); // POST real → el controlador redirige con flash
    });
}


// =========================================================================
// FORMULARIO EDITAR — intercepción + confirmación SweetAlert
// =========================================================================

/**
 * Idéntico al flujo de crear pero con texto "¿Actualizar marca?"
 * Usa marcaEditarData.id para el parámetro excluir en checkNombre.
 */
function initFormEditar() {
    const form = document.getElementById('formEditarMarca');
    if (!form) return;

    form.addEventListener('submit', async function (e) {
        e.preventDefault();

        const nombreInput = document.getElementById('inputNombre');
        const nombre = nombreInput?.value.trim() || '';

        if (!nombre) {
            nombreInput?.classList.add('is-invalid');
            nombreInput?.focus();
            Swal.fire({
                icon: 'warning',
                title: 'Campo requerido',
                text: 'El nombre de la marca es obligatorio.',
                confirmButtonText: 'Entendido',
                confirmButtonColor: '#0d9488',
            });
            return;
        }

        // ── Confirmación estilo Categorías ────────────────────────────
        const result = await Swal.fire({
            title: '¿Actualizar marca?',
            html: `Se guardarán los cambios en <strong>${escHtml(nombre)}</strong>.`,
            icon: 'question',
            showCancelButton: true,
            confirmButtonText: '<i class="fas fa-save me-1"></i> Sí, actualizar',
            cancelButtonText: 'Cancelar',
            confirmButtonColor: '#0d9488',
            cancelButtonColor: '#6b7280',
        });

        if (!result.isConfirmed) return;

        const btn = document.getElementById('btnActualizarMarca');
        if (btn) {
            btn.disabled = true;
            btn.innerHTML = '<span class="spinner-border spinner-border-sm me-1"></span> Guardando...';
        }

        form.submit();
    });
}

// =========================================================================
// FLASH DEL SERVIDOR (mensajes post-redirección)
// =========================================================================

/**
 * Muestra el SweetAlert que el servidor dejó en sesión.
 * Para el flujo de éxito ("¡Marca creada!") el icono será 'success'.
 */
function initFlashAlert() {
    if (typeof marcaSweetAlert === 'undefined' || !marcaSweetAlert) return;

    // Config base
    const cfg = {
        title: marcaSweetAlert.title || 'Aviso',
        text:  marcaSweetAlert.text  || '',
        icon:  marcaSweetAlert.type  || 'info',
        confirmButtonText: 'Aceptar',
        confirmButtonColor: '#0d9488',
    };

    // Si es éxito: añadir auto-cierre igual que Categorías
    if (marcaSweetAlert.type === 'success') {
        cfg.timer            = 3000;
        cfg.timerProgressBar = true;
        cfg.showConfirmButton = true;
    }

    Swal.fire(cfg);
}

// =========================================================================
// FILTROS Y PAGINACIÓN (listado)
// =========================================================================

function filtrarMarcas() {
    const filtro = document.getElementById('filtro')?.value || '';
    const estado = document.getElementById('estado')?.value || '';
    const limit  = document.getElementById('limit')?.value  || 20;

    const url = new URL(window.location.href);
    url.searchParams.set('filtro', filtro);
    url.searchParams.set('estado', estado);
    url.searchParams.set('limit',  limit);
    url.searchParams.set('pagina', 1);
    window.location.href = url.toString();
}

function limpiarFiltros() {
    const url = new URL(window.location.href);
    ['filtro', 'estado', 'limit', 'pagina'].forEach(p => url.searchParams.delete(p));
    window.location.href = url.toString();
}

function cambiarPagina(pagina) {
    const url = new URL(window.location.href);
    url.searchParams.set('pagina', pagina);
    window.location.href = url.toString();
}

function cambiarLimit(select) {
    const url = new URL(window.location.href);
    url.searchParams.set('limit',  select.value);
    url.searchParams.set('pagina', 1);
    window.location.href = url.toString();
}

// =========================================================================
// ACCIONES AJAX: eliminar / activar
// =========================================================================

async function eliminarMarca(id, nombre) {
    const result = await Swal.fire({
        title: '¿Desactivar marca?',
        html: `Se desactivará <strong>${escHtml(nombre)}</strong>.`,
        icon: 'warning',
        showCancelButton: true,
        confirmButtonText: 'Sí, desactivar',
        cancelButtonText: 'Cancelar',
        confirmButtonColor: '#ef4444',
        cancelButtonColor: '#6b7280',
    });
    if (!result.isConfirmed) return;

    Swal.fire({ title: 'Procesando...', didOpen: () => Swal.showLoading(), allowOutsideClick: false });

    try {
        const data = await ajaxPost('index.php?controller=marca&action=eliminar', { id });
        if (data.success) {
            toastSuccess(data.mensaje);
            setTimeout(() => location.reload(), 1500);
        } else {
            showError(data.mensaje);
        }
    } catch (e) {
        console.error(e);
        showError(e.message || 'Error al desactivar la marca.');
    }
}

async function activarMarca(id, nombre) {
    const result = await Swal.fire({
        title: '¿Activar marca?',
        html: `Se reactivará <strong>${escHtml(nombre)}</strong>.`,
        icon: 'question',
        showCancelButton: true,
        confirmButtonText: 'Sí, activar',
        cancelButtonText: 'Cancelar',
        confirmButtonColor: '#10b981',
        cancelButtonColor: '#6b7280',
    });
    if (!result.isConfirmed) return;

    Swal.fire({ title: 'Procesando...', didOpen: () => Swal.showLoading(), allowOutsideClick: false });

    try {
        const data = await ajaxPost('index.php?controller=marca&action=activar', { id });
        if (data.success) {
            toastSuccess(data.mensaje);
            setTimeout(() => location.reload(), 1500);
        } else {
            showError(data.mensaje);
        }
    } catch (e) {
        console.error(e);
        showError(e.message || 'Error al activar la marca.');
    }
}

// =========================================================================
// VALIDACIÓN EN TIEMPO REAL DEL NOMBRE
// =========================================================================

/**
 * Muestra indicador visual mientras el usuario escribe:
 *   - Verde  + "Nombre disponible"   → OK
 *   - Rojo   + "Ya existe esa marca" → duplicado (si el controlador
 *     expone index.php?controller=marca&action=checkNombre&nombre=…)
 *   - Gris   → campo vacío
 *
 * Si el endpoint no existe, simplemente no muestra feedback de red
 * pero la validación básica de "campo vacío" sigue funcionando.
 */
function initValidacionNombre() {
    const input    = document.getElementById('inputNombre');
    const feedback = document.getElementById('nombreFeedback');
    const icon     = document.getElementById('iconNombreStatus');
    if (!input || !feedback || !icon) return;

    // Contador de descripción
    const textarea  = document.getElementById('inputDescripcion');
    const descCount = document.getElementById('descCount');
    if (textarea && descCount) {
        const update = () => { descCount.textContent = `${textarea.value.length} / 500`; };
        textarea.addEventListener('input', update);
        update(); // inicial
    }

    let timer;
    const baseValue = input.value.trim(); // valor precargado (formData)

    function setStatus(type, msg) {
        // type: 'ok' | 'error' | 'checking' | 'empty'
        feedback.textContent = msg;
        feedback.className   = 'form-text mt-1';
        icon.className       = 'fas';

        if (type === 'ok') {
            feedback.classList.add('text-success');
            icon.classList.add('fa-check-circle', 'text-success');
        } else if (type === 'error') {
            feedback.classList.add('text-danger');
            icon.classList.add('fa-times-circle', 'text-danger');
        } else if (type === 'checking') {
            feedback.classList.add('text-muted');
            icon.classList.add('fa-spinner', 'fa-spin', 'text-muted');
        } else {
            feedback.classList.add('text-muted');
            icon.classList.add('fa-circle', 'text-secondary');
            icon.style.fontSize = '.65rem';
        }
    }

    async function checkNombre(nombre) {
        // Si no existe el endpoint, ignorar silenciosamente
        try {
            const url = `${typeof marcaCheckNombreUrl !== 'undefined' ? marcaCheckNombreUrl : 'index.php?controller=marca&action=checkNombre'}&nombre=${encodeURIComponent(nombre)}`;
            const resp = await fetch(url, {
                headers: { 'X-Requested-With': 'XMLHttpRequest' },
                credentials: 'same-origin',
            });
            if (!resp.ok) return; // endpoint no existe → silencio
            const data = await resp.json();
            if (data.disponible === true) {
                setStatus('ok', '✓ Nombre disponible');
                input.classList.remove('is-invalid');
                input.classList.add('is-valid');
            } else if (data.disponible === false) {
                setStatus('error', '✗ Ya existe una marca con ese nombre');
                input.classList.add('is-invalid');
                input.classList.remove('is-valid');
            }
        } catch (_) {
            // Sin conexión al endpoint → no mostrar nada
        }
    }

    input.addEventListener('input', function () {
        clearTimeout(timer);
        input.classList.remove('is-valid', 'is-invalid');
        const val = this.value.trim();

        if (!val) {
            setStatus('empty', '');
            return;
        }
        setStatus('checking', 'Verificando...');
        timer = setTimeout(() => checkNombre(val), 420);
    });

    // Inicializar con valor precargado
    if (baseValue) {
        setStatus('checking', 'Verificando...');
        checkNombre(baseValue);
    }
}


// =========================================================================
// VALIDACIÓN EN TIEMPO REAL — EDITAR (excluye la marca actual)
// =========================================================================

/**
 * Igual que initValidacionNombre() pero pasa ?excluir=id
 * para que el servidor ignore la marca que se está editando.
 */
function initValidacionNombreEditar() {
    const input    = document.getElementById('inputNombre');
    const feedback = document.getElementById('nombreFeedback');
    const icon     = document.getElementById('iconNombreStatus');
    if (!input || !feedback || !icon) return;

    // Contador de descripción
    const textarea  = document.getElementById('inputDescripcion');
    const descCount = document.getElementById('descCount');
    if (textarea && descCount) {
        const update = () => { descCount.textContent = `${textarea.value.length} / 500`; };
        textarea.addEventListener('input', update);
        update();
    }

    // id de la marca que se está editando (de marcaEditarData inyectado en la vista)
    const excluirId = (typeof marcaEditarData !== 'undefined') ? marcaEditarData.id : 0;

    let timer;

    function setStatus(type, msg) {
        feedback.textContent = msg;
        feedback.className   = 'form-text mt-1';
        icon.className       = 'fas';
        icon.style.fontSize  = '';

        if (type === 'ok') {
            feedback.classList.add('text-success');
            icon.classList.add('fa-check-circle', 'text-success');
        } else if (type === 'error') {
            feedback.classList.add('text-danger');
            icon.classList.add('fa-times-circle', 'text-danger');
        } else if (type === 'checking') {
            feedback.classList.add('text-muted');
            icon.classList.add('fa-spinner', 'fa-spin', 'text-muted');
        } else {
            feedback.classList.add('text-muted');
            icon.classList.add('fa-circle', 'text-secondary');
            icon.style.fontSize = '.65rem';
        }
    }

    async function checkNombre(nombre) {
        try {
            const base = typeof marcaCheckNombreUrl !== 'undefined'
                ? marcaCheckNombreUrl
                : 'index.php?controller=marca&action=checkNombre';
            const url = `${base}&nombre=${encodeURIComponent(nombre)}&excluir=${excluirId}`;
            const resp = await fetch(url, {
                headers: { 'X-Requested-With': 'XMLHttpRequest' },
                credentials: 'same-origin',
            });
            if (!resp.ok) return;
            const data = await resp.json();
            if (data.disponible === true) {
                setStatus('ok', '✓ Nombre disponible');
                input.classList.remove('is-invalid');
                input.classList.add('is-valid');
            } else if (data.disponible === false) {
                setStatus('error', '✗ Ya existe una marca con ese nombre');
                input.classList.add('is-invalid');
                input.classList.remove('is-valid');
            }
        } catch (_) { /* silencio si el endpoint falla */ }
    }

    input.addEventListener('input', function () {
        clearTimeout(timer);
        input.classList.remove('is-valid', 'is-invalid');
        const val = this.value.trim();
        if (!val) { setStatus('empty', ''); return; }
        setStatus('checking', 'Verificando...');
        timer = setTimeout(() => checkNombre(val), 420);
    });

    // Verificar el valor actual al cargar (nombre precargado de la BD)
    const valorInicial = input.value.trim();
    if (valorInicial) {
        setStatus('checking', 'Verificando...');
        checkNombre(valorInicial);
    }
}

// =========================================================================
// INICIALIZACIÓN
// =========================================================================

document.addEventListener('DOMContentLoaded', function () {

    // ── Token CSRF ─────────────────────────────────────────────────
    if (hasCsrfToken()) {
        console.log('✅ CSRF Token disponible');
    } else {
        console.error('❌ CSRF Token no encontrado');
    }

    // ── Flash del servidor (éxito / error tras redirección) ────────
    initFlashAlert();

    // ── Formulario crear con confirmación SweetAlert ───────────────
    initFormCrear();

    // ── Validación nombre en tiempo real ───────────────────────────
    initValidacionNombre();

    // ── Formulario editar con confirmación SweetAlert ─────────────
    initFormEditar();

    // ── Validación nombre editar (excluye marca actual) ───────────
    initValidacionNombreEditar();

    // ── Filtros (listado) ──────────────────────────────────────────
    document.getElementById('btnFiltrar')?.addEventListener('click', filtrarMarcas);
    document.getElementById('btnLimpiar')?.addEventListener('click', limpiarFiltros);

    // ── Acciones de tabla (listado) ────────────────────────────────
    document.querySelectorAll('.btn-eliminar-marca').forEach(btn => {
        btn.addEventListener('click', function (e) {
            e.preventDefault();
            eliminarMarca(this.dataset.id, this.dataset.nombre);
        });
    });

    document.querySelectorAll('.btn-activar-marca').forEach(btn => {
        btn.addEventListener('click', function (e) {
            e.preventDefault();
            activarMarca(this.dataset.id, this.dataset.nombre);
        });
    });
});