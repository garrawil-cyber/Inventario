/**
 * clientes.js — Módulo Clientes
 * Patrón idéntico al módulo Categorías:
 *   - Flash del servidor via initFlashAlert()
 *   - Confirmación SweetAlert en crear/editar
 *   - Validación en tiempo real del documento (debounce 500ms)
 *   - Eliminar: GET redirect (el controlador redirige con flash)
 *   - Activar:  POST AJAX con X-CSRF-Token
 *   - Sin funciones duplicadas, sin window.handle* pattern
 */

'use strict';

// =========================================================================
// ESTADO GLOBAL (mínimo necesario)
// =========================================================================
let documentoValido    = false;
let verificandoDoc     = false;
let debounceTimer      = null;

// =========================================================================
// UTILIDADES
// =========================================================================
function escHtml(str) {
    if (!str) return '';
    const d = document.createElement('div');
    d.appendChild(document.createTextNode(String(str)));
    return d.innerHTML;
}

function getCsrfToken() {
    return document.querySelector('meta[name="csrf-token"]')?.content || '';
}

// =========================================================================
// SWEETALERT HELPERS
// =========================================================================
function toastOk(msg) {
    Swal.fire({
        icon: 'success', text: msg, toast: true,
        position: 'top-end', timer: 3000,
        showConfirmButton: false, timerProgressBar: true,
    });
}

function showError(msg, title = 'Error') {
    return Swal.fire({
        icon: 'error', title, text: msg,
        confirmButtonText: 'Entendido', confirmButtonColor: '#ef4444',
    });
}

// =========================================================================
// FLASH DEL SERVIDOR (mensajes post-redirección)
// =========================================================================
function initFlashAlert() {
    if (typeof clienteSweetAlert === 'undefined' || !clienteSweetAlert) return;

    const cfg = {
        title:             clienteSweetAlert.title || 'Aviso',
        text:              clienteSweetAlert.text  || '',
        icon:              clienteSweetAlert.type  || 'info',
        confirmButtonText: 'Aceptar',
        confirmButtonColor: '#3b82f6',
    };
    if (clienteSweetAlert.type === 'success') {
        cfg.timer            = 3000;
        cfg.timerProgressBar = true;
        cfg.showConfirmButton = true;
    }
    Swal.fire(cfg);
}

// =========================================================================
// VALIDACIÓN DE FORMATO DE DOCUMENTO (sin petición al servidor)
// =========================================================================
function validarFormatoDocumento(tipo, numero) {
    switch (tipo) {
        case 'DNI':
            return /^\d{8}$/.test(numero)
                ? null : 'El DNI debe tener exactamente 8 dígitos numéricos.';
        case 'RUC':
            return /^\d{11}$/.test(numero)
                ? null : 'El RUC debe tener exactamente 11 dígitos numéricos.';
        case 'CE':
            return /^\d{8,12}$/.test(numero)
                ? null : 'El Carné de Extranjería debe tener entre 8 y 12 dígitos.';
        case 'Pasaporte':
            if (numero.length < 6 || numero.length > 20)
                return 'El Pasaporte debe tener entre 6 y 20 caracteres.';
            if (!/^[A-Za-z0-9]+$/.test(numero))
                return 'El Pasaporte solo puede contener letras y números.';
            return null;
        default:
            return null;
    }
}

// =========================================================================
// VERIFICACIÓN EN TIEMPO REAL DEL DOCUMENTO (GET AJAX sin CSRF)
// =========================================================================
function setDocStatus(input, feedback, icon, type, msg) {
    // type: 'ok' | 'error' | 'checking' | 'empty'
    // Usa clases cl- del sistema de diseño propio (no Bootstrap)
    if (feedback) {
        feedback.innerHTML = msg;
        // Mantener la clase base, solo cambiar el color vía clase auxiliar
        feedback.className = 'cl-field-feedback';
        if (type === 'ok')       feedback.classList.add('cl-feedback-ok-text');
        if (type === 'error')    feedback.classList.add('cl-feedback-err-text');
        if (type === 'checking') feedback.classList.add('cl-feedback-check-text');
    }
    if (icon) { icon.className = 'fas'; icon.style.fontSize = '1rem'; }
    input.classList.remove('is-valid', 'is-invalid');

    if (type === 'ok') {
        icon?.classList.add('fa-check-circle');
        icon?.style.setProperty('color', 'var(--cl-success)');
        input.classList.add('is-valid');
    } else if (type === 'error') {
        icon?.classList.add('fa-times-circle');
        icon?.style.setProperty('color', 'var(--cl-danger)');
        input.classList.add('is-invalid');
    } else if (type === 'checking') {
        icon?.classList.add('fa-spinner', 'fa-spin');
        icon?.style.setProperty('color', 'var(--cl-text-3)');
    } else {
        if (icon) { icon.style.fontSize = '.6rem'; icon.style.removeProperty('color'); }
        icon?.classList.add('fa-circle');
        icon?.style.setProperty('color', '#94a3b8');
    }
}

async function checkDocumentoServidor(tipo, numero, excluir) {
    const url = `index.php?controller=cliente&action=checkDocumento`
              + `&tipo=${encodeURIComponent(tipo)}`
              + `&numero=${encodeURIComponent(numero)}`
              + `&excluir=${excluir}`;
    const resp = await fetch(url, {
        headers: { 'X-Requested-With': 'XMLHttpRequest' },
        credentials: 'same-origin',
    });
    if (!resp.ok) throw new Error(`HTTP ${resp.status}`);
    return resp.json();
}

function initValidacionDocumento(excluirId = 0) {
    const input    = document.getElementById('numero_documento');
    const feedback = document.getElementById('documentoFeedback');
    const icon     = document.getElementById('iconDocumentoStatus');
    const select   = document.getElementById('tipo_documento');
    if (!input) return;

    // ── Restricciones de campo según tipo ──────────────────────────
    function aplicarRestricciones() {
        const tipo = select?.value || 'DNI';
        const cfg = {
            DNI:       { max: 8,  ph: 'Ej: 12345678 (8 dígitos)' },
            RUC:       { max: 11, ph: 'Ej: 12345678901 (11 dígitos)' },
            CE:        { max: 12, ph: 'Ej: 12345678 (8-12 dígitos)' },
            Pasaporte: { max: 20, ph: 'Ej: AB123456 (6-20 caracteres)' },
        };
        const c = cfg[tipo] || { max: 20, ph: '' };
        input.maxLength  = c.max;
        input.placeholder = c.ph;
    }

    // ── Verificar en el servidor con debounce ──────────────────────
    function verificar(numero) {
        const tipo = select?.value || 'DNI';
        clearTimeout(debounceTimer);

        if (!numero) {
            setDocStatus(input, feedback, icon, 'empty', '');
            documentoValido = false; verificandoDoc = false;
            return;
        }

        const errorFormato = validarFormatoDocumento(tipo, numero);
        if (errorFormato) {
            setDocStatus(input, feedback, icon, 'error',
                `<i class="fas fa-times-circle"></i> ${escHtml(errorFormato)}`);
            documentoValido = false; verificandoDoc = false;
            return;
        }

        // Formato OK → consultar servidor
        verificandoDoc = true;
        documentoValido = false;
        setDocStatus(input, feedback, icon, 'checking',
            '<i class="fas fa-spinner fa-spin"></i> Verificando...');

        debounceTimer = setTimeout(async () => {
            try {
                const data = await checkDocumentoServidor(tipo, numero, excluirId);
                if (data.disponible) {
                    setDocStatus(input, feedback, icon, 'ok',
                        '<i class="fas fa-check-circle"></i> Documento disponible');
                    documentoValido = true;
                } else {
                    setDocStatus(input, feedback, icon, 'error',
                        `<i class="fas fa-times-circle"></i> ${escHtml(data.mensaje)}`);
                    documentoValido = false;
                }
            } catch (err) {
                console.error('checkDocumento error:', err);
                // Error de red → dejar pasar, el servidor validará al guardar
                setDocStatus(input, feedback, icon, 'empty', '');
                documentoValido = true;
            } finally {
                verificandoDoc = false;
            }
        }, 300);
    }

    // ── Filtro de solo-números para DNI/RUC/CE ────────────────────
    input.addEventListener('input', function () {
        const tipo = select?.value || 'DNI';
        if (['DNI', 'RUC', 'CE'].includes(tipo)) {
            const limpio = this.value.replace(/[^0-9]/g, '');
            if (this.value !== limpio) this.value = limpio;
        }
        const val = this.value.trim();

        // Verificación inmediata cuando el número alcanza la longitud exacta
        // (DNI=8, RUC=11, CE=8-12, Pasaporte=6+)
        // Evita que el usuario tenga que esperar el debounce de 300ms
        const longitudesExactas = { DNI: 8, RUC: 11 };
        const longExacta = longitudesExactas[tipo];
        if (longExacta && val.length === longExacta) {
            // Cancelar debounce pendiente y verificar YA
            clearTimeout(debounceTimer);
            const errorFmt = validarFormatoDocumento(tipo, val);
            if (!errorFmt) {
                verificandoDoc = true;
                documentoValido = false;
                setDocStatus(input, feedback, icon, 'checking',
                    '<i class="fas fa-spinner fa-spin"></i> Verificando...');
                checkDocumentoServidor(tipo, val, excluirId).then(data => {
                    if (data.disponible) {
                        setDocStatus(input, feedback, icon, 'ok',
                            '<i class="fas fa-check-circle"></i> Documento disponible');
                        documentoValido = true;
                    } else {
                        setDocStatus(input, feedback, icon, 'error',
                            `<i class="fas fa-times-circle"></i> ${escHtml(data.mensaje)}`);
                        documentoValido = false;
                    }
                }).catch(() => {
                    setDocStatus(input, feedback, icon, 'empty', '');
                    documentoValido = true;
                }).finally(() => { verificandoDoc = false; });
                return; // ya se disparó — no llamar verificar() con debounce
            }
        }

        verificar(val);
    });

    input.addEventListener('blur', function () {
        if (this.value.trim() && !documentoValido && !verificandoDoc) {
            verificar(this.value.trim());
        }
    });

    // ── Cambio de tipo de documento ───────────────────────────────
    select?.addEventListener('change', function () {
        aplicarRestricciones();
        input.classList.remove('is-valid', 'is-invalid');
        if (feedback) feedback.innerHTML = '';
        documentoValido = false;
        if (input.value.trim()) verificar(input.value.trim());
    });

    aplicarRestricciones();

    // ── Valor precargado (editar) ─────────────────────────────────
    if (input.value.trim()) {
        if (excluirId > 0) {
            // En edición: el documento actual siempre es válido al cargar
            setDocStatus(input, feedback, icon, 'ok',
                '<i class="fas fa-check-circle"></i> Documento actual');
            documentoValido = true;
        } else {
            verificar(input.value.trim());
        }
    }
}

// =========================================================================
// VALIDACIONES EN TIEMPO REAL: nombre, email, teléfono, fecha
// =========================================================================
function initValidacionesCampos() {

    // ── Nombre ────────────────────────────────────────────────────
    const inputNombre = document.getElementById('nombre_completo');
    if (inputNombre) {
        function validarNombre(val) {
            const err = document.getElementById('error_nombre');
            const ok  = document.getElementById('valid_nombre');
            inputNombre.classList.remove('is-valid', 'is-invalid');
            if (err) err.textContent = '';
            if (ok)  ok.textContent  = '';

            if (!val) {
                inputNombre.classList.add('is-invalid');
                if (err) err.textContent = 'El nombre es obligatorio.';
                return false;
            }
            if (val.length < 3) {
                inputNombre.classList.add('is-invalid');
                if (err) err.textContent = 'Mínimo 3 caracteres.';
                return false;
            }
            if (!/^[a-zA-ZáéíóúñÁÉÍÓÚÑüÜ\s.]+$/.test(val)) {
                inputNombre.classList.add('is-invalid');
                if (err) err.textContent = 'Solo letras, espacios y puntos.';
                return false;
            }
            inputNombre.classList.add('is-valid');
            if (ok) ok.textContent = '✓ Nombre válido';
            return true;
        }
        inputNombre.addEventListener('input', () => validarNombre(inputNombre.value.trim()));
        inputNombre.addEventListener('blur',  () => validarNombre(inputNombre.value.trim()));
        if (inputNombre.value.trim()) validarNombre(inputNombre.value.trim());
    }

    // ── Email (opcional) ──────────────────────────────────────────
    const inputEmail = document.getElementById('email');
    if (inputEmail) {
        function validarEmail(val) {
            const err = document.getElementById('error_email');
            const ok  = document.getElementById('valid_email');
            inputEmail.classList.remove('is-valid', 'is-invalid');
            if (err) err.textContent = '';
            if (ok)  ok.textContent  = '';
            if (!val) return true; // opcional

            const re = /^[a-zA-Z0-9._%+-]+@[a-zA-Z0-9.-]+\.[a-zA-Z]{2,}$/;
            if (!re.test(val)) {
                inputEmail.classList.add('is-invalid');
                if (err) err.textContent = 'Ingrese un email válido.';
                return false;
            }
            inputEmail.classList.add('is-valid');
            if (ok) ok.textContent = '✓ Email válido';
            return true;
        }
        inputEmail.addEventListener('input', () => validarEmail(inputEmail.value.trim()));
        inputEmail.addEventListener('blur',  () => validarEmail(inputEmail.value.trim()));
        if (inputEmail.value.trim()) validarEmail(inputEmail.value.trim());
    }

    // ── Teléfono (opcional, solo números) ─────────────────────────
    const inputTel = document.getElementById('telefono');
    if (inputTel) {
        function validarTelefono(val) {
            const err = document.getElementById('error_telefono');
            const ok  = document.getElementById('valid_telefono');
            inputTel.classList.remove('is-valid', 'is-invalid');
            if (err) err.textContent = '';
            if (ok)  ok.textContent  = '';
            if (!val) return true; // opcional

            if (!/^\d{6,15}$/.test(val)) {
                inputTel.classList.add('is-invalid');
                if (err) err.textContent = 'Teléfono: entre 6 y 15 dígitos.';
                return false;
            }
            inputTel.classList.add('is-valid');
            if (ok) ok.textContent = '✓ Teléfono válido';
            return true;
        }
        inputTel.addEventListener('input', function () {
            this.value = this.value.replace(/[^0-9]/g, '');
            validarTelefono(this.value);
        });
        inputTel.addEventListener('blur', () => validarTelefono(inputTel.value));
        if (inputTel.value) validarTelefono(inputTel.value);
    }

    // ── Fecha de nacimiento (opcional, >= 18 años) ─────────────────
    const inputFecha = document.getElementById('fecha_nacimiento');
    if (inputFecha) {
        function validarFecha(val) {
            const err = document.getElementById('error_fecha');
            const ok  = document.getElementById('valid_fecha');
            inputFecha.classList.remove('is-valid', 'is-invalid');
            if (err) err.textContent = '';
            if (ok)  ok.textContent  = '';
            if (!val) return true;

            const fecha = new Date(val);
            if (isNaN(fecha.getTime())) {
                inputFecha.classList.add('is-invalid');
                if (err) err.textContent = 'Fecha inválida.';
                return false;
            }
            const hoy  = new Date();
            let edad   = hoy.getFullYear() - fecha.getFullYear();
            const mes  = hoy.getMonth() - fecha.getMonth();
            if (mes < 0 || (mes === 0 && hoy.getDate() < fecha.getDate())) edad--;

            if (edad < 18) {
                inputFecha.classList.add('is-invalid');
                if (err) err.textContent = 'Debe ser mayor de 18 años.';
                return false;
            }
            if (edad > 120) {
                inputFecha.classList.add('is-invalid');
                if (err) err.textContent = 'Fecha de nacimiento inválida.';
                return false;
            }
            inputFecha.classList.add('is-valid');
            if (ok) ok.textContent = '✓ Fecha válida';
            return true;
        }
        inputFecha.addEventListener('change', () => validarFecha(inputFecha.value));
        if (inputFecha.value) validarFecha(inputFecha.value);
    }
}

// =========================================================================
// FORMULARIO CREAR — intercepción + confirmación SweetAlert
// =========================================================================
function initFormCrear() {
    const form = document.getElementById('formCliente');
    if (!form || form.dataset.listenerCrear) return; // evitar doble registro
    form.dataset.listenerCrear = '1';

    form.addEventListener('submit', async function (e) {
        e.preventDefault();

        const inputDoc    = document.getElementById('numero_documento');
        const inputNombre = document.getElementById('nombre_completo');
        const selectTipo  = document.getElementById('tipo_documento');
        const tipo        = selectTipo?.value || 'DNI';
        const numero      = inputDoc?.value.trim() || '';
        const nombre      = inputNombre?.value.trim() || '';

        // 1. Documento vacío
        if (!numero) {
            inputDoc?.classList.add('is-invalid');
            inputDoc?.focus();
            await Swal.fire({ icon: 'warning', title: 'Campo requerido',
                text: 'El número de documento es obligatorio.',
                confirmButtonText: 'Entendido', confirmButtonColor: '#0d9488' });
            return;
        }

        // 2. Formato inválido
        const errorFormato = validarFormatoDocumento(tipo, numero);
        if (errorFormato) {
            inputDoc?.classList.add('is-invalid');
            inputDoc?.focus();
            await Swal.fire({ icon: 'error', title: 'Documento inválido',
                text: errorFormato,
                confirmButtonText: 'Entendido', confirmButtonColor: '#ef4444' });
            return;
        }

        // 3. Nombre vacío
        if (!nombre) {
            inputNombre?.classList.add('is-invalid');
            inputNombre?.focus();
            await Swal.fire({ icon: 'warning', title: 'Campo requerido',
                text: 'El nombre completo es obligatorio.',
                confirmButtonText: 'Entendido', confirmButtonColor: '#0d9488' });
            return;
        }

        // 4. Verificación pendiente
        if (verificandoDoc) {
            await Swal.fire({ icon: 'info', title: 'Un momento',
                text: 'Espera a que termine la verificación del documento.',
                confirmButtonText: 'OK', confirmButtonColor: '#0d9488' });
            return;
        }

        // 5. Documento no disponible
        if (!documentoValido) {
            await Swal.fire({ icon: 'error', title: 'Documento no disponible',
                text: 'El documento ya está registrado. Usa otro.',
                confirmButtonText: 'Entendido', confirmButtonColor: '#ef4444' });
            inputDoc?.focus();
            return;
        }

        // 6. Confirmación SweetAlert (patrón Categorías)
        const result = await Swal.fire({
            icon: 'question',
            title: '¿Crear cliente?',
            html: `Se creará <strong>${escHtml(nombre)}</strong> con estado <em>Activo</em>.`,
            showCancelButton: true,
            confirmButtonText: '<i class="fas fa-save me-1"></i> Sí, crear',
            cancelButtonText: 'Cancelar',
            confirmButtonColor: '#3b82f6',
            cancelButtonColor: '#6b7280',
        });
        if (!result.isConfirmed) return;

        const btn = document.getElementById('btnGuardarCliente');
        if (btn) {
            btn.disabled = true;
            btn.innerHTML = '<span class="spinner-border spinner-border-sm me-1"></span> Guardando...';
        }
        form.submit();
    });
}

// =========================================================================
// FORMULARIO EDITAR — intercepción + confirmación SweetAlert
// =========================================================================
function initFormEditar() {
    const form = document.getElementById('formCliente');
    if (!form || form.dataset.listenerEditar) return;
    form.dataset.listenerEditar = '1';

    form.addEventListener('submit', async function (e) {
        e.preventDefault();

        const inputDoc    = document.getElementById('numero_documento');
        const inputNombre = document.getElementById('nombre_completo');
        const selectTipo  = document.getElementById('tipo_documento');
        const tipo        = selectTipo?.value || 'DNI';
        const numero      = inputDoc?.value.trim() || '';
        const nombre      = inputNombre?.value.trim() || '';

        if (!numero) {
            inputDoc?.classList.add('is-invalid');
            inputDoc?.focus();
            await Swal.fire({ icon: 'warning', title: 'Campo requerido',
                text: 'El número de documento es obligatorio.',
                confirmButtonText: 'Entendido', confirmButtonColor: '#0d9488' });
            return;
        }

        const errorFormato = validarFormatoDocumento(tipo, numero);
        if (errorFormato) {
            inputDoc?.classList.add('is-invalid');
            inputDoc?.focus();
            await Swal.fire({ icon: 'error', title: 'Documento inválido',
                text: errorFormato, confirmButtonText: 'Entendido',
                confirmButtonColor: '#ef4444' });
            return;
        }

        if (!nombre) {
            inputNombre?.classList.add('is-invalid');
            inputNombre?.focus();
            await Swal.fire({ icon: 'warning', title: 'Campo requerido',
                text: 'El nombre completo es obligatorio.',
                confirmButtonText: 'Entendido', confirmButtonColor: '#0d9488' });
            return;
        }

        if (verificandoDoc) {
            await Swal.fire({ icon: 'info', title: 'Un momento',
                text: 'Espera a que termine la verificación del documento.',
                confirmButtonText: 'OK', confirmButtonColor: '#0d9488' });
            return;
        }

        if (!documentoValido) {
            await Swal.fire({ icon: 'error', title: 'Documento no disponible',
                text: 'El documento ingresado ya pertenece a otro cliente.',
                confirmButtonText: 'Entendido', confirmButtonColor: '#ef4444' });
            inputDoc?.focus();
            return;
        }

        const result = await Swal.fire({
            icon: 'question',
            title: '¿Actualizar cliente?',
            html: `Se guardarán los cambios en <strong>${escHtml(nombre)}</strong>.`,
            showCancelButton: true,
            confirmButtonText: '<i class="fas fa-save me-1"></i> Sí, actualizar',
            cancelButtonText: 'Cancelar',
            confirmButtonColor: '#3b82f6',
            cancelButtonColor: '#6b7280',
        });
        if (!result.isConfirmed) return;

        const btn = document.getElementById('btnActualizarCliente');
        if (btn) {
            btn.disabled = true;
            btn.innerHTML = '<span class="spinner-border spinner-border-sm me-1"></span> Guardando...';
        }
        form.submit();
    });
}

// =========================================================================
// ACCIONES: Eliminar (GET redirect) / Activar (POST AJAX)
// =========================================================================

/**
 * Eliminar: el controlador hace redirect con flash → location.href directo.
 * No usamos fetch porque el servidor no devuelve JSON, sino redirect 302.
 */
async function eliminarCliente(url, nombre) {
    const result = await Swal.fire({
        icon: 'warning',
        title: '¿Desactivar cliente?',
        html: `Se desactivará a <strong>${escHtml(nombre)}</strong>.`,
        showCancelButton: true,
        confirmButtonText: '<i class="fas fa-ban me-1"></i> Sí, desactivar',
        cancelButtonText: 'Cancelar',
        confirmButtonColor: '#ef4444',
        cancelButtonColor: '#6b7280',
    });
    if (!result.isConfirmed) return;

    // Redirect directo → el controlador redirige a index con flash success/warning
    window.location.href = url;
}

/**
 * Activar: POST AJAX con X-CSRF-Token, el controlador devuelve JSON.
 */
async function activarCliente(id, nombre) {
    const result = await Swal.fire({
        icon: 'question',
        title: '¿Activar cliente?',
        html: `Se reactivará a <strong>${escHtml(nombre)}</strong>.`,
        showCancelButton: true,
        confirmButtonText: '<i class="fas fa-check me-1"></i> Sí, activar',
        cancelButtonText: 'Cancelar',
        confirmButtonColor: '#10b981',
        cancelButtonColor: '#6b7280',
    });
    if (!result.isConfirmed) return;

    Swal.fire({ title: 'Procesando...', didOpen: () => Swal.showLoading(), allowOutsideClick: false });

    try {
        const resp = await fetch('index.php?controller=cliente&action=activar', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-Requested-With': 'XMLHttpRequest',
                'X-CSRF-Token': getCsrfToken(),
            },
            credentials: 'same-origin',
            body: JSON.stringify({ id: parseInt(id) }),
        });

        if (resp.status === 403) {
            const err = await resp.json().catch(() => ({}));
            throw new Error(err.mensaje || 'Token CSRF inválido. Recargue la página.');
        }

        const data = await resp.json();
        if (data.success) {
            toastOk(data.mensaje || 'Cliente activado correctamente.');
            setTimeout(() => location.reload(), 1500);
        } else {
            await showError(data.mensaje || 'No se pudo activar el cliente.');
        }
    } catch (err) {
        console.error('activarCliente error:', err);
        await showError(err.message || 'Error de conexión.');
    }
}

// =========================================================================
// FILTROS Y PAGINACIÓN (listado)
// =========================================================================
function filtrarClientes() {
    const filtro      = document.getElementById('filtro')?.value      || '';
    const tipoCliente = document.getElementById('tipo_cliente')?.value || '';
    const estado      = document.getElementById('estado')?.value      || '';
    const limit       = document.getElementById('limit')?.value       || 20;

    const url = new URL(window.location.href);
    url.searchParams.set('filtro',       filtro);
    url.searchParams.set('tipo_cliente', tipoCliente);
    url.searchParams.set('estado',       estado);
    url.searchParams.set('limit',        limit);
    url.searchParams.set('pagina',       1);
    window.location.href = url.toString();
}

function limpiarFiltros() {
    const url = new URL(window.location.href);
    ['filtro', 'tipo_cliente', 'estado', 'limit', 'pagina']
        .forEach(p => url.searchParams.delete(p));
    window.location.href = url.toString();
}

function cambiarPagina(pagina) {
    const url = new URL(window.location.href);
    url.searchParams.set('pagina', pagina);
    window.location.href = url.toString();
}

// =========================================================================
// INICIALIZACIÓN
// =========================================================================
document.addEventListener('DOMContentLoaded', function () {

    // ── Token CSRF ──────────────────────────────────────────────────
    if (getCsrfToken()) {
        console.log('✅ CSRF Token disponible');
    } else {
        console.warn('⚠️ CSRF Token no encontrado');
    }

    // ── Flash del servidor ──────────────────────────────────────────
    initFlashAlert();

    // ── Filtros (listado) ───────────────────────────────────────────
    document.getElementById('btnFiltrar')?.addEventListener('click', filtrarClientes);
    document.getElementById('btnLimpiar')?.addEventListener('click', limpiarFiltros);
    document.getElementById('filtro')?.addEventListener('keypress', e => {
        if (e.key === 'Enter') filtrarClientes();
    });

    // ── Acciones de tabla: eliminar ─────────────────────────────────
    document.querySelectorAll('.btn-delete').forEach(btn => {
        btn.addEventListener('click', function (e) {
            e.preventDefault();
            eliminarCliente(this.dataset.url, this.dataset.nombre);
        });
    });

    // ── Acciones de tabla: activar ──────────────────────────────────
    document.querySelectorAll('.btn-activate').forEach(btn => {
        btn.addEventListener('click', function (e) {
            e.preventDefault();
            // Extraer id desde data-url: ...&id=5
            const id = new URL(this.dataset.url, location.href).searchParams.get('id') || 0;
            activarCliente(id, this.dataset.nombre);
        });
    });

    // ── Vista CREAR ─────────────────────────────────────────────────
    const esEditar = !!document.getElementById('id_cliente')?.value;
    const tieneForm = !!document.getElementById('formCliente');

    if (tieneForm && !esEditar) {
        initValidacionDocumento(0);
        initValidacionesCampos();
        initFormCrear();
        console.log('📝 Vista CREAR inicializada');
    }

    // ── Vista EDITAR ────────────────────────────────────────────────
    if (tieneForm && esEditar) {
        const clienteId = typeof CLIENTE_ID !== 'undefined'
            ? CLIENTE_ID
            : parseInt(document.getElementById('id_cliente')?.value || 0);
        initValidacionDocumento(clienteId);
        initValidacionesCampos();
        initFormEditar();
        console.log('✏️ Vista EDITAR inicializada, id:', clienteId);
    }
});