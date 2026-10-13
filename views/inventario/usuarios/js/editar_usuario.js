/**
 * editar_usuario.js
 * Interactividad del formulario de edición de usuario.
 *
 * Diferencias vs crear_usuario.js:
 *  - Los campos ya tienen valor inicial → checklist arranca en verde
 *  - Contraseña es OPCIONAL → checklist y validación lo reflejan
 *  - Detector de cambios → muestra banner "Hay cambios sin guardar"
 *  - Confirm Swal muestra qué campos cambiaron vs el valor original
 *  - Advertencia si intenta salir con cambios sin guardar (beforeunload)
 */

'use strict';

/* ============================================================
   CONSTANTES
   ============================================================ */
const ROL_COLORS = {
    admin:       'us-avatar-admin',
    supervisor:  'us-avatar-supervisor',
    vendedor:    'us-avatar-vendedor',
    almacenero:  'us-avatar-almacenero',
};

// Estado original para detectar cambios (inyectado por la vista PHP)
const ORIGINAL = {
    nombre:   usuarioInicial.nombre,
    username: usuarioInicial.username,
    rol:      usuarioInicial.rol,
    iniciales:usuarioInicial.iniciales,
};

let formDirty   = false;
let submitReady = false; // evita doble disparo del beforeunload al confirmar

/* ============================================================
   INICIALIZACIÓN
   ============================================================ */
document.addEventListener('DOMContentLoaded', () => {
    initPreview();
    initChecklist();
    initChangeDetector();
    initFormSubmit();
    initBeforeUnload();

    // El avatar ya está pre-poblado por PHP, solo sincronizar la clase de color
    updateAvatarRol(usuarioInicial.rol);
});

/* ============================================================
   1. SELECTOR DE ROL (igual que crear, reutiliza window.selectRol de usuarios.js)
      Extendemos para actualizar también el avatar
   ============================================================ */
window.selectRol = function (rol) {
    const hidden = document.getElementById('rolHidden');
    if (!hidden) return;
    hidden.value = rol;

    // Reset todos
    document.querySelectorAll('[id^="rolOption_"]').forEach(el => {
        el.style.border     = '2px solid var(--us-gray-200)';
        el.style.background = '#fff';
    });
    document.querySelectorAll('[id^="rolCheck_"]').forEach(el => {
        el.style.border     = '2px solid var(--us-gray-200)';
        el.style.background = '#fff';
        el.innerHTML        = '';
    });

    // Activar seleccionado
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

    updateAvatarRol(rol);
    markDirty();
};

function updateAvatarRol(rol) {
    const avatar = document.getElementById('avatarPreview');
    if (!avatar) return;
    Object.values(ROL_COLORS).forEach(cls => avatar.classList.remove(cls));
    avatar.classList.add(ROL_COLORS[rol] ?? 'us-avatar-vendedor');
}

/* ============================================================
   2. PREVIEW DINÁMICO
   ============================================================ */
function initPreview() {
    document.getElementById('nombre_completo')?.addEventListener('input', updatePreview);
    document.getElementById('username')?.addEventListener('input',         updatePreview);
}

function updatePreview() {
    const nombre   = document.getElementById('nombre_completo')?.value.trim() || '';
    const username = document.getElementById('username')?.value.trim()        || '';
    const avatar   = document.getElementById('avatarPreview');
    const prevNom  = document.getElementById('previewNombre');
    const prevUser = document.getElementById('previewUsername');

    if (prevNom)  prevNom.textContent  = nombre   || ORIGINAL.nombre;
    if (prevUser) prevUser.textContent = username  ? `@${username}` : `@${ORIGINAL.username}`;

    if (avatar) {
        if (nombre) {
            const partes    = nombre.split(' ');
            const iniciales = ((partes[0]?.[0] ?? '') + (partes[1]?.[0] ?? '')).toUpperCase();
            avatar.innerHTML = iniciales || '<i class="fas fa-user"></i>';
        } else {
            avatar.innerHTML = ORIGINAL.iniciales || '<i class="fas fa-user"></i>';
        }
    }
}

/* ============================================================
   3. CHECKLIST
      En edición: nombre/email/username arrancan verdes (ya tienen valor).
      Contraseña es opcional: si se escribe algo, pasa a validar.
   ============================================================ */
const CHECKS_EDICION = {
    chkNombre:   () => (document.getElementById('nombre_completo')?.value.trim().length ?? 0) >= 3,
    chkEmail:    () => /^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(document.getElementById('email')?.value.trim() ?? ''),
    chkUsername: () => /^[a-zA-Z0-9_]{3,50}$/.test(document.getElementById('username')?.value.trim() ?? ''),
    chkPassword: () => {
        const pwd = document.getElementById('password')?.value ?? '';
        // Si está vacío → OK (no se cambia). Si tiene algo → debe tener ≥6 y coincidir.
        if (!pwd) return true;
        const conf = document.getElementById('password_confirm')?.value ?? '';
        return pwd.length >= 6 && pwd === conf;
    },
};

function initChecklist() {
    const watchIds = ['nombre_completo','email','username','password','password_confirm'];
    watchIds.forEach(id => {
        document.getElementById(id)?.addEventListener('input', updateChecklist);
    });
    updateChecklist(); // estado inicial (campos ya poblados → verde)
}

function updateChecklist() {
    Object.entries(CHECKS_EDICION).forEach(([id, fn]) => {
        const li   = document.getElementById(id);
        if (!li) return;
        const ok   = fn();
        li.style.color = ok ? 'var(--us-success)' : '#94a3b8';
        const icon = li.querySelector('i');
        if (icon) {
            icon.className  = ok ? 'fas fa-check-circle' : 'fas fa-circle';
            icon.style.fontSize = ok ? '.8rem' : '.45rem';
        }
    });
}

function allChecksPass() {
    return Object.values(CHECKS_EDICION).every(fn => fn());
}

/* ============================================================
   4. FEEDBACK DE CONTRASEÑA
   ============================================================ */

// Llamado desde oninput del campo password (en el HTML)
window.onPasswordInput = function () {
    const pwd = document.getElementById('password')?.value ?? '';
    updateStrengthForm(pwd, 'strengthBar', 'strengthText');

    // Mostrar/ocultar el banner informativo
    const banner = document.getElementById('passwordOptionalBanner');
    if (banner) {
        banner.style.display = pwd ? 'none' : 'flex';
    }

    updateChecklist();
    markDirty();
};

// Llamado desde oninput del campo password_confirm (en el HTML)
window.checkPasswordMatch = function () {
    const p1   = document.getElementById('password')?.value         ?? '';
    const p2   = document.getElementById('password_confirm')?.value ?? '';
    const hint = document.getElementById('matchHint');
    if (!hint) return;

    if (!p1 || !p2) { hint.textContent = ''; updateChecklist(); return; }

    if (p1 === p2) {
        hint.textContent = '✓ Las contraseñas coinciden';
        hint.style.color = 'var(--us-success)';
    } else {
        hint.textContent = '✗ Las contraseñas no coinciden';
        hint.style.color = 'var(--us-danger)';
    }
    updateChecklist();
};

/* ============================================================
   5. DETECTOR DE CAMBIOS
   ============================================================ */
function initChangeDetector() {
    const watchIds = ['nombre_completo','email','username','password','password_confirm','estado'];
    watchIds.forEach(id => {
        document.getElementById(id)?.addEventListener('input', markDirty);
        document.getElementById(id)?.addEventListener('change', markDirty);
    });
}

function markDirty() {
    formDirty = true;
    const banner = document.getElementById('changesBanner');
    if (banner) banner.style.display = 'block';
}

/* ============================================================
   6. ADVERTENCIA AL SALIR CON CAMBIOS SIN GUARDAR
   ============================================================ */
function initBeforeUnload() {
    window.addEventListener('beforeunload', (e) => {
        if (formDirty && !submitReady) {
            e.preventDefault();
            e.returnValue = '';
        }
    });

    // Cancelar antes de salir con el botón "Cancelar" sin cambios no advierte
    document.querySelector('a.us-btn-ghost')?.addEventListener('click', (e) => {
        if (!formDirty) return;
        e.preventDefault();
        const href = e.currentTarget.href;
        Swal.fire({
            title:             '¿Descartar cambios?',
            text:              'Tienes cambios sin guardar. Si sales ahora, se perderán.',
            icon:              'warning',
            showCancelButton:  true,
            confirmButtonText: 'Sí, descartar',
            cancelButtonText:  'Seguir editando',
            confirmButtonColor:'#ef4444',
            cancelButtonColor: '#94a3b8',
        }).then(result => {
            if (result.isConfirmed) {
                formDirty = false;
                window.location.href = href;
            }
        });
    });
}

/* ============================================================
   7. SUBMIT CON VALIDACIÓN + CONFIRM SWAL
   ============================================================ */
function initFormSubmit() {
    const form = document.getElementById('formEditarUsuario');
    if (!form) return;

    form.addEventListener('submit', async function (e) {
        e.preventDefault();

        // ── Validación client-side ────────────────────────────
        if (!allChecksPass()) {
            const firstFail = [
                ['nombre_completo', CHECKS_EDICION.chkNombre],
                ['email',           CHECKS_EDICION.chkEmail],
                ['username',        CHECKS_EDICION.chkUsername],
                ['password',        CHECKS_EDICION.chkPassword],
            ].find(([, fn]) => !fn());

            if (firstFail) {
                document.getElementById(firstFail[0])?.focus();
            }

            Swal.fire({
                title:             'Revisa el formulario',
                html:              'Corrige los campos señalados antes de guardar.',
                icon:              'warning',
                confirmButtonText: 'Entendido',
                confirmButtonColor:'#4f46e5',
            });
            return;
        }

        // ── Resumen de cambios ────────────────────────────────
        const nombre   = document.getElementById('nombre_completo')?.value.trim() ?? '';
        const username = document.getElementById('username')?.value.trim()        ?? '';
        const rol      = document.getElementById('rolHidden')?.value              ?? '';
        const estado   = document.getElementById('estado')?.value                 ?? '';
        const pwd      = document.getElementById('password')?.value               ?? '';

        const rolLabels = {
            admin:'Administrador', supervisor:'Supervisor',
            vendedor:'Vendedor',   almacenero:'Almacenero',
        };

        // Construir lista de cambios
        let cambios = '';
        if (nombre   !== ORIGINAL.nombre)   cambios += `<li><b>Nombre:</b> ${escHtml(nombre)}</li>`;
        if (username !== ORIGINAL.username)  cambios += `<li><b>Usuario:</b> @${escHtml(username)}</li>`;
        if (rol      !== ORIGINAL.rol)       cambios += `<li><b>Rol:</b> ${escHtml(rolLabels[rol] ?? rol)}</li>`;
        if (pwd)                              cambios += `<li><b>Contraseña:</b> ••••••••</li>`;

        const resumenHtml = cambios
            ? `<ul style="text-align:left;font-size:.85rem;color:#334155;margin:.5rem 0 0;padding-left:1.25rem;line-height:1.8">${cambios}</ul>`
            : `<p style="font-size:.85rem;color:#64748b;margin:.5rem 0 0">No se detectaron cambios en los campos principales.</p>`;

        // ── Confirm Swal ──────────────────────────────────────
        const confirm = await Swal.fire({
            title: '¿Guardar cambios?',
            html:  `<div style="text-align:left">
                      <p style="font-size:.85rem;color:#475569;margin:0">
                        Usuario: <strong>${escHtml(nombre)}</strong>
                        &nbsp;·&nbsp; Estado: ${estado === 'activo' ? '✅ Activo' : '⛔ Inactivo'}
                      </p>
                      ${resumenHtml}
                    </div>`,
            icon:              'question',
            showCancelButton:  true,
            confirmButtonText: '<i class="fas fa-save"></i> Sí, guardar',
            cancelButtonText:  'Seguir editando',
            confirmButtonColor:'#4f46e5',
            cancelButtonColor: '#94a3b8',
            focusCancel:       true,
        });

        if (!confirm.isConfirmed) return;

        // ── Loading + submit real ─────────────────────────────
        const btnGuardar = document.getElementById('btnGuardar');
        if (btnGuardar) {
            btnGuardar.disabled  = true;
            btnGuardar.innerHTML = '<span class="us-spinner"></span> Guardando...';
        }

        submitReady = true; // desactiva el beforeunload
        form.submit();
    });
}

/* ============================================================
   8. HELPERS
   ============================================================ */
function escHtml(str) {
    const d = document.createElement('div');
    d.appendChild(document.createTextNode(String(str)));
    return d.innerHTML;
}