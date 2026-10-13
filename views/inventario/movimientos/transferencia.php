<?php 
$currentPage = 'transferencia';
include __DIR__ . '/../layouts/inventario_layout.php'; 
?>

<div class="container-fluid">
    <!-- Encabezado -->
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h1 class="h3 mb-0">Transferencia entre Almacenes</h1>
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb mb-0">
                    <li class="breadcrumb-item"><a href="index.php?controller=dashboard&action=index">Dashboard</a></li>
                    <li class="breadcrumb-item"><a href="index.php?controller=movimiento&action=index">Movimientos</a></li>
                    <li class="breadcrumb-item active">Transferencia</li>
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
                    <h5 class="mb-0">Información de la Transferencia</h5>
                </div>
                <div class="card-body">
                    <form method="POST" action="index.php?controller=movimiento&action=registrarTransferencia" id="formTransferencia">
                        <div class="row g-3">
                            <!-- Producto -->
                            <div class="col-md-6">
                                <label for="id_producto" class="form-label required">Producto a Transferir</label>
                                <select class="form-select" id="id_producto" name="id_producto" required 
                                        onchange="cargarInfoProducto(this.value)">
                                    <option value="">Seleccionar producto...</option>
                                    <?php foreach ($productos as $producto): ?>
                                        <?php if ($producto['stock_actual'] > 0): ?>
                                            <option value="<?= $producto['id_producto'] ?>" 
                                                    data-stock="<?= $producto['stock_actual'] ?>"
                                                    data-codigo="<?= htmlspecialchars($producto['codigo']) ?>"
                                                    data-precio="<?= $producto['precio_compra'] ?>">
                                                <?= htmlspecialchars($producto['codigo']) ?> - <?= htmlspecialchars($producto['nombre']) ?>
                                                (Stock: <?= $producto['stock_actual'] ?>)
                                            </option>
                                        <?php endif; ?>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            
                            <!-- Cantidad -->
                            <div class="col-md-6">
                                <label for="cantidad" class="form-label required">Cantidad a Transferir</label>
                                <div class="input-group">
                                    <input type="number" class="form-control" id="cantidad" name="cantidad" 
                                           min="1" step="1" required oninput="validarStock()">
                                    <span class="input-group-text">unidades</span>
                                </div>
                                <div class="form-text" id="mensajeStock"></div>
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
                                            <small class="text-muted d-block">Stock Disponible</small>
                                            <span id="stockDisponible" class="fw-bold"></span>
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
                                <h6 class="mb-3">Origen y Destino</h6>
                            </div>
                            
                            <!-- Almacén Origen -->
                            <div class="col-md-6">
                                <label for="id_almacen_origen" class="form-label required">Almacén Origen</label>
                                <select class="form-select" id="id_almacen_origen" name="id_almacen_origen" required 
                                        onchange="cargarUbicacionesOrigen(this.value)">
                                    <option value="">Seleccionar almacén origen...</option>
                                    <?php foreach ($almacenes as $almacen): ?>
                                        <option value="<?= $almacen['id_almacen'] ?>">
                                            <?= htmlspecialchars($almacen['nombre']) ?> (<?= htmlspecialchars($almacen['codigo']) ?>)
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                                <div class="form-text" id="infoAlmacenOrigen"></div>
                            </div>
                            
                            <!-- Ubicación Origen -->
                            <div class="col-md-6">
                                <label for="ubicacion_origen" class="form-label">Ubicación Origen (Opcional)</label>
                                <select class="form-select" id="ubicacion_origen" name="ubicacion_origen">
                                    <option value="">Seleccionar ubicación...</option>
                                    <!-- Se cargará dinámicamente -->
                                </select>
                            </div>
                            
                            <!-- Almacén Destino -->
                            <div class="col-md-6">
                                <label for="id_almacen_destino" class="form-label required">Almacén Destino</label>
                                <select class="form-select" id="id_almacen_destino" name="id_almacen_destino" required 
                                        onchange="cargarUbicacionesDestino(this.value)">
                                    <option value="">Seleccionar almacén destino...</option>
                                    <?php foreach ($almacenes as $almacen): ?>
                                        <option value="<?= $almacen['id_almacen'] ?>">
                                            <?= htmlspecialchars($almacen['nombre']) ?> (<?= htmlspecialchars($almacen['codigo']) ?>)
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                                <div class="form-text" id="infoAlmacenDestino"></div>
                            </div>
                            
                            <!-- Ubicación Destino -->
                            <div class="col-md-6">
                                <label for="ubicacion_destino" class="form-label">Ubicación Destino (Opcional)</label>
                                <select class="form-select" id="ubicacion_destino" name="ubicacion_destino">
                                    <option value="">Seleccionar ubicación...</option>
                                    <!-- Se cargará dinámicamente -->
                                </select>
                            </div>
                            
                            <!-- Validación de almacenes diferentes -->
                            <div class="col-12" id="validacionAlmacenes" style="display: none;">
                                <div class="alert alert-warning">
                                    <i class="fas fa-exclamation-triangle me-2"></i>
                                    <span id="mensajeValidacion"></span>
                                </div>
                            </div>
                            
                            <div class="col-12">
                                <hr>
                            </div>
                            
                            <!-- Cálculos -->
                            <div class="col-12">
                                <div class="alert alert-light">
                                    <div class="row">
                                        <div class="col-md-4">
                                            <small class="text-muted d-block">Stock después en origen</small>
                                            <span id="stockOrigenDespues" class="fw-bold">0 unidades</span>
                                        </div>
                                        <div class="col-md-4">
                                            <small class="text-muted d-block">Stock después en destino</small>
                                            <span id="stockDestinoDespues" class="fw-bold">0 unidades</span>
                                        </div>
                                        <div class="col-md-4">
                                            <small class="text-muted d-block">Valor transferido</small>
                                            <span id="valorTransferido" class="fw-bold">S/ 0.00</span>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="col-12">
                                <hr>
                            </div>
                            
                            <!-- Observaciones -->
                            <div class="col-12">
                                <label for="observaciones" class="form-label">Observaciones</label>
                                <textarea class="form-control" id="observaciones" name="observaciones" rows="3" 
                                          placeholder="Motivo de la transferencia, persona responsable, etc..."></textarea>
                            </div>
                            
                            <!-- Confirmación -->
                            <div class="col-12">
                                <div class="form-check">
                                    <input class="form-check-input" type="checkbox" id="confirmar_transferencia" required>
                                    <label class="form-check-label" for="confirmar_transferencia">
                                        Confirmo que los productos han sido físicamente trasladados y recibidos
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
                    <h5 class="mb-0">Resumen de la Transferencia</h5>
                </div>
                <div class="card-body">
                    <div class="mb-3">
                        <small class="text-muted d-block">Tipo de operación</small>
                        <div class="fw-bold text-info">
                            <i class="fas fa-exchange-alt me-1"></i> Transferencia entre Almacenes
                        </div>
                    </div>
                    
                    <div class="mb-3">
                        <small class="text-muted d-block">Fecha y hora</small>
                        <div class="fw-bold"><?= date('d/m/Y H:i:s') ?></div>
                    </div>
                    
                    <div class="mb-3">
                        <small class="text-muted d-block">Usuario responsable</small>
                        <div class="fw-bold"><?= htmlspecialchars($_SESSION['usuario_nombre'] ?? 'Usuario') ?></div>
                    </div>
                    
                    <hr>
                    
                    <div class="mb-3">
                        <small class="text-muted d-block">Ruta de transferencia</small>
                        <div class="text-center my-3">
                            <div class="d-flex align-items-center justify-content-between">
                                <div class="text-center">
                                    <div class="avatar avatar-sm bg-danger">
                                        <i class="fas fa-arrow-left"></i>
                                    </div>
                                    <div class="mt-2">
                                        <small class="text-muted d-block">Origen</small>
                                        <div id="resumenAlmacenOrigen" class="fw-bold">-</div>
                                    </div>
                                </div>
                                <div class="mx-3">
                                    <i class="fas fa-arrow-right fa-2x text-info"></i>
                                </div>
                                <div class="text-center">
                                    <div class="avatar avatar-sm bg-success">
                                        <i class="fas fa-arrow-right"></i>
                                    </div>
                                    <div class="mt-2">
                                        <small class="text-muted d-block">Destino</small>
                                        <div id="resumenAlmacenDestino" class="fw-bold">-</div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="mb-3">
                        <small class="text-muted d-block">Detalles del producto</small>
                        <table class="table table-sm">
                            <tr>
                                <td>Producto:</td>
                                <td class="text-end"><span id="resumenProducto">-</span></td>
                            </tr>
                            <tr>
                                <td>Cantidad:</td>
                                <td class="text-end"><span id="resumenCantidad">0</span> unidades</td>
                            </tr>
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
                    
                    <div class="alert alert-info">
                        <small>
                            <i class="fas fa-info-circle me-1"></i>
                            Esta operación genera dos movimientos: salida del origen y entrada al destino.
                        </small>
                    </div>
                </div>
            </div>
            
            <!-- Acciones -->
            <div class="card">
                <div class="card-body">
                    <div class="d-grid gap-2">
                        <button type="submit" class="btn btn-info btn-lg">
                            <i class="fas fa-exchange-alt me-2"></i> Realizar Transferencia
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
            
            <!-- Guía rápida -->
            <div class="card mt-4">
                <div class="card-header">
                    <h6 class="mb-0"><i class="fas fa-truck me-1"></i> Proceso de transferencia</h6>
                </div>
                <div class="card-body">
                    <ol class="small mb-0">
                        <li>Seleccionar producto y cantidad</li>
                        <li>Definir almacén origen y destino</li>
                        <li>Validar stock disponible</li>
                        <li>Confirmar transferencia física</li>
                        <li>Sistema registra ambos movimientos</li>
                    </ol>
                </div>
            </div>
        </div>
        </form>
    </div>
</div>

<script>
let stockDisponible = 0;
let costoUnitario = 0;

function cargarInfoProducto(idProducto) {
    const select = document.getElementById('id_producto');
    const selectedOption = select.options[select.selectedIndex];
    
    if (idProducto && selectedOption) {
        // Mostrar información del producto
        document.getElementById('infoProducto').style.display = 'block';
        document.getElementById('codigoProducto').textContent = selectedOption.dataset.codigo;
        document.getElementById('stockDisponible').textContent = selectedOption.dataset.stock;
        document.getElementById('costoUnitario').textContent = 'S/ ' + parseFloat(selectedOption.dataset.precio).toFixed(2);
        
        // Guardar valores
        stockDisponible = parseInt(selectedOption.dataset.stock);
        costoUnitario = parseFloat(selectedOption.dataset.precio);
        
        // Actualizar resumen
        document.getElementById('resumenProducto').textContent = selectedOption.dataset.codigo;
        document.getElementById('resumenCostoUnitario').textContent = costoUnitario.toFixed(2);
        
        // Actualizar cálculos
        validarStock();
    } else {
        document.getElementById('infoProducto').style.display = 'none';
    }
}

function cargarUbicacionesOrigen(idAlmacen) {
    const select = document.getElementById('ubicacion_origen');
    const infoAlmacen = document.getElementById('infoAlmacenOrigen');
    
    // Limpiar opciones
    select.innerHTML = '<option value="">Seleccionar ubicación...</option>';
    
    if (!idAlmacen) {
        infoAlmacen.textContent = '';
        return;
    }
    
    // Aquí deberías hacer una petición AJAX para obtener las ubicaciones del almacén
    // Por ahora, simulamos datos
    const ubicaciones = [
        { id: 1, codigo: 'A-01-01', descripcion: 'Pasillo A, Nivel 1, Posición 1' },
        { id: 2, codigo: 'A-01-02', descripcion: 'Pasillo A, Nivel 1, Posición 2' },
        { id: 3, codigo: 'B-02-01', descripcion: 'Pasillo B, Nivel 2, Posición 1' }
    ];
    
    ubicaciones.forEach(ubicacion => {
        const option = document.createElement('option');
        option.value = ubicacion.id;
        option.textContent = `${ubicacion.codigo} - ${ubicacion.descripcion}`;
        select.appendChild(option);
    });
    
    // Actualizar info del almacén
    const almacenSelect = document.getElementById('id_almacen_origen');
    const almacenNombre = almacenSelect.options[almacenSelect.selectedIndex].text;
    infoAlmacen.textContent = almacenNombre;
    document.getElementById('resumenAlmacenOrigen').textContent = almacenNombre.split('(')[0].trim();
    
    // Validar que origen y destino sean diferentes
    validarAlmacenes();
}

function cargarUbicacionesDestino(idAlmacen) {
    const select = document.getElementById('ubicacion_destino');
    const infoAlmacen = document.getElementById('infoAlmacenDestino');
    
    // Limpiar opciones
    select.innerHTML = '<option value="">Seleccionar ubicación...</option>';
    
    if (!idAlmacen) {
        infoAlmacen.textContent = '';
        return;
    }
    
    // Simular datos (en producción sería AJAX)
    const ubicaciones = [
        { id: 4, codigo: 'C-01-01', descripcion: 'Pasillo C, Nivel 1, Posición 1' },
        { id: 5, codigo: 'C-01-02', descripcion: 'Pasillo C, Nivel 1, Posición 2' },
        { id: 6, codigo: 'D-02-01', descripcion: 'Pasillo D, Nivel 2, Posición 1' }
    ];
    
    ubicaciones.forEach(ubicacion => {
        const option = document.createElement('option');
        option.value = ubicacion.id;
        option.textContent = `${ubicacion.codigo} - ${ubicacion.descripcion}`;
        select.appendChild(option);
    });
    
    // Actualizar info del almacén
    const almacenSelect = document.getElementById('id_almacen_destino');
    const almacenNombre = almacenSelect.options[almacenSelect.selectedIndex].text;
    infoAlmacen.textContent = almacenNombre;
    document.getElementById('resumenAlmacenDestino').textContent = almacenNombre.split('(')[0].trim();
    
    // Validar que origen y destino sean diferentes
    validarAlmacenes();
}

function validarAlmacenes() {
    const origen = document.getElementById('id_almacen_origen').value;
    const destino = document.getElementById('id_almacen_destino').value;
    const validacion = document.getElementById('validacionAlmacenes');
    const mensaje = document.getElementById('mensajeValidacion');
    
    if (origen && destino) {
        if (origen === destino) {
            validacion.style.display = 'block';
            mensaje.textContent = 'El almacén origen y destino deben ser diferentes.';
            validacion.className = 'alert alert-danger';
        } else {
            validacion.style.display = 'none';
        }
    } else {
        validacion.style.display = 'none';
    }
}

function validarStock() {
    const cantidad = parseInt(document.getElementById('cantidad').value) || 0;
    const origen = document.getElementById('id_almacen_origen').value;
    const destino = document.getElementById('id_almacen_destino').value;
    const mensajeStock = document.getElementById('mensajeStock');
    
    if (cantidad <= 0) {
        mensajeStock.innerHTML = '<span class="text-danger">Ingrese una cantidad válida</span>';
        return;
    }
    
    // Calcular stocks después
    const stockOrigenDespues = stockDisponible - cantidad;
    document.getElementById('stockOrigenDespues').textContent = stockOrigenDespues + ' unidades';
    
    // Para el destino, asumimos que actualmente tiene 0 (simplificado)
    // En producción, deberías obtener el stock actual en el almacén destino
    document.getElementById('stockDestinoDespues').textContent = cantidad + ' unidades';
    
    // Calcular valor transferido
    const valorTransferido = cantidad * costoUnitario;
    document.getElementById('valorTransferido').textContent = 'S/ ' + valorTransferido.toFixed(2);
    
    // Actualizar resumen
    document.getElementById('resumenCantidad').textContent = cantidad;
    document.getElementById('resumenValorTotal').textContent = valorTransferido.toFixed(2);
    
    // Validar stock disponible
    if (cantidad > stockDisponible) {
        mensajeStock.innerHTML = '<span class="text-danger">Stock insuficiente. Disponible: ' + stockDisponible + '</span>';
    } else if (stockOrigenDespues < 0) {
        mensajeStock.innerHTML = '<span class="text-danger">Stock en origen quedaría negativo</span>';
    } else if (!origen) {
        mensajeStock.innerHTML = '<span class="text-warning">Seleccione un almacén origen</span>';
    } else if (!destino) {
        mensajeStock.innerHTML = '<span class="text-warning">Seleccione un almacén destino</span>';
    } else if (origen === destino) {
        mensajeStock.innerHTML = '<span class="text-danger">Almacenes origen y destino iguales</span>';
    } else {
        mensajeStock.innerHTML = '<span class="text-success">Stock disponible: ' + stockDisponible + '</span>';
    }
}

// Event listeners
document.getElementById('cantidad').addEventListener('input', validarStock);
document.getElementById('id_almacen_origen').addEventListener('change', validarStock);
document.getElementById('id_almacen_destino').addEventListener('change', validarStock);

// Validación del formulario
document.getElementById('formTransferencia').addEventListener('submit', function(e) {
    const cantidad = parseInt(document.getElementById('cantidad').value);
    const origen = document.getElementById('id_almacen_origen').value;
    const destino = document.getElementById('id_almacen_destino').value;
    
    if (cantidad <= 0) {
        e.preventDefault();
        alert('La cantidad debe ser mayor a 0');
        document.getElementById('cantidad').focus();
        return false;
    }
    
    if (cantidad > stockDisponible) {
        e.preventDefault();
        alert('Stock insuficiente. Disponible: ' + stockDisponible);
        document.getElementById('cantidad').focus();
        return false;
    }
    
    if (!origen) {
        e.preventDefault();
        alert('Debe seleccionar un almacén origen');
        document.getElementById('id_almacen_origen').focus();
        return false;
    }
    
    if (!destino) {
        e.preventDefault();
        alert('Debe seleccionar un almacén destino');
        document.getElementById('id_almacen_destino').focus();
        return false;
    }
    
    if (origen === destino) {
        e.preventDefault();
        alert('El almacén origen y destino deben ser diferentes');
        return false;
    }
    
    if (!document.getElementById('confirmar_transferencia').checked) {
        e.preventDefault();
        alert('Debe confirmar que los productos han sido trasladados físicamente');
        return false;
    }
    
    // Confirmación final
    const origenNombre = document.getElementById('id_almacen_origen').options[document.getElementById('id_almacen_origen').selectedIndex].text;
    const destinoNombre = document.getElementById('id_almacen_destino').options[document.getElementById('id_almacen_destino').selectedIndex].text;
    
    const confirmacion = confirm(`¿Está seguro de transferir ${cantidad} unidades?\n\n` +
                                 `Origen: ${origenNombre}\n` +
                                 `Destino: ${destinoNombre}\n\n` +
                                 `Valor: S/ ${(cantidad * costoUnitario).toFixed(2)}\n\n` +
                                 `Esta acción generará dos movimientos en el sistema.`);
    
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

.avatar {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    width: 40px;
    height: 40px;
    border-radius: 50%;
    color: white;
    font-size: 1rem;
}
</style>

<?php include __DIR__ . '/../layouts/footer.php'; ?>