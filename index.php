<?php
/**
 * Front Controller — index.php  VERSIÓN DEPURADA
 * Correcciones aplicadas:
 *  1. $controller, $action e $id sanitizados inmediatamente tras leerlos de $_GET.
 *     Permissions::checkPermission() sanitizaba copias locales internas, pero las
 *     variables de routing seguían siendo los valores crudos del usuario.
 *  2. Bloque catch: reemplazado echo del mensaje interno por respuesta genérica
 *     segura; el detalle ya queda en error_log().
 *  3. Default de $action cambiado a 'dashboard' cuando el controlador es 'reporte',
 *     ya que ReporteController no implementa index() y esa acción no figura en
 *     la tabla de permisos de ningún rol.
 *  4. Eliminado residuo de depuración: //die($controllerFile).
 *  5. [Nota de diseño] checkAuth() ya se ejecuta aquí antes de instanciar ningún
 *     controlador. Los controladores que repiten esa llamada en su constructor
 *     son redundantes (aunque inocuos). Se recomienda eliminar esa llamada de los
 *     constructores para mantener la responsabilidad en el front controller.
 */

require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/config/SessionMiddleware.php';
require_once __DIR__ . '/config/permissions.php';

// ✅ AÑADIR: detectar si es petición AJAX aquí, en el router
$isAjax = !empty($_SERVER['HTTP_X_REQUESTED_WITH'])
       && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest';

SessionMiddleware::checkAuth();

// ── Información del usuario autenticado ──────────────────────────────────────
$userInfo = SessionMiddleware::getUserInfo();

// ── Parámetros de routing ─────────────────────────────────────────────────────
// [FIX 1] Se sanitizan aquí, sobre las mismas variables que se usarán en todo
// el resto del archivo: lookup en $controllers, method_exists() y la llamada
// dinámica $controllerInstance->$action(). El mismo patrón que usa internamente
// Permissions::checkPermission(), ahora aplicado en origen.
$controller = preg_replace('/[^a-z0-9_]/i', '', $_GET['controller'] ?? 'dashboard');
$action     = preg_replace('/[^a-z0-9_]/i', '', $_GET['action']     ?? 'index');
$id         = isset($_GET['id']) ? preg_replace('/[^a-z0-9_\-]/i', '', $_GET['id']) : null;

// [FIX 3] El módulo 'reporte' no tiene acción 'index': ni método en el controlador
// ni entrada en la tabla de permisos. Se redirige al punto de entrada correcto.
if ($controller === 'reporte' && $action === 'index') {
    $action = 'dashboard';
}

// ── Verificación de permisos ──────────────────────────────────────────────────
// Debe ejecutarse DESPUÉS de sanitizar, para que el valor evaluado sea
// idéntico al que se usará en el routing.
Permissions::checkPermission($controller, $action);

// ── Mapeo de controladores ────────────────────────────────────────────────────
$controllers = [
    'dashboard'        => 'DashboardController',
    'producto'         => 'ProductoController',
    'movimiento'       => 'MovimientoController',
    'kardex'           => 'KardexController',
    'alerta'           => 'AlertaController',
    'reporte'          => 'ReporteController',
    'inventario' => 'InventarioFisicoController',
    'categoria'        => 'CategoriaController',
    'proveedor'        => 'ProveedorController',
    'almacen'          => 'AlmacenController',
    'cliente' => 'ClienteController', 
    'compra' => 'CompraController', 
    'ventas' => 'VentasController', 
    'marca' => 'MarcaController',
    'auth' => 'AuthController',
    'usuario' => 'UsuarioController',
    'ubicacion' => 'UbicacionController',
];

// ── Resolución del controlador ────────────────────────────────────────────────
if (!isset($controllers[$controller])) {
    http_response_code(404);
    $basePath = rtrim(dirname($_SERVER['SCRIPT_NAME']), '/');
    header('Location: ' . $basePath . '/views/layouts/404-view.php');
    exit();
}

$controllerClass = $controllers[$controller];
$controllerFile  = __DIR__ . "/controllers/$controllerClass.php";

if (!file_exists($controllerFile)) {
    http_response_code(404);
    $basePath = rtrim(dirname($_SERVER['SCRIPT_NAME']), '/');
    header('Location: ' . $basePath . '/views/layouts/404-view.php');
    exit();
}

require_once $controllerFile;

// ── Despacho de la acción ─────────────────────────────────────────────────────
try {
    $controllerInstance = new $controllerClass();

    if (!method_exists($controllerInstance, $action)) {
        http_response_code(404);
        die('Acción no encontrada.');
    }

    // Pasar $id al método solo cuando viene en la URL
    if ($id !== null) {
        $controllerInstance->$action($id);
    } else {
        $controllerInstance->$action();
    }

} catch (Exception $e) {
    error_log("Error en $controllerClass::$action — " . $e->getMessage()
              . " | Trace: " . $e->getTraceAsString());

    // ✅ AÑADIR: si es AJAX, responder JSON — nunca redirigir a HTML
    if ($isAjax) {
        if (ob_get_level()) ob_clean();
        header('Content-Type: application/json');
        http_response_code(500);
        echo json_encode([
            'success' => false,
            'error'   => 'Error interno del servidor'
        ]);
        exit();
    }

    // Solo para peticiones normales: redirigir a página de error
    http_response_code(500);
    $basePath = rtrim(dirname($_SERVER['SCRIPT_NAME']), '/');
    header('Location: ' . $basePath . '/views/layouts/500-view.php');
    exit();
}

?>