<?php
/**
 * views/inventario/movimientos/entrada.php — VERSIÓN DEPURADA
 *
 * Variables provistas por MovimientoController::entrada():
 *   $productos       → getAll(['estado'=>'activo'])
 *   $almacenes       → getForSelect()  → claves: value, text
 *   $proveedores     → getForSelect()  → claves: value, text
 *   $producto        → getById() o null (preseleccionado)
 *   $sweetAlert      → leído de $_SESSION['sweet_alert'] por el controlador
 *   $ultimasEntradas → array (si el controlador lo pasa) o [] por defecto
 *
 * Correcciones aplicadas:
 *
 *  B1 [CRÍTICO] Conflicto entre btnSubmit-click y formEntrada-submit.
 *     El handler de 'click' en #btnSubmit hacía e.preventDefault() y luego
 *     form.submit(). Como form.submit() NO dispara el evento 'submit',
 *     todas las validaciones del submit-handler quedaban ignoradas:
 *     el usuario podía registrar entradas con campos vacíos.
 *     SOLUCIÓN: Se elimina el handler de 'click'. Todo se centraliza en
 *     el handler 'submit': primero valida, luego muestra el Swal de
 *     confirmación, y solo si el usuario confirma llama a form.submit()
 *     (que en ese punto es seguro porque la validación ya pasó).
 *
 *  B2 [CRÍTICO] PHP bool false con <?= ?> produce string vacío en JS:
 *     showConfirmButton: ,  → SyntaxError en consola.
 *     SOLUCIÓN: json_encode() para todo el array $sweetAlert.
 *
 *  B3  timer: null ?? 3000 evaluaba a 3000 para errores, sobreescribiendo
 *     la intención del controlador (null = sin temporizador en errores).
 *     SOLUCIÓN: json_encode() respeta null correctamente.
 *
 *  B4  Faltan claves toast, position y confirmButtonText del array que
 *     devuelve setSweetAlert() del controlador, rompiendo el modo toast.
 *     SOLUCIÓN: json_encode($sweetAlert) pasa TODAS las claves de una vez.
 *
 *  B5  addslashes() para title/text es frágil con caracteres especiales.
 *     SOLUCIÓN: json_encode() genera strings JS válidos y seguros.
 *
 *  B6  $movimientoModel es propiedad privada del controlador, inaccesible
 *     desde la vista. El bloque siempre devolvía array vacío. Se elimina
 *     el acceso al modelo desde la vista; se usa $ultimasEntradas si el
 *     controlador la pasa (debe agregar esa variable), o [] por defecto.
 */

$currentPage    = 'entradas';
$producto       = $producto       ?? null;
$sweetAlert     = $sweetAlert     ?? null;
$ultimasEntradas = $ultimasEntradas ?? [];   // [B6] no acceder a $movimientoModel

include __DIR__ . '/../../layouts/inventario_layout.php';
?>
<br>
<div class="container-fluid">

    <!-- Encabezado ──────────────────────────────────────────────────────── -->
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h1 class="h3 mb-0">Registrar Entrada de Productos</h1>
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb mb-0 small">
                    <li class="breadcrumb-item">
                        <a href="index.php?controller=dashboard&action=index">Dashboard</a>
                    </li>
                    <li class="breadcrumb-item">
                        <a href="index.php?controller=movimiento&action=index">Movimientos</a>
                    </li>
                    <li class="breadcrumb-item active">Entrada</li>
                </ol>
            </nav>
        </div>
        <a href="index.php?controller=movimiento&action=index" class="btn btn-secondary">
            <i class="fas fa-arrow-left me-2"></i>Volver
        </a>
    </div>

    <!-- Formulario ──────────────────────────────────────────────────────── -->
    <div class="row">
        <div class="col-lg-8 mx-auto">
            <div class="card shadow-sm">
                <div class="card-header">
                    <h5 class="mb-0">Datos de la Entrada</h5>
                </div>
                <div class="card-body">
                    <!--
                        [B1] id="formEntrada" — todo el flujo submit+confirmación
                             vive en el handler 'submit'; NO hay handler 'click'
                             en #btnSubmit.
                    -->
                    <form method="POST"
                          action="index.php?controller=movimiento&action=procesar_entrada"
                          id="formEntrada">
                        <div class="row g-3">

                            <!-- Producto -->
                            <div class="col-md-6">
                                <label class="form-label">
                                    Producto <span class="text-danger">*</span>
                                </label>
                                <select name="id_producto" id="selectProducto"
                                        class="form-select" required
                                        onchange="cargarInfoProducto(this.value)">
                                    <option value="">Seleccione un producto</option>
                                    <?php foreach ($productos as $prod): ?>
                                        <option value="<?= $prod['id_producto'] ?>"
                                            <?= (isset($producto) &&
                                                $producto['id_producto'] == $prod['id_producto'])
                                                    ? 'selected' : '' ?>>
                                            <?= htmlspecialchars(
                                                $prod['codigo'] . ' - ' . $prod['nombre']
                                            ) ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>

                            <!-- Almacén -->
                            <div class="col-md-6">
                                <label class="form-label">
                                    Almacén Destino <span class="text-danger">*</span>
                                </label>
                                <select name="id_almacen" class="form-select" required>
                                    <option value="">Seleccione un almacén</option>
                                    <?php foreach ($almacenes as $alm): ?>
                                        <option value="<?= htmlspecialchars($alm['value']) ?>">
                                            <?= htmlspecialchars($alm['text']) ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>

                            <!-- Info dinámica del producto -->
                            <div class="col-12" id="infoProducto" style="display:none;">
                                <div class="alert alert-info mb-0">
                                    <div class="row g-2">
                                        <div class="col-md-4">
                                            <small class="text-muted d-block">Stock Actual</small>
                                            <div class="fw-bold" id="stockActual">0</div>
                                        </div>
                                        <div class="col-md-4">
                                            <small class="text-muted d-block">Stock Mínimo</small>
                                            <div class="fw-bold" id="stockMinimo">0</div>
                                        </div>
                                        <div class="col-md-4">
                                            <small class="text-muted d-block">Precio Compra</small>
                                            <div class="fw-bold" id="precioCompra">S/ 0.00</div>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Cantidad -->
                            <div class="col-md-6">
                                <label class="form-label">
                                    Cantidad <span class="text-danger">*</span>
                                </label>
                                <input type="number" name="cantidad" id="cantidad"
                                       class="form-control" required
                                       min="0.01" step="0.01" placeholder="0.00">
                                <small class="text-muted">Ingrese la cantidad a registrar</small>
                            </div>

                            <!-- Precio Unitario -->
                            <div class="col-md-6">
                                <label class="form-label">
                                    Precio Unitario (S/) <span class="text-danger">*</span>
                                </label>
                                <input type="number" name="precio_unitario" id="precioUnitario"
                                       class="form-control" required
                                       min="0.01" step="0.01" placeholder="0.00">
                                <small class="text-muted">Precio de compra por unidad</small>
                            </div>

                            <!-- Proveedor -->
                            <div class="col-md-6">
                                <label class="form-label">Proveedor</label>
                                <select name="id_proveedor" class="form-select">
                                    <option value="">Sin proveedor</option>
                                    <?php foreach ($proveedores as $prov): ?>
                                        <option value="<?= htmlspecialchars($prov['value']) ?>">
                                            <?= htmlspecialchars($prov['text']) ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>

                            <!-- Documento de Referencia -->
                            <div class="col-md-6">
                                <label class="form-label">Documento de Referencia</label>
                                <input type="text" name="documento_numero" class="form-control"
                                       placeholder="Ej: Factura N° 001-001-0000001"
                                       maxlength="100">
                                <small class="text-muted">Factura, guía de remisión, etc.</small>
                            </div>

                            <!-- Observaciones -->
                            <div class="col-12">
                                <label class="form-label">Observaciones</label>
                                <textarea name="observaciones" class="form-control" rows="3"
                                          placeholder="Detalles adicionales de la entrada..."></textarea>
                            </div>

                            <!-- Botones -->
                            <div class="col-12 mt-3">
                                <div class="d-flex justify-content-end gap-2">
                                    <a href="index.php?controller=movimiento&action=index"
                                       class="btn btn-secondary">
                                        <i class="fas fa-times me-2"></i>Cancelar
                                    </a>
                                    <!--
                                        [B1] type="submit" — activa el handler 'submit'
                                             del form donde viven validación + Swal.
                                             Ya NO tiene handler 'click' separado.
                                    -->
                                    <button type="submit" class="btn btn-success" id="btnSubmit">
                                        <i class="fas fa-save me-2"></i>Registrar Entrada
                                    </button>
                                </div>
                            </div>

                        </div><!-- /row g-3 -->
                    </form>
                </div>
            </div>

            <!-- Últimas Entradas ──────────────────────────────────────────── -->
            <div class="card mt-4 shadow-sm">
                <div class="card-header">
                    <h6 class="mb-0">
                        <i class="fas fa-history me-2"></i>Últimas Entradas
                    </h6>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-sm mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th>Fecha</th>
                                    <th>Producto</th>
                                    <th class="text-center">Cantidad</th>
                                    <th class="text-end">Precio U.</th>
                                </tr>
                            </thead>
                            <tbody>
                                <!--
                                    [B6] $movimientoModel era privado del controlador
                                         e inaccesible desde la vista → siempre vacío.
                                         Ahora se usa $ultimasEntradas que debe ser
                                         preparada por el controlador antes del include.
                                         Si no existe o está vacía, muestra estado vacío.
                                -->
                                <?php if (empty($ultimasEntradas)): ?>
                                    <tr>
                                        <td colspan="4" class="text-center text-muted py-3">
                                            <i class="fas fa-inbox me-1 opacity-50"></i>
                                            No hay entradas recientes
                                        </td>
                                    </tr>
                                <?php else: ?>
                                    <?php foreach ($ultimasEntradas as $ent): ?>
                                        <tr>
                                            <td>
                                                <small>
                                                    <?= date('d/m/Y H:i',
                                                        strtotime(
                                                            $ent['fecha_movimiento']
                                                            ?? $ent['fecha']
                                                            ?? 'now'
                                                        )
                                                    ) ?>
                                                </small>
                                            </td>
                                            <td>
                                                <?= htmlspecialchars(
                                                    $ent['producto_nombre']
                                                    ?? $ent['producto']
                                                    ?? '—'
                                                ) ?>
                                            </td>
                                            <td class="text-center">
                                                <?= $ent['cantidad'] ?? 0 ?>
                                            </td>
                                            <td class="text-end">
                                                S/ <?= number_format(
                                                    $ent['costo_unitario']
                                                    ?? $ent['precio']
                                                    ?? 0,
                                                    2
                                                ) ?>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

        </div><!-- /col-lg-8 -->
    </div><!-- /row -->
</div><!-- /container-fluid -->

<script>
/* ── Helper de seguridad XSS ─────────────────────────────────────────── */
function escapeHtml(str) {
    const map = { '&':'&amp;', '<':'&lt;', '>':'&gt;', '"':'&quot;', "'":'&#039;' };
    return String(str || '').replace(/[&<>"']/g, c => map[c]);
}

/* ── AJAX: cargar info del producto seleccionado ────────────────────── */
function cargarInfoProducto(idProducto) {
    const panel = document.getElementById('infoProducto');
    if (!idProducto) { panel.style.display = 'none'; return; }

    fetch('index.php?controller=producto&action=obtener_detalles&id=' + idProducto)
        .then(r => r.json())
        .then(data => {
            if (!data.success) return;
            const p = data.producto;
            document.getElementById('stockActual').textContent = p.stock_actual || 0;
            document.getElementById('stockMinimo').textContent = p.stock_minimo || 0;
            document.getElementById('precioCompra').textContent =
                'S/ ' + parseFloat(p.precio_compra || 0).toFixed(2);

            // Sugerir precio si el campo está vacío
            const precioInput = document.getElementById('precioUnitario');
            if (precioInput && !precioInput.value) {
                precioInput.value = p.precio_compra || '';
            }

            panel.style.display = 'block';
        })
        .catch(err => console.error('Error cargando producto:', err));
}

/* ── [B1] ÚNICO handler: submit centraliza validación + confirmación ─── *
 *                                                                          *
 * ANTES (código original):                                                 *
 *   - Handler 'click' en #btnSubmit: e.preventDefault() → Swal → form.submit()
 *   - Handler 'submit' en #formEntrada: validaciones                       *
 *   PROBLEMA: form.submit() NO dispara el evento 'submit',                 *
 *   por lo que las validaciones del submit-handler eran completamente      *
 *   ignoradas. El usuario podía confirmar el Swal y enviar datos vacíos.   *
 *                                                                          *
 * AHORA (corrección):                                                      *
 *   - Solo un handler 'submit' en #formEntrada                            *
 *   - Primero valida todos los campos                                      *
 *   - Si todo es válido, muestra el Swal de confirmación                  *
 *   - Solo si el usuario confirma, llama a form.submit()                  *
 *   - En ese punto form.submit() es seguro porque la validación ya pasó   *
 * ──────────────────────────────────────────────────────────────────────── */
document.getElementById('formEntrada').addEventListener('submit', function (e) {
    e.preventDefault();
    const form = this;

    const productoVal = document.getElementById('selectProducto').value;
    const almacenVal  = document.querySelector('select[name="id_almacen"]').value;
    const cantidadVal = parseFloat(document.getElementById('cantidad').value) || 0;
    const precioVal   = parseFloat(document.getElementById('precioUnitario').value) || 0;

    // ── Validaciones ────────────────────────────────────────────────────
    if (!productoVal) {
        Swal.fire({
            icon: 'warning', title: 'Producto requerido',
            text: 'Por favor seleccione un producto',
            confirmButtonColor: '#3085d6'
        });
        return;
    }
    if (!almacenVal) {
        Swal.fire({
            icon: 'warning', title: 'Almacén requerido',
            text: 'Por favor seleccione un almacén destino',
            confirmButtonColor: '#3085d6'
        });
        return;
    }
    if (cantidadVal <= 0) {
        Swal.fire({
            icon: 'warning', title: 'Cantidad inválida',
            text: 'Ingrese una cantidad mayor a cero',
            confirmButtonColor: '#3085d6'
        });
        return;
    }
    if (precioVal <= 0) {
        Swal.fire({
            icon: 'warning', title: 'Precio inválido',
            text: 'Ingrese un precio unitario válido (mayor a cero)',
            confirmButtonColor: '#3085d6'
        });
        return;
    }

    // ── Confirmación ─────────────────────────────────────────────────────
    const sel          = document.getElementById('selectProducto');
    const productoNombre = sel.options[sel.selectedIndex]?.text || '';
    const total        = (cantidadVal * precioVal).toFixed(2);

    Swal.fire({
        title:             '¿Registrar entrada?',
        html:              '¿Confirma el ingreso de <strong>' +
                           escapeHtml(productoNombre) + '</strong>?<br>' +
                           '<small class="text-muted">' +
                           'Cantidad: ' + cantidadVal + ' uds — ' +
                           'Total: S/ ' + total +
                           '</small>',
        icon:              'question',
        showCancelButton:  true,
        confirmButtonColor:'#28a745',
        cancelButtonColor: '#6c757d',
        confirmButtonText: '<i class="fas fa-check me-1"></i> Sí, registrar',
        cancelButtonText:  'Cancelar',
        focusCancel:       true          // evita confirmaciones accidentales
    }).then(function (result) {
        if (result.isConfirmed) {
            // Deshabilitar botón para evitar doble envío
            document.getElementById('btnSubmit').disabled = true;
            document.getElementById('btnSubmit').innerHTML =
                '<span class="spinner-border spinner-border-sm me-2"></span>Registrando…';
            // [B1] form.submit() es seguro aquí: validación ya pasó arriba
            form.submit();
        }
    });
});

/* ── Auto-cargar si hay producto preseleccionado ──────────────────────── */
<?php if (isset($producto) && $producto): ?>
document.addEventListener('DOMContentLoaded', function () {
    cargarInfoProducto(<?= (int)$producto['id_producto'] ?>);
});
<?php endif; ?>

/* ── [B2–B5] SweetAlert de respuesta del controlador ─────────────────── *
 *                                                                          *
 * ANTES (código original):                                                 *
 *   timer:             <?= $sweetAlert['timer'] ?? 3000 ?>   ← null??3000=3000 *
 *   timerProgressBar:  <?= $sweetAlert['timerProgressBar'] ?>← true→"1" ok *
 *   showConfirmButton: <?= $sweetAlert['showConfirmButton'] ?>← false→""  *
 *                                                                SYNTAX ERROR *
 *   Además faltaban las claves: toast, position, confirmButtonText.        *
 *                                                                          *
 * AHORA (corrección):                                                      *
 *   json_encode($sweetAlert) serializa el array completo del controlador   *
 *   manteniendo tipos PHP correctos:                                       *
 *     null   → null       (no timer en errores)                            *
 *     true   → true       (no "1")                                         *
 *     false  → false      (no "")                                          *
 *   Y pasa TODAS las claves: type, title, text, timer, timerProgressBar,   *
 *   showConfirmButton, position, toast, confirmButtonText.                 *
 * ──────────────────────────────────────────────────────────────────────── */
<?php if ($sweetAlert && is_array($sweetAlert)): ?>
document.addEventListener('DOMContentLoaded', function () {
    if (typeof Swal === 'undefined') {
        console.warn('SweetAlert2 no está disponible.');
        return;
    }

    /*
     * json_encode() convierte el array PHP completo a objeto JS literal.
     * Tipos preservados: null→null, true→true, false→false, int→number.
     * Se usa 'icon' porque el controlador guarda la clave como 'type',
     * que SweetAlert2 también acepta (alias).
     */
    const swConfig = <?= json_encode($sweetAlert, JSON_UNESCAPED_UNICODE) ?>;

    // SweetAlert2 usa 'icon', el controlador guarda 'type' → normalizar
    if (swConfig.type && !swConfig.icon) {
        swConfig.icon = swConfig.type;
    }

    // Botón de confirmación por defecto si no viene del controlador
    if (!swConfig.confirmButtonText) {
        swConfig.confirmButtonText = 'Aceptar';
    }
    if (!swConfig.confirmButtonColor) {
        swConfig.confirmButtonColor = '#0d6efd';
    }

    Swal.fire(swConfig);
});
<?php endif; ?>
</script>

<?php include __DIR__ . '/../../layouts/footer.php'; ?>