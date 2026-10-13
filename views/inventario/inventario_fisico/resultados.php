<?php
/**
 * resultados.php — Inventario Físico · Resultados
 * FIXES aplicados:
 *   FIX #1 — Keys de $detalle corregidas: codigo→producto_codigo,
 *             nombre→producto_nombre (alias reales del sp_IF_GetDetalle).
 *             Celdas ya no quedan vacías.
 *   FIX #2 — Tarjetas "Sobrantes" y "Faltantes" ahora muestran CANTIDAD
 *             de productos (conteo de filas), no suma de diferencias.
 *             Se agrega tarjeta "Con diferencia" para mayor claridad.
 *   FIX #3 — Clases de filas "det-row" → "if-det-row" y contador
 *             "countVisible" → "ifCountVisible" para que filtrarDiff(),
 *             buscarDetalle() y actualizarVisible() de inventario_fisico.js
 *             los encuentren sin conflicto. Script inline de filtros eliminado.
 *   FIX #4 — Flash SweetAlert envuelto en DOMContentLoaded para garantizar
 *             que SweetAlert2 esté disponible antes de llamarlo.
 *   FIX #5 — Fechas usan fecha_inicio_fmt / fecha_fin_fmt (ya formateadas
 *             por el SP) en lugar de llamar strtotime() sobre el campo crudo.
 *   FIX #6 — Se expone ifCsrfToken oculto por consistencia con el resto
 *             de vistas del módulo.
 */
$currentPage = 'inventario_fisico';
include __DIR__ . '/../../layouts/inventario_layout.php';
?>

<!-- ══════════════════════════════════════════════════════════
     INVENTARIO FÍSICO — Resultados
     Variables: $inventario (array), $detalle (array), $resumen (array)
════════════════════════════════════════════════════════════ -->
<meta name="csrf-token" content="<?= htmlspecialchars($csrfToken ?? '') ?>">
<style>
  @import url('https://fonts.googleapis.com/css2?family=Syne:wght@600;700;800&family=DM+Sans:wght@300;400;500;600&display=swap');
  :root{--teal:#0d9488;--navy:#0f172a;--border:#e2e8f0;--muted:#64748b;--radius:14px;--shadow:0 4px 24px rgba(15,23,42,.08);}
  body,.inv-wrap{font-family:'DM Sans',sans-serif;}
  .inv-wrap{padding:28px 24px;}
  .page-title{font-family:'Syne',sans-serif;font-weight:800;font-size:1.65rem;color:var(--navy);margin:0;letter-spacing:-.5px;}
  .breadcrumb-inv{font-size:.8rem;color:var(--muted);}
  .breadcrumb-inv a{color:var(--teal);text-decoration:none;}

  /* Summary cards */
  .sum-grid{display:grid;grid-template-columns:repeat(auto-fit,minmax(160px,1fr));gap:14px;margin-bottom:24px;}
  .sum-card{background:#fff;border-radius:var(--radius);box-shadow:var(--shadow);border:1px solid var(--border);padding:18px 20px;}
  .sum-lbl{font-size:.72rem;font-weight:700;text-transform:uppercase;letter-spacing:.07em;color:var(--muted);margin-bottom:6px;}
  .sum-val{font-family:'Syne',sans-serif;font-size:1.8rem;font-weight:800;color:var(--navy);}
  .sum-val.pos{color:#059669;} .sum-val.neg{color:#ef4444;} .sum-val.warn{color:#f59e0b;}

  /* Table card */
  .res-card{background:#fff;border-radius:var(--radius);box-shadow:var(--shadow);border:1px solid var(--border);overflow:hidden;}
  .res-header{padding:16px 22px;border-bottom:1px solid var(--border);display:flex;align-items:center;justify-content:space-between;flex-wrap:wrap;gap:10px;}
  .res-title{font-family:'Syne',sans-serif;font-weight:700;color:var(--navy);font-size:.95rem;}

  .res-table{width:100%;border-collapse:collapse;}
  .res-table thead th{background:#f8fafc;padding:10px 14px;font-size:.72rem;font-weight:700;text-transform:uppercase;letter-spacing:.07em;color:var(--muted);border-bottom:2px solid var(--border);white-space:nowrap;}
  .res-table tbody td{padding:12px 14px;border-bottom:1px solid #f1f5f9;vertical-align:middle;font-size:.875rem;color:#334155;}
  .res-table tbody tr:last-child td{border-bottom:none;}
  .res-table tbody tr:hover td{background:#f8fafc;}

  .diff-pos{color:#059669;font-weight:700;} .diff-neg{color:#ef4444;font-weight:700;} .diff-zero{color:var(--muted);}

  /* Status pill */
  .status-pill{display:inline-flex;align-items:center;gap:6px;padding:5px 14px;border-radius:999px;font-size:.78rem;font-weight:700;}
  .pill-fin{background:#d1fae5;color:#065f46;}
  .pill-proc{background:#fef3c7;color:#92400e;}
  .pill-plan{background:#eff6ff;color:#1d4ed8;}
  .pill-cancel{background:#fee2e2;color:#991b1b;}

  /* Buttons */
  .btn-prim{background:var(--teal);color:#fff;border:none;border-radius:10px;padding:10px 20px;font-size:.875rem;font-weight:600;cursor:pointer;display:inline-flex;align-items:center;gap:7px;transition:background .15s;font-family:'DM Sans',sans-serif;text-decoration:none;}
  .btn-prim:hover{background:#0f766e;color:#fff;}
  .btn-sec{background:#fff;color:#334155;border:1.5px solid var(--border);border-radius:10px;padding:9px 18px;font-size:.875rem;font-weight:500;cursor:pointer;display:inline-flex;align-items:center;gap:7px;transition:all .15s;text-decoration:none;font-family:'DM Sans',sans-serif;}
  .btn-sec:hover{border-color:var(--teal);color:var(--teal);}
  .btn-red{background:#ef4444;color:#fff;border:none;border-radius:10px;padding:10px 20px;font-size:.875rem;font-weight:600;cursor:pointer;display:inline-flex;align-items:center;gap:7px;text-decoration:none;font-family:'DM Sans',sans-serif;}
  .btn-red:hover{background:#b91c1c;color:#fff;}

  /* Search */
  .search-bar{display:flex;align-items:center;gap:8px;background:#f8fafc;border:1.5px solid var(--border);border-radius:10px;padding:7px 12px;width:220px;}
  .search-bar input{border:none;background:transparent;outline:none;font-size:.85rem;font-family:'DM Sans',sans-serif;width:100%;}

  /* Filter tabs */
  .if-ftab{padding:6px 14px;border-radius:8px;font-size:.78rem;font-weight:600;cursor:pointer;border:1.5px solid var(--border);background:#fff;color:var(--muted);transition:all .15s;}
  .if-ftab.active{background:var(--teal);color:#fff;border-color:var(--teal);}
</style>

<div class="inv-wrap">

  <!-- Encabezado -->
  <div class="d-flex justify-content-between align-items-start mb-4 flex-wrap gap-3">
    <div>
      <h1 class="page-title">
        <i class="fas fa-chart-bar me-2" style="color:var(--teal);font-size:1.3rem;"></i>Resultados del Inventario
      </h1>
      <div class="breadcrumb-inv mt-1">
        <a href="index.php?controller=dashboard&action=index">Dashboard</a> /
        <a href="index.php?controller=inventario&action=index">Inventarios Físicos</a> /
        <span>Resultados</span>
      </div>
    </div>
    <div class="d-flex gap-2 flex-wrap">
      <a href="index.php?controller=inventario&action=index" class="btn-sec">
        <i class="fas fa-arrow-left"></i> Volver
      </a>
      <?php if ($inventario['estado'] === 'en_proceso'): ?>
        <a href="index.php?controller=inventario&action=realizar&id=<?= (int)$inventario['id_inventario'] ?>" class="btn-prim">
          <i class="fas fa-pencil-alt"></i> Continuar conteo
        </a>
      <?php endif; ?>
      <?php if ($inventario['estado'] === 'finalizado'): ?>
        <a href="index.php?controller=inventario&action=exportar&id=<?= (int)$inventario['id_inventario'] ?>" class="btn-red">
          <i class="fas fa-file-csv"></i> Exportar CSV
        </a>
      <?php endif; ?>
    </div>
  </div>

  <!-- FIX #6: token CSRF disponible para futuras acciones AJAX -->
  <input type="hidden" id="ifCsrfToken" value="<?= htmlspecialchars($csrfToken ?? '') ?>">

  <!-- Info del inventario -->
  <div style="background:#fff;border-radius:var(--radius);box-shadow:var(--shadow);border:1px solid var(--border);padding:18px 22px;margin-bottom:20px;display:flex;flex-wrap:wrap;gap:20px;align-items:center;">
    <div style="flex:1;min-width:200px;">
      <div style="font-size:.72rem;font-weight:700;text-transform:uppercase;letter-spacing:.07em;color:var(--muted);">Código</div>
      <div style="font-family:monospace;font-size:1.1rem;font-weight:700;color:var(--navy);"><?= htmlspecialchars($inventario['codigo']) ?></div>
    </div>
    <div style="flex:1;min-width:160px;">
      <div style="font-size:.72rem;font-weight:700;text-transform:uppercase;letter-spacing:.07em;color:var(--muted);">Almacén</div>
      <div style="font-weight:600;color:var(--navy);"><?= htmlspecialchars($inventario['almacen_nombre']) ?></div>
    </div>
    <div style="flex:1;min-width:140px;">
      <div style="font-size:.72rem;font-weight:700;text-transform:uppercase;letter-spacing:.07em;color:var(--muted);">Fecha inicio</div>
      <!-- FIX #5: usar fecha_inicio_fmt del SP, ya formateada -->
      <div style="font-weight:600;"><?= htmlspecialchars($inventario['fecha_inicio_fmt'] ?? $inventario['fecha_inicio'] ?? '—') ?></div>
    </div>
    <?php if (!empty($inventario['fecha_fin'])): ?>
    <div style="flex:1;min-width:140px;">
      <div style="font-size:.72rem;font-weight:700;text-transform:uppercase;letter-spacing:.07em;color:var(--muted);">Fecha fin</div>
      <!-- FIX #5: usar fecha_fin_fmt del SP -->
      <div style="font-weight:600;"><?= htmlspecialchars($inventario['fecha_fin_fmt'] ?? $inventario['fecha_fin'] ?? '—') ?></div>
    </div>
    <?php endif; ?>
    <div>
      <?php
        $est    = $inventario['estado'];
        $pClass = match($est) {
            'finalizado' => 'pill-fin',
            'en_proceso' => 'pill-proc',
            'cancelado'  => 'pill-cancel',
            default      => 'pill-plan',
        };
        $pIcon  = match($est) {
            'finalizado' => 'fa-check-circle',
            'en_proceso' => 'fa-spinner',
            'cancelado'  => 'fa-ban',
            default      => 'fa-calendar',
        };
      ?>
      <span class="status-pill <?= $pClass ?>">
        <i class="fas <?= $pIcon ?>"></i>
        <?= ucwords(str_replace('_', ' ', $est)) ?>
      </span>
    </div>
  </div>

  <!-- Resumen estadístico -->
  <?php if (!empty($resumen)): ?>
  <?php
    // FIX #2: calcular conteos reales de productos con/sin diferencia
    $cntSobrantes   = count(array_filter($detalle, fn($d) => (float)($d['diferencia'] ?? 0) > 0));
    $cntFaltantes   = count(array_filter($detalle, fn($d) => (float)($d['diferencia'] ?? 0) < 0));
    $cntConDiff     = $cntSobrantes + $cntFaltantes;
    $cntSinDiff     = count($detalle) - $cntConDiff;
  ?>
  <div class="sum-grid">
    <div class="sum-card">
      <div class="sum-lbl">Total productos</div>
      <div class="sum-val"><?= (int)($resumen['total_productos'] ?? 0) ?></div>
    </div>
    <div class="sum-card">
      <div class="sum-lbl">Stock sistema</div>
      <div class="sum-val"><?= number_format((float)($resumen['total_sistema'] ?? 0), 2) ?></div>
    </div>
    <div class="sum-card">
      <div class="sum-lbl">Stock físico</div>
      <div class="sum-val"><?= number_format((float)($resumen['total_fisico'] ?? 0), 2) ?></div>
    </div>
    <!-- FIX #2: mostrar CANTIDAD de productos sobrantes, no suma de diferencias -->
    <div class="sum-card">
      <div class="sum-lbl">Prods. sobrantes</div>
      <div class="sum-val pos"><?= $cntSobrantes ?></div>
    </div>
    <div class="sum-card">
      <div class="sum-lbl">Prods. faltantes</div>
      <div class="sum-val neg"><?= $cntFaltantes ?></div>
    </div>
    <div class="sum-card">
      <div class="sum-lbl">Sin diferencia</div>
      <div class="sum-val" style="color:#0284c7;"><?= $cntSinDiff ?></div>
    </div>
    <div class="sum-card">
      <div class="sum-lbl">Valor diferencia</div>
      <div class="sum-val warn">S/ <?= number_format((float)($resumen['valor_diferencia_total'] ?? 0), 2) ?></div>
    </div>
  </div>
  <?php endif; ?>

  <!-- Tabla de detalle -->
  <div class="res-card">
    <div class="res-header">
      <span class="res-title">
        <i class="fas fa-list me-2" style="color:var(--teal);"></i>
        Detalle por producto
        <!-- FIX #3: id ifCountVisible para actualizarVisible() del JS global -->
        &nbsp;<span style="font-size:.78rem;font-weight:400;color:var(--muted);">
          (<span id="ifCountVisible"><?= count($detalle) ?></span> de <?= count($detalle) ?>)
        </span>
      </span>
      <div class="d-flex gap-2 flex-wrap align-items-center">
        <!-- FIX #3: clase if-ftab y función filtrarDiff del JS global -->
        <div class="d-flex gap-1">
          <button class="if-ftab active" onclick="filtrarDiff('todos',this)">Todos</button>
          <button class="if-ftab" onclick="filtrarDiff('sobrante',this)">Sobrantes</button>
          <button class="if-ftab" onclick="filtrarDiff('faltante',this)">Faltantes</button>
          <button class="if-ftab" onclick="filtrarDiff('sin_diff',this)">Sin diff.</button>
        </div>
        <div class="search-bar">
          <i class="fas fa-search" style="color:var(--muted);font-size:.8rem;"></i>
          <!-- FIX #3: buscarDetalle del JS global -->
          <input type="text" placeholder="Buscar…" oninput="buscarDetalle(this.value)">
        </div>
      </div>
    </div>

    <?php if (empty($detalle)): ?>
      <div style="padding:48px 24px;text-align:center;color:var(--muted);">
        <i class="fas fa-inbox" style="font-size:2.5rem;margin-bottom:12px;display:block;color:#cbd5e1;"></i>
        <div style="font-family:'Syne',sans-serif;font-weight:700;color:#334155;margin-bottom:6px;">Sin detalle registrado</div>
        <div style="font-size:.875rem;">Aún no se han ingresado conteos para este inventario.</div>
      </div>
    <?php else: ?>
    <div class="table-responsive">
      <table class="res-table">
        <thead>
          <tr>
            <th>Código / SKU</th>
            <th>Producto</th>
            <th>Categoría</th>
            <th style="text-align:center;">Stock Sistema</th>
            <th style="text-align:center;">Stock Físico</th>
            <th style="text-align:center;">Diferencia</th>
            <th style="text-align:right;">Valor Dif.</th>
            <th>Contado por</th>
            <th>Observaciones</th>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($detalle as $d):
            $diff    = (float)($d['diferencia'] ?? 0);
            $diffCls = $diff > 0 ? 'diff-pos' : ($diff < 0 ? 'diff-neg' : 'diff-zero');
            // FIX #2: tag coherente con filtrarDiff() del JS global
            $rowTag  = $diff > 0 ? 'sobrante' : ($diff < 0 ? 'faltante' : 'sin_diff');
            $valDif  = (float)($d['valor_diferencia'] ?? 0);
            $valCls  = $valDif > 0 ? 'diff-pos' : ($valDif < 0 ? 'diff-neg' : 'diff-zero');
          ?>
          <!-- FIX #3: clase "if-det-row" para filtrarDiff/buscarDetalle del JS global -->
          <tr class="if-det-row" data-tipo="<?= $rowTag ?>">
            <td>
              <!-- FIX #1: producto_codigo en lugar de codigo -->
              <div style="font-family:monospace;font-size:.78rem;background:#f1f5f9;display:inline-block;padding:2px 7px;border-radius:5px;font-weight:600;">
                <?= htmlspecialchars($d['producto_codigo'] ?? $d['producto_sku'] ?? '—') ?>
              </div>
            </td>
            <td style="font-weight:600;color:var(--navy);">
              <!-- FIX #1: producto_nombre en lugar de nombre -->
              <?= htmlspecialchars($d['producto_nombre'] ?? '—') ?>
              <?php if (!empty($d['talla']) || !empty($d['color'])): ?>
                <div style="font-size:.72rem;color:var(--muted);font-weight:400;margin-top:2px;">
                  <?= htmlspecialchars(implode(' · ', array_filter([$d['talla'] ?? '', $d['color'] ?? '']))) ?>
                </div>
              <?php endif; ?>
            </td>
            <td style="font-size:.8rem;color:var(--muted);"><?= htmlspecialchars($d['categoria'] ?? '—') ?></td>
            <td style="text-align:center;"><?= number_format((float)($d['stock_sistema'] ?? 0), 2) ?></td>
            <td style="text-align:center;"><?= number_format((float)($d['stock_fisico']  ?? 0), 2) ?></td>
            <td style="text-align:center;">
              <span class="<?= $diffCls ?>"><?= ($diff > 0 ? '+' : '') . number_format($diff, 2) ?></span>
            </td>
            <td style="text-align:right;font-size:.85rem;">
              <span class="<?= $valCls ?>">S/ <?= number_format(abs($valDif), 2) ?></span>
            </td>
            <td style="font-size:.8rem;"><?= htmlspecialchars($d['contado_por'] ?? '—') ?></td>
            <td style="font-size:.8rem;color:var(--muted);max-width:160px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;"
                title="<?= htmlspecialchars($d['observaciones'] ?? '') ?>">
              <?= htmlspecialchars($d['observaciones'] ?? '—') ?>
            </td>
          </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>
    <div style="padding:12px 20px;background:#f8fafc;border-top:1px solid var(--border);font-size:.8rem;color:var(--muted);">
      Mostrando <strong id="ifCountVisible"><?= count($detalle) ?></strong> de <?= count($detalle) ?> productos
    </div>
    <?php endif; ?>
  </div>

</div>

<!--
  FIX #3: Script de filtros eliminado — filtrarDiff(), buscarDetalle() y
  actualizarVisible() ya están implementados en inventario_fisico.js y usan
  las clases/IDs corregidos: "if-det-row", "ifCountVisible", "if-ftab".

  FIX #4: Flash envuelto en DOMContentLoaded para que Swal esté disponible.
-->
<script>
<?php if (!empty($_SESSION['flash_message'])): ?>
document.addEventListener('DOMContentLoaded', function () {
    Swal.fire({
        icon:              '<?= $_SESSION['flash_type'] === 'success' ? 'success' : 'error' ?>',
        title:             '<?= $_SESSION['flash_type'] === 'success' ? '¡Listo!' : 'Error' ?>',
        text:              '<?= addslashes($_SESSION['flash_message']) ?>',
        timer:             3500,
        showConfirmButton: false,
        timerProgressBar:  true,
        toast:             true,
        position:          'top-end'
    });
});
<?php unset($_SESSION['flash_message'], $_SESSION['flash_type']); ?>
<?php endif; ?>
</script>

<?php include __DIR__ . '/../../layouts/footer.php'; ?>