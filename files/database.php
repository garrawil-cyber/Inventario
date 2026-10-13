<?php
// ============================================================
//  database.php — Conexión PDO Singleton
//  Usa las constantes definidas en config.php si están disponibles,
//  o valores por defecto si se incluye de forma aislada.
// ============================================================

class Database {
    private static $instance = null;
    private $connection;

    private function __construct() {
        // Leer desde constantes de config.php si existen
        $host    = defined('DB_HOST')    ? DB_HOST    : 'localhost';
        $dbname  = defined('DB_NAME')    ? DB_NAME    : 'ropavirtual_db';
        $user    = defined('DB_USER')    ? DB_USER    : 'root';
        $pass    = defined('DB_PASS')    ? DB_PASS    : '';
        $charset = defined('DB_CHARSET') ? DB_CHARSET : 'utf8mb4';

        try {
            $this->connection = new PDO(
                "mysql:host={$host};dbname={$dbname};charset={$charset}",
                $user,
                $pass,
                [
                    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
                    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                    PDO::ATTR_EMULATE_PREPARES   => false,
                ]
            );
        } catch (PDOException $e) {
            // En producción no mostrar detalles del error
            if (defined('APP_ENV') && APP_ENV === 'production') {
                error_log('DB Connection Error: ' . $e->getMessage());
                die('Error de conexión a la base de datos. Contacte al administrador.');
            }
            die('Error de conexión: ' . $e->getMessage());
        }
    }

    // Prevenir clonación del singleton
    private function __clone() {}

    public static function getInstance(): self {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    public function getConnection(): PDO {
        return $this->connection;
    }

    public static function getConnectionStatic(): PDO {
        return self::getInstance()->getConnection();
    }
}

// ── Helper global usado en todos los modelos (Producto.php, etc.) ──
function getDB(): PDO {
    return Database::getConnectionStatic();
}
