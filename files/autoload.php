<?php
// ============================================================
//  autoload.php — Carga automática de clases del sistema
//  Soporta: clases con namespace (App\...) y sin namespace
//  Requerido por config.php — NO incluir directamente
// ============================================================

// ── Mapa de namespaces → directorios (PSR-4) ─────────────────
//    Agrega aquí cualquier nuevo namespace que crees
$namespacesMap = [
    'App\\Helpers\\'    => ROOT_PATH . '/app/Helpers/',
    'App\\Models\\'     => ROOT_PATH . '/models/',
    'App\\Controllers\\' => ROOT_PATH . '/controllers/',
    'App\\Config\\'     => ROOT_PATH . '/config/',
];

// ── Mapa de clases sin namespace → archivo exacto ────────────
//    Para clases legacy que no usan namespace
$classesMap = [
    // Modelos
    'Producto'           => MODELS_PATH . '/Producto.php',
    'Marca'              => MODELS_PATH . '/Marca.php',
    'Categoria'          => MODELS_PATH . '/Categoria.php',
    'Almacen'            => MODELS_PATH . '/Almacen.php',
    'Proveedor'          => MODELS_PATH . '/Proveedor.php',
    'Cliente'            => MODELS_PATH . '/Cliente.php',
    'Venta'              => MODELS_PATH . '/Venta.php',
    'Kardex'             => MODELS_PATH . '/Kardex.php',
    'Reporte'            => MODELS_PATH . '/Reporte.php',

    // Controladores
    'ProductoController'  => CONTROLLERS_PATH . '/ProductoController.php',
    'DashboardController' => CONTROLLERS_PATH . '/DashboardController.php',
    'VentaController'     => CONTROLLERS_PATH . '/VentaController.php',
    'ClienteController'   => CONTROLLERS_PATH . '/ClienteController.php',
    'KardexController'    => CONTROLLERS_PATH . '/KardexController.php',
    'ReporteController'   => CONTROLLERS_PATH . '/ReporteController.php',
    'AuthController'      => CONTROLLERS_PATH . '/AuthController.php',

    // Config / Infraestructura
    'Database'            => CONFIG_PATH . '/database.php',
    'SessionMiddleware'   => CONFIG_PATH . '/SessionMiddleware.php',
];

// ── Registrar el autoloader ──────────────────────────────────
spl_autoload_register(function (string $className) use ($namespacesMap, $classesMap) {

    // 1️⃣  Buscar en el mapa exacto de clases sin namespace
    if (isset($classesMap[$className])) {
        $file = $classesMap[$className];
        if (file_exists($file)) {
            require_once $file;
            return;
        }
    }

    // 2️⃣  Buscar por prefijo de namespace
    foreach ($namespacesMap as $prefix => $baseDir) {
        if (strncmp($prefix, $className, strlen($prefix)) !== 0) {
            continue;
        }
        // Obtener la parte relativa del nombre de clase
        $relativeClass = substr($className, strlen($prefix));

        // Construir la ruta al archivo
        $file = $baseDir . str_replace('\\', '/', $relativeClass) . '.php';

        if (file_exists($file)) {
            require_once $file;
            return;
        }
    }

    // 3️⃣  Fallback: buscar en directorios comunes por convención de nombre
    $fallbackDirs = [
        CONTROLLERS_PATH,
        MODELS_PATH,
        CONFIG_PATH,
        HELPERS_PATH,
    ];

    // Tomar solo la última parte del nombre (sin namespace)
    $parts     = explode('\\', $className);
    $shortName = end($parts);

    foreach ($fallbackDirs as $dir) {
        $file = $dir . '/' . $shortName . '.php';
        if (file_exists($file)) {
            require_once $file;
            return;
        }
    }

    // Si no se encuentra, PHP lanzará un error estándar de "Class not found"
});
