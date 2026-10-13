<?php 
$currentPage = 'ajuste';
include __DIR__ . '/../layouts/inventario_layout.php'; 
?>

<div class="container-fluid">
    <!-- Encabezado -->
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h1 class="h3 mb-0">Registrar Ajuste de Stock</h1>
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb mb-0">
                    <li class="breadcrumb-item"><a href="index.php?controller=dashboard&action=index">Dashboard</a></li>
                    <li class="breadcrumb-item"><a href="index.php?controller=movimiento&action=index">Movimientos</a></li>
                    <li class="breadcrumb-item active">Ajuste</li>
                </ol>
            </nav>
        </div>
        <div>
            <a href="index.php?controller=movimiento&action=index" class="btn btn-secondary">
                <i class="fas fa-arrow-left me-1"></i> Volver
            </a>
        </div>
    </div>

    <!-- Mensajes -->
    <?php if (isset($_SESSION['error'])): ?>
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            <i class="fas fa-exclamation-circle me-2"></i>
            <?= $_SESSION['error'] ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
        <?php unset($_SESSION['error']); ?>
    <?php endif; ?>

    <div class="row">
        <div class="col-lg-8">
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0">Información del Ajuste</h5>
                </div>
                <div class="card-body">
                    <form method="POST" action="index.php?controller=movimiento&action=registrarAjuste" id="formAjuste">
                        <div class="row g-3">
                            <!-- Tipo de ajuste -->
                            <div class="col-md-6">
                                <label class="form-label required">Tipo de Ajuste</label>
                                <div class="form-check mb-2">
                                    <input class="form-check-input" type="radio" name="tipo_ajuste" id="positivo" value="positivo" checked>
                                    <label class="form-check-label text-success" for="positivo">
                                        <i class="fas fa-plus-circle me-1"></i> Ajuste Positivo (Incrementar Stock)
                                    </label>
                                    <small class="d-block text-muted">Cuando hay más unidades físicamente</small>
                                </div>
                                <div class="form-check">
                                    <input class="form-check-input" type="radio" name="tipo_ajuste" id="negativo" value="negativo">
                                    <label class="form-check-label text-danger" for="negativo">
                                        <i class="fas fa-minus-circle me-1"></i> Ajuste Negativo (Disminuir Stock)
                                    </label>
                                    <small class="d-block text-muted">Cuando hay menos unidades físicamente</small>
                                </div>
                            </div>
                            
                            <!-- Motivo común -->
                            <div class="col-md-6">
                                <label class="form-label">Motivo común</label>
                                <select class="form-select" id="motivo_rapido" onchange="seleccionarMotivo(this.value)">
                                    <option value="">Seleccionar motivo rápido...</option>
                                    <option value="merma">Merma o deterioro</option>
                                    <option value="donacion">Donación</option>
                                    <option value="muestra">Muestra o demostración</option>
                                    <option value="robo">Pérdida o robo</option>
                                    <option value="error">Error de conteo anterior</option>
                                    <option value="devolucion">Devolución no registrada</option>
                                    <option value="otro">Otro motivo</option>
                                </select>
                                <div class="form-text">Seleccione un motivo predefinido</div>
                            </div>
                            
                            <div class="col-12">
                                <hr>
                            </div>
                            
                            <!-- Producto -->
                            <div class="col-md-6">
                                <label for="id_producto" class="form-label required">Producto</label>
                                <select class="form-select" id="id_producto" name="id_producto" required 
                                        onchange="cargarInfoProducto(this.value)">
                                    <option value="">Seleccionar producto...</option>
                                    <?php foreach ($productos as $producto): ?>
                                        <option value="<?= $producto['id_producto'] ?>" 
                                                data-stock="<?= $producto['stock_actual'] ?>"
                                                data-codigo="<?= htmlspecialchars($producto['codigo']) ?>"
                                                data-precio="<?= $producto['precio_compra'] ?>">
                                            <?= htmlspecialchars($producto['codigo']) ?> - <?= htmlspecialchars($producto['nombre']) ?>
                                            (Stock: <?= $producto['stock_actual'] ?>)
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            
                            <!-- Almacén -->
                            <div class="col-md-6">
                                <label for="id_almacen" class="form-label required">Almacén</label>
                                <select class="form-select" id="id_almacen" name="id_almacen" required>
                                    <option value="">Seleccionar almacén...</option>
                                    <?php foreach ($almacenes as $almacen): ?>
                                        <option value="<?= $almacen['id_almacen'] ?>">
                                            <?= htmlspecialchars($almacen['nombre']) ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            
                            <!-- Información del producto -->
                            <div class="col-12" id="infoProducto" style="display: none;">
                                <div class="alert alert-info">
                                    <div class="row">
                                        <div class="col-md-4">
                                            <small class="text-muted d-block">Código</small>
                                            <span id="codigoProducto" class="fw-bold"></span>
                                        </div>
                                        <div class="col-md-4">
                                            <small class="text-muted d-block">Stock Actual</small>
                                            <span id="stockActual" class="fw-bold"></span>
                                        </div>
                                        <div class="col-md-4">
                                            <small class="text-muted d-block">Costo Unitario</small>
                                            <span id="costoUnitario" class="fw-bold"></span>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="col-12">
                                <hr>
                            </div>
                            
                            <!-- Cantidad y costo -->
                            <div class="col-md-6">
                                <label for="cantidad" class="form-label required">Cantidad a Ajustar</label>
                                <div class="input-group">
                                    <input type="number" class="form-control" id="cantidad" name="cantidad" 
                                           min="1" step="1" required>
                                    <span class="input-group-text">unidades</span>
                                </div>
                                <div class="form-text" id="mensajeCantidad"></div>
                            </div>
                            
                            <div class="col-md-6">
                                <label for="costo_unitario" class="form-label required">Costo Unitario</label>
                                <div class="input-group">
                                    <span class="input-group-text">S/</span>
                                    <input type="number" class="form-control" id="costo_unitario" name="costo_unitario" 
                                           step="0.01" min="0" required>
                                </div>
                                <div class="form-text">Costo para el ajuste positivo</div>
                            </div>
                            
                            <!-- Cálculos -->
                            <div class="col-12">
                                <div class="alert alert-light">
                                    <div class="row">
                                        <div class="col-md-4">
                                            <small class="text-muted d-block">Stock después</small>
                                            <span id="stockDespues" class="fw-bold">0 unidades</span>
                                        </div>
                                        <div class="col-md-4">
                                            <small class="text-muted d-block">Valor ajuste</small>
                                            <span id="valorAjuste" class="fw-bold">S/ 0.00</span>
                                        </div>
                                        <div class="col-md-4">
                                            <small class="text-muted d-block">Diferencia</small>
                                            <span id="diferenciaStock" class="fw-bold">±0</span>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="col-12">
                                <hr>
                            </div>
                            
                            <!-- Observaciones -->
                            <div class="col-12">
                                <label for="observaciones" class="form-label required">Motivo del Ajuste</label>
                                <textarea class="form-control" id="observaciones" name="observaciones" rows="3" 
                                          placeholder="Describa el motivo detallado del ajuste..." required></textarea>
                                <div class="form-text">Sea específico sobre por qué se realiza este ajuste</div>
                            </div>
                            
                            <!-- Aprobación -->
                            <div class="col-12">
                                <div class="form-check">
                                    <input class="form-check-input" type="checkbox" id="confirmar_ajuste" required>
                                    <label class="form-check-label" for="confirmar_ajuste">
                                        Confirmo que este ajuste es necesario y está autorizado
                                    </label>
                                </div>
                            </div>
                        </div>
                </div>
            </div>
        </div>
        
        <div class="col-lg-4">
            <!-- Resumen -->
            <div class="card mb-4">
                <div class="card-header">
                    <h5 class="mb-0">Resumen del Ajuste</h5>
                </div>
                <div class="card-body">
                    <div class="mb-3">
                        <small class="text-muted d-block">Tipo de ajuste</small>
                        <div id="resumenTipo" class="fw-bold text-success">
                            <i class="fas fa-plus-circle me-1"></i> Positivo
                        </div>
                    </div>
                    
                    <div class="mb-3">
                        <small class="text-muted d-block">Impacto en inventario</small>
                        <table class="table table-sm">
                            <tr>
                                <td>Stock actual:</td>
                                <td class="text-end"><span id="resumenStockActual">0</span> unidades</td>
                            </tr>
                            <tr>
                                <td>Cantidad ajuste:</td>
                                <td class="text-end"><span id="resumenCantidad">0</span> unidades</td>
                            </tr>
                            <tr class="table-active">
                                <td><strong>Stock resultante:</strong></td>
                                <td class="text-end"><strong><span id="resumenStockResultante">0</span> unidades</strong></td>
                            </tr>
                        </table>
                    </div>
                    
                    <div class="mb-3">
                        <small class="text-muted d-block">Impacto financiero</small>
                        <table class="table table-sm">
                            <tr>
                                <td>Costo unitario:</td>
                                <td class="text-end">S/ <span id="resumenCostoUnitario">0.00</span></td>
                            </tr>
                            <tr class="table-active">
                                <td><strong>Valor total:</strong></td>
                                <td class="text-end"><strong>S/ <span id="resumenValorTotal">0.00</span></strong></td>
                            </tr>
                        </table>
                    </div>
                    
                    <div class="alert alert-warning" id="alertaAjuste">
                        <small>
                            <i class="fas fa-exclamation-triangle me-1"></i>
                            Los ajustes afectan directamente el valor del inventario y deben estar justificados.
                        </small>
                    </div>
                </div>
            </div>
            
            <!-- Acciones -->
            <div class="card">
                <div class="card-body">
                    <div class="d-grid gap-2">
                        <button type="submit" class="btn btn-warning btn-lg">
                            <i class="fas fa-check-circle me-2"></i> Registrar Ajuste
                        </button>
                        <button type="reset" class="btn btn-outline-secondary">
                            <i class="fas fa-redo me-2"></i> Limpiar
                        </button>
                        <a href="index.php?controller=movimiento&action=index" class="btn btn-outline-danger">
                            <i class="fas fa-times me-2"></i> Cancelar
                        </a>
                    </div>
                </div>
            </div>
            
            <!-- Guía de ajustes -->
            <div class="card mt-4">
                <div class="card-header">
                    <h6 class="mb-0"><i class="fas fa-question-circle me-1"></i> ¿Cuándo usar ajustes?</h6>
                </div>
                <div class="card-body">
                    <ul class="small mb-0">
                        <li><strong>Ajuste positivo:</strong> Inventario físico > Sistema</li>
                        <li><strong>Ajuste negativo:</strong> Inventario físico < Sistema</li>
                        <li><strong>Ejemplos:</strong> Mermas, donaciones, muestras, errores de conteo</li>
                    </ul>
                </div>
            </div>
        </div>
        </form>
    </div>
</div>

<script>
let stockActual = 0;
let costoUnitario = 0;

function cargarInfoProducto(idProducto) {
    const select = document.getElementById('id_producto');
    const selectedOption = select.options[select.selectedIndex];
    
    if (idProducto && selectedOption) {
        // Mostrar información del producto
        document.getElementById('infoProducto').style.display = 'block';
        document.getElementById('codigoProducto').textContent = selectedOption.dataset.codigo;
        document.getElementById('stockActual').textContent = selectedOption.dataset.stock;
        document.getElementById('costoUnitario').textContent = 'S/ ' + parseFloat(selectedOption.dataset.precio).toFixed(2);
        
        // Guardar valores
        stockActual = parseInt(selectedOption.dataset.stock);
        costoUnitario = parseFloat(selectedOption.dataset.precio);
        
        // Actualizar costo unitario
        document.getElementById('costo_unitario').value = costoUnitario.toFixed(2);
        
        // Actualizar resumen
        document.getElementById('resumenStockActual').textContent = stockActual;
        document.getElementById('resumenCostoUnitario').textContent = costoUnitario.toFixed(2);
        
        // Actualizar cálculos
        actualizarCalculos();
    } else {
        document.getElementById('infoProducto').style.display = 'none';
    }
}

function actualizarCalculos() {
    const tipoAjuste = document.querySelector('input[name="tipo_ajuste"]:checked').value;
    const cantidad = parseInt(document.getElementById('cantidad').value) || 0;
    const costo = parseFloat(document.getElementById('costo_unitario').value) || 0;
    
    // Calcular stock después
    let stockDespues = stockActual;
    let diferencia = 0;
    
    if (tipoAjuste === 'positivo') {
        stockDespues = stockActual + cantidad;
        diferencia = '+' + cantidad;
        document.getElementById('diferenciaStock').className = 'fw-bold text-success';
    } else {
        stockDespues = stockActual - cantidad;
        diferencia = '-' + cantidad;
        document.getElementById('diferenciaStock').className = 'fw-bold text-danger';
        
        // Validar stock para ajuste negativo
        if (cantidad > stockActual) {
            document.getElementById('mensajeCantidad').innerHTML = 
                '<span class="text-danger">Stock insuficiente. Disponible: ' + stockActual + '</span>';
        } else {
            document.getElementById('mensajeCantidad').innerHTML = '';
        }
    }
    
    // Actualizar valores
    document.getElementById('stockDespues').textContent = stockDespues + ' unidades';
    document.getElementById('diferenciaStock').textContent = diferencia;
    
    const valorAjuste = cantidad * costo;
    document.getElementById('valorAjuste').textContent = 'S/ ' + valorAjuste.toFixed(2);
    
    // Actualizar resumen
    document.getElementById('resumenCantidad').textContent = cantidad;
    document.getElementById('resumenStockResultante').textContent = stockDespues;
    document.getElementById('resumenValorTotal').textContent = valorAjuste.toFixed(2);
    
    // Actualizar tipo en resumen
    const resumenTipo = document.getElementById('resumenTipo');
    if (tipoAjuste === 'positivo') {
        resumenTipo.innerHTML = '<i class="fas fa-plus-circle me-1"></i> Positivo';
        resumenTipo.className = 'fw-bold text-success';
        document.getElementById('alertaAjuste').className = 'alert alert-warning';
    } else {
        resumenTipo.innerHTML = '<i class="fas fa-minus-circle me-1"></i> Negativo';
        resumenTipo.className = 'fw-bold text-danger';
        document.getElementById('alertaAjuste').className = 'alert alert-danger';
    }
}

function seleccionarMotivo(motivo) {
    const observaciones = document.getElementById('observaciones');
    let texto = '';
    
    switch(motivo) {
        case 'merma':
            texto = 'Ajuste por merma o deterioro natural del producto. Producto no apto para venta.';
            break;
        case 'donacion':
            texto = 'Ajuste por donación a institución benéfica. Documento de donación disponible.';
            break;
        case 'muestra':
            texto = 'Ajuste por muestra o demostración a cliente potencial. Uso promocional.';
            break;
        case 'robo':
            texto = 'Ajuste por pérdida o robo confirmado. Reporte de incidente disponible.';
            break;
        case 'error':
            texto = 'Ajuste por error en conteo físico anterior. Recontado y verificado.';
            break;
        case 'devolucion':
            texto = 'Ajuste por devolución de cliente no registrada en sistema anteriormente.';
            break;
        case 'otro':
            texto = '';
            break;
    }
    
    observaciones.value = texto;
}

// Event listeners
document.getElementById('cantidad').addEventListener('input', actualizarCalculos);
document.getElementById('costo_unitario').addEventListener('input', actualizarCalculos);
document.querySelectorAll('input[name="tipo_ajuste"]').forEach(radio => {
    radio.addEventListener('change', actualizarCalculos);
});

// Validación del formulario
document.getElementById('formAjuste').addEventListener('submit', function(e) {
    const tipoAjuste = document.querySelector('input[name="tipo_ajuste"]:checked').value;
    const cantidad = parseInt(document.getElementById('cantidad').value);
    const costo = parseFloat(document.getElementById('costo_unitario').value);
    const observaciones = document.getElementById('observaciones').value.trim();
    
    if (cantidad <= 0) {
        e.preventDefault();
        alert('La cantidad debe ser mayor a 0');
        document.getElementById('cantidad').focus();
        return false;
    }
    
    if (costo <= 0) {
        e.preventDefault();
        alert('El costo unitario debe ser mayor a 0');
        document.getElementById('costo_unitario').focus();
        return false;
    }
    
    if (tipoAjuste === 'negativo' && cantidad > stockActual) {
        e.preventDefault();
        alert('No puede realizar un ajuste negativo mayor al stock disponible (' + stockActual + ' unidades)');
        document.getElementById('cantidad').focus();
        return false;
    }
    
    if (!observaciones) {
        e.preventDefault();
        alert('Debe especificar el motivo del ajuste');
        document.getElementById('observaciones').focus();
        return false;
    }
    
    // Confirmación final
    const tipoTexto = tipoAjuste === 'positivo' ? 'POSITIVO (incremento)' : 'NEGATIVO (decremento)';
    const confirmacion = confirm(`¿Está seguro de registrar un ajuste ${tipoTexto} de ${cantidad} unidades?\n\n` +
                                 `Stock actual: ${stockActual}\n` +
                                 `Stock después: ${tipoAjuste === 'positivo' ? stockActual + cantidad : stockActual - cantidad}\n\n` +
                                 `Valor: S/ ${(cantidad * costo).toFixed(2)}\n\n` +
                                 `Motivo: ${observaciones.substring(0, 100)}...`);
    
    if (!confirmacion) {
        e.preventDefault();
        return false;
    }
    
    return true;
});
</script>

<style>
.required:after {
    content: " *";
    color: red;
}
</style>

<?php include __DIR__ . '/../layouts/footer.php'; ?>