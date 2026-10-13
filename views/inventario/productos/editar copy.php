<?php
$currentPage = 'productos';

// ── Garantizar sesión activa ANTES de leer el flash de SweetAlert ──
require_once __DIR__ . '/../../../config/SessionMiddleware.php';
SessionMiddleware::startSession();

// ── Leer SweetAlert de sesión (errores/éxitos de redirección desde actualizar()) ──
$sweetAlert = null;
if (!empty($_SESSION['sweet_alert'])) {
    $sweetAlert = $_SESSION['sweet_alert'];
    unset($_SESSION['sweet_alert']);
}

include __DIR__ . '/../../layouts/inventario_layout.php';

/* ── Variables de conveniencia ── */
$p      = $producto;   // alias corto
$idProd = (int)$p['id_producto'];
?>

<link rel="preconnect" href="https://fonts.googleapis.com">
<link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;500;600;700;800&family=DM+Mono:wght@400;500&display=swap" rel="stylesheet">
<link rel="stylesheet" href="views/inventario/productos/css/styles.css">

<style>
/* ── Chips de meta información en el header ── */
.meta-strip { display:flex; align-items:center; gap:.5rem; flex-wrap:wrap; margin-top:.6rem; }
.meta-chip  { display:inline-flex; align-items:center; gap:.3rem; font-size:.65rem; font-weight:500;
              color:var(--tx3); background:var(--surface2); border:1px solid var(--border);
              border-radius:5px; padding:.15rem .5rem; }
.meta-chip i { font-size:.65rem; }
.code-chip   { font-family:var(--mono); font-size:.68rem; font-weight:500; background:var(--surface2);
               border:1px solid var(--border); border-radius:5px; padding:.15rem .5rem; color:var(--tx2); }

/* ── SKU de sólo lectura ── */
.fld-readonly { background:var(--surface2) !important; color:var(--tx3); cursor:not-allowed; }

/* ── Form footer con botón eliminar ── */
.form-footer         { display:flex; align-items:center; justify-content:space-between; gap:.6rem;
                       padding:1rem 0 .5rem; }
.form-footer-right   { display:flex; gap:.5rem; }
.btn-danger          { display:inline-flex; align-items:center; gap:.4rem; background:var(--danger-lt);
                       color:var(--danger); border:1px solid #fca5a5; border-radius:var(--r-sm);
                       padding:.55rem .9rem; font-size:.78rem; font-weight:600; font-family:var(--f);
                       cursor:pointer; transition:all .18s; }
.btn-danger:hover    { background:var(--danger); color:#fff; }

/* ── Estado toggles ── */
.status-toggles { display:flex; gap:.5rem; flex-wrap:wrap; }
.status-opt     { cursor:pointer; border:1px solid var(--border); border-radius:var(--r-sm);
                  padding:.45rem .85rem; font-size:.78rem; font-weight:500; color:var(--tx2);
                  transition:all .15s; display:flex; align-items:center; gap:.35rem;
                  user-select:none; }
.status-opt.active-activo   { border-color:var(--ok);      background:var(--ok-lt);     color:var(--ok); }
.status-opt.active-inactivo { border-color:var(--border2);  background:var(--surface2);  color:var(--tx3); }

/* ── Banner de advertencia de stock ── */
.stock-warn { display:flex; align-items:flex-start; gap:.5rem; background:#fffbeb;
              border:1px solid #fcd34d; border-radius:var(--r-sm); padding:.6rem .85rem;
              font-size:.72rem; color:#92400e; margin-top:.6rem; line-height:1.4; }
.stock-warn i { font-size:.8rem; margin-top:.1rem; flex-shrink:0; color:#d97706; }

/* ── Nota informativa ── */
.info-note { background:var(--surface2); border-radius:var(--r-sm); padding:.6rem .8rem; }
.info-note i { color:var(--info); margin-right:.4rem; }
</style>

<div class="pg-wrap">

    <!-- ── Breadcrumb ── -->
    <nav class="breadcrumb-bar" aria-label="Ruta de navegación">
        <a href="index.php?controller=dashboard&action=index"><i class="fas fa-home"></i></a>
        <span class="sep">/</span>
        <a href="index.php?controller=producto&action=index">Productos</a>
        <span class="sep">/</span>
        <a href="index.php?controller=producto&action=ver&id=<?= $idProd ?>">
            <?= htmlspecialchars($p['nombre']) ?>
        </a>
        <span class="sep">/</span>
        <span class="current">Editar</span>
    </nav>

    <!-- ── Page Header ── -->
    <div class="pg-head">
        <div class="pg-head-left">
            <div class="pg-eyebrow">Gestión de Inventario</div>
            <h1 class="pg-title">Editar <span>Producto</span></h1>
            <div class="meta-strip">
                <span class="code-chip"><?= htmlspecialchars($p['codigo']) ?></span>
                <?php if (!empty($p['sku'])): ?>
                    <span class="code-chip" style="color:var(--info);"><?= htmlspecialchars($p['sku']) ?></span>
                <?php endif; ?>
                <span class="meta-chip"><i class="fas fa-tag"></i> <?= htmlspecialchars($p['categoria_nombre'] ?? '—') ?></span>
                <?php if (!empty($p['almacen_nombre'])): ?>
                    <span class="meta-chip"><i class="fas fa-warehouse"></i> <?= htmlspecialchars($p['almacen_nombre']) ?></span>
                <?php endif; ?>
                <span class="meta-chip"><i class="fas fa-hashtag"></i> ID #<?= $idProd ?></span>
                <span class="meta-chip" style="<?= ($p['estado'] ?? 'activo') === 'activo' ? 'border-color:var(--ok);color:var(--ok);background:var(--ok-lt);' : '' ?>">
                    <i class="fas <?= ($p['estado'] ?? 'activo') === 'activo' ? 'fa-check-circle' : 'fa-pause-circle' ?>"></i>
                    <?= ucfirst(htmlspecialchars($p['estado'] ?? 'activo')) ?>
                </span>
            </div>
        </div>
    </div>

    <!-- ── Steps (5 secciones) ── -->
    <div class="steps-bar" role="list">
        <div class="step-item active" role="listitem">
            <div class="step-dot">1</div> Identificación
        </div>
        <div class="steps-sep"></div>
        <div class="step-item" role="listitem">
            <div class="step-dot">2</div> Clasificación
        </div>
        <div class="steps-sep"></div>
        <div class="step-item" role="listitem">
            <div class="step-dot">3</div> Stock
        </div>
        <div class="steps-sep"></div>
        <div class="step-item" role="listitem">
            <div class="step-dot">4</div> Precios
        </div>
        <div class="steps-sep"></div>
        <div class="step-item" role="listitem">
            <div class="step-dot">5</div> Estado
        </div>
    </div>

    <!-- ══════════════════════════════════════════
         FORMULARIO
    ══════════════════════════════════════════ -->
    <form id="frmEditar" method="POST"
          action="index.php?controller=producto&action=actualizar&id=<?= $idProd ?>"
          novalidate>

        <!-- ── Sección 1: Identificación ── -->
        <div class="card" id="sec-1">
            <div class="card-hdr">
                <span class="card-ttl">
                    <span class="ttl-icon"><i class="fas fa-tag"></i></span>
                    Identificación del Producto
                </span>
                <span class="card-badge">Paso 1 de 5</span>
            </div>
            <div class="card-body">

                <div class="form-grid fg-2" style="margin-bottom:1rem;">
                    <!-- Código -->
                    <div class="fld">
                        <label class="fld-label" for="codigo">
                            Código <span class="req">*</span>
                        </label>
                        <input type="text" id="codigo" name="codigo" class="fld-input mono"
                               placeholder="PROD-000001"
                               value="<?= htmlspecialchars($p['codigo']) ?>"
                               required maxlength="50">
                        <span class="fld-hint">Código único de identificación interna.</span>
                        <span class="fld-error" id="err-codigo">El código es obligatorio.</span>
                    </div>

                    <!-- SKU (solo lectura) -->
                    <div class="fld">
                        <label class="fld-label">SKU</label>
                        <input type="text" class="fld-input mono fld-readonly"
                               value="<?= htmlspecialchars($p['sku'] ?? '—') ?>"
                               readonly
                               title="El SKU se genera automáticamente y no es editable.">
                        <span class="fld-hint">El SKU no es editable una vez creado.</span>
                    </div>
                </div>

                <!-- Nombre -->
                <div class="fld" style="margin-bottom:1rem;">
                    <label class="fld-label" for="nombre">
                        Nombre del Producto <span class="req">*</span>
                    </label>
                    <input type="text" id="nombre" name="nombre" class="fld-input"
                           placeholder="Ej: Vestido Casual Verano Talla M"
                           value="<?= htmlspecialchars($p['nombre']) ?>"
                           required maxlength="200">
                    <span class="fld-error" id="err-nombre">El nombre es obligatorio.</span>
                </div>

                <!-- Descripción -->
                <div class="fld">
                    <label class="fld-label" for="descripcion">Descripción</label>
                    <textarea id="descripcion" name="descripcion" class="fld-textarea"
                              placeholder="Descripción detallada: características, talla, material, color..."
                              maxlength="1000"><?= htmlspecialchars($p['descripcion'] ?? '') ?></textarea>
                    <span class="fld-hint" id="charCount">0 / 1000 caracteres</span>
                </div>

            </div>
        </div>

        <!-- ── Sección 2: Clasificación ── -->
        <div class="card" id="sec-2">
            <div class="card-hdr">
                <span class="card-ttl">
                    <span class="ttl-icon"><i class="fas fa-sitemap"></i></span>
                    Clasificación
                </span>
                <span class="card-badge">Paso 2 de 5</span>
            </div>
            <div class="card-body">

                <!-- Fila 1: Categoría + Marca -->
                <div class="form-grid fg-2" style="margin-bottom:1rem;">
                    <div class="fld">
                        <label class="fld-label" for="id_categoria">
                            Categoría <span class="req">*</span>
                        </label>
                        <select id="id_categoria" name="id_categoria" class="fld-select" required>
                            <option value="">— Seleccionar categoría —</option>
                            <?php foreach ($categorias as $cat): ?>
                                <option value="<?= htmlspecialchars($cat['value']) ?>"
                                    <?= $p['id_categoria'] == $cat['value'] ? 'selected' : '' ?>>
                                    <?= htmlspecialchars($cat['text']) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                        <span class="fld-error" id="err-categoria">Seleccione una categoría.</span>
                    </div>

                    <div class="fld">
                        <label class="fld-label" for="id_marca">
                            Marca <span class="opt">(opcional)</span>
                        </label>
                        <select id="id_marca" name="id_marca" class="fld-select">
                            <option value="">— Sin marca —</option>
                            <?php foreach ($marcas as $marca): ?>
                                <option value="<?= htmlspecialchars($marca['value']) ?>"
                                    <?= ($p['id_marca'] ?? null) == $marca['value'] ? 'selected' : '' ?>>
                                    <?= htmlspecialchars($marca['text']) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>

                <!-- Fila 2: Proveedor + Unidad de medida -->
                <div class="form-grid fg-2" style="margin-bottom:1rem;">
                    <div class="fld">
                        <label class="fld-label" for="id_proveedor">
                            Proveedor Principal <span class="opt">(opcional)</span>
                        </label>
                        <select id="id_proveedor" name="id_proveedor" class="fld-select">
                            <option value="">— Sin asignar —</option>
                            <?php foreach ($proveedores as $prov): ?>
                                <option value="<?= htmlspecialchars($prov['value']) ?>"
                                    <?= ($p['id_proveedor'] ?? null) == $prov['value'] ? 'selected' : '' ?>>
                                    <?= htmlspecialchars($prov['text']) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="fld">
                        <label class="fld-label" for="unidad_medida">
                            Unidad de Medida <span class="opt">(opcional)</span>
                        </label>
                        <select id="unidad_medida" name="unidad_medida" class="fld-select">
                            <option value="">— Seleccione —</option>
                            <?php
                            $uMedidas = ['unidad'=>'Unidad','par'=>'Par','docena'=>'Docena','caja'=>'Caja',
                                         'paquete'=>'Paquete','rollo'=>'Rollo','kg'=>'Kilogramo (kg)',
                                         'gramo'=>'Gramo (g)','litro'=>'Litro (L)','metro'=>'Metro (m)','set'=>'Set / Juego'];
                            $umSel = $p['unidad_medida'] ?? '';
                            foreach ($uMedidas as $val => $label): ?>
                                <option value="<?= $val ?>" <?= $umSel === $val ? 'selected' : '' ?>>
                                    <?= $label ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                        <span class="fld-hint">Medida de referencia para el inventario.</span>
                    </div>
                </div>

                <!-- Fila 3: Almacén (CORREGIDO - sin ubicación) -->
                <div class="form-grid fg-2" style="margin-bottom:1rem;">
                    <div class="fld">
                        <label class="fld-label" for="id_almacen">
                            Almacén <span class="opt">(opcional)</span>
                        </label>
                        <select id="id_almacen" name="id_almacen" class="fld-select">
                            <option value="">— Sin almacén —</option>
                            <?php foreach ($almacenes as $alm): ?>
                                <option value="<?= htmlspecialchars($alm['value']) ?>"
                                    <?= (!empty($p['id_almacen']) && $p['id_almacen'] == $alm['value']) ? 'selected' : '' ?>>
                                    <?= htmlspecialchars($alm['text']) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                        <span class="fld-hint">Selecciona el almacén donde se encuentra el producto.</span>
                    </div>

                    <div class="fld">
                        <div class="info-note">
                            <i class="fas fa-info-circle"></i>
                            <span style="font-size:.7rem; color:var(--tx3);">
                                Las ubicaciones específicas se gestionan desde el módulo de <strong>Almacenes</strong>.
                            </span>
                        </div>
                    </div>
                </div>

                <!-- Fila 4: Temporada + Género -->
                <div class="form-grid fg-2" style="margin-bottom:1rem;">
                    <div class="fld">
                        <label class="fld-label" for="temporada">Temporada</label>
                        <select id="temporada" name="temporada" class="fld-select">
                            <?php
                            $temporadas = [
                                'todo_año'  => 'Todo el año',
                                'verano'    => 'Verano',
                                'otoño'     => 'Otoño',
                                'invierno'  => 'Invierno',
                                'primavera' => 'Primavera',
                            ];
                            $tempSel = $p['temporada'] ?? 'todo_año';
                            foreach ($temporadas as $val => $label): ?>
                                <option value="<?= $val ?>" <?= $tempSel === $val ? 'selected' : '' ?>>
                                    <?= $label ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="fld">
                        <label class="fld-label">Género</label>
                        <div class="genero-pills">
                            <?php
                            $generos = [
                                ['value' => 'Unisex', 'icon' => 'fa-venus-mars',  'label' => 'Unisex'],
                                ['value' => 'Hombre', 'icon' => 'fa-mars',        'label' => 'Hombre'],
                                ['value' => 'Mujer',  'icon' => 'fa-venus',       'label' => 'Mujer'],
                                ['value' => 'Niño',   'icon' => 'fa-child',       'label' => 'Niño'],
                                ['value' => 'Niña',   'icon' => 'fa-child-dress', 'label' => 'Niña'],
                            ];
                            $generoSel = $p['genero'] ?? 'Unisex';
                            foreach ($generos as $g): ?>
                                <div class="genero-pill">
                                    <input type="radio" name="genero"
                                           id="edit_gen_<?= $g['value'] ?>"
                                           value="<?= $g['value'] ?>"
                                           <?= $generoSel === $g['value'] ? 'checked' : '' ?>>
                                    <label for="edit_gen_<?= $g['value'] ?>">
                                        <i class="fas <?= $g['icon'] ?>"></i>
                                        <?= $g['label'] ?>
                                    </label>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                </div>

            </div>
        </div>

        <!-- ── Sección 3: Stock ── -->
        <div class="card" id="sec-3">
            <div class="card-hdr">
                <span class="card-ttl">
                    <span class="ttl-icon"><i class="fas fa-layer-group"></i></span>
                    Control de Stock
                </span>
                <span class="card-badge">Paso 3 de 5</span>
            </div>
            <div class="card-body">

                <div class="form-grid fg-3" style="margin-bottom:1rem;">
                    <!-- Stock actual -->
                    <div class="fld">
                        <label class="fld-label" for="stock_actual">
                            Stock Actual
                        </label>
                        <input type="number" id="stock_actual" name="stock_actual"
                               class="fld-input" min="0"
                               value="<?= htmlspecialchars($p['stock_actual'] ?? 0) ?>"
                               oninput="updateStockVisual()">
                        <div class="stock-warn">
                            <i class="fas fa-exclamation-triangle"></i>
                            Para ajustes formales de stock use <strong>Movimientos de Inventario</strong>.
                        </div>
                    </div>

                    <!-- Stock mínimo -->
                    <div class="fld">
                        <label class="fld-label" for="stock_minimo">
                            Stock Mínimo <span class="req">*</span>
                        </label>
                        <input type="number" id="stock_minimo" name="stock_minimo"
                               class="fld-input" min="0" required
                               value="<?= htmlspecialchars($p['stock_minimo'] ?? 0) ?>"
                               oninput="updateStockVisual()">
                        <span class="fld-hint">Dispara alerta de reposición.</span>
                        <span class="fld-error" id="err-stock-minimo">El stock mínimo es obligatorio.</span>
                    </div>

                    <!-- Stock máximo -->
                    <div class="fld">
                        <label class="fld-label" for="stock_maximo">Stock Máximo</label>
                        <input type="number" id="stock_maximo" name="stock_maximo"
                               class="fld-input" min="0"
                               value="<?= htmlspecialchars($p['stock_maximo'] ?? 100) ?>"
                               oninput="updateStockVisual()">
                        <span class="fld-hint">Capacidad máxima en almacén.</span>
                    </div>
                </div>

                <!-- Punto de reorden -->
                <div class="form-grid fg-2">
                    <div class="fld">
                        <label class="fld-label" for="punto_reorden">
                            Punto de Reorden <span class="opt">(vacío = automático)</span>
                        </label>
                        <input type="number" id="punto_reorden" name="punto_reorden"
                               class="fld-input" min="0"
                               placeholder="Auto (stock_min × 1.5)"
                               value="<?= htmlspecialchars($p['punto_reorden'] ?? '') ?>">
                        <span class="fld-hint">Stock al cual se genera la alerta preventiva de reposición.</span>
                    </div>
                </div>

                <!-- Barra visual de stock -->
                <div class="stock-visual" id="stockVisual">
                    <span class="sv-label" id="svMin">Mín: 0</span>
                    <div class="sv-bar-wrap">
                        <div class="sv-bar-fill" id="svFill"></div>
                    </div>
                    <span class="sv-label" id="svMax">Máx: 100</span>
                </div>

            </div>
        </div>

        <!-- ── Sección 4: Precios y Atributos ── -->
        <div class="card" id="sec-4">
            <div class="card-hdr">
                <span class="card-ttl">
                    <span class="ttl-icon"><i class="fas fa-tags"></i></span>
                    Precios y Atributos
                </span>
                <span class="card-badge">Paso 4 de 5</span>
            </div>
            <div class="card-body">

                <div class="sec-div"><i class="fas fa-dollar-sign"></i> Precios</div>

                <!-- Precio compra + venta + mayorista -->
                <div class="form-grid fg-3" style="margin-bottom:1rem;">
                    <div class="fld">
                        <label class="fld-label" for="precio_compra">
                            Precio de Compra (S/) <span class="req">*</span>
                        </label>
                        <div class="fld-ctrl">
                            <span class="fld-prefix">S/</span>
                            <input type="number" id="precio_compra" name="precio_compra"
                                   class="fld-input fld-input-has-prefix"
                                   min="0.01" step="0.01" placeholder="0.00"
                                   value="<?= htmlspecialchars($p['precio_compra'] ?? '') ?>"
                                   required oninput="calcMargen()">
                        </div>
                        <span class="fld-error" id="err-compra">Ingrese el precio de compra.</span>
                    </div>

                    <div class="fld">
                        <label class="fld-label" for="precio_venta">
                            Precio de Venta (S/) <span class="req">*</span>
                        </label>
                        <div class="fld-ctrl">
                            <span class="fld-prefix">S/</span>
                            <input type="number" id="precio_venta" name="precio_venta"
                                   class="fld-input fld-input-has-prefix"
                                   min="0.01" step="0.01" placeholder="0.00"
                                   value="<?= htmlspecialchars($p['precio_venta'] ?? '') ?>"
                                   required oninput="calcMargen()">
                        </div>
                        <span class="fld-error" id="err-venta">Ingrese el precio de venta.</span>
                    </div>

                    <div class="fld">
                        <label class="fld-label" for="precio_mayorista">
                            Precio Mayorista (S/) <span class="opt">(opcional)</span>
                        </label>
                        <div class="fld-ctrl">
                            <span class="fld-prefix">S/</span>
                            <input type="number" id="precio_mayorista" name="precio_mayorista"
                                   class="fld-input fld-input-has-prefix"
                                   min="0.01" step="0.01" placeholder="0.00"
                                   value="<?= !empty($p['precio_mayorista']) ? htmlspecialchars($p['precio_mayorista']) : '' ?>">
                        </div>
                        <span class="fld-hint">Dejar vacío si no aplica.</span>
                    </div>
                </div>

                <!-- Panel de margen -->
                <div class="price-panel" id="pricePanel" style="margin-bottom:1.5rem;">
                    <div class="price-panel-grid">
                        <div class="pp-item">
                            <div class="pp-label"><i class="fas fa-arrow-up"></i> Utilidad</div>
                            <div class="pp-value" id="ppUtilidad">—</div>
                        </div>
                        <div class="pp-item">
                            <div class="pp-label"><i class="fas fa-percent"></i> Margen</div>
                            <div class="pp-value" id="ppMargen">—</div>
                        </div>
                        <div class="pp-item">
                            <div class="pp-label"><i class="fas fa-times"></i> Multiplicador</div>
                            <div class="pp-value" id="ppMulti">—</div>
                        </div>
                    </div>
                </div>

                <div class="sec-div"><i class="fas fa-tshirt"></i> Atributos físicos</div>

                <div class="form-grid fg-3">
                    <div class="fld">
                        <label class="fld-label" for="talla">
                            Talla <span class="opt">(opcional)</span>
                        </label>
                        <input type="text" id="talla" name="talla" class="fld-input"
                               placeholder="Ej: M, 38, XL"
                               value="<?= htmlspecialchars($p['talla'] ?? '') ?>"
                               maxlength="50">
                    </div>
                    <div class="fld">
                        <label class="fld-label" for="color">
                            Color <span class="opt">(opcional)</span>
                        </label>
                        <input type="text" id="color" name="color" class="fld-input"
                               placeholder="Ej: Azul marino, Rojo"
                               value="<?= htmlspecialchars($p['color'] ?? '') ?>"
                               maxlength="50">
                    </div>
                    <div class="fld">
                        <label class="fld-label" for="material">
                            Material <span class="opt">(opcional)</span>
                        </label>
                        <input type="text" id="material" name="material" class="fld-input"
                               placeholder="Ej: Algodón 100%, Poliéster"
                               value="<?= htmlspecialchars($p['material'] ?? '') ?>"
                               maxlength="100">
                    </div>
                </div>

            </div>
        </div>

        <!-- ── Sección 5: Estado ── -->
        <div class="card" id="sec-5">
            <div class="card-hdr">
                <span class="card-ttl">
                    <span class="ttl-icon"><i class="fas fa-toggle-on"></i></span>
                    Estado del Producto
                </span>
                <span class="card-badge">Paso 5 de 5</span>
            </div>
            <div class="card-body">

                <div class="status-toggles">
                    <label class="status-opt <?= ($p['estado'] ?? 'activo') === 'activo' ? 'active-activo' : '' ?>" id="optActivo">
                        <input type="radio" name="estado" value="activo" style="display:none;"
                               <?= ($p['estado'] ?? 'activo') === 'activo' ? 'checked' : '' ?>>
                        <i class="fas fa-check-circle"></i> Activo
                    </label>
                    <label class="status-opt <?= ($p['estado'] ?? '') === 'inactivo' ? 'active-inactivo' : '' ?>" id="optInactivo">
                        <input type="radio" name="estado" value="inactivo" style="display:none;"
                               <?= ($p['estado'] ?? '') === 'inactivo' ? 'checked' : '' ?>>
                        <i class="fas fa-pause-circle"></i> Inactivo
                    </label>
                </div>
                <p style="font-size:.7rem;color:var(--tx3);margin-top:.6rem;">
                    Los productos inactivos no aparecen en ventas pero conservan su historial completo.
                </p>

            </div>
        </div>

        <!-- ── Form Footer ── -->
        <div class="form-footer">
            <button type="button" class="btn-danger" onclick="confirmarEliminar()">
                <i class="fas fa-trash"></i> Eliminar Producto
            </button>
            <div class="form-footer-right">
                <a href="index.php?controller=producto&action=ver&id=<?= $idProd ?>" class="btn-ghost">
                    <i class="fas fa-times"></i> Cancelar
                </a>
                <button type="submit" class="btn-primary" id="btnGuardar">
                    <span class="spinner" id="btnSpinner"></span>
                    <i class="fas fa-save btn-icon"></i>
                    Guardar Cambios
                </button>
            </div>
        </div>

    </form>
</div><!-- /.pg-wrap -->

<!-- ═══════════════════════════════════════════
     JAVASCRIPT
═══════════════════════════════════════════ -->
<script>
const APP_URL = '<?= $APP_URL ?? '' ?>';

/* ══════════════════════════════════════════
   1. SWEET ALERT DE SESIÓN
══════════════════════════════════════════ */
<?php if ($sweetAlert): ?>
document.addEventListener('DOMContentLoaded', function () {
    Swal.fire({
        title:              <?= json_encode($sweetAlert['title']   ?? 'Aviso') ?>,
        text:               <?= json_encode($sweetAlert['message'] ?? '') ?>,
        icon:               <?= json_encode(in_array($sweetAlert['type'] ?? '', ['success','error','warning','info','question'])
                                            ? $sweetAlert['type'] : 'info') ?>,
        confirmButtonColor: '#2563eb',
        confirmButtonText:  'Entendido'
    });
});
<?php endif; ?>

/* ══════════════════════════════════════════
   2. CONTADOR DE CARACTERES (descripción)
══════════════════════════════════════════ */
const descEl    = document.getElementById('descripcion');
const charCount = document.getElementById('charCount');
function updateCharCount() {
    const n = descEl.value.length;
    charCount.textContent = n + ' / 1000 caracteres';
    charCount.style.color = n > 900 ? 'var(--warn)' : 'var(--tx3)';
}
descEl.addEventListener('input', updateCharCount);
updateCharCount();

/* ══════════════════════════════════════════
   3. CÁLCULO DE MARGEN DE PRECIOS
══════════════════════════════════════════ */
function calcMargen() {
    const compra = parseFloat(document.getElementById('precio_compra').value) || 0;
    const venta  = parseFloat(document.getElementById('precio_venta').value)  || 0;
    const panel  = document.getElementById('pricePanel');

    if (compra > 0 && venta > 0) {
        const utilidad = venta - compra;
        const margen   = ((utilidad / compra) * 100).toFixed(1);
        const multi    = (venta / compra).toFixed(2);

        document.getElementById('ppUtilidad').textContent = 'S/ ' + utilidad.toFixed(2);
        document.getElementById('ppMargen').textContent   = margen + '%';
        document.getElementById('ppMulti').textContent    = multi + 'x';

        const cls = utilidad >= 0 ? 'ok' : 'danger';
        ['ppUtilidad', 'ppMargen', 'ppMulti'].forEach(id => {
            document.getElementById(id).className = 'pp-value ' + cls;
        });
        panel.classList.add('show');
    } else {
        panel.classList.remove('show');
    }
}
calcMargen();

/* ══════════════════════════════════════════
   4. BARRA VISUAL DE STOCK
══════════════════════════════════════════ */
function updateStockVisual() {
    const min = parseInt(document.getElementById('stock_minimo').value) || 0;
    const max = parseInt(document.getElementById('stock_maximo').value) || 0;
    const vis = document.getElementById('stockVisual');

    if (max > 0) {
        const pct = Math.min((min / max) * 100, 100);
        document.getElementById('svFill').style.width = pct + '%';
        document.getElementById('svMin').textContent  = 'Mín: ' + min;
        document.getElementById('svMax').textContent  = 'Máx: ' + max;
        vis.classList.add('show');
    } else {
        vis.classList.remove('show');
    }
}
updateStockVisual();

/* ══════════════════════════════════════════
   5. STATUS TOGGLE (Activo / Inactivo)
══════════════════════════════════════════ */
document.querySelectorAll('.status-opt').forEach(opt => {
    opt.addEventListener('click', function () {
        const val = this.querySelector('input').value;
        document.getElementById('optActivo').className   = 'status-opt' + (val === 'activo'   ? ' active-activo'   : '');
        document.getElementById('optInactivo').className = 'status-opt' + (val === 'inactivo' ? ' active-inactivo' : '');
    });
});

/* ══════════════════════════════════════════
   6. PROGRESS STEPS (scroll)
══════════════════════════════════════════ */
const stepItems = document.querySelectorAll('.step-item');
const sections  = ['sec-1','sec-2','sec-3','sec-4','sec-5'].map(id => document.getElementById(id));

function updateSteps() {
    let active = 0;
    sections.forEach((sec, i) => {
        if (sec) {
            const rect = sec.getBoundingClientRect();
            if (rect.top < window.innerHeight * 0.6) active = i;
        }
    });
    stepItems.forEach((el, i) => {
        el.classList.toggle('active', i === active);
        el.classList.toggle('done',   i < active);
    });
}
window.addEventListener('scroll', updateSteps, { passive: true });
stepItems.forEach((el, i) => {
    el.addEventListener('click', () => {
        if (sections[i]) sections[i].scrollIntoView({ behavior: 'smooth', block: 'start' });
    });
});

/* ══════════════════════════════════════════
   7. VALIDACIÓN DEL FORMULARIO + SUBMIT
══════════════════════════════════════════ */
document.getElementById('frmEditar').addEventListener('submit', function (e) {
    e.preventDefault();

    let valid = true;

    const setError  = (id, show) => { const el = document.getElementById(id); if (el) el.classList.toggle('show', show); };
    const markInput = (id, bad)  => { const el = document.getElementById(id); if (el) el.classList.toggle('is-invalid', bad); };

    // Código
    const codigo = document.getElementById('codigo').value.trim();
    setError('err-codigo', !codigo);
    markInput('codigo', !codigo);
    if (!codigo) valid = false;

    // Nombre
    const nombre = document.getElementById('nombre').value.trim();
    setError('err-nombre', !nombre);
    markInput('nombre', !nombre);
    if (!nombre) valid = false;

    // Categoría
    const cat = document.getElementById('id_categoria').value;
    setError('err-categoria', !cat);
    markInput('id_categoria', !cat);
    if (!cat) valid = false;

    // Stock mínimo requerido
    const sMin = document.getElementById('stock_minimo').value;
    setError('err-stock-minimo', sMin === '');
    markInput('stock_minimo', sMin === '');
    if (sMin === '') valid = false;

    // Stock actual ≥ 0
    const sActual            = parseInt(document.getElementById('stock_actual').value);
    const stockActualInvalid = !isNaN(sActual) && sActual < 0;
    if (stockActualInvalid) {
        markInput('stock_actual', true);
        valid = false;
    }

    // Stock mínimo ≤ stock máximo
    const sMax = parseInt(document.getElementById('stock_maximo').value);
    if (!isNaN(parseInt(sMin)) && !isNaN(sMax) && parseInt(sMin) > sMax) {
        Swal.fire({
            title:              'Stock inválido',
            text:               'El stock mínimo no puede ser mayor al máximo.',
            icon:               'warning',
            confirmButtonColor: '#2563eb',
            confirmButtonText:  'Corregir'
        });
        markInput('stock_minimo', true);
        return;
    }

    // Precios
    const compra = parseFloat(document.getElementById('precio_compra').value) || 0;
    const venta  = parseFloat(document.getElementById('precio_venta').value)  || 0;

    if (!compra || compra <= 0) { setError('err-compra', true);  markInput('precio_compra', true);  valid = false; }
    else                        { setError('err-compra', false); markInput('precio_compra', false); }

    if (!venta  || venta  <= 0) { setError('err-venta',  true);  markInput('precio_venta',  true);  valid = false; }
    else                        { setError('err-venta',  false); markInput('precio_venta',  false); }

    // Precio venta ≥ precio compra
    if (venta > 0 && compra > 0 && venta < compra) {
        Swal.fire({
            title:              'Precio inválido',
            html:               'El precio de venta (<strong>S/ ' + venta.toFixed(2) + '</strong>) '
                              + 'no puede ser menor al de compra (<strong>S/ ' + compra.toFixed(2) + '</strong>).',
            icon:               'warning',
            confirmButtonColor: '#2563eb',
            confirmButtonText:  'Corregir'
        });
        markInput('precio_venta', true);
        return;
    }

    if (!valid) {
        Swal.fire({
            title:              'Campos incompletos',
            text:               'Por favor complete todos los campos marcados con * antes de guardar.',
            icon:               'warning',
            confirmButtonColor: '#2563eb',
            confirmButtonText:  'Revisar'
        });
        const firstErr = document.querySelector('.fld-input.is-invalid, .fld-select.is-invalid');
        if (firstErr) firstErr.scrollIntoView({ behavior: 'smooth', block: 'center' });
        return;
    }

    // Todo OK → confirmar y enviar
    Swal.fire({
        title:              '¿Guardar cambios?',
        html:               'Se actualizará <strong>' + htmlEsc(nombre) + '</strong><br>'
                          + '<small style="color:#94a3b8;">Código: ' + htmlEsc(codigo) + '</small>',
        icon:               'question',
        showCancelButton:   true,
        confirmButtonColor: '#2563eb',
        cancelButtonColor:  '#94a3b8',
        confirmButtonText:  '<i class="fas fa-save"></i> Guardar cambios',
        cancelButtonText:   'Revisar'
    }).then(result => {
        if (result.isConfirmed) {
            const btn = document.getElementById('btnGuardar');
            btn.classList.add('loading');
            btn.disabled = true;
            document.getElementById('frmEditar').submit();
        }
    });
});

function htmlEsc(str) {
    const d = document.createElement('div');
    d.appendChild(document.createTextNode(str));
    return d.innerHTML;
}

/* ── Limpiar error al enfocar campo ── */
document.querySelectorAll('.fld-input, .fld-select').forEach(el => {
    el.addEventListener('focus', function () {
        this.classList.remove('is-invalid');
    });
});

/* ══════════════════════════════════════════
   8. ELIMINAR PRODUCTO
══════════════════════════════════════════ */
function confirmarEliminar() {
    Swal.fire({
        title:              '¿Eliminar producto?',
        html:               '<p style="font-size:.9rem;color:#4a5568;">Esta acción no se puede deshacer.<br>'
                          + 'El producto quedará marcado como eliminado.</p>',
        icon:               'warning',
        showCancelButton:   true,
        confirmButtonColor: '#dc2626',
        cancelButtonColor:  '#94a3b8',
        confirmButtonText:  '<i class="fas fa-trash"></i> Eliminar',
        cancelButtonText:   'Cancelar'
    }).then(r => {
        if (r.isConfirmed)
            window.location.href = 'index.php?controller=producto&action=eliminar&id=<?= $idProd ?>';
    });
}
</script>

<?php include __DIR__ . '/../../layouts/footer.php'; ?>