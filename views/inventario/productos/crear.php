<?php
/**
 * crear.php — Vista "Registrar Nuevo Producto"
 * ─────────────────────────────────────────────
 * CORRECCIONES APLICADAS:
 *  [F1] Campos obligatorios: Categoría, Almacén, Marca, Proveedor
 *  [F2] Carga dinámica de ubicaciones por almacén (solo visual, no se envía)
 *  [F3] Validación JS completa para todos los obligatorios
 *  [F4] Eliminado campo id_ubicacion del POST (no existe en SP)
 */

$currentPage = 'productos';

require_once __DIR__ . '/../../../config/SessionMiddleware.php';
SessionMiddleware::startSession();

$sweetAlert = null;
if (!empty($_SESSION['sweet_alert'])) {
    $sweetAlert = $_SESSION['sweet_alert'];
    unset($_SESSION['sweet_alert']);
}

include __DIR__ . '/../../layouts/inventario_layout.php';
?>

<!-- ══════════════════════════════════════════════════════════
     FUENTES  +  CSS BASE
══════════════════════════════════════════════════════════ -->
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@300;400;500;600;700;800&family=JetBrains+Mono:wght@400;500;600&display=swap" rel="stylesheet">
<link rel="stylesheet" href="<?= BASE_URL ?>/views/inventario/productos/css/stylos_crear.css">

<!-- ══════════════════════════════════════════════════════════
     CONTENIDO
══════════════════════════════════════════════════════════ -->
<div class="cr-wrap">

    <!-- BREADCRUMB -->
    <nav class="cr-bread" aria-label="Ruta de navegación">
        <a href="index.php?controller=dashboard&action=index"><i class="fas fa-home"></i></a>
        <span class="sep">/</span>
        <a href="index.php?controller=producto&action=index">Productos</a>
        <span class="sep">/</span>
        <span class="cur">Nuevo Producto</span>
    </nav>

    <!-- HEADER -->
    <div class="cr-head">
        <div class="cr-head-left">
            <div class="cr-eyebrow"><i class="fas fa-plus-circle"></i> Inventario</div>
            <h1 class="cr-title">Registrar <span>Nuevo Producto</span></h1>
            <p class="cr-subtitle">Complete los campos para agregar el artículo al inventario.</p>
        </div>
        <div class="cr-head-actions">
            <a href="index.php?controller=producto&action=index" class="btn-ghost">
                <i class="fas fa-arrow-left"></i> Volver
            </a>
        </div>
    </div>

    <!-- STEPPER -->
    <nav class="cr-steps" id="crSteps" role="list">
        <div class="step active" data-sec="sec-1" role="listitem">
            <div class="step-num">1</div>
            <span class="step-label">Identificación</span>
        </div>
        <div class="step-line"></div>
        <div class="step" data-sec="sec-2" role="listitem">
            <div class="step-num">2</div>
            <span class="step-label">Clasificación</span>
        </div>
        <div class="step-line"></div>
        <div class="step" data-sec="sec-3" role="listitem">
            <div class="step-num">3</div>
            <span class="step-label">Stock</span>
        </div>
        <div class="step-line"></div>
        <div class="step" data-sec="sec-4" role="listitem">
            <div class="step-num">4</div>
            <span class="step-label">Precios</span>
        </div>
        <div class="step-line"></div>
        <div class="step" data-sec="sec-5" role="listitem">
            <div class="step-num">5</div>
            <span class="step-label">Atributos</span>
        </div>
    </nav>

    <!-- ══════════════════════════════
         FORMULARIO PRINCIPAL
    ══════════════════════════════ -->
    <form id="frmCrear" method="POST"
          action="index.php?controller=producto&action=guardar"
          novalidate>

        <!-- Campo oculto para imagen_principal -->
        <input type="hidden" name="imagen_principal" value="">

        <!-- ────────────────────────────
             SECCIÓN 1 — IDENTIFICACIÓN
        ──────────────────────────── -->
        <div class="cr-card" id="sec-1">
            <div class="cr-card-hdr">
                <span class="cr-card-ttl">
                    <span class="ttl-icon"><i class="fas fa-tag"></i></span>
                    Identificación del Producto
                </span>
                <span class="step-badge">Paso 1 / 5</span>
            </div>
            <div class="cr-card-body">

                <div class="fg-2" style="margin-bottom:1.25rem;">

                    <!-- Código (OBLIGATORIO) -->
                    <div class="fld">
                        <label class="fld-label" for="codigo">
                            Código <span class="req">*</span>
                        </label>
                        <div class="fld-with-btn">
                            <input type="text" id="codigo" name="codigo"
                                   class="fld-input mono"
                                   placeholder="PROD-000001"
                                   value="<?= htmlspecialchars($_POST['codigo'] ?? '') ?>"
                                   maxlength="50"
                                   autocomplete="off">
                            <button type="button" class="fld-btn" id="btnGenCodigo"
                                    title="Generar código automático">
                                <i class="fas fa-sync-alt"></i> Auto
                            </button>
                        </div>
                        <span class="fld-hint">Identificación interna única. Use «Auto» o escríbalo.</span>
                        <span class="fld-error" id="err-codigo">El código es obligatorio.</span>
                    </div>

                    <!-- SKU (opcional, se autogenera) -->
                    <div class="fld">
                        <label class="fld-label" for="sku">
                            SKU <span class="opt">(se genera si se deja vacío)</span>
                        </label>
                        <input type="text" id="sku" name="sku"
                               class="fld-input mono"
                               placeholder="Auto-generado por el sistema"
                               value="<?= htmlspecialchars($_POST['sku'] ?? '') ?>"
                               maxlength="50">
                        <span class="fld-hint">Stock Keeping Unit — referencia comercial.</span>
                    </div>

                </div>

                <!-- Nombre (OBLIGATORIO) -->
                <div class="fld" style="margin-bottom:1.25rem;">
                    <label class="fld-label" for="nombre">
                        Nombre del Producto <span class="req">*</span>
                    </label>
                    <input type="text" id="nombre" name="nombre"
                           class="fld-input"
                           placeholder="Ej: Vestido Casual Verano Talla M"
                           value="<?= htmlspecialchars($_POST['nombre'] ?? '') ?>"
                           maxlength="200">
                    <span class="fld-error" id="err-nombre">El nombre es obligatorio.</span>
                </div>

                <!-- Descripción -->
                <div class="fld">
                    <label class="fld-label" for="descripcion">Descripción</label>
                    <textarea id="descripcion" name="descripcion"
                              class="fld-textarea"
                              placeholder="Características, talla, material, uso recomendado…"
                              maxlength="1000"><?= htmlspecialchars($_POST['descripcion'] ?? '') ?></textarea>
                    <span class="fld-hint" id="charCount">0 / 1000 caracteres</span>
                </div>

            </div>
        </div><!-- /sec-1 -->

        <!-- ────────────────────────────
             SECCIÓN 2 — CLASIFICACIÓN
        ──────────────────────────── -->
        <div class="cr-card" id="sec-2">
            <div class="cr-card-hdr">
                <span class="cr-card-ttl">
                    <span class="ttl-icon"><i class="fas fa-sitemap"></i></span>
                    Clasificación y Ubicación
                </span>
                <span class="step-badge">Paso 2 / 5</span>
            </div>
            <div class="cr-card-body">

                <!-- Fila 1: Categoría (OBLIGATORIO) + Proveedor -->
                <div class="fg-2" style="margin-bottom:1.25rem;">
                    <div class="fld">
                        <label class="fld-label" for="id_categoria">
                            Categoría <span class="req">*</span>
                        </label>
                        <select id="id_categoria" name="id_categoria" class="fld-select">
                            <option value="">— Seleccionar categoría —</option>
                            <?php foreach ($categorias as $cat): ?>
                                <option value="<?= htmlspecialchars($cat['value']) ?>"
                                    <?= (!empty($_POST['id_categoria']) && $_POST['id_categoria'] == $cat['value']) ? 'selected' : '' ?>>
                                    <?= htmlspecialchars($cat['text']) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                        <span class="fld-error" id="err-categoria">Seleccione una categoría.</span>
                    </div>

                    <div class="fld">
                        <label class="fld-label" for="id_proveedor">
                            Proveedor <span class="req">*</span>
                        </label>
                        <select id="id_proveedor" name="id_proveedor" class="fld-select">
                            <option value="">— Sin asignar —</option>
                            <?php foreach ($proveedores as $prov): ?>
                                <option value="<?= htmlspecialchars($prov['value']) ?>"
                                    <?= (!empty($_POST['id_proveedor']) && $_POST['id_proveedor'] == $prov['value']) ? 'selected' : '' ?>>
                                    <?= htmlspecialchars($prov['text']) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                        <span class="fld-error" id="err-proveedor">Seleccione un proveedor.</span>
                    </div>
                </div>

                <!-- Fila 2: Marca + Almacén (AMBOS OBLIGATORIOS) -->
                <div class="fg-2" style="margin-bottom:1.25rem;">
                    <div class="fld">
                        <label class="fld-label" for="id_marca">
                            Marca <span class="req">*</span>
                        </label>
                        <select id="id_marca" name="id_marca" class="fld-select">
                            <option value="">— Seleccionar marca —</option>
                            <?php foreach ($marcas as $marca): ?>
                                <option value="<?= htmlspecialchars($marca['value']) ?>"
                                    <?= (!empty($_POST['id_marca']) && $_POST['id_marca'] == $marca['value']) ? 'selected' : '' ?>>
                                    <?= htmlspecialchars($marca['text']) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                        <span class="fld-error" id="err-marca">Seleccione una marca.</span>
                    </div>

                    <div class="fld">
                        <label class="fld-label" for="id_almacen">
                            Almacén <span class="req">*</span>
                        </label>
                        <select id="id_almacen" name="id_almacen" class="fld-select">
                            <option value="">— Seleccionar almacén —</option>
                            <?php foreach ($almacenes as $alm): ?>
                                <option value="<?= htmlspecialchars($alm['value']) ?>"
                                    <?= (!empty($_POST['id_almacen']) && $_POST['id_almacen'] == $alm['value']) ? 'selected' : '' ?>>
                                    <?= htmlspecialchars($alm['text']) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                        <span class="fld-error" id="err-almacen">Seleccione un almacén.</span>
                    </div>
                </div>

                <!-- Fila 3: Ubicación (SOLO VISUAL, no se envía) + Unidad de medida -->
                <div class="fg-2" style="margin-bottom:1.25rem;">
                    <div class="fld">
                        <label class="fld-label" for="ubicacion_info">
                            Ubicaciones Disponibles <span class="opt">(solo referencia)</span>
                        </label>
                        <div id="ubicacion_info" class="ubicacion-panel">
                            <span class="ubicacion-tag"><i class="fas fa-info-circle"></i> Seleccione un almacén</span>
                        </div>
                        <span class="fld-hint">Ubicaciones registradas en el almacén seleccionado.</span>
                    </div>

                    <div class="fld">
                        <label class="fld-label" for="unidad_medida">
                            Unidad de Medida <span class="opt">(opcional)</span>
                        </label>
                        <select id="unidad_medida" name="unidad_medida" class="fld-select">
                            <option value="">— Seleccione —</option>
                            <?php
                            $unidades = [
                                'unidad'=>'Unidad','par'=>'Par','docena'=>'Docena',
                                'caja'=>'Caja','paquete'=>'Paquete','rollo'=>'Rollo',
                                'kg'=>'Kilogramo (kg)','gramo'=>'Gramo (g)',
                                'litro'=>'Litro (L)','metro'=>'Metro (m)','set'=>'Set / Juego',
                            ];
                            $uSel = $_POST['unidad_medida'] ?? '';
                            foreach ($unidades as $v => $l): ?>
                                <option value="<?= $v ?>" <?= $uSel === $v ? 'selected' : '' ?>>
                                    <?= $l ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>

                <!-- Fila 4: Temporada + Género -->
                <div class="fg-2">
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
                            $tempSel = $_POST['temporada'] ?? 'todo_año';
                            foreach ($temporadas as $v => $l): ?>
                                <option value="<?= $v ?>" <?= $tempSel === $v ? 'selected' : '' ?>>
                                    <?= $l ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="fld">
                        <label class="fld-label">
                            Género <span class="req">*</span>
                            <span class="fld-error" id="err-genero" style="display:none;margin-left:.4rem;">Requerido.</span>
                        </label>
                        <div class="genero-grid">
                            <?php
                            $generos = [
                                ['value' => 'Unisex', 'icon' => 'fa-venus-mars',  'label' => 'Unisex'],
                                ['value' => 'Hombre', 'icon' => 'fa-mars',        'label' => 'Hombre'],
                                ['value' => 'Mujer',  'icon' => 'fa-venus',       'label' => 'Mujer'],
                                ['value' => 'Niño',   'icon' => 'fa-child',       'label' => 'Niño'],
                                ['value' => 'Niña',   'icon' => 'fa-child-dress', 'label' => 'Niña'],
                            ];
                            $generoSel = $_POST['genero'] ?? 'Unisex';
                            foreach ($generos as $g): ?>
                                <div class="g-pill">
                                    <input type="radio" name="genero"
                                           id="gen_<?= $g['value'] ?>"
                                           value="<?= $g['value'] ?>"
                                           <?= $generoSel === $g['value'] ? 'checked' : '' ?>>
                                    <label for="gen_<?= $g['value'] ?>">
                                        <i class="fas <?= $g['icon'] ?>"></i>
                                        <?= $g['label'] ?>
                                    </label>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                </div><!-- /fg-2 temporada-género -->

            </div>
        </div><!-- /sec-2 -->

        <!-- ────────────────────────────
             SECCIÓN 3 — STOCK
        ──────────────────────────── -->
        <div class="cr-card" id="sec-3">
            <div class="cr-card-hdr">
                <span class="cr-card-ttl">
                    <span class="ttl-icon"><i class="fas fa-layer-group"></i></span>
                    Control de Stock
                </span>
                <span class="step-badge">Paso 3 / 5</span>
            </div>
            <div class="cr-card-body">

                <div class="fg-3" style="margin-bottom:1.25rem;">
                    <!-- Stock actual -->
                    <div class="fld">
                        <label class="fld-label" for="stock_actual">
                            Stock Inicial <span class="req">*</span>
                        </label>
                        <input type="number" id="stock_actual" name="stock_actual"
                               class="fld-input" min="0" placeholder="0"
                               value="<?= htmlspecialchars($_POST['stock_actual'] ?? '0') ?>"
                               oninput="onStockChange()">
                        <span class="fld-hint">Unidades al momento del registro.</span>
                        <span class="fld-error" id="err-stock">El stock no puede ser negativo.</span>
                    </div>
                    <!-- Stock mínimo -->
                    <div class="fld">
                        <label class="fld-label" for="stock_minimo">
                            Stock Mínimo <span class="req">*</span>
                        </label>
                        <input type="number" id="stock_minimo" name="stock_minimo"
                               class="fld-input" min="0" placeholder="10"
                               value="<?= htmlspecialchars($_POST['stock_minimo'] ?? '10') ?>"
                               oninput="onStockChange()">
                        <span class="fld-hint">Dispara alerta de reposición.</span>
                        <span class="fld-error" id="err-stock-min">El stock mínimo es obligatorio.</span>
                    </div>
                    <!-- Stock máximo -->
                    <div class="fld">
                        <label class="fld-label" for="stock_maximo">Stock Máximo</label>
                        <input type="number" id="stock_maximo" name="stock_maximo"
                               class="fld-input" min="0" placeholder="100"
                               value="<?= htmlspecialchars($_POST['stock_maximo'] ?? '100') ?>"
                               oninput="onStockChange()">
                        <span class="fld-hint">Capacidad máxima en almacén.</span>
                    </div>
                </div>

                <div class="fg-2" style="margin-bottom:1.25rem;">
                    <!-- Punto de reorden -->
                    <div class="fld">
                        <label class="fld-label" for="punto_reorden">
                            Punto de Reorden <span class="opt">(auto si vacío)</span>
                        </label>
                        <input type="number" id="punto_reorden" name="punto_reorden"
                               class="fld-input" min="0" placeholder="stock_min × 1.5"
                               value="<?= htmlspecialchars($_POST['punto_reorden'] ?? '') ?>">
                        <span class="fld-hint">Umbral de alerta preventiva de reposición.</span>
                    </div>
                    <!-- Lote -->
                    <div class="fld">
                        <label class="fld-label" for="lote">
                            Lote <span class="opt">(opcional)</span>
                        </label>
                        <input type="text" id="lote" name="lote"
                               class="fld-input mono" placeholder="Ej: LT-2025-001"
                               value="<?= htmlspecialchars($_POST['lote'] ?? '') ?>"
                               maxlength="50">
                        <span class="fld-hint">Número de lote del fabricante.</span>
                    </div>
                </div>

                <!-- Barra visual de stock -->
                <div class="stock-vis" id="stockVis">
                    <span class="sv-label" id="svMin">Mín: 0</span>
                    <div class="sv-track">
                        <div class="sv-fill" id="svFill"></div>
                    </div>
                    <span class="sv-label" id="svMax">Máx: 100</span>
                </div>

            </div>
        </div><!-- /sec-3 -->

        <!-- ────────────────────────────
             SECCIÓN 4 — PRECIOS
        ──────────────────────────── -->
        <div class="cr-card" id="sec-4">
            <div class="cr-card-hdr">
                <span class="cr-card-ttl">
                    <span class="ttl-icon"><i class="fas fa-tags"></i></span>
                    Precios
                </span>
                <span class="step-badge">Paso 4 / 5</span>
            </div>
            <div class="cr-card-body">

                <div class="fg-3">
                    <!-- Precio compra -->
                    <div class="fld">
                        <label class="fld-label" for="precio_compra">
                            Precio Compra (S/) <span class="req">*</span>
                        </label>
                        <div class="fld-ctrl">
                            <span class="fld-prefix">S/</span>
                            <input type="number" id="precio_compra" name="precio_compra"
                                   class="fld-input fld-input-prefix"
                                   min="0.01" step="0.01" placeholder="0.00"
                                   value="<?= htmlspecialchars($_POST['precio_compra'] ?? '') ?>"
                                   oninput="calcMargen()">
                        </div>
                        <span class="fld-error" id="err-compra">Ingrese el precio de compra.</span>
                    </div>
                    <!-- Precio venta -->
                    <div class="fld">
                        <label class="fld-label" for="precio_venta">
                            Precio Venta (S/) <span class="req">*</span>
                        </label>
                        <div class="fld-ctrl">
                            <span class="fld-prefix">S/</span>
                            <input type="number" id="precio_venta" name="precio_venta"
                                   class="fld-input fld-input-prefix"
                                   min="0.01" step="0.01" placeholder="0.00"
                                   value="<?= htmlspecialchars($_POST['precio_venta'] ?? '') ?>"
                                   oninput="calcMargen()">
                        </div>
                        <span class="fld-error" id="err-venta">Ingrese el precio de venta.</span>
                    </div>
                    <!-- Precio mayorista -->
                    <div class="fld">
                        <label class="fld-label" for="precio_mayorista">
                            Precio Mayorista (S/) <span class="opt">(opcional)</span>
                        </label>
                        <div class="fld-ctrl">
                            <span class="fld-prefix">S/</span>
                            <input type="number" id="precio_mayorista" name="precio_mayorista"
                                   class="fld-input fld-input-prefix"
                                   min="0.01" step="0.01" placeholder="0.00"
                                   value="<?= htmlspecialchars($_POST['precio_mayorista'] ?? '') ?>"
                                   oninput="calcMargen()">
                        </div>
                        <span class="fld-hint">Precio para ventas al por mayor.</span>
                    </div>
                </div>

                <!-- Panel de margen -->
                <div class="price-panel" id="pricePanel">
                    <div class="pp-item">
                        <span class="pp-lbl"><i class="fas fa-arrow-up"></i> Utilidad</span>
                        <span class="pp-val" id="ppUtilidad">—</span>
                    </div>
                    <div class="pp-item">
                        <span class="pp-lbl"><i class="fas fa-percent"></i> Margen</span>
                        <span class="pp-val" id="ppMargen">—</span>
                    </div>
                    <div class="pp-item">
                        <span class="pp-lbl"><i class="fas fa-times"></i> Multiplicador</span>
                        <span class="pp-val" id="ppMulti">—</span>
                    </div>
                </div>

            </div>
        </div><!-- /sec-4 -->

        <!-- ────────────────────────────
             SECCIÓN 5 — ATRIBUTOS
        ──────────────────────────── -->
        <div class="cr-card" id="sec-5">
            <div class="cr-card-hdr">
                <span class="cr-card-ttl">
                    <span class="ttl-icon"><i class="fas fa-sliders-h"></i></span>
                    Atributos y Detalles
                </span>
                <span class="step-badge">Paso 5 / 5</span>
            </div>
            <div class="cr-card-body">

                <!-- Características físicas -->
                <div class="sec-sep"><i class="fas fa-tshirt"></i> Características físicas</div>

                <div class="fg-3" style="margin-bottom:1.25rem;">
                    <div class="fld">
                        <label class="fld-label" for="talla">Talla <span class="opt">(opcional)</span></label>
                        <input type="text" id="talla" name="talla" class="fld-input"
                               placeholder="Ej: M, 38, XL"
                               value="<?= htmlspecialchars($_POST['talla'] ?? '') ?>"
                               maxlength="50">
                    </div>
                    <div class="fld">
                        <label class="fld-label" for="color">Color <span class="opt">(opcional)</span></label>
                        <input type="text" id="color" name="color" class="fld-input"
                               placeholder="Ej: Azul marino"
                               value="<?= htmlspecialchars($_POST['color'] ?? '') ?>"
                               maxlength="50">
                    </div>
                    <div class="fld">
                        <label class="fld-label" for="material">Material <span class="opt">(opcional)</span></label>
                        <input type="text" id="material" name="material" class="fld-input"
                               placeholder="Ej: Algodón 100%"
                               value="<?= htmlspecialchars($_POST['material'] ?? '') ?>"
                               maxlength="100">
                    </div>
                </div>

                <!-- Trazabilidad -->
                <div class="sec-sep"><i class="fas fa-barcode"></i> Trazabilidad</div>

                <div class="fg-2" style="margin-bottom:1.25rem;">
                    <div class="fld">
                        <label class="fld-label" for="codigo_barras">
                            Código de Barras <span class="opt">(opcional)</span>
                        </label>
                        <div class="fld-with-btn">
                            <input type="text" id="codigo_barras" name="codigo_barras"
                                   class="fld-input mono" placeholder="Ej: 7750123456789"
                                   value="<?= htmlspecialchars($_POST['codigo_barras'] ?? '') ?>"
                                   maxlength="100">
                            <button type="button" class="fld-btn" id="btnGenBarras"
                                    title="Generar EAN-13 automático">
                                <i class="fas fa-barcode"></i> Auto
                            </button>
                        </div>
                        <span class="fld-hint">EAN-13 / UPC. Vacío = no se asigna.</span>
                        <span class="fld-error" id="err-barras">El código de barras ya existe.</span>
                    </div>

                    <div class="fld">
                        <label class="fld-label" for="fecha_vencimiento">
                            Fecha de Vencimiento <span class="opt">(opcional)</span>
                        </label>
                        <input type="date" id="fecha_vencimiento" name="fecha_vencimiento"
                               class="fld-input"
                               min="<?= date('Y-m-d') ?>"
                               value="<?= htmlspecialchars($_POST['fecha_vencimiento'] ?? '') ?>">
                        <span class="fld-hint">Solo para productos con caducidad.</span>
                        <span class="fld-error" id="err-fecha">La fecha no puede ser pasada.</span>
                    </div>
                </div>

                <!-- Preview código de barras -->
                <div class="barcode-wrap" id="barcodeWrap">
                    <svg id="barcodeSvg" width="280" height="80"></svg>
                    <span class="barcode-num" id="barcodeNum"></span>
                </div>

                <!-- Observaciones -->
                <div class="sec-sep"><i class="fas fa-sticky-note"></i> Notas internas</div>

                <div class="fld">
                    <label class="fld-label" for="observaciones">
                        Observaciones <span class="opt">(opcional)</span>
                    </label>
                    <textarea id="observaciones" name="observaciones"
                              class="fld-textarea"
                              placeholder="Instrucciones de almacenamiento, notas del proveedor…"
                              maxlength="500"><?= htmlspecialchars($_POST['observaciones'] ?? '') ?></textarea>
                </div>

            </div>
        </div><!-- /sec-5 -->

        <!-- FOOTER -->
        <div class="cr-footer">
            <a href="index.php?controller=producto&action=index" class="btn-ghost">
                <i class="fas fa-times"></i> Cancelar
            </a>
            <button type="submit" class="btn-primary" id="btnGuardar">
                <span class="btn-spinner" id="btnSpinner"></span>
                <i class="fas fa-save btn-icon"></i>
                Guardar Producto
            </button>
        </div>

    </form>
</div><!-- /.cr-wrap -->


<!-- ══════════════════════════════════════════════════════════
     JAVASCRIPT
══════════════════════════════════════════════════════════ -->
<script>
'use strict';
const APP_URL = '<?= $APP_URL ?? '' ?>';

/* ─────────────────────────────────────
   0. SWEETALERT FLASH (desde sesión)
───────────────────────────────────── */
<?php if ($sweetAlert): ?>
document.addEventListener('DOMContentLoaded', () => {
    Swal.fire({
        title            : <?= json_encode($sweetAlert['title']   ?? 'Aviso') ?>,
        text             : <?= json_encode($sweetAlert['message'] ?? '') ?>,
        icon             : <?= json_encode(in_array($sweetAlert['type'] ?? '', ['success','error','warning','info','question']) ? $sweetAlert['type'] : 'info') ?>,
        confirmButtonColor: '#3b82f6',
        confirmButtonText: 'Entendido'
    });
});
<?php endif; ?>

/* ─────────────────────────────────────
   1. CONTADOR DE CARACTERES
───────────────────────────────────── */
const descEl    = document.getElementById('descripcion');
const charCount = document.getElementById('charCount');
function updateCharCount() {
    const n = descEl.value.length;
    charCount.textContent = n + ' / 1000 caracteres';
    charCount.style.color = n > 900 ? 'var(--warn)' : 'var(--tx-3)';
}
descEl.addEventListener('input', updateCharCount);
updateCharCount();

/* ─────────────────────────────────────
   2. GENERADOR DE CÓDIGO INTERNO
───────────────────────────────────── */
document.getElementById('btnGenCodigo').addEventListener('click', function () {
    const btn  = this;
    const inp  = document.getElementById('codigo');
    btn.disabled  = true;
    const orig    = btn.innerHTML;
    btn.innerHTML = '<i class="fas fa-circle-notch fa-spin"></i>';

    fetch(APP_URL + '/index.php?controller=producto&action=generar_codigo')
        .then(r => { if (!r.ok) throw new Error(); return r.json(); })
        .then(data => {
            if (data.success && data.codigo) {
                inp.value = data.codigo;
                flashInput(inp, 'ok');
                toast('¡Código generado!', 'Código: ' + data.codigo, 'success');
            } else {
                throw new Error(data.message || 'Sin código');
            }
        })
        .catch(() => {
            const fb = fallbackCodigo();
            inp.value = fb;
            flashInput(inp, 'warn');
            toast('Código local', fb + ' (sin conexión al servidor)', 'warning');
        })
        .finally(() => {
            btn.disabled  = false;
            btn.innerHTML = orig;
            clearError('err-codigo');
            inp.classList.remove('is-invalid');
        });
});

function fallbackCodigo() {
    const n = new Date();
    const z = v => String(v).padStart(2,'0');
    return 'PROD-' + n.getFullYear() + z(n.getMonth()+1) + z(n.getDate())
                   + z(n.getHours()) + z(n.getMinutes()) + z(n.getSeconds());
}

/* ─────────────────────────────────────
   3. CARGA DINÁMICA DE UBICACIONES (SOLO VISUAL)
───────────────────────────────────── */
const almacenSel = document.getElementById('id_almacen');
const ubicacionInfo = document.getElementById('ubicacion_info');

function cargarUbicaciones(almacenId) {
    if (!almacenId) {
        ubicacionInfo.innerHTML = '<span class="ubicacion-tag"><i class="fas fa-info-circle"></i> Seleccione un almacén</span>';
        return;
    }
    
    ubicacionInfo.innerHTML = '<span class="ubicacion-tag"><i class="fas fa-spinner fa-pulse"></i> Cargando ubicaciones...</span>';
    
    fetch(APP_URL + '/index.php?controller=producto&action=get_ubicaciones&id_almacen=' + almacenId)
        .then(r => r.json())
        .then(data => {
            if (data.ubicaciones && data.ubicaciones.length > 0) {
                let html = '';
                data.ubicaciones.forEach(u => {
                    html += `<span class="ubicacion-tag">
                                <i class="fas fa-map-marker-alt"></i> ${escapeHtml(u.codigo || 'Sin código')}
                                ${u.descripcion ? ' — ' + escapeHtml(u.descripcion) : ''}
                             </span>`;
                });
                ubicacionInfo.innerHTML = html;
            } else {
                ubicacionInfo.innerHTML = '<span class="ubicacion-tag"><i class="fas fa-exclamation-triangle"></i> No hay ubicaciones registradas</span>';
            }
        })
        .catch(() => {
            ubicacionInfo.innerHTML = '<span class="ubicacion-tag"><i class="fas fa-times-circle"></i> Error al cargar ubicaciones</span>';
        });
}

almacenSel.addEventListener('change', function () {
    const id = this.value;
    cargarUbicaciones(id);
});

/* ─────────────────────────────────────
   4. BARRA VISUAL DE STOCK
───────────────────────────────────── */
function onStockChange() {
    const min = parseInt(document.getElementById('stock_minimo').value) || 0;
    const max = parseInt(document.getElementById('stock_maximo').value) || 0;
    const vis = document.getElementById('stockVis');
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

/* ─────────────────────────────────────
   5. CÁLCULO DE MARGEN
───────────────────────────────────── */
function calcMargen() {
    const c = parseFloat(document.getElementById('precio_compra').value)  || 0;
    const v = parseFloat(document.getElementById('precio_venta').value)   || 0;
    const p = document.getElementById('pricePanel');
    if (c > 0 && v > 0) {
        const util  = v - c;
        const margen= ((util / c) * 100).toFixed(1);
        const multi = (v / c).toFixed(2);
        const cls   = util >= 0 ? 'ok' : 'danger';
        document.getElementById('ppUtilidad').textContent = 'S/ ' + util.toFixed(2);
        document.getElementById('ppMargen').textContent   = margen + '%';
        document.getElementById('ppMulti').textContent    = multi  + 'x';
        ['ppUtilidad','ppMargen','ppMulti'].forEach(id => {
            document.getElementById(id).className = 'pp-val ' + cls;
        });
        p.classList.add('show');
    } else {
        p.classList.remove('show');
    }
}

/* ─────────────────────────────────────
   6. STEPPER — actualizar al hacer scroll
───────────────────────────────────── */
const steps   = document.querySelectorAll('.step[data-sec]');
const secIds  = ['sec-1','sec-2','sec-3','sec-4','sec-5'];
const secEls  = secIds.map(id => document.getElementById(id));

function updateStepper() {
    let active = 0;
    secEls.forEach((el, i) => {
        if (el && el.getBoundingClientRect().top < window.innerHeight * 0.55) active = i;
    });
    steps.forEach((s, i) => {
        s.classList.toggle('active', i === active);
        s.classList.toggle('done',   i < active);
    });
}
window.addEventListener('scroll', updateStepper, { passive: true });
steps.forEach((s, i) => {
    s.addEventListener('click', () => {
        secEls[i]?.scrollIntoView({ behavior: 'smooth', block: 'start' });
    });
});

/* ─────────────────────────────────────
   7. CÓDIGO DE BARRAS EAN-13
───────────────────────────────────── */
function ean13() {
    const pre = '775';
    const mid = String(Math.floor(Math.random() * 100000000)).padStart(9,'0');
    let b = pre + mid, s = 0;
    for (let i = 0; i < 12; i++) s += parseInt(b[i]) * (i%2===0 ? 1 : 3);
    return b + ((10 - s%10) % 10);
}

function validarEAN13(c) {
    if (!c || c.length !== 13 || !/^\d+$/.test(c)) return false;
    let s = 0;
    for (let i = 0; i < 12; i++) s += parseInt(c[i]) * (i%2===0 ? 1 : 3);
    return parseInt(c[12]) === (10 - s%10) % 10;
}

function dibujarBarcode(codigo) {
    const wrap = document.getElementById('barcodeWrap');
    const svg  = document.getElementById('barcodeSvg');
    const num  = document.getElementById('barcodeNum');
    if (!codigo || codigo.length < 8) { wrap.classList.remove('show'); return; }
    const W = 280, H = 80, bw = W / (codigo.length * 2.5);
    let s = `<svg width="${W}" height="${H}" xmlns="http://www.w3.org/2000/svg">`;
    s += `<rect width="100%" height="100%" fill="white"/>`;
    for (let i = 0; i < codigo.length; i++) {
        const p = [1,1,1,0,0,1,0,1,1,0][parseInt(codigo[i])] || 1;
        if (p) s += `<rect x="${i*bw*2.5}" y="8" width="${bw*1.2}" height="${H-22}" fill="black"/>`;
    }
    s += `<text x="50%" y="${H-4}" text-anchor="middle" font-family="monospace" font-size="10" fill="#333">${codigo}</text>`;
    s += `</svg>`;
    svg.outerHTML = s;
    num.textContent = codigo;
    wrap.classList.add('show');
}

document.getElementById('btnGenBarras').addEventListener('click', function () {
    const btn  = this, inp = document.getElementById('codigo_barras');
    btn.disabled  = true;
    const orig    = btn.innerHTML;
    btn.innerHTML = '<i class="fas fa-circle-notch fa-spin"></i>';
    const code = ean13();
    fetch(APP_URL + '/index.php?controller=producto&action=verificar_codigo_barras&codigo=' + code)
        .then(r => r.json())
        .then(d => {
            inp.value = d.existe ? ean13() : code;
            dibujarBarcode(inp.value);
            toast('Código generado', inp.value, 'success');
        })
        .catch(() => {
            inp.value = '775' + Date.now().toString().slice(-10);
            dibujarBarcode(inp.value);
        })
        .finally(() => { btn.disabled = false; btn.innerHTML = orig; });
});

document.getElementById('codigo_barras').addEventListener('input', function () {
    const v = this.value.trim();
    dibujarBarcode(v);
    if (v.length === 13) {
        const ok = validarEAN13(v);
        this.style.borderColor = ok ? 'var(--ok)' : 'var(--danger)';
        document.getElementById('err-barras').classList.toggle('show', !ok);
    } else {
        this.style.borderColor = '';
        clearError('err-barras');
    }
});

/* ─────────────────────────────────────
   8. VALIDACIÓN Y SUBMIT (completa)
───────────────────────────────────── */
document.getElementById('frmCrear').addEventListener('submit', function (e) {
    e.preventDefault();
    let valid = true;

    const se  = (id, show) => { const el = document.getElementById(id); if (el) el.classList.toggle('show', show); };
    const mi  = (id, bad)  => { const el = document.getElementById(id); if (el) el.classList.toggle('is-invalid', bad); };
    const req = (fieldId, errId) => {
        const v = document.getElementById(fieldId)?.value.trim();
        const bad = !v || v === '';
        se(errId, bad); mi(fieldId, bad);
        if (bad) valid = false;
        return !bad;
    };

    // OBLIGATORIOS
    req('codigo',           'err-codigo');
    req('nombre',           'err-nombre');
    req('id_categoria',     'err-categoria');
    req('id_marca',         'err-marca');
    req('id_almacen',       'err-almacen');
    req('stock_minimo',     'err-stock-min');

    // Stock actual ≥ 0
    const sA = parseInt(document.getElementById('stock_actual').value);
    const sABad = isNaN(sA) || sA < 0;
    se('err-stock', sABad); mi('stock_actual', sABad);
    if (sABad) valid = false;

    // Género — al menos un radio checked
    const genChecked = document.querySelector('input[name="genero"]:checked');
    const errGen = document.getElementById('err-genero');
    if (!genChecked) {
        if (errGen) errGen.style.display = 'inline';
        valid = false;
    } else {
        if (errGen) errGen.style.display = 'none';
    }

    // Precios
    const c = parseFloat(document.getElementById('precio_compra').value) || 0;
    const v = parseFloat(document.getElementById('precio_venta').value)  || 0;
    se('err-compra', c <= 0); mi('precio_compra', c <= 0); if (c <= 0) valid = false;
    se('err-venta',  v <= 0); mi('precio_venta',  v <= 0); if (v <= 0) valid = false;

    if (c > 0 && v > 0 && v < c) {
        Swal.fire({
            title: 'Precio inválido',
            html : 'El precio de venta (<strong>S/ ' + v.toFixed(2) + '</strong>) '
                 + 'no puede ser menor al de compra (<strong>S/ ' + c.toFixed(2) + '</strong>).',
            icon : 'warning',
            confirmButtonColor: '#3b82f6',
            confirmButtonText : 'Corregir'
        });
        mi('precio_venta', true);
        return;
    }

    // Fecha de vencimiento
    const fv = document.getElementById('fecha_vencimiento').value;
    if (fv) {
        const bad = fv < new Date().toISOString().split('T')[0];
        se('err-fecha', bad); mi('fecha_vencimiento', bad);
        if (bad) valid = false;
    }

    if (!valid) {
        Swal.fire({
            title: 'Campos incompletos',
            text : 'Por favor complete todos los campos marcados con * antes de guardar.',
            icon : 'warning',
            confirmButtonColor: '#3b82f6',
            confirmButtonText : 'Revisar'
        });
        document.querySelector('.fld-input.is-invalid, .fld-select.is-invalid')
            ?.scrollIntoView({ behavior: 'smooth', block: 'center' });
        return;
    }

    // Confirmación final
    const nombre = document.getElementById('nombre').value.trim();
    const codigo = document.getElementById('codigo').value.trim();
    Swal.fire({
        title            : '¿Guardar producto?',
        html             : '<strong>' + esc(nombre) + '</strong><br>'
                         + '<small style="color:#9aa1b8;">Código: ' + esc(codigo) + '</small>',
        icon             : 'question',
        showCancelButton : true,
        confirmButtonColor: '#3b82f6',
        cancelButtonColor : '#374151',
        confirmButtonText : '<i class="fas fa-save"></i> Sí, guardar',
        cancelButtonText  : 'Revisar'
    }).then(r => {
        if (r.isConfirmed) {
            const btn = document.getElementById('btnGuardar');
            btn.classList.add('loading');
            btn.disabled = true;
            document.getElementById('frmCrear').submit();
        }
    });
});

/* ─────────────────────────────────────
   UTILIDADES
───────────────────────────────────── */
function esc(str) {
    const d = document.createElement('div');
    d.appendChild(document.createTextNode(str));
    return d.innerHTML;
}

function escapeHtml(str) {
    if (!str) return '';
    return str.replace(/[&<>]/g, function(m) {
        if (m === '&') return '&amp;';
        if (m === '<') return '&lt;';
        if (m === '>') return '&gt;';
        return m;
    });
}

function toast(title, text, icon) {
    Swal.fire({ title, text, icon, timer: 2500, toast: true,
                position: 'top-end', showConfirmButton: false });
}
function clearError(id) {
    document.getElementById(id)?.classList.remove('show');
}
function flashInput(el, type) {
    el.style.borderColor = type === 'ok' ? 'var(--ok)' : 'var(--warn)';
    setTimeout(() => { el.style.borderColor = ''; }, 2000);
}

/* Limpiar errores al enfocar */
document.querySelectorAll('.fld-input, .fld-select').forEach(el => {
    el.addEventListener('focus', () => el.classList.remove('is-invalid'));
});

/* Inicializar visuales al cargar */
document.addEventListener('DOMContentLoaded', () => {
    onStockChange();
    calcMargen();
    const cb = document.getElementById('codigo_barras').value;
    if (cb && cb.length >= 8) dibujarBarcode(cb);
    
    // Cargar ubicaciones si ya hay un almacén seleccionado
    if (almacenSel.value) {
        cargarUbicaciones(almacenSel.value);
    }
});
</script>

<?php include __DIR__ . '/../../layouts/footer.php'; ?>