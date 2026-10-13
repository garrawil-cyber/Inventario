/**
 * movimientos.js — Módulo Movimientos de Inventario
 * Funcionalidades:
 *   · Flash SweetAlert del servidor (sweet_alert en sesión)
 *   · Panel de filtros colapsable con chevron animado
 *   · Modal de detalle con animación propia (sin Bootstrap)
 *   · Helpers de formato numérico y de fecha
 */

'use strict';

// =========================================================================
// HELPERS
// =========================================================================

function mvFmt(num, dec = 2) {
    return Number(num || 0).toLocaleString('es-PE', {
        minimumFractionDigits: dec,
        maximumFractionDigits: dec,
    });
}

function mvFmtFecha(str) {
    if (!str) return '—';
    const d = new Date(str.replace(' ', 'T'));
    return d.toLocaleString('es-PE', {
        day: '2-digit', month: '2-digit', year: 'numeric',
        hour: '2-digit', minute: '2-digit',
    });
}

function mvEsc(s) {
    if (!s) return '';
    const d = document.createElement('div');
    d.appendChild(document.createTextNode(String(s)));
    return d.innerHTML;
}

// =========================================================================
// FLASH SWEETALERT (lee mvFlashData inyectado por la vista)
// =========================================================================

function initFlashAlert() {
    if (typeof mvFlashData === 'undefined' || !mvFlashData) return;

    const cfg = {
        title:             mvFlashData.title   || 'Aviso',
        text:              mvFlashData.text    || mvFlashData.message || '',
        icon:              mvFlashData.type    || 'info',
        timer:             mvFlashData.timer   || undefined,
        timerProgressBar:  !!mvFlashData.timerProgressBar,
        showConfirmButton: mvFlashData.showConfirmButton ?? true,
        confirmButtonText: mvFlashData.confirmButtonText || 'Aceptar',
        confirmButtonColor: '#2563eb',
        position:          mvFlashData.toast ? 'top-end' : 'center',
        toast:             !!mvFlashData.toast,
    };

    Swal.fire(cfg);
}

// =========================================================================
// PANEL DE FILTROS
// =========================================================================

function initFiltros() {
    const toggle  = document.getElementById('mvFilterToggle');
    const body    = document.getElementById('mvFilterBody');
    const chevron = document.getElementById('mvFilterChevron');
    if (!toggle || !body) return;

    // Estado inicial: abierto si hay filtros activos
    const hayFiltros = parseInt(toggle.dataset.active || '0') > 0;
    if (hayFiltros) {
        body.style.display = 'block';
        chevron?.classList.add('open');
    } else {
        body.style.display = 'none';
    }

    toggle.addEventListener('click', function () {
        const abierto = body.style.display !== 'none';
        body.style.display = abierto ? 'none' : 'block';
        chevron?.classList.toggle('open', !abierto);
    });
}

// =========================================================================
// MODAL DE DETALLE (custom, sin Bootstrap)
// =========================================================================

const TIPO_LABELS = {
    entrada: '↓ Entrada',
    salida:  '↑ Salida',
    ajuste:  '⇄ Ajuste',
};

function mvShowDetalle(m) {
    const overlay = document.getElementById('mvModalOverlay');
    if (!overlay) return;

    const total = parseFloat(m.costo_total) || (parseFloat(m.cantidad || 0) * parseFloat(m.costo_unitario || 0));
    const tipo  = m.tipo_movimiento || 'otro';

    // ── Cabecera ───────────────────────────────────────────
    _set('mvDCodigo',  m.codigo || '');
    _set('mvDTipoBadge', `<span class="mv-pill mv-pill-${tipo}">${mvEsc(TIPO_LABELS[tipo] || ucfirst(tipo))}</span>`, true);

    // ── Producto ───────────────────────────────────────────
    _set('mvDNombre',   m.producto_nombre || '—');
    _set('mvDCodProd',  m.producto_codigo || '—');
    _set('mvDAlmacen',  m.almacen_nombre  || '—');
    _set('mvDProveedor',m.proveedor_nombre|| '—');

    // ── Movimiento ─────────────────────────────────────────
    _set('mvDFecha',    mvFmtFecha(m.fecha_movimiento));
    _set('mvDUsuario',  m.usuario_nombre  || '—');
    const docTxt = [m.documento_tipo, m.documento_numero].filter(Boolean).join(' ') || '—';
    _set('mvDDocumento', docTxt);

    // ── Estadísticas ───────────────────────────────────────
    _set('mvDStockAnt', m.stock_anterior != null ? Number(m.stock_anterior).toLocaleString() : '—');
    _set('mvDCantidad', m.cantidad != null ? Number(m.cantidad).toLocaleString() : '—');
    _set('mvDStockNvo', m.stock_nuevo != null ? Number(m.stock_nuevo).toLocaleString() : '—');
    _set('mvDTotal',   'S/ ' + mvFmt(total));

    // Colorear total según tipo
    const totalEl = document.getElementById('mvDTotal');
    if (totalEl) {
        totalEl.className = 'mv-modal-stat-val ' +
            (tipo === 'entrada' ? 'mv-money-entrada' : tipo === 'salida' ? 'mv-money-salida' : '');
    }

    // ── Observaciones ──────────────────────────────────────
    _set('mvDObs', m.observaciones || 'Sin observaciones.');

    // ── Abrir overlay ──────────────────────────────────────
    overlay.classList.add('active');
    document.body.style.overflow = 'hidden';
}

function mvCloseDetalle() {
    const overlay = document.getElementById('mvModalOverlay');
    if (overlay) overlay.classList.remove('active');
    document.body.style.overflow = '';
}

function _set(id, val, html = false) {
    const el = document.getElementById(id);
    if (!el) return;
    if (html) el.innerHTML = val;
    else el.textContent = val;
}

function ucfirst(s) {
    if (!s) return '';
    return s.charAt(0).toUpperCase() + s.slice(1);
}

// =========================================================================
// INICIALIZACIÓN
// =========================================================================

document.addEventListener('DOMContentLoaded', function () {

    // Flash del servidor
    initFlashAlert();

    // Filtros colapsables
    initFiltros();

    // Cerrar modal con overlay o botón X
    const overlay = document.getElementById('mvModalOverlay');
    if (overlay) {
        overlay.addEventListener('click', function (e) {
            if (e.target === overlay) mvCloseDetalle();
        });
    }
    document.getElementById('mvModalClose')?.addEventListener('click', mvCloseDetalle);
    document.addEventListener('keydown', function (e) {
        if (e.key === 'Escape') mvCloseDetalle();
    });
});