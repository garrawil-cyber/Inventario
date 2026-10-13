<?php
/**
 * logout.php — Stub de compatibilidad
 * ------------------------------------
 * Este archivo YA NO debe llamarse directamente desde el navegador.
 * El .htaccess lo bloquea con [F] cuando RewriteEngine está activo.
 *
 * El cierre de sesión se gestiona exclusivamente a través del
 * front-controller:
 *
 *   index.php?controller=auth&action=logout
 *
 * Este archivo se mantiene ÚNICAMENTE como respaldo para entornos
 * sin mod_rewrite activo (p. ej. desarrollo local sin Apache).
 * En producción con el .htaccess correcto, nunca se ejecuta.
 */

// Arrancar sesión de forma segura usando el middleware del sistema
require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/config/SessionMiddleware.php';

SessionMiddleware::startSession();

// ── Destruir sesión ──────────────────────────────────────────────────────────

// 1. Limpiar todas las variables de sesión
$_SESSION = [];

// 2. Eliminar la cookie de sesión del navegador del cliente
if (ini_get('session.use_cookies')) {
    $params = session_get_cookie_params();
    setcookie(
        session_name(),
        '',
        time() - 42000,         // Tiempo en el pasado → fuerza expiración inmediata
        $params['path'],
        $params['domain'],
        $params['secure'],
        $params['httponly']
    );
}

// 3. Destruir los datos de sesión en el servidor
session_destroy();

// ── Redirigir al login ───────────────────────────────────────────────────────
header('Location: ' . BASE_URL . '/login.php');
exit();