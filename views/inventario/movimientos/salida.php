<?php
/**
 * Vista para registrar salida de inventario
 * REDISEÑADA - Con integración completa de clientes (CRUD desde modal)
 */
$currentPage = 'salidas';
include __DIR__ . '/../../layouts/inventario_layout.php'; 
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Registrar Salida - Sistema de Inventario</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        .card-header {
            background: linear-gradient(135deg, #dc3545, #c82333);
            color: white;
        }
        .required-field::after {
            content: " *";
            color: red;
            font-weight: bold;
        }
        .client-info-card {
            background-color: #f8f9fa;
            border-left: 4px solid #dc3545;
            margin-top: 15px;
        }
        .table-clientes tbody tr {
            cursor: pointer;
            transition: background-color 0.2s;
        }
        .table-clientes tbody tr:hover {
            background-color: #e3f2fd;
        }
        .search-box {
            position: relative;
        }
        .search-box i {
            position: absolute;
            left: 12px;
            top: 50%;
            transform: translateY(-50%);
            color: #6c757d;
        }
        .search-box input {
            padding-left: 35px;
        }
        .badge-client-type {
            font-size: 0.7rem;
            padding: 3px 8px;
        }
        .client-selected {
            background-color: #d4edda;
            border: 1px solid #c3e6cb;
            border-radius: 8px;
            padding: 10px;
        }
        .action-buttons-modal {
            display: flex;
            gap: 5px;
        }
        .btn-group-sm .btn {
            padding: 0.2rem 0.5rem;
        }
    </style>
</head>
<body>

<div class="container-fluid mt-4">
    <div class="row">
        <div class="col-md-8 mx-auto">
            <div class="card shadow">
                <div class="card-header">
                    <h4 class="mb-0">
                        <i class="fas fa-sign-out-alt me-2"></i>
                        Registrar Salida de Stock
                    </h4>
                </div>
                <div class="card-body">
                    
                    <!-- Mostrar errores de sesión -->
                    <?php if (isset($_SESSION['flash_message'])): ?>
                        <div class="alert alert-<?= $_SESSION['flash_type'] ?? 'info' ?> alert-dismissible fade show" role="alert">
                            <?= htmlspecialchars($_SESSION['flash_message']) ?>
                            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                        </div>
                        <?php unset($_SESSION['flash_message'], $_SESSION['flash_type']); ?>
                    <?php endif; ?>

                    <form method="POST" action="index.php?controller=movimiento&action=procesar_salida" id="formSalida">
                        
                        <!-- Producto -->
                        <div class="mb-3">
                            <label class="form-label required-field">Producto</label>
                            <select name="id_producto" id="id_producto" class="form-select" required>
                                <option value="">Seleccione un producto</option>
                                <?php foreach ($productos as $prod): ?>
                                    <option value="<?= $prod['id_producto'] ?>" 
                                            data-stock="<?= $prod['stock_actual'] ?>"
                                            data-precio="<?= $prod['precio_venta'] ?>"
                                            <?= (isset($producto) && $producto['id_producto'] == $prod['id_producto']) ? 'selected' : '' ?>>
                                        <?= htmlspecialchars($prod['codigo'] . ' – ' . $prod['nombre']) ?> 
                                        (Stock: <?= $prod['stock_actual'] ?>)
                                    </option>
                                <?php endforeach; ?>
                            </select>
                            <small class="text-muted">Solo se muestran productos con stock disponible</small>
                        </div>

                        <!-- Tipo de Salida -->
                        <div class="mb-3">
                            <label class="form-label required-field">Tipo de Salida</label>
                            <select name="id_tipo_movimiento" id="id_tipo_movimiento" class="form-select" required>
                                <option value="3">Venta</option>
                                <option value="4">Devolución a Proveedor</option>
                                <option value="5">Merma / Deterioro</option>
                                <option value="7">Ajuste Negativo</option>
                            </select>
                        </div>

                        <!-- Cantidad -->
                        <div class="mb-3">
                            <label class="form-label required-field">Cantidad a Retirar</label>
                            <div class="input-group">
                                <input type="number" name="cantidad" id="cantidad" class="form-control" 
                                       min="1" step="1" required>
                                <span class="input-group-text">unidades</span>
                            </div>
                            <small class="text-muted" id="stockDisponibleMsg"></small>
                        </div>

                        <!-- Almacén Origen -->
                        <div class="mb-3">
                            <label class="form-label required-field">Almacén Origen</label>
                            <select name="id_almacen" class="form-select" required>
                                <option value="">Seleccione un almacén</option>
                                <?php foreach ($almacenes as $alm): ?>
                                    <option value="<?= $alm['value'] ?>">
                                        <?= htmlspecialchars($alm['text']) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <!-- Documento -->
                        <div class="row">
                            <div class="col-md-4 mb-3">
                                <label class="form-label">Tipo de Documento</label>
                                <select name="documento_tipo" id="documento_tipo" class="form-select">
                                    <option value="">Seleccionar</option>
                                    <option value="boleta">Boleta</option>
                                    <option value="factura">Factura</option>
                                    <option value="guia">Guía de Remisión</option>
                                    <option value="nota_credito">Nota de Crédito</option>
                                </select>
                            </div>
                            <div class="col-md-8 mb-3">
                                <label class="form-label">Número de Documento</label>
                                <input type="text" name="documento_numero" class="form-control" 
                                       placeholder="Ej: B001-00025">
                            </div>
                        </div>

                        <!-- DESTINO / CLIENTE - SECCIÓN CON INTEGRACIÓN CRUD -->
                        <div class="mb-3">
                            <label class="form-label">Destino / Cliente</label>
                            <div class="input-group">
                                <input type="text" name="cliente_nombre" id="cliente_nombre" 
                                       class="form-control" 
                                       placeholder="Seleccione un cliente usando el botón buscar"
                                       readonly>
                                <input type="hidden" name="id_cliente" id="id_cliente" value="">
                                <button type="button" class="btn btn-outline-secondary" 
                                        data-bs-toggle="modal" data-bs-target="#modalClientes">
                                    <i class="fas fa-search"></i> Buscar Cliente
                                </button>
                                <button type="button" class="btn btn-outline-danger" id="btnLimpiarCliente">
                                    <i class="fas fa-times"></i>
                                </button>
                            </div>
                            <small class="text-muted">
                                <i class="fas fa-info-circle"></i> 
                                Seleccione un cliente para asociar esta salida (venta)
                            </small>
                        </div>

                        <!-- Información del Cliente Seleccionado -->
                        <div id="clienteInfoContainer" style="display: none;">
                            <div class="client-selected">
                                <div class="row">
                                    <div class="col-md-6">
                                        <small class="text-muted">Cliente seleccionado:</small>
                                        <div id="clienteInfoNombre" class="fw-bold"></div>
                                    </div>
                                    <div class="col-md-6">
                                        <small class="text-muted">Documento:</small>
                                        <div id="clienteInfoDocumento"></div>
                                    </div>
                                </div>
                                <div class="row mt-2">
                                    <div class="col-md-6">
                                        <small class="text-muted">Teléfono:</small>
                                        <div id="clienteInfoTelefono"></div>
                                    </div>
                                    <div class="col-md-6">
                                        <small class="text-muted">Tipo de Cliente:</small>
                                        <div id="clienteInfoTipo"></div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Observaciones -->
                        <div class="mb-3">
                            <label class="form-label">Observaciones</label>
                            <textarea name="observaciones" class="form-control" rows="3" 
                                      placeholder="Motivo de la salida, notas adicionales..."></textarea>
                        </div>

                        <!-- Botones -->
                        <div class="d-flex justify-content-between">
                            <a href="index.php?controller=movimiento&action=index" class="btn btn-secondary">
                                <i class="fas fa-arrow-left"></i> Cancelar
                            </a>
                            <button type="submit" class="btn btn-danger" id="btnSubmit">
                                <i class="fas fa-save"></i> Registrar Salida
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- MODAL PARA BUSCAR Y SELECCIONAR CLIENTE CON CRUD INTEGRADO -->
<div class="modal fade" id="modalClientes" data-bs-backdrop="static" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header bg-danger text-white">
                <h5 class="modal-title">
                    <i class="fas fa-users me-2"></i>
                    Seleccionar Cliente
                </h5>
                <div class="action-buttons-modal ms-auto me-3">
                    <a href="index.php?controller=cliente&action=create" 
                       class="btn btn-sm btn-success" target="_blank"
                       title="Crear nuevo cliente">
                        <i class="fas fa-plus"></i> Nuevo
                    </a>
                    <button type="button" 
                            class="btn btn-sm btn-info" 
                            id="btnEditarClienteModal" 
                            style="display: none;"
                            title="Editar cliente seleccionado">
                        <i class="fas fa-edit"></i> Editar
                    </button>
                    <button type="button" 
                            class="btn btn-sm btn-primary" 
                            id="btnVerClienteModal" 
                            style="display: none;"
                            title="Ver detalle del cliente">
                        <i class="fas fa-eye"></i> Ver
                    </button>
                </div>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <!-- Barra de búsqueda y acciones -->
                <div class="d-flex mb-3 gap-2">
                    <div class="search-box flex-grow-1">
                        <i class="fas fa-search"></i>
                        <input type="text" id="searchCliente" class="form-control" 
                               placeholder="Buscar por nombre, documento, teléfono o email...">
                    </div>
                    <button type="button" class="btn btn-outline-secondary" id="btnRecargarClientes" title="Recargar lista">
                        <i class="fas fa-sync-alt"></i>
                    </button>
                </div>

                <!-- Tabla de clientes -->
                <div class="table-responsive">
                    <table class="table table-hover table-clientes" id="tablaClientes">
                        <thead class="table-light">
                            <tr>
                                <th width="40">#</th>
                                <th>Documento</th>
                                <th>Nombre Completo</th>
                                <th>Teléfono</th>
                                <th>Email</th>
                                <th>Tipo</th>
                                <th width="120">Acciones</th>
                            </tr>
                        </thead>
                        <tbody id="clientesTableBody">
                            <tr>
                                <td colspan="7" class="text-center text-muted">
                                    <div class="spinner-border spinner-border-sm me-2" role="status"></div>
                                    Cargando clientes...
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cerrar</button>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script>
// ============================================
// VARIABLES GLOBALES
// ============================================
let clientesData = [];
let clienteSeleccionado = null;
let clienteIdSeleccionado = null;

// ============================================
// FUNCIONES DE CARGA Y RENDERIZADO
// ============================================

// Cargar clientes desde el servidor
function cargarClientes() {
    const tbody = document.getElementById('clientesTableBody');
    tbody.innerHTML = '<tr><td colspan="7" class="text-center"><div class="spinner-border spinner-border-sm me-2"></div>Cargando clientes...</td></tr>';
    
    fetch('index.php?controller=cliente&action=getClientesJson')
        .then(response => response.json())
        .then(data => {
            if (data.success && data.clientes) {
                clientesData = data.clientes;
                renderClientesTable(clientesData);
            } else {
                tbody.innerHTML = '<tr><td colspan="7" class="text-center text-danger">Error al cargar clientes</td></tr>';
            }
        })
        .catch(error => {
            console.error('Error:', error);
            tbody.innerHTML = '<tr><td colspan="7" class="text-center text-danger">Error de conexión</td></tr>';
        });
}

// Renderizar tabla de clientes con acciones CRUD
function renderClientesTable(clientes) {
    const tbody = document.getElementById('clientesTableBody');
    
    if (!clientes || clientes.length === 0) {
        tbody.innerHTML = '<tr><td colspan="7" class="text-center text-muted">No se encontraron clientes</td></tr>';
        return;
    }
    
    tbody.innerHTML = clientes.map((cliente, index) => `
        <tr onclick="seleccionarClienteDesdeTabla(${JSON.stringify(cliente).replace(/"/g, '&quot;')})" 
            style="cursor: pointer;">
            <td>${index + 1}</td>
            <td>
                <small class="text-muted">${cliente.tipo_documento || 'DNI'}</small><br>
                <strong>${escapeHtml(cliente.numero_documento || '-')}</strong>
            </td>
            <td>
                <strong>${escapeHtml(cliente.nombre_completo)}</strong>
            </td>
            <td>${cliente.telefono || '-'}</td>
            <td>${cliente.email || '-'}</td>
            <td>
                <span class="badge ${getTipoClienteBadgeClass(cliente.tipo_cliente)} badge-client-type">
                    ${getTipoClienteTexto(cliente.tipo_cliente)}
                </span>
            </td>
            <td onclick="event.stopPropagation();">
                <div class="btn-group btn-group-sm">
                    <button class="btn btn-info" onclick="verCliente(${cliente.id_cliente})" title="Ver detalle">
                        <i class="fas fa-eye"></i>
                    </button>
                    <button class="btn btn-warning" onclick="editarCliente(${cliente.id_cliente})" title="Editar cliente">
                        <i class="fas fa-edit"></i>
                    </button>
                    <button class="btn btn-danger" onclick="eliminarCliente(${cliente.id_cliente}, '${escapeHtml(cliente.nombre_completo)}')" title="Eliminar cliente">
                        <i class="fas fa-trash"></i>
                    </button>
                </div>
            </td>
        </tr>
    `).join('');
}

// ============================================
// FUNCIONES AUXILIARES
// ============================================

function escapeHtml(text) {
    if (!text) return '';
    const div = document.createElement('div');
    div.textContent = text;
    return div.innerHTML;
}

function getTipoClienteBadgeClass(tipo) {
    switch(tipo) {
        case 'vip': return 'bg-warning text-dark';
        case 'frecuente': return 'bg-info text-dark';
        case 'mayorista': return 'bg-success';
        default: return 'bg-secondary';
    }
}

function getTipoClienteTexto(tipo) {
    switch(tipo) {
        case 'vip': return 'VIP';
        case 'frecuente': return 'Frecuente';
        case 'mayorista': return 'Mayorista';
        default: return 'Regular';
    }
}

// ============================================
// FUNCIONES DE SELECCIÓN DE CLIENTE
// ============================================

function seleccionarClienteDesdeTabla(cliente) {
    clienteSeleccionado = cliente;
    clienteIdSeleccionado = cliente.id_cliente;
    
    // Actualizar campos del formulario principal
    document.getElementById('id_cliente').value = cliente.id_cliente;
    document.getElementById('cliente_nombre').value = cliente.nombre_completo;
    
    // Mostrar información adicional del cliente
    const infoContainer = document.getElementById('clienteInfoContainer');
    document.getElementById('clienteInfoNombre').innerHTML = `<i class="fas fa-user"></i> ${escapeHtml(cliente.nombre_completo)}`;
    document.getElementById('clienteInfoDocumento').innerHTML = `<i class="fas fa-id-card"></i> ${cliente.tipo_documento || 'DNI'}: ${cliente.numero_documento || '-'}`;
    document.getElementById('clienteInfoTelefono').innerHTML = `<i class="fas fa-phone"></i> ${cliente.telefono || 'No registrado'}`;
    document.getElementById('clienteInfoTipo').innerHTML = `<i class="fas fa-tag"></i> ${getTipoClienteTexto(cliente.tipo_cliente)}`;
    
    infoContainer.style.display = 'block';
    
    // Cerrar modal
    const modal = bootstrap.Modal.getInstance(document.getElementById('modalClientes'));
    modal.hide();
    
    // Mostrar notificación
    Swal.fire({
        icon: 'success',
        title: 'Cliente seleccionado',
        text: `Se ha seleccionado a ${cliente.nombre_completo}`,
        timer: 1500,
        showConfirmButton: false,
        toast: true,
        position: 'top-end'
    });
}

// Limpiar cliente seleccionado
document.getElementById('btnLimpiarCliente').addEventListener('click', function() {
    clienteSeleccionado = null;
    clienteIdSeleccionado = null;
    document.getElementById('id_cliente').value = '';
    document.getElementById('cliente_nombre').value = '';
    document.getElementById('clienteInfoContainer').style.display = 'none';
});

// ============================================
// FUNCIONES CRUD DE CLIENTES (desde el modal)
// ============================================

// Ver cliente (abre en nueva pestaña)
function verCliente(id) {
    window.open(`index.php?controller=cliente&action=ver&id=${id}`, '_blank');
}

// Editar cliente (abre en nueva pestaña)
function editarCliente(id) {
    window.open(`index.php?controller=cliente&action=edit&id=${id}`, '_blank');
}

// Eliminar cliente con confirmación
function eliminarCliente(id, nombre) {
    event.stopPropagation();
    
    Swal.fire({
        title: '¿Eliminar cliente?',
        html: `¿Está seguro de eliminar a <strong>${escapeHtml(nombre)}</strong>?<br><br>
               <small class="text-muted">Si tiene ventas asociadas, solo se desactivará.</small>`,
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#d33',
        cancelButtonColor: '#3085d6',
        confirmButtonText: 'Sí, eliminar',
        cancelButtonText: 'Cancelar'
    }).then((result) => {
        if (result.isConfirmed) {
            window.location.href = `index.php?controller=cliente&action=delete&id=${id}`;
        }
    });
}

// ============================================
// FUNCIONES DE BÚSQUEDA Y FILTRADO
// ============================================

// Búsqueda en tiempo real
document.getElementById('searchCliente').addEventListener('input', function(e) {
    const searchTerm = e.target.value.toLowerCase();
    const filtered = clientesData.filter(cliente => 
        (cliente.nombre_completo && cliente.nombre_completo.toLowerCase().includes(searchTerm)) ||
        (cliente.numero_documento && cliente.numero_documento.toLowerCase().includes(searchTerm)) ||
        (cliente.telefono && cliente.telefono.toLowerCase().includes(searchTerm)) ||
        (cliente.email && cliente.email.toLowerCase().includes(searchTerm))
    );
    renderClientesTable(filtered);
});

// Recargar lista de clientes
document.getElementById('btnRecargarClientes').addEventListener('click', function() {
    cargarClientes();
    document.getElementById('searchCliente').value = '';
});

// ============================================
// VALIDACIONES DEL FORMULARIO PRINCIPAL
// ============================================

// Validación de stock al seleccionar producto
document.getElementById('id_producto').addEventListener('change', function() {
    const selectedOption = this.options[this.selectedIndex];
    const stock = selectedOption.getAttribute('data-stock');
    const msgSpan = document.getElementById('stockDisponibleMsg');
    
    if (stock !== null) {
        msgSpan.innerHTML = `Stock disponible: ${stock} unidades`;
        msgSpan.style.color = stock > 0 ? 'green' : 'red';
        
        const cantidadInput = document.getElementById('cantidad');
        cantidadInput.max = stock;
        cantidadInput.placeholder = `Máx: ${stock}`;
    }
});

// Validación antes de enviar
document.getElementById('formSalida').addEventListener('submit', function(e) {
    const tipoMovimiento = document.getElementById('id_tipo_movimiento').value;
    const idCliente = document.getElementById('id_cliente').value;
    const producto = document.getElementById('id_producto').value;
    const cantidad = document.getElementById('cantidad').value;
    
    // Si es tipo "Venta" (valor 3), validar que se haya seleccionado un cliente
    if (tipoMovimiento === '3') {
        if (!idCliente || idCliente === '') {
            e.preventDefault();
            Swal.fire({
                icon: 'warning',
                title: 'Cliente requerido',
                text: 'Para registrar una VENTA, debe seleccionar un cliente',
                confirmButtonColor: '#dc3545'
            });
            return false;
        }
    }
    
    // Validar cantidad
    if (!cantidad || cantidad <= 0) {
        e.preventDefault();
        Swal.fire({
            icon: 'error',
            title: 'Cantidad inválida',
            text: 'Ingrese una cantidad válida mayor a cero',
            confirmButtonColor: '#dc3545'
        });
        return false;
    }
    
    // Validar producto
    if (!producto) {
        e.preventDefault();
        Swal.fire({
            icon: 'error',
            title: 'Producto no seleccionado',
            text: 'Seleccione un producto para continuar',
            confirmButtonColor: '#dc3545'
        });
        return false;
    }
});

// Confirmación de envío
document.getElementById('btnSubmit').addEventListener('click', function(e) {
    const form = document.getElementById('formSalida');
    const tipoMovimiento = document.getElementById('id_tipo_movimiento').value;
    
    if (tipoMovimiento === '3') {
        const idCliente = document.getElementById('id_cliente').value;
        if (!idCliente) {
            e.preventDefault();
            return;
        }
    }
    
    e.preventDefault();
    Swal.fire({
        title: '¿Confirmar salida?',
        text: 'Verifique que los datos sean correctos antes de continuar',
        icon: 'question',
        showCancelButton: true,
        confirmButtonColor: '#dc3545',
        cancelButtonColor: '#6c757d',
        confirmButtonText: 'Sí, registrar',
        cancelButtonText: 'Cancelar'
    }).then((result) => {
        if (result.isConfirmed) {
            form.submit();
        }
    });
});

// ============================================
// EVENTOS DEL MODAL
// ============================================

// Al abrir el modal, cargar clientes y resetear
document.getElementById('modalClientes').addEventListener('show.bs.modal', function() {
    cargarClientes();
    document.getElementById('searchCliente').value = '';
});

// ============================================
// INICIALIZACIÓN
// ============================================
document.addEventListener('DOMContentLoaded', function() {
    const productoSelect = document.getElementById('id_producto');
    if (productoSelect && productoSelect.value) {
        const event = new Event('change');
        productoSelect.dispatchEvent(event);
    }
});
</script>

<style>
.swal2-popup {
    font-size: 0.9rem;
}
.table-clientes tr.selected-row {
    background-color: #e3f2fd !important;
}
.btn-group-sm .btn {
    padding: 0.2rem 0.5rem;
    margin: 0 2px;
}
</style>

</body>
</html>