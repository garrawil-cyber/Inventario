/**
 * usuarios.js
 * Módulo de Gestión de Usuarios — RopaVirtual
 *
 * Responsabilidades:
 *  - SweetAlert de bienvenida (sweetAlertData desde PHP)
 *  - Eliminar usuario: confirm Swal → AJAX POST → Swal resultado → reload
 *  - Cambiar contraseña: Swal steps → AJAX POST → Swal resultado
 *  - Filtros con debounce en index
 */

/* ============================================================
   1. SWEET ALERT INICIAL (mensajes flash del servidor)
   ============================================================ */
document.addEventListener('DOMContentLoaded', () => {

    // sweetAlertData es inyectada por cada vista PHP
    if (typeof sweetAlertData !== 'undefined' && sweetAlertData) {
        Swal.fire({
            title:             sweetAlertData.title   || 'Aviso',
            html:              sweetAlertData.text    || '',
            icon:              sweetAlertData.type    || 'info',
            confirmButtonText: 'Entendido',
            confirmButtonColor:'#4f46e5',
            timer:             sweetAlertData.type === 'success' ? 3000 : undefined,
            timerProgressBar:  sweetAlertData.type === 'success',
        });
    }

    initFiltros();
    initEliminar();
    initCambiarPassword();
    initToggleEstado();
});

/* ============================================================
   2. FILTROS CON DEBOUNCE (index)
   ============================================================ */
function initFiltros() {
    const inputFiltro = document.getElementById('inputFiltro');
    if (!inputFiltro) return;

    let debounceTimer;
    inputFiltro.addEventListener('input', () => {
        clearTimeout(debounceTimer);
        debounceTimer = setTimeout(() => {
            document.getElementById('formFiltros')?.submit();
        }, 500);
    });
}

/* ============================================================
   3. ELIMINAR (desactivar) — confirm → AJAX → resultado
   ============================================================ */
function initEliminar() {
    document.querySelectorAll('[data-action="eliminar"]').forEach(btn => {
        btn.addEventListener('click', async function () {
            const id     = this.dataset.id;
            const nombre = this.dataset.nombre || 'este usuario';
            const csrf   = this.dataset.csrf;

            const confirm = await Swal.fire({
                title:              '¿Desactivar usuario?',
                html:               `El usuario <strong>${escapeHtml(nombre)}</strong> será desactivado.<br><small style="color:#64748b">Podrá reactivarlo editando el usuario.</small>`,
                icon:               'warning',
                showCancelButton:   true,
                confirmButtonText:  '<i class="fas fa-user-slash"></i> Sí, desactivar',
                cancelButtonText:   'Cancelar',
                confirmButtonColor: '#ef4444',
                cancelButtonColor:  '#94a3b8',
                focusCancel:        true,
            });

            if (!confirm.isConfirmed) return;

            // Mostrar loading
            Swal.fire({
                title:             'Desactivando...',
                allowOutsideClick: false,
                didOpen:           () => Swal.showLoading(),
            });

            try {
                const resp = await fetch('index.php?controller=usuario&action=eliminar', {
                    method:  'POST',
                    headers: {
                        'Content-Type':     'application/json',
                        'X-Requested-With': 'XMLHttpRequest',
                    },
                    credentials: 'same-origin',
                    body: JSON.stringify({ id: parseInt(id), csrf_token: csrf }),
                });

                if (!resp.headers.get('content-type')?.includes('application/json')) {
                    throw new Error('Respuesta inesperada del servidor');
                }

                const data = await resp.json();

                if (data.success) {
                    await Swal.fire({
                        title:             '¡Desactivado!',
                        text:              data.mensaje,
                        icon:              'success',
                        confirmButtonColor:'#4f46e5',
                        timer:             2500,
                        timerProgressBar:  true,
                    });
                    location.reload();
                } else {
                    Swal.fire({
                        title:             'No se pudo desactivar',
                        text:              data.mensaje || 'Ocurrió un error.',
                        icon:              'error',
                        confirmButtonColor:'#4f46e5',
                    });
                }
            } catch (err) {
                console.error('eliminar:', err);
                Swal.fire({
                    title:             'Error de conexión',
                    text:              err.message,
                    icon:              'error',
                    confirmButtonColor:'#4f46e5',
                });
            }
        });
    });
}

/* ============================================================
   4. CAMBIAR CONTRASEÑA — flujo multi-step con Swal
      Step 1: password actual
      Step 2: nueva + confirmación
      Step 3: llamada AJAX
      Step 4: resultado
   ============================================================ */
function initCambiarPassword() {
    document.querySelectorAll('[data-action="cambiar-password"]').forEach(btn => {
        btn.addEventListener('click', async function () {
            const id     = this.dataset.id;
            const nombre = this.dataset.nombre || 'este usuario';
            const csrf   = document.getElementById('csrfGlobal')?.value || '';

            /* ── Step 1: contraseña actual ───────────────────────── */
            const step1 = await Swal.fire({
                title:         `Cambiar contraseña`,
                html: `<p style="margin-bottom:.75rem;color:#64748b;font-size:.9rem">Usuario: <strong>${escapeHtml(nombre)}</strong></p>
                       <label style="display:block;text-align:left;font-size:.78rem;font-weight:600;color:#475569;margin-bottom:.3rem">CONTRASEÑA ACTUAL</label>
                       <div style="position:relative">
                         <input type="password" id="swal-pwd-actual" class="swal2-input" placeholder="••••••••" style="margin:0;width:100%;font-size:.9rem">
                         <button type="button" onclick="toggleSwalEye('swal-pwd-actual',this)" style="position:absolute;right:12px;top:50%;transform:translateY(-50%);background:none;border:none;cursor:pointer;color:#94a3b8">
                           <i class="fas fa-eye"></i>
                         </button>
                       </div>`,
                confirmButtonText:  'Siguiente <i class="fas fa-arrow-right"></i>',
                cancelButtonText:   'Cancelar',
                showCancelButton:   true,
                confirmButtonColor: '#4f46e5',
                focusConfirm:       false,
                preConfirm: () => {
                    const val = document.getElementById('swal-pwd-actual')?.value || '';
                    if (!val.trim()) {
                        Swal.showValidationMessage('Debes ingresar tu contraseña actual');
                        return false;
                    }
                    return val;
                },
            });
            if (!step1.isConfirmed) return;
            const passwordActual = step1.value;

            /* ── Step 2: nueva contraseña ────────────────────────── */
            const step2 = await Swal.fire({
                title:  'Nueva contraseña',
                html: `<label style="display:block;text-align:left;font-size:.78rem;font-weight:600;color:#475569;margin-bottom:.3rem">NUEVA CONTRASEÑA</label>
                       <div style="position:relative;margin-bottom:.75rem">
                         <input type="password" id="swal-pwd-nueva" class="swal2-input" placeholder="Mínimo 6 caracteres" style="margin:0;width:100%;font-size:.9rem" oninput="updateStrength(this.value)">
                         <button type="button" onclick="toggleSwalEye('swal-pwd-nueva',this)" style="position:absolute;right:12px;top:50%;transform:translateY(-50%);background:none;border:none;cursor:pointer;color:#94a3b8">
                           <i class="fas fa-eye"></i>
                         </button>
                       </div>
                       <div style="height:4px;border-radius:2px;background:#e2e8f0;overflow:hidden;margin-bottom:.3rem">
                         <div id="swal-strength-bar" style="height:100%;width:0;border-radius:2px;transition:all .3s"></div>
                       </div>
                       <p id="swal-strength-text" style="font-size:.72rem;color:#94a3b8;margin:0 0 .75rem"></p>
                       <label style="display:block;text-align:left;font-size:.78rem;font-weight:600;color:#475569;margin-bottom:.3rem">CONFIRMAR CONTRASEÑA</label>
                       <div style="position:relative">
                         <input type="password" id="swal-pwd-confirm" class="swal2-input" placeholder="Repite la nueva contraseña" style="margin:0;width:100%;font-size:.9rem">
                         <button type="button" onclick="toggleSwalEye('swal-pwd-confirm',this)" style="position:absolute;right:12px;top:50%;transform:translateY(-50%);background:none;border:none;cursor:pointer;color:#94a3b8">
                           <i class="fas fa-eye"></i>
                         </button>
                       </div>`,
                confirmButtonText:  '<i class="fas fa-key"></i> Cambiar contraseña',
                cancelButtonText:   'Atrás',
                showCancelButton:   true,
                confirmButtonColor: '#4f46e5',
                focusConfirm:       false,
                preConfirm: () => {
                    const nueva   = document.getElementById('swal-pwd-nueva')?.value   || '';
                    const confirm = document.getElementById('swal-pwd-confirm')?.value || '';
                    if (nueva.length < 6) {
                        Swal.showValidationMessage('La contraseña debe tener al menos 6 caracteres');
                        return false;
                    }
                    if (nueva !== confirm) {
                        Swal.showValidationMessage('Las contraseñas no coinciden');
                        return false;
                    }
                    return { nueva, confirm };
                },
            });
            if (!step2.isConfirmed) return;

            /* ── Step 3: loading + AJAX ──────────────────────────── */
            Swal.fire({ title: 'Actualizando...', allowOutsideClick: false, didOpen: () => Swal.showLoading() });

            try {
                const resp = await fetch('index.php?controller=usuario&action=cambiarPassword', {
                    method:  'POST',
                    headers: {
                        'Content-Type':     'application/json',
                        'X-Requested-With': 'XMLHttpRequest',
                    },
                    credentials: 'same-origin',
                    body: JSON.stringify({
                        id_usuario:           parseInt(id),
                        password_actual:      passwordActual,
                        password_nueva:       step2.value.nueva,
                        password_nueva_confirm: step2.value.confirm,
                        csrf_token:           csrf,
                    }),
                });

                if (!resp.headers.get('content-type')?.includes('application/json')) {
                    throw new Error('Respuesta inesperada del servidor');
                }

                const data = await resp.json();

                Swal.fire({
                    title:             data.success ? '¡Contraseña actualizada!' : 'No se pudo actualizar',
                    text:              data.mensaje,
                    icon:              data.success ? 'success' : 'error',
                    confirmButtonColor:'#4f46e5',
                    timer:             data.success ? 3000 : undefined,
                    timerProgressBar:  data.success,
                });

            } catch (err) {
                console.error('cambiarPassword:', err);
                Swal.fire({ title: 'Error de conexión', text: err.message, icon: 'error', confirmButtonColor: '#4f46e5' });
            }
        });
    });
}

/* ============================================================
   5. TOGGLE ESTADO (activar/desactivar desde botón en tabla)
      Reutiliza el flujo de eliminar con diferente confirmación
   ============================================================ */
function initToggleEstado() {
    // Implementación reutilizable si se añade acción toggleEstado en el controlador
}

/* ============================================================
   6. HELPERS GLOBALES (accesibles desde HTML inline de Swal)
   ============================================================ */

/** Muestra/oculta contraseña dentro de un popup de Swal */
window.toggleSwalEye = function (inputId, btn) {
    const input = document.getElementById(inputId);
    if (!input) return;
    const isPass = input.type === 'password';
    input.type = isPass ? 'text' : 'password';
    btn.innerHTML = isPass ? '<i class="fas fa-eye-slash"></i>' : '<i class="fas fa-eye"></i>';
};

/** Muestra/oculta contraseña en formularios normales */
window.toggleEye = function (inputId, btn) {
    const input = document.getElementById(inputId);
    if (!input) return;
    const isPass = input.type === 'password';
    input.type = isPass ? 'text' : 'password';
    const icon = btn.querySelector('i');
    if (icon) { icon.className = isPass ? 'fas fa-eye-slash' : 'fas fa-eye'; }
};

/** Barra de fortaleza de contraseña dentro de Swal */
window.updateStrength = function (val) {
    const bar  = document.getElementById('swal-strength-bar');
    const text = document.getElementById('swal-strength-text');
    if (!bar || !text) return;

    let score = 0;
    if (val.length >= 6)               score++;
    if (val.length >= 10)              score++;
    if (/[A-Z]/.test(val))            score++;
    if (/[0-9]/.test(val))            score++;
    if (/[^A-Za-z0-9]/.test(val))     score++;

    const levels = [
        { w: '0%',   bg: 'transparent', label: '' },
        { w: '25%',  bg: '#ef4444',     label: 'Muy débil' },
        { w: '50%',  bg: '#f59e0b',     label: 'Débil' },
        { w: '75%',  bg: '#3b82f6',     label: 'Buena' },
        { w: '90%',  bg: '#10b981',     label: 'Fuerte' },
        { w: '100%', bg: '#059669',     label: 'Muy fuerte' },
    ];
    const lvl = levels[Math.min(score, 5)];
    bar.style.width      = lvl.w;
    bar.style.background = lvl.bg;
    text.textContent     = lvl.label;
    text.style.color     = lvl.bg;
};

/** Fortaleza de contraseña en formularios normales */
window.updateStrengthForm = function (val, barId, textId) {
    const bar  = document.getElementById(barId);
    const text = document.getElementById(textId);
    if (!bar || !text) return;

    let score = 0;
    if (val.length >= 6)               score++;
    if (val.length >= 10)              score++;
    if (/[A-Z]/.test(val))            score++;
    if (/[0-9]/.test(val))            score++;
    if (/[^A-Za-z0-9]/.test(val))     score++;

    const levels = [
        { w: '0%',   bg: 'transparent', label: '' },
        { w: '25%',  bg: '#ef4444',     label: 'Muy débil' },
        { w: '50%',  bg: '#f59e0b',     label: 'Débil' },
        { w: '75%',  bg: '#3b82f6',     label: 'Buena' },
        { w: '90%',  bg: '#10b981',     label: 'Fuerte' },
        { w: '100%', bg: '#059669',     label: 'Muy fuerte' },
    ];
    const lvl = levels[Math.min(score, 5)];
    bar.style.width      = lvl.w;
    bar.style.background = lvl.bg;
    text.textContent     = lvl.label;
    text.style.color     = lvl.bg;
};

/** Escapa HTML para inserción segura en innerHTML */
function escapeHtml(str) {
    const d = document.createElement('div');
    d.appendChild(document.createTextNode(String(str)));
    return d.innerHTML;
}