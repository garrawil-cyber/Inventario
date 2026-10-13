<?php
/**
 * AuthController.php
 * ------------------
 * Gestiona autenticación: login, logout.
 * Ubicación sugerida: controllers/AuthController.php
 *
 * Rutas:
 *   Login  → index.php?controller=auth&action=login
 *   Logout → index.php?controller=auth&action=logout
 */

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/SessionMiddleware.php';

class AuthController
{
    /**
     * Cierra la sesión activa y redirige al login.
     *
     * Protección CSRF: verifica el token enviado por el enlace
     * de "Cerrar Sesión" en el navbar. Si el token no coincide
     * (petición forjada desde otro origen), se ignora la acción.
     */
    public function logout(): void
    {
        SessionMiddleware::startSession();

        // ── Validación CSRF ──────────────────────────────────────────────────
        $tokenRecibido  = $_GET['_token'] ?? $_POST['_token'] ?? '';
        $tokenSesion    = $_SESSION['csrf_token'] ?? '';

        if (empty($tokenRecibido) || !hash_equals($tokenSesion, $tokenRecibido)) {
            // Token inválido: redirigir al dashboard sin cerrar sesión
            header('Location: ' . BASE_URL . '/index.php?controller=dashboard&action=index');
            exit();
        }

        // ── Destruir sesión ──────────────────────────────────────────────────

        // 1. Limpiar variables de sesión
        $_SESSION = [];

        // 2. Eliminar cookie de sesión del navegador
        if (ini_get('session.use_cookies')) {
            $params = session_get_cookie_params();
            setcookie(
                session_name(),
                '',
                time() - 42000,
                $params['path'],
                $params['domain'],
                $params['secure'],
                $params['httponly']
            );
        }

        // 3. Destruir datos en el servidor
        session_destroy();

        // ── Redirigir al login ───────────────────────────────────────────────
        header('Location: ' . BASE_URL . '/login.php');
        exit();
    }

    /**
     * Genera (o reutiliza) un token CSRF para la sesión actual.
     * Llamar en SessionMiddleware::startSession() o al inicio de cada vista.
     */
public static function getCsrfToken(): string {
    SessionMiddleware::startSession();
    
    // Usar el método reusable que no acumula tokens
    if (empty($_SESSION['csrf_token_single'])) {
        $_SESSION['csrf_token_single'] = bin2hex(random_bytes(32));
        $_SESSION['csrf_token_single_time'] = time();
    }
    
    // Renovar si pasó 1 hora
    if (time() - $_SESSION['csrf_token_single_time'] > 3600) {
        $_SESSION['csrf_token_single'] = bin2hex(random_bytes(32));
        $_SESSION['csrf_token_single_time'] = time();
    }
    
    return $_SESSION['csrf_token_single'];
}
}