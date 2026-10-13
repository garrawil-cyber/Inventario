<?php
/**
 * Vista: Editar Proveedor
 * Mutación: POST → JSON con SweetAlert
 * Variables: $proveedor (array con datos del proveedor)
 */

$currentPage = 'proveedores';
$proveedor = $proveedor ?? [];
?>
<head>
    <link rel="stylesheet" href="<?= BASE_URL ?>/views/inventario/proveedores/css/proveedores.css">
</head>
<body class="page-proveedores-editar">

<?php include __DIR__ . '/../../layouts/inventario_layout.php'; ?>

<div class="container-fluid py-4">
    <div class="row justify-content-center">
        <div class="col-lg-10">
            
            <!-- Header -->
            <div class="d-flex justify-content-between align-items-center mb-4">
                <div>
                    <h1 class="display-6 fw-bold mb-1" style="font-size: 1.5rem;">
                        <i class="fas fa-edit me-2" style="color: #4f46e5;"></i>
                        Editar Proveedor
                    </h1>
                    <p class="text-muted mb-0">Actualice la información del proveedor</p>
                </div>
                <a href="index.php?controller=proveedor&action=index" class="btn btn-outline-secondary">
                    <i class="fas fa-arrow-left me-2"></i>Volver
                </a>
            </div>
            
            <!-- Formulario -->
            <div class="card" style="border-radius: 16px; border: 1px solid #e2e8f0;">
                <div class="card-header bg-white" style="border-bottom: 1px solid #e2e8f0; border-radius: 16px 16px 0 0;">
                    <i class="fas fa-info-circle me-2"></i>
                    Información General
                </div>
                <div class="card-body">
                    <form id="proveedorForm" method="post">
                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label class="form-label">RUC <span class="text-danger">*</span></label>
                                    <input type="text" name="ruc" id="ruc" class="form-control" 
                                           required maxlength="11" value="<?= htmlspecialchars($proveedor['ruc'] ?? '') ?>">
                                    <div id="mensajeRUC" class="mt-1 small"></div>
                                </div>
                            </div>
                            
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label class="form-label">Razón Social <span class="text-danger">*</span></label>
                                    <input type="text" name="razon_social" class="form-control" 
                                           required maxlength="255" value="<?= htmlspecialchars($proveedor['razon_social'] ?? '') ?>">
                                </div>
                            </div>
                        </div>
                        
                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label class="form-label">Nombre Comercial</label>
                                    <input type="text" name="nombre_comercial" class="form-control" 
                                           maxlength="255" value="<?= htmlspecialchars($proveedor['nombre_comercial'] ?? '') ?>">
                                </div>
                            </div>
                            
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label class="form-label">Ciudad</label>
                                    <input type="text" name="ciudad" class="form-control" 
                                           value="<?= htmlspecialchars($proveedor['ciudad'] ?? '') ?>">
                                </div>
                            </div>
                        </div>
                        
                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label class="form-label">País</label>
                                    <input type="text" name="pais" class="form-control" 
                                           value="<?= htmlspecialchars($proveedor['pais'] ?? 'Perú') ?>">
                                </div>
                            </div>
                            
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label class="form-label">Teléfono</label>
                                    <input type="tel" name="telefono" class="form-control" 
                                           maxlength="15" value="<?= htmlspecialchars($proveedor['telefono'] ?? '') ?>">
                                </div>
                            </div>
                        </div>
                        
                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label class="form-label">Email</label>
                                    <input type="email" name="email" class="form-control" 
                                           maxlength="100" value="<?= htmlspecialchars($proveedor['email'] ?? '') ?>">
                                </div>
                            </div>
                            
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label class="form-label">Dirección</label>
                                    <input type="text" name="direccion" class="form-control" 
                                           maxlength="255" value="<?= htmlspecialchars($proveedor['direccion'] ?? '') ?>">
                                </div>
                            </div>
                        </div>
                        
                        <div class="row">
                            <div class="col-md-4">
                                <div class="mb-3">
                                    <label class="form-label">Tiempo Entrega (días)</label>
                                    <input type="number" name="tiempo_entrega_dias" class="form-control" 
                                           value="<?= $proveedor['tiempo_entrega_dias'] ?? 7 ?>" min="1" max="365">
                                </div>
                            </div>
                            
                            <div class="col-md-4">
                                <div class="mb-3">
                                    <label class="form-label">Estado</label>
                                    <select name="estado" class="form-select">
                                        <option value="activo" <?= ($proveedor['estado'] ?? '') === 'activo' ? 'selected' : '' ?>>Activo</option>
                                        <option value="inactivo" <?= ($proveedor['estado'] ?? '') === 'inactivo' ? 'selected' : '' ?>>Inactivo</option>
                                        <option value="suspendido" <?= ($proveedor['estado'] ?? '') === 'suspendido' ? 'selected' : '' ?>>Suspendido</option>
                                    </select>
                                </div>
                            </div>
                            
                            <div class="col-md-4">
                                <div class="mb-3">
                                    <label class="form-label">Condiciones de Pago</label>
                                    <input type="text" name="condiciones_pago" class="form-control" 
                                           value="<?= htmlspecialchars($proveedor['condiciones_pago'] ?? '') ?>">
                                </div>
                            </div>
                        </div>
                        
                        <hr class="my-4">
                        
                        <h6 class="fw-bold mb-3">
                            <i class="fas fa-user-tie me-2" style="color: #4f46e5;"></i>
                            Persona de Contacto
                        </h6>
                        
                        <div class="row">
                            <div class="col-md-4">
                                <div class="mb-3">
                                    <label class="form-label">Nombre del Contacto</label>
                                    <input type="text" name="contacto_nombre" class="form-control" 
                                           maxlength="100" value="<?= htmlspecialchars($proveedor['contacto_nombre'] ?? '') ?>">
                                </div>
                            </div>
                            
                            <div class="col-md-4">
                                <div class="mb-3">
                                    <label class="form-label">Teléfono de Contacto</label>
                                    <input type="tel" name="contacto_telefono" class="form-control" 
                                           maxlength="15" value="<?= htmlspecialchars($proveedor['contacto_telefono'] ?? '') ?>">
                                </div>
                            </div>
                            
                            <div class="col-md-4">
                                <div class="mb-3">
                                    <label class="form-label">Email de Contacto</label>
                                    <input type="email" name="contacto_email" class="form-control" 
                                           maxlength="100" value="<?= htmlspecialchars($proveedor['contacto_email'] ?? '') ?>">
                                </div>
                            </div>
                        </div>
                        
                        <!-- Estadísticas -->
                        <div class="alert alert-light mt-4 border">
                            <div class="row text-center">
                                <div class="col-md-4">
                                    <small class="text-muted d-block">Productos Asociados</small>
                                    <h5 class="text-primary mb-0"><?= $proveedor['total_productos'] ?? 0 ?></h5>
                                </div>
                                <div class="col-md-4">
                                    <small class="text-muted d-block">Movimientos Registrados</small>
                                    <h5 class="text-success mb-0"><?= $proveedor['total_movimientos'] ?? 0 ?></h5>
                                </div>
                                <div class="col-md-4">
                                    <small class="text-muted d-block">Valor Compras</small>
                                    <h5 class="text-warning mb-0">S/ <?= number_format($proveedor['valor_compras'] ?? 0, 2) ?></h5>
                                </div>
                            </div>
                        </div>
                        
                        <div class="d-flex justify-content-end gap-2 mt-4">
                            <a href="index.php?controller=proveedor&action=index" class="btn btn-outline-secondary px-4">
                                Cancelar
                            </a>
                            <button type="submit" class="btn btn-primary px-4">
                                <i class="fas fa-save me-2"></i>Actualizar Proveedor
                            </button>
                        </div>
                    </form>
                </div>
            </div>
            
        </div>
    </div>
</div>
</body>
<?php include __DIR__ . '/../../layouts/footer.php'; ?>

<script>
    window.proveedorId = <?= json_encode($proveedor['id_proveedor'] ?? 0) ?>;
    window.proveedorNombre = <?= json_encode($proveedor['razon_social'] ?? '', JSON_UNESCAPED_UNICODE) ?>;
    window.proveedorRucOriginal = <?= json_encode($proveedor['ruc'] ?? '', JSON_UNESCAPED_UNICODE) ?>;
</script>

<script src="<?= BASE_URL ?>/views/inventario/proveedores/js/proveedores.js"></script>


