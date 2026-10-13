<?php
/**
 * SessionMiddleware.php — Manejo de sesiones, autenticación y mensajes flash
 * 
 * Versión optimizada para el módulo de Inventario Físico
 * 
 * Cambios principales:
 *   - validateCSRFTokenReusable(): NO consume el token (múltiples usos)
 *   - generateCSRFToken(): purga tokens expirados automáticamente
 *   - Soporte completo para mensajes SweetAlert
 *   - Manejo seguro de sesión con regeneración de ID
 */

if (class_exists('SessionMiddleware')) return;

class SessionMiddleware {

    // =========================================================================
    // SESIÓN
    // =========================================================================

    /**
     * Inicia la sesión si aún no está activa
     */
public static function startSession(): void {
    if (session_status() === PHP_SESSION_NONE) {
        // Configuración segura de cookies de sesión
        session_set_cookie_params([
            'lifetime' => 0,
            'path'     => '/',
            'secure'   => (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off'),
            'httponly' => true,
            'samesite' => 'Lax',
        ]);
        session_start();
    }
    
    // FORZAR que la sesión no se cierre accidentalmente
    if (session_status() === PHP_SESSION_ACTIVE) {
        // Renovar el tiempo de vida de la sesión
        if (isset($_SESSION['LAST_ACTIVITY']) && (time() - $_SESSION['LAST_ACTIVITY'] > 1800)) {
            // Sesión muy antigua, regenerar
            session_regenerate_id(true);
        }
        $_SESSION['LAST_ACTIVITY'] = time();
    }
}

    /**
     * Verifica autenticación; redirige al login si no está autenticado
     */
    public static function checkAuth(): void {
        self::startSession();

        if (!self::isAuthenticated()) {
            if (self::isAjaxRequest()) {
                header('Content-Type: application/json');
                http_response_code(401);
                echo json_encode(['error' => 'No autenticado', 'redirect' => '/login.php']);
                exit();
            }

            $_SESSION['redirect_url'] = $_SERVER['REQUEST_URI'] ?? '/';
            $host = $_SERVER['HTTP_HOST'] ?? 'localhost';
            $base_path = (in_array($host, ['localhost', '127.0.0.1'])) ? '/INVENTARIO' : '';
            header('Location: ' . $base_path . '/login.php');
            exit();
        }
    }

    /**
     * ¿Está el usuario autenticado?
     */
    public static function isAuthenticated(): bool {
        self::startSession();
        return !empty($_SESSION['usuario_id']);
    }

    /**
     * ¿La petición es AJAX?
     */
    public static function isAjaxRequest(): bool {
        return !empty($_SERVER['HTTP_X_REQUESTED_WITH'])
            && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest';
    }

    /**
     * Verifica autenticación y roles permitidos
     */
    public static function checkRole(array $allowedRoles = []): void {
        self::checkAuth();

        if (!empty($allowedRoles) && !in_array($_SESSION['usuario_rol'] ?? '', $allowedRoles, true)) {
            http_response_code(403);
            $basePath = rtrim(dirname($_SERVER['SCRIPT_NAME']), '/');
            header('Location: ' . $basePath . '/views/layouts/403-view.php');
            exit();
        }
    }

    /**
     * ¿El usuario tiene alguno de los roles indicados?
     */
    public static function hasRole(string|array $roles): bool {
        self::startSession();
        $userRol = $_SESSION['usuario_rol'] ?? '';
        return is_array($roles) ? in_array($userRol, $roles, true) : $userRol === $roles;
    }

    /**
     * ¿El usuario tiene un permiso específico?
     */
    public static function hasPermission(string $permission): bool {
        self::startSession();
        $permisos = $_SESSION['usuario_permisos'] ?? [];
        if (is_array($permisos)) return in_array($permission, $permisos, true);
        return $permisos === $permission;
    }

    // =========================================================================
    // DATOS DE USUARIO
    // =========================================================================

    /**
     * Obtiene datos del usuario desde la sesión
     */
    public static function getUserData(?string $key = null, mixed $default = null): mixed {
        self::startSession();
        
        if ($key === null) {
            return [
                'id'         => $_SESSION['usuario_id']         ?? null,
                'nombre'     => $_SESSION['usuario_nombre']     ?? null,
                'username'   => $_SESSION['usuario_username']   ?? null,
                'email'      => $_SESSION['usuario_email']      ?? null,
                'rol'        => $_SESSION['usuario_rol']        ?? null,
                'permisos'   => $_SESSION['usuario_permisos']   ?? [],
                'login_time' => $_SESSION['usuario_login_time'] ?? null,
                'nombre_completo' => $_SESSION['usuario_nombre'] ?? null,
            ];
        }
        
        // Soporte para alias 'nombre_completo'
        if ($key === 'nombre_completo') {
            $key = 'nombre';
        }
        
        return $_SESSION['usuario_' . $key] ?? $default;
    }
/**
 * Obtiene información básica del usuario (método de compatibilidad)
 */
public static function getUserInfo(): array {
    self::startSession();
    return [
        'id'         => $_SESSION['usuario_id'] ?? null,
        'nombre'     => $_SESSION['usuario_nombre'] ?? 'Usuario',
        'rol'        => $_SESSION['usuario_rol'] ?? 'invitado',
        'login_time' => $_SESSION['usuario_login_time'] ?? null,
    ];
}
    /**
     * Guarda datos del usuario en sesión
     */
    public static function setUserData(array $userData, bool $isLogin = true): void {
        self::startSession();

        $_SESSION['usuario_id']        = $userData['id']       ?? null;
        $_SESSION['usuario_nombre']    = $userData['nombre']   ?? null;
        $_SESSION['usuario_username']  = $userData['username'] ?? null;
        $_SESSION['usuario_email']     = $userData['email']    ?? null;
        $_SESSION['usuario_rol']       = $userData['rol']      ?? null;
        $_SESSION['usuario_nombre_completo'] = $userData['nombre'] ?? null;
        $_SESSION['usuario_permisos']  = $userData['permisos'] ?? [];
        $_SESSION['usuario_login_time'] = time();

        if ($isLogin) {
            session_regenerate_id(true);
            self::logActivity('login');
        }
    }

    /**
     * Cierra sesión de forma segura
     */
    public static function logout(): void {
        self::startSession();

        // Registrar antes de destruir
        self::logActivity('logout');

        // Limpiar datos de sesión
        $_SESSION = [];

        // Eliminar cookie de sesión
        if (ini_get('session.use_cookies')) {
            $params = session_get_cookie_params();
            setcookie(
                session_name(), '', time() - 42000,
                $params['path'], $params['domain'],
                $params['secure'], $params['httponly']
            );
        }

        session_destroy();
    }

    // =========================================================================
    // AUTENTICACIÓN DECLARATIVA
    // =========================================================================

    public static function requireAuth(string $redirectUrl = '/login.php', ?string $message = null): void {
        if (!self::isAuthenticated()) {
            self::redirect($redirectUrl, $message ?? 'Debe iniciar sesión para continuar.', 'warning');
        }
    }

    public static function requireRole(string|array $roles, string $redirectUrl = '/index.php', ?string $message = null): void {
        self::requireAuth($redirectUrl);
        if (!self::hasRole($roles)) {
            self::redirect($redirectUrl, $message ?? 'No tiene permisos para acceder a esta página.', 'danger');
        }
    }

    public static function requirePermission(string $permission, string $redirectUrl = '/index.php', ?string $message = null): void {
        self::requireAuth($redirectUrl);
        if (!self::hasPermission($permission)) {
            self::redirect($redirectUrl, $message ?? 'No tiene los permisos necesarios.', 'danger');
        }
    }

    // =========================================================================
    // MENSAJES FLASH
    // =========================================================================

    /**
     * Establece un mensaje flash
     */
    public static function setFlashMessage(string $message, string $type = 'info'): void {
        self::startSession();
        $_SESSION['flash_messages'][] = [
            'message'   => $message,
            'type'      => $type,
            'timestamp' => time(),
        ];
    }

    /**
     * Obtiene el primer mensaje flash disponible
     */
    public static function getFlashMessage(): ?array {
        self::startSession();

        // Patrón legacy
        if (!empty($_SESSION['flash_message'])) {
            $msg = [
                'message' => $_SESSION['flash_message'],
                'type'    => $_SESSION['flash_type'] ?? 'info',
            ];
            unset($_SESSION['flash_message'], $_SESSION['flash_type']);
            return $msg;
        }

        // Patrón nuevo
        if (!empty($_SESSION['flash_messages'])) {
            $messages = $_SESSION['flash_messages'];
            $first = array_shift($messages);
            $_SESSION['flash_messages'] = $messages ?: null;
            if (empty($_SESSION['flash_messages'])) {
                unset($_SESSION['flash_messages']);
            }
            return $first;
        }

        return null;
    }

    /**
     * Obtiene TODOS los mensajes flash pendientes
     */
    public static function getFlashMessages(): ?array {
        self::startSession();
        $all = [];

        // Legacy
        if (!empty($_SESSION['flash_message'])) {
            $all[] = ['message' => $_SESSION['flash_message'], 'type' => $_SESSION['flash_type'] ?? 'info'];
            unset($_SESSION['flash_message'], $_SESSION['flash_type']);
        }

        // Nuevo
        if (!empty($_SESSION['flash_messages'])) {
            $all = array_merge($all, $_SESSION['flash_messages']);
            unset($_SESSION['flash_messages']);
        }

        return !empty($all) ? $all : null;
    }

    // =========================================================================
    // SWEETALERT DIRECTO
    // =========================================================================

    /**
     * Guarda un mensaje para SweetAlert
     */
    public static function setSweetAlert(string $title, string $text = '', string $type = 'info', array $options = []): void {
        self::startSession();
        $_SESSION['sweetalert_message'] = [
            'title'     => $title,
            'text'      => $text,
            'type'      => $type,
            'options'   => $options,
            'timestamp' => time(),
        ];
    }

    /**
     * Obtiene y elimina el mensaje SweetAlert pendiente
     */
    public static function getSweetAlert(): ?array {
        self::startSession();
        if (isset($_SESSION['sweetalert_message'])) {
            $msg = $_SESSION['sweetalert_message'];
            unset($_SESSION['sweetalert_message']);
            return $msg;
        }
        return null;
    }

    // =========================================================================
    // REDIRECCIÓN
    // =========================================================================

    public static function redirect(string $url, ?string $message = null, string $type = 'info'): never {
        if ($message !== null) {
            self::setFlashMessage($message, $type);
        }
        header('Location: ' . $url);
        exit();
    }

    public static function redirectWithSweetAlert(string $url, string $title, string $text = '', string $type = 'info'): never {
        self::setSweetAlert($title, $text, $type);
        header('Location: ' . $url);
        exit();
    }

    // =========================================================================
    // CSRF TOKEN MANAGEMENT
    // =========================================================================

    /**
     * Genera un token CSRF y lo almacena en sesión
     * Los tokens expiran después de 1 hora
     */
    public static function generateCSRFToken(): string {
    self::startSession(); // Solo inicia si no está activa
    
    // NO llamar a session_regenerate_id() aquí
    
    if (!isset($_SESSION['csrf_tokens'])) {
        $_SESSION['csrf_tokens'] = [];
    }
    
    // Purgar tokens expirados
    $now = time();
    foreach ($_SESSION['csrf_tokens'] as $token => $timestamp) {
        if ($now - $timestamp > 3600) {
            unset($_SESSION['csrf_tokens'][$token]);
        }
    }
    
    $token = bin2hex(random_bytes(32));
    $_SESSION['csrf_tokens'][$token] = $now;
    
    // Log temporal
    error_log("Token generado y guardado en sesión. Session ID: " . session_id());
    
    return $token;
}

    /**
     * Valida un token CSRF de un solo uso (lo consume)
     */
    public static function validateCSRFToken(string $token): bool {
        self::startSession();

        if (empty($token) || !isset($_SESSION['csrf_tokens'][$token])) {
            return false;
        }

        if (time() - $_SESSION['csrf_tokens'][$token] > 3600) {
            unset($_SESSION['csrf_tokens'][$token]);
            return false;
        }

        // Consumir el token (eliminarlo después de usarlo)
        unset($_SESSION['csrf_tokens'][$token]);
        return true;
    }

    /**
     * Valida un token CSRF SIN consumirlo (reutilizable)
     * CRUCIAL para el módulo de Inventario Físico donde se hacen
     * múltiples peticiones AJAX desde la misma página
     */
    public static function validateCSRFTokenReusable(string $token): bool {
        self::startSession();

       if (empty($token)) {
            error_log("validateCSRFTokenReusable: Token vacío");
            return false;
        }

        if (!isset($_SESSION['csrf_tokens'][$token])) {
            error_log("validateCSRFTokenReusable: Token no encontrado en sesión. Buscado: " . substr($token, 0, 20) . "...");
            return false;
        }

        if (time() - $_SESSION['csrf_tokens'][$token] > 3600) {
            error_log("validateCSRFTokenReusable: Token expirado");
            unset($_SESSION['csrf_tokens'][$token]);
            return false;
        }

        // NO eliminamos el token para permitir múltiples usos
        return true;
    }

    // =========================================================================
    // LOG DE ACTIVIDAD
    // =========================================================================

    /**
     * Registra actividad en el log de sesión
     */
    private static function logActivity(string $action, array $data = []): void {
        if (session_status() !== PHP_SESSION_ACTIVE) return;

        if (!isset($_SESSION['user_activity_log'])) {
            $_SESSION['user_activity_log'] = [];
        }

        $_SESSION['user_activity_log'][] = [
            'action'    => $action,
            'timestamp' => time(),
            'user_id'   => $_SESSION['usuario_id'] ?? null,
            'ip'        => $_SERVER['REMOTE_ADDR'] ?? null,
            'data'      => $data,
        ];

        // Mantener solo las últimas 100 entradas
        if (count($_SESSION['user_activity_log']) > 100) {
            array_shift($_SESSION['user_activity_log']);
        }
    }

    /**
 * Genera un token CSRF único por sesión (no acumulativo)
 */
public static function generateUniqueCSRFToken(): string {
    self::startSession();
    
    // Si ya existe un token y no ha expirado, reutilizarlo
    if (!empty($_SESSION['csrf_unique_token']) && 
        isset($_SESSION['csrf_unique_token_time']) && 
        (time() - $_SESSION['csrf_unique_token_time'] < 3600)) {
        return $_SESSION['csrf_unique_token'];
    }
    
    // Generar nuevo token
    $_SESSION['csrf_unique_token'] = bin2hex(random_bytes(32));
    $_SESSION['csrf_unique_token_time'] = time();
    
    return $_SESSION['csrf_unique_token'];
}

/**
 * Valida el token CSRF único
 */
public static function validateUniqueCSRFToken(string $token): bool {
    self::startSession();
    
    if (empty($_SESSION['csrf_unique_token'])) {
        error_log("CSRF Error: No hay token único en sesión");
        return false;
    }
    
    if (time() - $_SESSION['csrf_unique_token_time'] > 3600) {
        error_log("CSRF Error: Token expirado");
        return false;
    }
    
    if (!hash_equals($_SESSION['csrf_unique_token'], $token)) {
        error_log("CSRF Error: Token no coincide. Esperado: " . substr($_SESSION['csrf_unique_token'], 0, 20) . "..., Recibido: " . substr($token, 0, 20) . "...");
        return false;
    }
    
    return true;
}

}