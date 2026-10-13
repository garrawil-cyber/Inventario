<?php
// ============================================================
//  config.php — Configuración central del sistema INVENTARIO
//  Incluir PRIMERO en index.php, antes de cualquier otro archivo
// ============================================================

// ── 1. ENTORNO ───────────────────────────────────────────────
// Cambiar a 'production' al desplegar en servidor real
define('APP_ENV', 'development');

// ── 2. REPORTE DE ERRORES ────────────────────────────────────
if (APP_ENV === 'development') {
    ini_set('display_errors', 1);
    ini_set('display_startup_errors', 1);
    error_reporting(E_ALL);
} else {
    ini_set('display_errors', 0);
    ini_set('display_startup_errors', 0);
    error_reporting(0);
    // En producción: registrar errores en archivo de log
    ini_set('log_errors', 1);
    ini_set('error_log', __DIR__ . '/../logs/error.log');
}

// ── 3. ZONA HORARIA ──────────────────────────────────────────
date_default_timezone_set('America/Lima');

// ── 4. RUTAS DEL PROYECTO ────────────────────────────────────
define('ROOT_PATH',        __DIR__ . '/..');           // Raíz del proyecto
define('CONFIG_PATH',      __DIR__);                   // /config
define('CONTROLLERS_PATH', ROOT_PATH . '/controllers');
define('MODELS_PATH',      ROOT_PATH . '/models');
define('VIEWS_PATH',       ROOT_PATH . '/views');
define('HELPERS_PATH',     ROOT_PATH . '/app/Helpers');
define('LOGS_PATH',        ROOT_PATH . '/logs');

// ── 5. URL BASE ──────────────────────────────────────────────
// Se detecta automáticamente. Puedes forzarla si es necesario:
// define('BASE_URL', 'http://localhost/INVENTARIO');
if (!defined('BASE_URL')) {
    $protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
    $host     = $_SERVER['HTTP_HOST']   ?? 'localhost';
    $script   = $_SERVER['SCRIPT_NAME'] ?? '/index.php';
    // Obtiene el directorio raíz del proyecto (sin /index.php)
    define('BASE_URL', $protocol . '://' . $host . rtrim(dirname($script), '/'));
}

// ── 6. BASE DE DATOS ─────────────────────────────────────────
define('DB_HOST',    'localhost');
define('DB_NAME',    'ropavirtual_db');
define('DB_USER',    'root');
define('DB_PASS',    '');
define('DB_CHARSET', 'utf8mb4');

// ── 7. SESIÓN ────────────────────────────────────────────────
define('SESSION_NAME',     'INVENTARIO_SESSION');
define('SESSION_LIFETIME', 7200);   // 2 horas en segundos
define('SESSION_SECURE',   false);  // true solo si usas HTTPS
define('SESSION_HTTPONLY',  true);

// ── 8. SISTEMA ───────────────────────────────────────────────
define('APP_NAME',     'Sistema de Inventario');
define('APP_VERSION',  '1.0.0');
define('APP_CURRENCY', 'S/');       // Sol peruano

// ── 9. CONTROLADOR Y ACCIÓN POR DEFECTO ─────────────────────
define('DEFAULT_CONTROLLER', 'dashboard');
define('DEFAULT_ACTION',     'index');

// ── 10. PAGINACIÓN ───────────────────────────────────────────
define('ITEMS_PER_PAGE', 20);

// ── 11. CARGA DE ARCHIVOS BASE ───────────────────────────────
require_once CONFIG_PATH . '/autoload.php';
require_once CONFIG_PATH . '/database.php';
