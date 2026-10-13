/**
 * kardex.js — Módulo Kardex (inventario valorizado)
 * Funcionalidades:
 *   · Flash SweetAlert del servidor (sweet_alert en sesión)
 *   · Panel de filtros colapsable con chevron animado
 *   · Modal de detalle con animación propia (sin Bootstrap)
 *   · Buscador de productos con autocomplete AJAX (buscar_productos)
 *   · Helpers de formato numérico y de fecha
 */

'use strict';

// =========================================================================
// HELPERS
// =========================================================================

function kxFmt(num, dec = 2) {
    return Number(num || 0).toLocaleString('es-PE', {
        minimumFractionDigits: dec,
        maximumFractionDigits: dec,
    });
}

function kxFmtFecha(str) {
    if (!str) return '—';
    const d = new Date(str.replace(' ', 'T'));
    return d.toLocaleString('es-PE', {
        day: '2-digit', month: '2-digit', year: 'numeric',
        hour: '2-digit', minute: '2-digit',
    });
}

function kxEsc(s) {
    if (!s) return '';
    const d = document.createElement('div');
    d.appendChild(document.createTextNode(String(s)));
    return d.innerHTML;
}

function kxDebounce(fn, wait = 280) {
    let t;
    return function (...args) {
        clearTimeout(t);
        t = setTimeout(() => fn.apply(this, args), wait);
    };
}

// =========================================================================
// FLASH SWEETALERT (lee kxFlashData inyectado por la vista)
// =========================================================================

function initFlashAlert() {
    if (typeof kxFlashData === 'undefined' || !kxFlashData) return;

    Swal.fire({
        title:              kxFlashData.title   || 'Aviso',
        text:               kxFlashData.text    || kxFlashData.message || '',
        icon:               kxFlashData.type    || 'info',
        timer:              kxFlashData.timer   || undefined,
        timerProgressBar:   !!kxFlashData.timerProgressBar,
        showConfirmButton:  kxFlashData.showConfirmButton ?? true,
        confirmButtonText:  kxFlashData.confirmButtonText || 'Aceptar',
        confirmButtonColor: '#2563eb',
        position:           kxFlashData.toast ? 'top-end' : 'center',
        toast:              !!kxFlashData.toast,
    });
}

// =========================================================================
// PANEL DE FILTROS
// =========================================================================

function initFiltros() {
    const toggle  = document.getElementById('kxFilterToggle');
    const body    = document.getElementById('kxFilterBody');
    const chevron = document.getElementById('kxFilterChevron');
    if (!toggle || !body) return;

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

const KX_TIPO_LABELS = {
    entrada: '↓ Entrada',
    salida:  '↑ Salida',
    ajuste:  '⇄ Ajuste',
};

function kxShowDetalle(k) {
    const overlay = document.getElementById('kxModalOverlay');
    if (!overlay) return;

    const tipo = k.tipo_movimiento || 'otro';

    _set('kxDTipoBadge', `<span class="kx-pill kx-pill-${tipo}">${kxEsc(KX_TIPO_LABELS[tipo] || kxUcfirst(tipo))}</span>`, true);
    _set('kxDFecha', kxFmtFecha(k.fecha_movimiento));
    _set('kxDDocumento', [k.documento_tipo, k.documento_numero].filter(Boolean).join(' ') || '—');
    _set('kxDAlmacen', k.almacen_nombre || '—');
    _set('kxDProveedor', k.proveedor_nombre || '—');
    _set('kxDUsuario', k.usuario_nombre || '—');

    const entradaCant = Number(k.entrada_cantidad || 0);
    const salidaCant  = Number(k.salida_cantidad || 0);
    const saldoCant   = Number(k.saldo_cantidad || 0);

    _set('kxDEntrada', entradaCant > 0 ? '+' + entradaCant.toLocaleString() : '—');
    _set('kxDSalida',  salidaCant  > 0 ? '−' + salidaCant.toLocaleString()  : '—');
    _set('kxDSaldo',   saldoCant.toLocaleString());

    _set('kxDObs', k.documento_referencia || k.observaciones || 'Sin observaciones.');

    overlay.classList.add('active');
    document.body.style.overflow = 'hidden';
}

function kxCloseDetalle() {
    const overlay = document.getElementById('kxModalOverlay');
    if (overlay) overlay.classList.remove('active');
    document.body.style.overflow = '';
}

function _set(id, val, html = false) {
    const el = document.getElementById(id);
    if (!el) return;
    if (html) el.innerHTML = val;
    else el.textContent = val;
}

function kxUcfirst(s) {
    if (!s) return '';
    return s.charAt(0).toUpperCase() + s.slice(1);
}

// =========================================================================
// BUSCADOR DE PRODUCTOS (autocomplete AJAX → buscar_productos)
// =========================================================================

function initBuscadorProductos() {
    const input   = document.getElementById('kxSearchInput');
    const results = document.getElementById('kxSearchResults');
    if (!input || !results) return;

    const baseUrl = input.dataset.searchUrl || 'index.php?controller=kardex&action=buscar_productos';
    const gotoUrl = input.dataset.gotoUrl   || 'index.php?controller=kardex&action=producto';

    let activeIndex = -1;
    let currentItems = [];

    function renderLoading() {
        results.innerHTML = '<div class="kx-search-loading"><i class="fas fa-circle-notch fa-spin"></i> Buscando…</div>';
        results.classList.add('open');
    }

    function renderEmpty() {
        results.innerHTML = '<div class="kx-search-empty">Sin resultados para tu búsqueda.</div>';
        results.classList.add('open');
    }

    function renderResults(productos) {
        currentItems = productos;
        activeIndex = -1;

        if (!productos.length) { renderEmpty(); return; }

        results.innerHTML = productos.map((p, i) => `
            <div class="kx-search-item" data-index="${i}" data-id="${p.id_producto}">
                <div>
                    <div class="kx-search-item-name">${kxEsc(p.nombre)}</div>
                    <div class="kx-search-item-code">${kxEsc(p.codigo)}${p.sku ? ' · ' + kxEsc(p.sku) : ''}</div>
                </div>
                <div class="kx-search-item-stock">${Number(p.stock_actual || 0).toLocaleString()} und.</div>
            </div>
        `).join('');
        results.classList.add('open');

        results.querySelectorAll('.kx-search-item').forEach(el => {
            el.addEventListener('click', () => {
                window.location.href = gotoUrl + '&id=' + el.dataset.id;
            });
        });
    }

    const doSearch = kxDebounce(async function (q) {
        if (q.length < 2) { results.classList.remove('open'); return; }
        renderLoading();
        try {
            const resp = await fetch(baseUrl + '&q=' + encodeURIComponent(q), {
                headers: { 'X-Requested-With': 'XMLHttpRequest' },
            });
            const data = await resp.json();
            renderResults(data.productos || []);
        } catch (e) {
            results.innerHTML = '<div class="kx-search-empty">Error al buscar. Intenta de nuevo.</div>';
        }
    }, 280);

    input.addEventListener('input', () => doSearch(input.value.trim()));
    input.addEventListener('focus', () => { if (currentItems.length) results.classList.add('open'); });

    input.addEventListener('keydown', (e) => {
        const items = results.querySelectorAll('.kx-search-item');
        if (!items.length) return;

        if (e.key === 'ArrowDown') {
            e.preventDefault();
            activeIndex = Math.min(activeIndex + 1, items.length - 1);
            items.forEach(el => el.classList.remove('active'));
            items[activeIndex]?.classList.add('active');
            items[activeIndex]?.scrollIntoView({ block: 'nearest' });
        } else if (e.key === 'ArrowUp') {
            e.preventDefault();
            activeIndex = Math.max(activeIndex - 1, 0);
            items.forEach(el => el.classList.remove('active'));
            items[activeIndex]?.classList.add('active');
            items[activeIndex]?.scrollIntoView({ block: 'nearest' });
        } else if (e.key === 'Enter') {
            e.preventDefault();
            if (activeIndex >= 0 && items[activeIndex]) {
                window.location.href = gotoUrl + '&id=' + items[activeIndex].dataset.id;
            }
        } else if (e.key === 'Escape') {
            results.classList.remove('open');
        }
    });

    document.addEventListener('click', (e) => {
        if (!results.contains(e.target) && e.target !== input) {
            results.classList.remove('open');
        }
    });
}

// =========================================================================
// INICIALIZACIÓN
// =========================================================================

document.addEventListener('DOMContentLoaded', function () {
    initFlashAlert();
    initFiltros();
    initBuscadorProductos();

    const overlay = document.getElementById('kxModalOverlay');
    if (overlay) {
        overlay.addEventListener('click', function (e) {
            if (e.target === overlay) kxCloseDetalle();
        });
    }
    document.getElementById('kxModalClose')?.addEventListener('click', kxCloseDetalle);
    document.addEventListener('keydown', function (e) {
        if (e.key === 'Escape') kxCloseDetalle();
    });
});