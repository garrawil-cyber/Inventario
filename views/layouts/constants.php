<?php
// Definir constantes del sistema
define('APP_NAME', 'Sistema de Gestión de Almacén');
define('APP_VERSION', '2.0.0');
define('APP_AUTHOR', 'Equipo de Desarrollo');
define('APP_YEAR', date('Y'));

// Configuración de la aplicación
define('MAX_LOGIN_ATTEMPTS', 5);
define('SESSION_TIMEOUT', 3600); // 1 hora en segundos
define('ITEMS_PER_PAGE', 25);

// Rutas de la aplicación
define('BASE_URL', ($_SERVER['SERVER_NAME'] == 'localhost') ? '/SISTEMA_ALMACEN' : '');
define('UPLOAD_PATH', __DIR__ . '/../uploads/');
define('BACKUP_PATH', __DIR__ . '/../backups/');

// Niveles de log
define('LOG_LEVEL_ERROR', 1);
define('LOG_LEVEL_WARNING', 2);
define('LOG_LEVEL_INFO', 3);
define('LOG_LEVEL_DEBUG', 4);

// Colores para estados
define('COLOR_SUCCESS', '#1cc88a');
define('COLOR_WARNING', '#f6c23e');
define('COLOR_DANGER', '#e74a3b');
define('COLOR_INFO', '#36b9cc');
define('COLOR_PRIMARY', '#4e73df');
?>