<?php
/**
 * Vista: Crear Nuevo Proveedor
 * Mutación: POST → JSON con SweetAlert
 */

$currentPage = 'proveedores';
include __DIR__ . '/../../layouts/inventario_layout.php'; 
?>

<link rel="stylesheet" href="<?= BASE_URL ?>/views/inventario/proveedores/css/proveedores.css">
</head>
<body class="page-proveedores-crear">


<div class="container-fluid py-4">
    <div class="row justify-content-center">
        <div class="col-lg-10">
            
            <!-- Header -->
            <div class="d-flex justify-content-between align-items-center mb-4">
                <div>
                    <h1 class="display-6 fw-bold mb-1" style="font-size: 1.5rem;">
                        <i class="fas fa-plus-circle me-2" style="color: var(--pv-primary);"></i>
                        Nuevo Proveedor
                    </h1>
                    <p class="text-muted mb-0">Complete la información del nuevo proveedor</p>
                </div>
                <a href="index.php?controller=proveedor&action=index" class="btn btn-outline-secondary">
                    <i class="fas fa-arrow-left me-2"></i>Volver
                </a>
            </div>
            
            <!-- Formulario -->
            <div class="pv-card">
                <div class="pv-card-header">
                    <i class="fas fa-info-circle me-2"></i>
                    Información General
                </div>
                <div class="pv-card-body">
                    <form id="proveedorForm" method="post">
                        <div class="row">
                            <div class="col-md-6">
                                <div class="pv-form-group">
                                    <label class="pv-form-label">RUC <span class="text-danger">*</span></label>
                                    <input type="text" name="ruc" id="ruc" class="pv-form-control" 
                                           required maxlength="11" placeholder="Ej: 20123456789">
                                    <div id="mensajeRUC" class="mt-1 small"></div>
                                </div>
                            </div>
                            
                            <div class="col-md-6">
                                <div class="pv-form-group">
                                    <label class="pv-form-label">Razón Social <span class="text-danger">*</span></label>
                                    <input type="text" name="razon_social" class="pv-form-control" 
                                           required maxlength="255" placeholder="Nombre o razón social">
                                </div>
                            </div>
                        </div>
                        
                        <div class="row">
                            <div class="col-md-6">
                                <div class="pv-form-group">
                                    <label class="pv-form-label">Nombre Comercial</label>
                                    <input type="text" name="nombre_comercial" class="pv-form-control" 
                                           maxlength="255" placeholder="Nombre comercial (opcional)">
                                </div>
                            </div>
                            
                            <div class="col-md-6">
                                <div class="pv-form-group">
                                    <label class="pv-form-label">Ciudad</label>
                                    <input type="text" name="ciudad" class="pv-form-control" 
                                           placeholder="Ciudad">
                                </div>
                            </div>
                        </div>
                        
                        <div class="row">
                            <div class="col-md-6">
                                <div class="pv-form-group">
                                    <label class="pv-form-label">País</label>
                                    <input type="text" name="pais" class="pv-form-control" 
                                           value="Perú" placeholder="País">
                                </div>
                            </div>
                            
                            <div class="col-md-6">
                                <div class="pv-form-group">
                                    <label class="pv-form-label">Teléfono</label>
                                    <input type="tel" name="telefono" class="pv-form-control" 
                                           maxlength="15" placeholder="Ej: 987654321">
                                </div>
                            </div>
                        </div>
                        
                        <div class="row">
                            <div class="col-md-6">
                                <div class="pv-form-group">
                                    <label class="pv-form-label">Email</label>
                                    <input type="email" name="email" class="pv-form-control" 
                                           maxlength="100" placeholder="correo@empresa.com">
                                </div>
                            </div>
                            
                            <div class="col-md-6">
                                <div class="pv-form-group">
                                    <label class="pv-form-label">Dirección</label>
                                    <input type="text" name="direccion" class="pv-form-control" 
                                           maxlength="255" placeholder="Dirección completa">
                                </div>
                            </div>
                        </div>
                        
                        <div class="row">
                            <div class="col-md-4">
                                <div class="pv-form-group">
                                    <label class="pv-form-label">Tiempo Entrega (días)</label>
                                    <input type="number" name="tiempo_entrega_dias" class="pv-form-control" 
                                           value="7" min="1" max="365">
                                </div>
                            </div>
                            
                            <div class="col-md-8">
                                <div class="pv-form-group">
                                    <label class="pv-form-label">Condiciones de Pago</label>
                                    <input type="text" name="condiciones_pago" class="pv-form-control" 
                                           placeholder="Ej: Crédito 30 días, Contado, etc.">
                                </div>
                            </div>
                        </div>
                        
                        <hr class="my-4">
                        
                        <h6 class="fw-bold mb-3">
                            <i class="fas fa-user-tie me-2" style="color: var(--pv-primary);"></i>
                            Persona de Contacto
                        </h6>
                        
                        <div class="row">
                            <div class="col-md-4">
                                <div class="pv-form-group">
                                    <label class="pv-form-label">Nombre del Contacto</label>
                                    <input type="text" name="contacto_nombre" class="pv-form-control" 
                                           maxlength="100" placeholder="Nombre completo">
                                </div>
                            </div>
                            
                            <div class="col-md-4">
                                <div class="pv-form-group">
                                    <label class="pv-form-label">Teléfono de Contacto</label>
                                    <input type="tel" name="contacto_telefono" class="pv-form-control" 
                                           maxlength="15" placeholder="Teléfono del contacto">
                                </div>
                            </div>
                            
                            <div class="col-md-4">
                                <div class="pv-form-group">
                                    <label class="pv-form-label">Email de Contacto</label>
                                    <input type="email" name="contacto_email" class="pv-form-control" 
                                           maxlength="100" placeholder="correo@contacto.com">
                                </div>
                            </div>
                        </div>
                        
                        <div class="alert alert-info mt-4">
                            <i class="fas fa-info-circle me-2"></i>
                            <strong>Información importante:</strong>
                            <ul class="mb-0 mt-2">
                                <li>El RUC debe ser válido y único en el sistema</li>
                                <li>Los datos de contacto son opcionales pero recomendados</li>
                                <li>Todos los campos marcados con <span class="text-danger">*</span> son obligatorios</li>
                            </ul>
                        </div>
                        
                        <div class="d-flex justify-content-end gap-2 mt-4">
                            <a href="index.php?controller=proveedor&action=index" class="btn btn-outline-secondary px-4">
                                Cancelar
                            </a>
                            <button type="submit" class="btn btn-primary px-4">
                                <i class="fas fa-save me-2"></i>Guardar Proveedor
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

<script src="<?= BASE_URL ?>/views/inventario/proveedores/js/proveedores.js"></script>

