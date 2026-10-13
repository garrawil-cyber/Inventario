/**
 * crear_usuario.js
 * Interactividad del formulario de creación de usuario.
 *
 * Responsabilidades:
 *  - Selector visual de rol (tarjetas radio)
 *  - Preview dinámico de avatar y nombre
 *  - Checklist de campos requeridos en tiempo real
 *  - Validación client-side antes del submit con SweetAlert de confirmación
 *  - Feedback de coincidencia de contraseñas
 */

'use strict';

/* ============================================================
   CONSTANTES
   ============================================================ */
const ROL_COLORS = {
    admin:       { bg: 'linear-gradient(135deg,#4f46e5,#7c3aed)',  cls: 'us-avatar-admin'      },
    supervisor:  { bg: 'linear-gradient(135deg,#0891b2,#06b6d4)',  cls: 'us-avatar-supervisor' },
    vendedor:    { bg: 'linear-gradient(135deg,#059669,#10b981)',   cls: 'us-avatar-vendedor'   },
    almacenero:  { bg: 'linear-gradient(135deg,#d97706,#f59e0b)',   cls: 'us-avatar-almacenero' },
};

/* ============================================================
   INICIALIZACIÓN
   ============================================================ */
document.addEventListener('DOMContentLoaded', () => {
    initPreview();
    initChecklist();
    initFormSubmit();
    // Repoblar preview si hay form_data (error de validación PHP)
    triggerAllInputs();
});

/* ============================================================
   1. SELECTOR DE ROL
   ============================================================ */
window.selectRol = function (rol) {
    const hidden = document.getElementById('rolHidden');
    if (!hidden) return;
    hidden.value = rol;

    // Estilos de todas las opciones
    document.querySelectorAll('[id^="rolOption_"]').forEach(el => {
        el.style.border     = '2px solid var(--us-gray-200)';
        el.style.background = '#fff';
    });
    document.querySelectorAll('[id^="rolCheck_"]').forEach(el => {
        el.style.border     = '2px solid var(--us-gray-200)';
        el.style.background = '#fff';
        el.innerHTML        = '';
    });

    // Activar la opción seleccionada
    const optEl   = document.getElementById(`rolOption_${rol}`);
    const checkEl = document.getElementById(`rolCheck_${rol}`);
    if (optEl) {
        optEl.style.border     = '2px solid var(--us-primary)';
        optEl.style.background = 'rgba(79,70,229,.05)';
    }
    if (checkEl) {
        checkEl.style.border     = '2px solid var(--us-primary)';
        checkEl.style.background = 'var(--us-primary)';
        checkEl.innerHTML        = '<i class="fas fa-check" style="color:#fff;font-size:.55rem"></i>';
    }

    // Actualizar avatar del preview
    updateAvatarRol(rol);
};

function updateAvatarRol(rol) {
    const avatar = document.getElementById('avatarPreview');
    if (!avatar) return;
    // Limpiar clases de rol anteriores
    Object.values(ROL_COLORS).forEach(v => avatar.classList.remove(v.cls));
    avatar.classList.add(ROL_COLORS[rol]?.cls ?? 'us-avatar-vendedor');
}

/* ============================================================
   2. PREVIEW DINÁMICO (nombre, username, avatar iniciales)
   ============================================================ */
function initPreview() {
    const nombreInput   = document.getElementById('nombre_completo');
    const usernameInput = document.getElementById('username');
    if (!nombreInput || !usernameInput) return;

    nombreInput.addEventListener('input',   updatePreview);
    usernameInput.addEventListener('input', updatePreview);
}

function updatePreview() {
    const nombre   = document.getElementById('nombre_completo')?.value.trim() || '';
    const username = document.getElementById('username')?.value.trim()        || '';
    const avatar   = document.getElementById('avatarPreview');
    const prevNom  = document.getElementById('previewNombre');
    const prevUser = document.getElementById('previewUsername');

    if (prevNom)  prevNom.textContent  = nombre   || 'Nuevo usuario';
    if (prevUser) prevUser.textContent = username ? `@${username}` : '@username';

    if (avatar) {
        if (nombre) {
            const partes    = nombre.split(' ');
            const iniciales = (partes[0][0] || '') + (partes[1]?.[0] || '');
            avatar.innerHTML  = iniciales.toUpperCase();
        } else {
            avatar.innerHTML = '<i class="fas fa-user"></i>';
        }
    }
}

/* ============================================================
   3. CHECKLIST DE CAMPOS REQUERIDOS
   ============================================================ */
const CHECKS = {
    chkNombre:   () => (document.getElementById('nombre_completo')?.value.trim().length  ?? 0) >= 3,
    chkEmail:    () => /^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(document.getElementById('email')?.value.trim() ?? ''),
    chkUsername: () => /^[a-zA-Z0-9_]{3,50}$/.test(document.getElementById('username')?.value.trim() ?? ''),
    chkPassword: () => (document.getElementById('password')?.value.length ?? 0) >= 6,
    chkConfirm:  () => {
        const p1 = document.getElementById('password')?.value         ?? '';
        const p2 = document.getElementById('password_confirm')?.value ?? '';
        return p1.length >= 6 && p1 === p2;
    },
};

function initChecklist() {
    const watchIds = ['nombre_completo','email','username','password','password_confirm'];
    watchIds.forEach(id => {
        document.getElementById(id)?.addEventListener('input', updateChecklist);
    });
    updateChecklist(); // estado inicial
}

function updateChecklist() {
    Object.entries(CHECKS).forEach(([id, fn]) => {
        const li   = document.getElementById(id);
        if (!li) return;
        const ok   = fn();
        li.style.color = ok ? 'var(--us-success)' : '#94a3b8';
        const icon = li.querySelector('i');
        if (icon) {
            icon.className = ok ? 'fas fa-check-circle' : 'fas fa-circle';
            icon.style.fontSize = ok ? '.8rem' : '.45rem';
        }
    });
}

function allChecksPass() {
    return Object.values(CHECKS).every(fn => fn());
}

/* ============================================================
   4. FEEDBACK DE COINCIDENCIA DE CONTRASEÑA
   ============================================================ */
window.checkPasswordMatch = function () {
    const p1   = document.getElementById('password')?.value         ?? '';
    const p2   = document.getElementById('password_confirm')?.value ?? '';
    const hint = document.getElementById('matchHint');
    if (!hint) return;

    if (!p2) { hint.textContent = ''; return; }

    if (p1 === p2) {
        hint.textContent  = '✓ Las contraseñas coinciden';
        hint.style.color  = 'var(--us-success)';
    } else {
        hint.textContent  = '✗ Las contraseñas no coinciden';
        hint.style.color  = 'var(--us-danger)';
    }
};

/* ============================================================
   5. SUBMIT CON VALIDACIÓN + CONFIRM SWAL
   ============================================================ */
function initFormSubmit() {
    const form = document.getElementById('formCrearUsuario');
    if (!form) return;

    form.addEventListener('submit', async function (e) {
        e.preventDefault();

        // ── Validación client-side ────────────────────────────
        if (!allChecksPass()) {
            // Determinar el primer campo incompleto
            const firstFail = [
                ['nombre_completo', CHECKS.chkNombre],
                ['email',           CHECKS.chkEmail],
                ['username',        CHECKS.chkUsername],
                ['password',        CHECKS.chkPassword],
                ['password_confirm',CHECKS.chkConfirm],
            ].find(([, fn]) => !fn());

            if (firstFail) {
                const input = document.getElementById(firstFail[0]);
                input?.focus();
                input?.classList.add('is-invalid');
                setTimeout(() => input?.classList.remove('is-invalid'), 2500);
            }

            Swal.fire({
                title:             'Completa el formulario',
                html:              'Revisa los campos marcados en rojo antes de continuar.',
                icon:              'warning',
                confirmButtonText: 'Entendido',
                confirmButtonColor:'#4f46e5',
            });
            return;
        }

        // ── Confirmación antes de enviar ─────────────────────
        const nombre   = document.getElementById('nombre_completo')?.value.trim() || '';
        const username = document.getElementById('username')?.value.trim()        || '';
        const rol      = document.getElementById('rolHidden')?.value              || 'vendedor';
        const estado   = document.getElementById('estado')?.value                 || 'activo';

        const rolLabels = {
            admin:'Administrador', supervisor:'Supervisor',
            vendedor:'Vendedor',   almacenero:'Almacenero',
        };

        const confirm = await Swal.fire({
            title:       '¿Crear usuario?',
            html: `<div style="text-align:left;font-size:.88rem;color:#334155;line-height:1.7">
                     <b>Nombre:</b> ${escHtml(nombre)}<br>
                     <b>Usuario:</b> @${escHtml(username)}<br>
                     <b>Rol:</b> ${escHtml(rolLabels[rol] ?? rol)}<br>
                     <b>Estado:</b> ${estado === 'activo' ? '✅ Activo' : '⛔ Inactivo'}
                   </div>`,
            icon:              'question',
            showCancelButton:  true,
            confirmButtonText: '<i class="fas fa-user-plus"></i> Sí, crear usuario',
            cancelButtonText:  'Revisar',
            confirmButtonColor:'#4f46e5',
            cancelButtonColor: '#94a3b8',
            focusCancel:       true,
        });

        if (!confirm.isConfirmed) return;

        // ── Loading ───────────────────────────────────────────
        const btnGuardar = document.getElementById('btnGuardar');
        if (btnGuardar) {
            btnGuardar.disabled   = true;
            btnGuardar.innerHTML  = '<span class="us-spinner"></span> Creando...';
        }

        // Enviar el formulario realmente
        form.submit();
    });
}

/* ============================================================
   6. HELPERS
   ============================================================ */
function triggerAllInputs() {
    ['nombre_completo','email','username','password','password_confirm'].forEach(id => {
        const el = document.getElementById(id);
        if (el?.value) el.dispatchEvent(new Event('input'));
    });
}

function escHtml(str) {
    const d = document.createElement('div');
    d.appendChild(document.createTextNode(String(str)));
    return d.innerHTML;
}