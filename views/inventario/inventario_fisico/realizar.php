<?php
$currentPage = 'inventario_fisico';
include __DIR__ . '/../../layouts/inventario_layout.php';
?>

<!-- ══════════════════════════════════════════════════════════
     INVENTARIO FÍSICO — Realizar conteo
     Variables: $inventario, $almacen, $productos (array), $detalle (array)
════════════════════════════════════════════════════════════ -->

<style>
  @import url('https://fonts.googleapis.com/css2?family=Syne:wght@600;700;800&family=DM+Sans:wght@300;400;500;600&display=swap');
  :root{--teal:#0d9488;--navy:#0f172a;--border:#e2e8f0;--muted:#64748b;--radius:14px;--shadow:0 4px 24px rgba(15,23,42,.08);--amber:#f59e0b;}

  body,.inv-wrap{font-family:'DM Sans',sans-serif;}
  .inv-wrap{padding:28px 24px;}
  .page-title{font-family:'Syne',sans-serif;font-weight:800;font-size:1.65rem;color:var(--navy);margin:0;letter-spacing:-.5px;}
  .breadcrumb-inv{font-size:.8rem;color:var(--muted);}
  .breadcrumb-inv a{color:var(--teal);text-decoration:none;}

  /* Info strip */
  .info-strip{display:flex;flex-wrap:wrap;gap:12px;background:#fff;border-radius:var(--radius);box-shadow:var(--shadow);border:1px solid var(--border);padding:16px 22px;margin-bottom:22px;align-items:center;}
  .info-item{display:flex;flex-direction:column;gap:2px;}
  .info-item .lbl{font-size:.72rem;font-weight:700;text-transform:uppercase;letter-spacing:.07em;color:var(--muted);}
  .info-item .val{font-size:.9rem;font-weight:600;color:var(--navy);}
  .info-divider{width:1px;background:var(--border);height:36px;align-self:center;}

  /* Table card */
  .count-card{background:#fff;border-radius:var(--radius);box-shadow:var(--shadow);border:1px solid var(--border);overflow:hidden;}
  .count-header{padding:16px 22px;border-bottom:1px solid var(--border);display:flex;align-items:center;justify-content:space-between;flex-wrap:wrap;gap:10px;}
  .count-title{font-family:'Syne',sans-serif;font-weight:700;color:var(--navy);font-size:.95rem;}

  .count-table{width:100%;border-collapse:collapse;}
  .count-table thead th{background:#f8fafc;padding:10px 14px;font-size:.72rem;font-weight:700;text-transform:uppercase;letter-spacing:.07em;color:var(--muted);border-bottom:2px solid var(--border);white-space:nowrap;}
  .count-table tbody td{padding:10px 14px;border-bottom:1px solid #f1f5f9;vertical-align:middle;font-size:.875rem;color:#334155;}
  .count-table tbody tr:last-child td{border-bottom:none;}
  .count-table tbody tr:hover td{background:#f8fafc;}

  /* Count input */
  .qty-input{width:90px;border:1.5px solid var(--border);border-radius:8px;padding:7px 10px;font-size:.9rem;text-align:center;transition:border-color .15s,box-shadow .15s;outline:none;font-family:'DM Sans',sans-serif;}
  .qty-input:focus{border-color:var(--teal);box-shadow:0 0 0 3px rgba(13,148,136,.12);}
  .qty-input.changed{border-color:var(--amber);background:#fffbeb;}

  /* Diff badge */
  .diff-pos{color:#059669;font-weight:700;}
  .diff-neg{color:#ef4444;font-weight:700;}
  .diff-zero{color:var(--muted);}

  /* Search bar */
  .search-bar{display:flex;align-items:center;gap:8px;background:#f8fafc;border:1.5px solid var(--border);border-radius:10px;padding:8px 14px;width:260px;}
  .search-bar i{color:var(--muted);font-size:.85rem;}
  .search-bar input{border:none;background:transparent;outline:none;font-size:.875rem;font-family:'DM Sans',sans-serif;width:100%;color:var(--navy);}

  /* Buttons */
  .btn-prim{background:var(--teal);color:#fff;border:none;border-radius:10px;padding:10px 20px;font-size:.875rem;font-weight:600;cursor:pointer;display:inline-flex;align-items:center;gap:7px;transition:background .15s;font-family:'DM Sans',sans-serif;text-decoration:none;}
  .btn-prim:hover{background:#0f766e;color:#fff;}
  .btn-sec{background:#fff;color:#334155;border:1.5px solid var(--border);border-radius:10px;padding:9px 18px;font-size:.875rem;font-weight:500;cursor:pointer;display:inline-flex;align-items:center;gap:7px;transition:all .15s;text-decoration:none;font-family:'DM Sans',sans-serif;}
  .btn-sec:hover{border-color:var(--teal);color:var(--teal);}
  .btn-warn{background:#f59e0b;color:#fff;border:none;border-radius:10px;padding:10px 20px;font-size:.875rem;font-weight:600;cursor:pointer;display:inline-flex;align-items:center;gap:7px;font-family:'DM Sans',sans-serif;}
  .btn-warn:hover{background:#d97706;}

  /* Progress bar */
  .prog-bar-wrap{background:#f1f5f9;border-radius:999px;height:8px;overflow:hidden;}
  .prog-bar-fill{height:100%;background:var(--teal);border-radius:999px;transition:width .3s;}
</style>

<meta name="csrf-token" content="<?= htmlspecialchars($csrfToken ?? '') ?>">

<div class="inv-wrap">
  <!-- Encabezado -->
  <div class="d-flex justify-content-between align-items-start mb-4 flex-wrap gap-3">
    <div>
      <h1 class="page-title"><i class="fas fa-pencil-alt me-2" style="color:var(--teal);font-size:1.3rem;"></i>Realizar Conteo Físico</h1>
      <div class="breadcrumb-inv mt-1">
        <a href="index.php?controller=dashboard&action=index">Dashboard</a> /
        <a href="index.php?controller=inventario&action=index">Inventarios Físicos</a> /
        <span>Conteo</span>
      </div>
    </div>
    <div class="d-flex gap-2">
      <a href="index.php?controller=inventario&action=ver&id=<?= (int)$inventario['id_inventario'] ?>" class="btn-sec">
        <i class="fas fa-arrow-left"></i> Volver
      </a>
      <button type="button"
              id="btnFinalizar"
              class="btn-warn"
              data-id="<?= (int)$inventario['id_inventario'] ?>"
              onclick="confirmarFinalizar()">
        <i class="fas fa-check-double"></i> Finalizar Inventario
      </button>
    </div>
  </div>

  <!-- Info strip -->
  <div class="info-strip">
    <div class="info-item">
      <span class="lbl">Código</span>
      <span class="val" style="font-family:monospace;"><?= htmlspecialchars($inventario['codigo']) ?></span>
    </div>
    <div class="info-divider"></div>
    <div class="info-item">
      <span class="lbl">Almacén</span>
      <span class="val"><?= htmlspecialchars($inventario['almacen_nombre']) ?></span>
    </div>
    <div class="info-divider"></div>
    <div class="info-item">
      <span class="lbl">Tipo</span>
      <span class="val"><?= ucfirst($inventario['tipo'] ?? 'Total') ?></span>
    </div>
    <div class="info-divider"></div>
    <div class="info-item">
      <span class="lbl">Responsable</span>
      <span class="val"><?= htmlspecialchars($inventario['responsable'] ?? '—') ?></span>
    </div>
    <div class="ms-auto d-flex flex-column gap-1" style="min-width:160px;">
      <div style="font-size:.72rem;font-weight:700;text-transform:uppercase;letter-spacing:.07em;color:var(--muted);">Progreso del conteo</div>
      <div class="prog-bar-wrap">
        <div class="prog-bar-fill" id="progBar" style="width:0%"></div>
      </div>
      <div style="font-size:.75rem;color:var(--muted);text-align:right;"><span id="progText">0</span> / <?= count($productos) ?> productos</div>
    </div>
  </div>

  <!-- Tabla de conteo -->
  <form id="formConteo" method="POST" action="index.php?controller=inventario&action=guardarConteo">
    <input type="hidden" name="id_inventario" value="<?= $inventario['id_inventario'] ?>">
    <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken ?? '') ?>">
    <input type="hidden" id="ifCsrfToken" value="<?= htmlspecialchars($csrfToken ?? '') ?>">

    <div class="count-card">
      <div class="count-header">
        <span class="count-title"><i class="fas fa-table me-2" style="color:var(--teal);"></i>Productos — Ingresa el stock físico contado</span>
        <div class="d-flex gap-2 align-items-center flex-wrap">
          <div class="search-bar">
            <i class="fas fa-search"></i>
            <input type="text" id="searchInput" placeholder="Buscar producto…" oninput="filtrarProductos(this.value)">
          </div>
          <button type="button" class="btn-sec" onclick="limpiarConteos()" style="font-size:.8rem;padding:8px 14px;">
            <i class="fas fa-eraser"></i> Limpiar
          </button>
          <button type="submit" class="btn-prim" style="font-size:.85rem;">
            <i class="fas fa-save"></i> Guardar conteo
          </button>
        </div>
      </div>

      <div class="table-responsive">
        <table class="count-table" id="countTable">
          <thead>
            <tr>
              <th>#</th>
              <th>Código / SKU</th>
              <th>Producto</th>
              <th>Categoría</th>
              <th style="text-align:center;">Stock Sistema</th>
              <th style="text-align:center;">Stock Físico</th>
              <th style="text-align:center;">Diferencia</th>
              <th>Observaciones</th>
            </tr>
          </thead>
          <tbody>
            <?php
            $detalleMap = [];
            foreach ($detalle as $d) $detalleMap[$d['id_producto']] = $d;
            $n = 0;
            foreach ($productos as $prod):
              $n++;
              $prev   = $detalleMap[$prod['id_producto']] ?? null;
              $prevQty = $prev ? $prev['stock_fisico'] : '';
              $prevObs = $prev ? htmlspecialchars($prev['observaciones'] ?? '') : '';
            ?>
            <tr class="prod-row">
              <td style="color:var(--muted);font-size:.8rem;"><?= $n ?></td>
              <td>
                <div style="font-family:monospace;font-size:.8rem;background:#f1f5f9;display:inline-block;padding:2px 8px;border-radius:5px;font-weight:600;">
                  <?= htmlspecialchars($prod['codigo'] ?? $prod['sku'] ?? '—') ?>
                </div>
               </div>
              <td>
                <div style="font-weight:600;color:var(--navy);"><?= htmlspecialchars($prod['nombre']) ?></div>
               </div>
              <td>
                <span style="font-size:.78rem;color:var(--muted);"><?= htmlspecialchars($prod['categoria'] ?? '—') ?></span>
               </div>
              <td style="text-align:center;font-weight:600;" data-stock="<?= (float)($prod['stock_actual'] ?? 0) ?>">
                <?= number_format((float)($prod['stock_actual'] ?? 0), 2) ?>
               </div>
              <td style="text-align:center;">
                <input
                  type="number"
                  name="conteos[<?= $prod['id_producto'] ?>][stock_fisico]"
                  class="qty-input"
                  min="0"
                  step="0.01"
                  value="<?= $prevQty ?>"
                  placeholder="0.00"
                  data-sistema="<?= (float)($prod['stock_actual'] ?? 0) ?>"
                  oninput="calcDiff(this)"
                  data-id="<?= $prod['id_producto'] ?>">
               </div>
              <td style="text-align:center;">
                <span class="diff-cell diff-zero" id="diff_<?= $prod['id_producto'] ?>">
                  <?php if ($prevQty !== ''): ?>
                    <?php $d = (float)$prevQty - (float)($prod['stock_actual'] ?? 0); ?>
                    <span class="<?= $d > 0 ? 'diff-pos' : ($d < 0 ? 'diff-neg' : 'diff-zero') ?>">
                      <?= ($d > 0 ? '+' : '') . number_format($d, 2) ?>
                    </span>
                  <?php else: ?>—<?php endif; ?>
                </span>
               </div>
              <td>
                <input
                  type="text"
                  name="conteos[<?= $prod['id_producto'] ?>][observaciones]"
                  class="inp"
                  style="width:140px;padding:6px 10px;font-size:.8rem;border:1.5px solid var(--border);border-radius:8px;outline:none;"
                  placeholder="Obs…"
                  value="<?= $prevObs ?>"
                  onfocus="this.style.borderColor='var(--teal)'"
                  onblur="this.style.borderColor='var(--border)'">
               </div>
             </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      </div>

      <div style="padding:14px 20px;background:#f8fafc;border-top:1px solid var(--border);display:flex;justify-content:space-between;align-items:center;flex-wrap:wrap;gap:10px;">
        <span style="font-size:.8rem;color:var(--muted);">
          <i class="fas fa-info-circle me-1"></i>
          Los productos con diferencia serán ajustados al finalizar el inventario.
        </span>
        <button type="submit" class="btn-prim">
          <i class="fas fa-save"></i> Guardar conteo
        </button>
      </div>
    </div>
  </form>
</div>

<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.all.min.js"></script>
<script>
/* ── Flash ─────────────────────────────────────────────────────────── */
<?php if (!empty($_SESSION['flash_message'])): ?>
Swal.fire({
  icon: '<?= $_SESSION['flash_type'] === 'success' ? 'success' : 'error' ?>',
  title: '<?= $_SESSION['flash_type'] === 'success' ? '¡Guardado!' : 'Error' ?>',
  text: '<?= addslashes($_SESSION['flash_message']) ?>',
  timer: 3000, showConfirmButton: false, timerProgressBar: true, toast: true, position: 'top-end'
});
<?php unset($_SESSION['flash_message'], $_SESSION['flash_type']); ?>
<?php endif; ?>

const totalProductos = <?= count($productos) ?>;

/* ── Calcula diferencia al escribir ───────────────────────────────── */
function calcDiff(input) {
  const sistema = parseFloat(input.dataset.sistema) || 0;
  const fisico  = input.value !== '' ? parseFloat(input.value) : null;
  const id      = input.dataset.id;
  const cell    = document.getElementById('diff_' + id);

  if (fisico === null || isNaN(fisico)) {
    cell.innerHTML = '<span class="diff-zero">—</span>';
    input.classList.remove('changed');
  } else {
    const diff = fisico - sistema;
    const cls  = diff > 0 ? 'diff-pos' : (diff < 0 ? 'diff-neg' : 'diff-zero');
    const sign = diff > 0 ? '+' : '';
    cell.innerHTML = `<span class="${cls}">${sign}${diff.toFixed(2)}</span>`;
    input.classList.toggle('changed', diff !== 0);
  }
  actualizarProgreso();
}

/* ── Progreso ─────────────────────────────────────────────────────── */
function actualizarProgreso() {
  const inputs  = document.querySelectorAll('.qty-input');
  const contados = [...inputs].filter(i => i.value !== '').length;
  const pct = totalProductos > 0 ? Math.round((contados / totalProductos) * 100) : 0;
  document.getElementById('progBar').style.width = pct + '%';
  document.getElementById('progText').textContent = contados;
}

/* ── Búsqueda ─────────────────────────────────────────────────────── */
function filtrarProductos(q) {
  const rows = document.querySelectorAll('.prod-row');
  rows.forEach(r => {
    const txt = r.textContent.toLowerCase();
    r.style.display = txt.includes(q.toLowerCase()) ? '' : 'none';
  });
}

/* ── Limpiar conteos ──────────────────────────────────────────────── */
function limpiarConteos() {
  Swal.fire({
    title: '¿Limpiar todos los conteos?',
    text: 'Se borrarán todos los valores de stock físico ingresados.',
    icon: 'warning',
    showCancelButton: true,
    confirmButtonText: 'Sí, limpiar',
    cancelButtonText: 'Cancelar',
    confirmButtonColor: '#ef4444'
  }).then(r => {
    if (r.isConfirmed) {
      document.querySelectorAll('.qty-input').forEach(i => { i.value = ''; i.classList.remove('changed'); calcDiff(i); });
    }
  });
}

/* ── Confirmar finalizar ──────────────────────────────────────────── */
function confirmarFinalizar() {
  Swal.fire({
    title: '¿Finalizar inventario?',
    html: '<strong>Se aplicarán todos los ajustes de diferencias al stock.</strong><br>Esta acción no se puede deshacer.',
    icon: 'warning',
    showCancelButton: true,
    confirmButtonText: '<i class="fas fa-check-double"></i> Finalizar y aplicar',
    cancelButtonText: 'Cancelar',
    confirmButtonColor: '#0d9488'
  }).then(r => {
    if (r.isConfirmed) {
      document.getElementById('btnFinalizar')?.click() || window.location.href = 'index.php?controller=inventario&action=ver&id=<?= $inventario['id_inventario'] ?>';
    }
  });
}

/* ── Guardar con confirmación ─────────────────────────────────────── */
document.getElementById('formConteo').addEventListener('submit', function(e) {
  e.preventDefault();
  const inputs = document.querySelectorAll('.qty-input');
  const contados = [...inputs].filter(i => i.value !== '').length;

  Swal.fire({
    title: '¿Guardar conteo?',
    text: `Se guardarán ${contados} de ${totalProductos} productos contados.`,
    icon: 'question',
    showCancelButton: true,
    confirmButtonText: '<i class="fas fa-save"></i> Guardar',
    cancelButtonText: 'Revisar',
    confirmButtonColor: '#0d9488'
  }).then(r => { if (r.isConfirmed) this.submit(); });
});

// Inicializar progreso
actualizarProgreso();
</script>

<?php include __DIR__ . '/../../layouts/footer.php'; ?>