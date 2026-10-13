<?php
/**
 * Permissions.php - Sistema de Gestión de Permisos por Rol
 * 
 * Versión depurada con permisos consistentes para:
 * - admin: Control total
 * - supervisor: Gestión completa sin eliminaciones
 * - vendedor: Enfocado en ventas y consultas
 * - almacenero: Control de inventario y movimientos
 */

class Permissions {
    
    /**
     * Matriz de permisos por rol
     * Estructura: $permissions[rol][controlador] = [acciones_permitidas]
     */
    private static $permissions = [
        // 👑 ADMINISTRADOR - Acceso Total
        'admin' => [
            'dashboard' => ['index', 'datos_graficos', 'resumen_rapido'],
            'producto' => ['index', 'ver', 'obtener_detalles'],
            'movimiento' => ['index', 'entrada', 'procesar_entrada', 'salida', 'procesar_salida', 'ver', 'exportar'],
            'kardex' => ['index', 'producto', 'almacen', 'pdf', 'exportar', 'costo_promedio'],
            'alerta' => ['index', 'ver', 'atender', 'ignorar', 'crear', 'guardar', 'limpiar', 'generar_automaticas', 'estadisticas'],
            'reporte' => ['stock_bajo', 'rotacion', 'valorizacion', 'top_vendidos', 'movimientos_dia', 'proveedores', 'productos_sin_movimiento', 'exportar', 'dashboard'],
            'categoria' => ['index', 'crear', 'guardar', 'editar', 'actualizar', 'eliminar', 'activar', 'verificar_codigo'],
            'proveedor' => ['index', 'crear', 'guardar', 'editar', 'actualizar', 'eliminar', 'activar', 'ver', 'verificar_ruc'],
            'almacen' => ['index', 'crear', 'guardar', 'editar', 'actualizar', 'eliminar', 'activar', 'ver', 'capacidad', 'verificar_codigo'],
            'inventario' => ['index','ver', 'crear', 'guardar', 'iniciar', 'finalizar', 'realizar','guardarConteo','getProductos','agregarProducto','actualizarProducto',
                      'eliminarProducto','getDetalle','resumen', 'exportar', 'resultados', 'pdf'],
            'cliente' => ['index', 'crear', 'guardar', 'editar', 'actualizar', 'eliminar', 'activar', 'buscarPorDocumento', 'ver', 'getClientesJson', 'autocomplete', 'exportar'],
            'ventas' => ['index', 'ver', 'crear', 'guardar', 'anular', 'exportar', 'getEstadisticasJson'],
            'compra' => ['index', 'ver', 'crear', 'guardar', 'anular', 'getComprasJson', 'autocomplete', 'exportar'],
            'marca' => ['index', 'crear', 'guardar', 'editar', 'actualizar', 'eliminar', 'ver','activar', 'verificar_nombre', 'exportar'],
            'auth' => ['logout'],
            'usuario' => ['index', 'ver', 'crear', 'guardar', 'editar',
                          'actualizar', 'eliminar', 'cambiarpasword', 'getforselect'],
        ],
        
        // 👔 SUPERVISOR - Gestión Completa sin Eliminaciones
        'supervisor' => [
            'dashboard' => ['index', 'datos_graficos'],
            'producto' => ['index', 'ver', 'obtener_detalles'],
            'movimiento' => ['index', 'entrada', 'procesar_entrada', 'salida', 'procesar_salida', 'ver'],
            'kardex' => ['index', 'producto', 'almacen'],
            'alerta' => ['index', 'ver', 'atender', 'ignorar', 'crear', 'guardar'],
            'reporte' => ['stock_bajo', 'rotacion', 'valorizacion', 'dashboard'],
            'categoria' => ['index', 'crear', 'guardar', 'editar', 'actualizar'],
            'proveedor' => ['index', 'crear', 'guardar', 'editar', 'actualizar', 'ver'],
            'almacen' => ['index', 'crear', 'guardar', 'editar', 'actualizar', 'ver'],
            'inventario' => ['index', 'crear', 'realizar', 'guardar_conteo'],
            'cliente' => ['index', 'create', 'store', 'edit', 'update', 'ver', 'getClientesJson', 'autocomplete', 'exportar', 'delete'],
            'ventas' => ['index', 'ver', 'crear', 'registrar', 'anular', 'exportar',
                        'getestadisticasjson', 'getsiguientenumero',
                        'buscarproductos', 'listarproductosmodal', 'getproducto'],
            
            'compra' => ['index', 'ver', 'crear', 'guardar', 'getComprasJson', 'autocomplete'],
            'marca' => ['index', 'crear', 'guardar', 'editar', 'actualizar'],
            'auth' => ['logout'],
        ],
        
        // 💼 VENDEDOR - Orientado a Ventas y Consultas
        'vendedor' => [
            'dashboard' => ['index'],
            'producto' => ['index', 'ver'],
            'movimiento' => ['salida', 'procesar_salida'],  // Solo salidas (ventas)
            'kardex' => ['index', 'producto'],
            'alerta' => ['index', 'ver'],
            'reporte' => ['stock_bajo', 'top_vendidos', 'dashboard'],
            'ventas' => ['index', 'ver', 'crear', 'registrar', 'anular', 'exportar',
                        'getestadisticasjson', 'getsiguientenumero',
                        'buscarproductos', 'listarproductosmodal', 'getproducto'],
            'cliente' => ['index', 'create', 'store', 'edit', 'update', 'ver', 'getClientesJson', 'autocomplete', 'exportar', 'delete'],
            'marca' => ['index', 'ver'],
            'auth' => ['logout'],
            // Sin acceso a: categoria, proveedor, almacen, inventariofisico, compras
        ],
        
        // 📦 ALMACENERO - Control de Inventario y Almacén
        'almacenero' => [
            'dashboard' => ['index'],
            'producto' => ['index', 'ver', 'crear', 'guardar', 'editar', 'actualizar','eliminar', 'obtener_detalles','get_ubicaciones'],
            'movimiento' => ['index', 'entrada', 'procesar_entrada', 'salida', 'procesar_salida', 'ver'],
            'kardex' => ['index', 'producto', 'almacen', 'pdf', 'exportar', 'costo_promedio', 'buscar_productos', 'resumen_rapido'],
            'alerta' => ['index', 'ver', 'atender', 'ignorar', 'atenderMultiple', 'crear', 'guardar', 'activar', 'limpiar', 'generar_automaticas', 'estadisticas'],
            'reporte' => ['stock_bajo', 'dashboard'],
            'inventario' => ['index','ver', 'crear', 'guardar', 'iniciar', 'finalizar', 'realizar','guardarConteo','getProductos','agregarProducto','actualizarProducto',
                      'eliminarProducto','getDetalle','resumen', 'exportar', 'resultados','pdf'],
            'cliente' => ['index', 'crear', 'guardar', 'editar', 'actualizar', 'eliminar', 'activar', 'buscarPorDocumento', 'ver', 'getClientesJson', 'autocomplete', 'exportar'],
            'compra' => ['index', 'ver', 'crear', 'guardar', 'anular', 'getComprasJson', 'autocomplete', 'exportar'],
            'marca' => ['index', 'crear', 'guardar', 'editar', 'actualizar', 'eliminar', 'ver','activar', 'verificar_nombre', 'exportar'],
            'categoria' => ['index', 'crear', 'guardar', 'editar', 'actualizar', 'eliminar', 'activar', 'verificar_codigo'],
            'proveedor' => ['index', 'crear', 'guardar', 'editar', 'actualizar', 'eliminar', 'activar', 'ver', 'verificar_ruc'],
            'almacen' => ['index', 'crear', 'guardar', 'editar', 'actualizar', 'eliminar', 'activar', 'ver', 'capacidad', 'verificar_codigo'],
            'auth' => ['logout'],
            // Sin acceso a: categoria, proveedor, almacen
        ]
    ];
    
    /**
     * Obtener todos los roles disponibles
     * @return array Lista de roles
     */
    public static function getAllRoles() {
        return array_keys(self::$permissions);
    }
    
    /**
     * Verificar si un rol es válido
     * @param string $rol Rol a verificar
     * @return bool
     */
    public static function isValidRole($rol) {
        return isset(self::$permissions[$rol]);
    }
    
    /**
     * Verificar permiso y redirigir si no tiene acceso
     * @param string $controller Controlador
     * @param string $action Acción
     * @param string|null $rol Rol del usuario (opcional)
     */
    public static function checkPermission($controller, $action, $rol = null) {
        // Sanitizar parámetros
        $controller = preg_replace('/[^a-z0-9_]/i', '', strtolower($controller));
        $action = preg_replace('/[^a-z0-9_]/i', '', strtolower($action));
        
        // Si no se proporciona rol, obtener de sesión
        if ($rol === null) {
            $rol = $_SESSION['usuario_rol'] ?? 'vendedor';
        }
        
        // Validar que el rol existe, si no, usar vendedor como fallback
        if (!isset(self::$permissions[$rol])) {
            $rol = 'vendedor';
        }
        
        // Verificar permiso
        if (!self::hasPermission($rol, $controller, $action)) {
            http_response_code(403);
            // Redirigir a página 403
            $basePath = rtrim(dirname($_SERVER['SCRIPT_NAME']), '/');
            header('Location: ' . $basePath . '/views/layouts/403-view.php');
            exit();
        }
    }
    
    /**
     * Verificar si un rol tiene permiso para una acción específica
     * @param string $rol Rol del usuario
     * @param string $controller Controlador
     * @param string $action Acción
     * @return bool
     */
    public static function hasPermission($rol, $controller, $action) {
    if ($rol === 'admin') return true;
    if (!isset(self::$permissions[$rol])) return false;

    $controller = strtolower($controller);
    $action     = strtolower($action);

    if (!isset(self::$permissions[$rol][$controller])) return false;

    // ✅ Normalizar la matriz también al comparar
    $accionesPermitidas = array_map('strtolower', self::$permissions[$rol][$controller]);
    return in_array($action, $accionesPermitidas);
}
    
    /**
     * Verificar si un rol puede acceder a un módulo completo
     * @param string $rol Rol del usuario
     * @param string $controller Controlador
     * @return bool
     */
    public static function canAccessModule($rol, $controller) {
        if ($rol === 'admin') {
            return true;
        }
        
        $controller = strtolower($controller);
        return isset(self::$permissions[$rol][$controller]);
    }
    
    /**
     * Obtener todos los permisos de un rol
     * @param string $rol Rol del usuario
     * @return array Matriz de permisos del rol
     */
    public static function getRolePermissions($rol) {
        if ($rol === 'admin') {
            // Para admin, retornar una estructura especial que indica acceso total
            return ['*' => ['*']];
        }
        return self::$permissions[$rol] ?? [];
    }
    
    /**
     * Obtener acciones permitidas para un controlador específico
     * @param string $rol Rol del usuario
     * @param string $controller Controlador
     * @return array Lista de acciones permitidas
     */
    public static function getAllowedActions($rol, $controller) {
        if ($rol === 'admin') {
            // Admin tiene todas las acciones posibles
            return ['index', 'create', 'store', 'edit', 'update', 'delete', 'ver', 
                    'getClientesJson', 'autocomplete', 'exportar', 'dashboard',
                    'datos_graficos', 'resumen_rapido', 'obtener_detalles', 'entrada',
                    'procesar_entrada', 'salida', 'procesar_salida', 'pdf', 'costo_promedio',
                    'atender', 'ignorar', 'limpiar', 'generar_automaticas', 'estadisticas',
                    'stock_bajo', 'rotacion', 'valorizacion', 'top_vendidos', 'movimientos_dia',
                    'proveedores', 'productos_sin_movimiento', 'activar', 'verificar_codigo',
                    'verificar_ruc', 'capacidad', 'realizar', 'guardar_conteo', 'resultados',
                    'historial', 'anular', 'getEstadisticasJson',
                    'logout', 'guardar', 'crear', 'actualizar', 'eliminar',
                    'verificar_nombre', 'buscar_productos', 'get_ubicaciones',
                    'atenderMultiple', 'getComprasJson'];
        }
        
        $controller = strtolower($controller);
        if (isset(self::$permissions[$rol][$controller])) {
            return self::$permissions[$rol][$controller];
        }
        
        return [];
    }
    
    /**
     * Obtener menú según rol del usuario
     * @param string $rol Rol del usuario
     * @return array Estructura del menú
     */
    public static function getMenuByRole($rol) {
        // Validar rol
        if (!isset(self::$permissions[$rol])) {
            $rol = 'vendedor';
        }
        
        $menus = [
            // 👑 Administrador
            'admin' => [
                ['title' => 'Dashboard', 'icon' => 'fas fa-tachometer-alt', 'controller' => 'dashboard', 'action' => 'index'],
                ['title' => 'Productos', 'icon' => 'fas fa-boxes', 'controller' => 'producto', 'action' => 'index'],
                ['title' => 'Movimientos', 'icon' => 'fas fa-exchange-alt', 'controller' => 'movimiento', 'action' => 'index'],
                ['title' => 'Ventas', 'icon' => 'fas fa-shopping-cart', 'controller' => 'ventas', 'action' => 'index'],
                ['title' => 'Compras', 'icon' => 'fas fa-truck', 'controller' => 'compra', 'action' => 'index'],
                ['title' => 'Kardex', 'icon' => 'fas fa-book', 'controller' => 'kardex', 'action' => 'index'],
                ['title' => 'Alertas', 'icon' => 'fas fa-exclamation-triangle', 'controller' => 'alerta', 'action' => 'index'],
                ['title' => 'Reportes', 'icon' => 'fas fa-chart-bar', 'controller' => 'reporte', 'action' => 'index'],
                ['title' => 'Clientes', 'icon' => 'fas fa-users', 'controller' => 'cliente', 'action' => 'index'],
                ['title' => 'Inventario Físico', 'icon' => 'fas fa-clipboard-check', 'controller' => 'inventario', 'action' => 'index'],
                ['title' => 'Catálogos', 'icon' => 'fas fa-folder', 'submenu' => [
                    ['title' => 'Categorías', 'icon' => 'fas fa-tags', 'controller' => 'categoria', 'action' => 'index'],
                    ['title' => 'Proveedores', 'icon' => 'fas fa-building', 'controller' => 'proveedor', 'action' => 'index'],
                    ['title' => 'Almacenes', 'icon' => 'fas fa-warehouse', 'controller' => 'almacen', 'action' => 'index'],
                    ['title' => 'Ubicaciones', 'icon' => 'fas fa-city', 'controller' => 'ubicacion', 'action' => 'index'],
                    ['title' => 'Marcas', 'icon' => 'fas fa-tag', 'controller' => 'marca', 'action' => 'index'],
                    ['title' => 'Usuarios', 'icon' => 'fas fa-user', 'controller' => 'usuario', 'action' => 'index']
                ]]
            ],
            
            // 👔 Supervisor
            'supervisor' => [
                ['title' => 'Dashboard', 'icon' => 'fas fa-tachometer-alt', 'controller' => 'dashboard', 'action' => 'index'],
                ['title' => 'Productos', 'icon' => 'fas fa-boxes', 'controller' => 'producto', 'action' => 'index'],
                ['title' => 'Movimientos', 'icon' => 'fas fa-exchange-alt', 'controller' => 'movimiento', 'action' => 'index'],
                ['title' => 'Ventas', 'icon' => 'fas fa-shopping-cart', 'controller' => 'ventas', 'action' => 'index'],
                ['title' => 'Compras', 'icon' => 'fas fa-truck', 'controller' => 'compra', 'action' => 'index'],
                ['title' => 'Kardex', 'icon' => 'fas fa-book', 'controller' => 'kardex', 'action' => 'index'],
                ['title' => 'Alertas', 'icon' => 'fas fa-exclamation-triangle', 'controller' => 'alerta', 'action' => 'index'],
                ['title' => 'Reportes', 'icon' => 'fas fa-chart-bar', 'controller' => 'reporte', 'action' => 'index'],
                ['title' => 'Clientes', 'icon' => 'fas fa-users', 'controller' => 'cliente', 'action' => 'index'],
                ['title' => 'Inventario Físico', 'icon' => 'fas fa-clipboard-check', 'controller' => 'inventario', 'action' => 'index'],
                ['title' => 'Catálogos', 'icon' => 'fas fa-folder', 'submenu' => [
                    ['title' => 'Categorías', 'icon' => 'fas fa-tags', 'controller' => 'categoria', 'action' => 'index'],
                    ['title' => 'Proveedores', 'icon' => 'fas fa-building', 'controller' => 'proveedor', 'action' => 'index'],
                    ['title' => 'Almacenes', 'icon' => 'fas fa-warehouse', 'controller' => 'almacen', 'action' => 'index']
                    
                ]]
            ],
            
            // 💼 Vendedor
            'vendedor' => [
                ['title' => 'Dashboard', 'icon' => 'fas fa-tachometer-alt', 'controller' => 'dashboard', 'action' => 'index'],
                ['title' => 'Productos', 'icon' => 'fas fa-boxes', 'controller' => 'producto', 'action' => 'index'],
                ['title' => 'Ventas', 'icon' => 'fas fa-shopping-cart', 'controller' => 'ventas', 'action' => 'index'],
                ['title' => 'Clientes', 'icon' => 'fas fa-users', 'controller' => 'cliente', 'action' => 'index'],
                ['title' => 'Kardex', 'icon' => 'fas fa-book', 'controller' => 'kardex', 'action' => 'index'],
                ['title' => 'Reportes', 'icon' => 'fas fa-chart-bar', 'submenu' => [
                    ['title' => 'Stock Bajo', 'icon' => 'fas fa-exclamation-circle', 'controller' => 'reporte', 'action' => 'stock_bajo'],
                    ['title' => 'Top Vendidos', 'icon' => 'fas fa-chart-line', 'controller' => 'reporte', 'action' => 'top_vendidos']
                ]]
            ],
            
            // 📦 Almacenero
            'almacenero' => [
                ['title' => 'Dashboard', 'icon' => 'fas fa-tachometer-alt', 'controller' => 'dashboard', 'action' => 'index'],
                ['title' => 'Productos', 'icon' => 'fas fa-boxes', 'controller' => 'producto', 'action' => 'index'],
                ['title' => 'Movimientos', 'icon' => 'fas fa-exchange-alt', 'controller' => 'movimiento', 'action' => 'index'],
                ['title' => 'Kardex', 'icon' => 'fas fa-book', 'controller' => 'kardex', 'action' => 'index'],
                ['title' => 'Inventario Físico', 'icon' => 'fas fa-clipboard-check', 'controller' => 'inventario', 'action' => 'index'],
                ['title' => 'Alertas', 'icon' => 'fas fa-exclamation-triangle', 'controller' => 'alerta', 'action' => 'index'],
                ['title' => 'Compras', 'icon' => 'fas fa-truck', 'controller' => 'compra', 'action' => 'index'],
                ['title' => 'Catálogos', 'icon' => 'fas fa-folder', 'submenu' => [
                    ['title' => 'Productos', 'icon' => 'fas fa-boxes', 'controller' => 'producto', 'action' => 'index'],
                    ['title' => 'Clientes', 'icon' => 'fas fa-users', 'controller' => 'cliente', 'action' => 'index'],
                    ['title' => 'Categorías', 'icon' => 'fas fa-tags', 'controller' => 'categoria', 'action' => 'index'],
                    ['title' => 'Proveedores', 'icon' => 'fas fa-building', 'controller' => 'proveedor', 'action' => 'index'],
                    ['title' => 'Almacenes', 'icon' => 'fas fa-warehouse', 'controller' => 'almacen', 'action' => 'index']
                    
                ]],
                ['title' => 'Reportes', 'icon' => 'fas fa-chart-bar', 'submenu' => [
                    ['title' => 'Stock Bajo', 'icon' => 'fas fa-exclamation-circle', 'controller' => 'reporte', 'action' => 'stock_bajo'],
                    ['title' => 'Marcas', 'icon' => 'fas fa-tag', 'controller' => 'marca', 'action' => 'index']
                ]]
            ]
        ];
        
        return $menus[$rol] ?? $menus['vendedor'];
    }
    
    /**
     * Obtener descripción de un rol
     * @param string $rol Rol del usuario
     * @return string Descripción del rol
     */
    public static function getRoleDescription($rol) {
        $descriptions = [
            'admin' => 'Administrador - Acceso total al sistema',
            'supervisor' => 'Supervisor - Gestión completa sin eliminaciones',
            'vendedor' => 'Vendedor - Orientado a ventas y consultas',
            'almacenero' => 'Almacenero - Control de inventario y movimientos'
        ];
        
        return $descriptions[$rol] ?? 'Usuario del sistema';
    }
    
    /**
     * Verificar si un rol puede realizar una acción específica (método helper)
     * @param string $rol Rol del usuario
     * @param string $permission Permiso en formato "controlador.accion"
     * @return bool
     */
    public static function can($rol, $permission) {
        $parts = explode('.', $permission);
        if (count($parts) !== 2) {
            return false;
        }
        
        $controller = $parts[0];
        $action = $parts[1];
        
        return self::hasPermission($rol, $controller, $action);
    }
}
?>