<?php
/**
 * Modelo: Cliente.php
 * CRUD para la tabla clientes
 */

require_once __DIR__ . '/../config/database.php';

class Cliente
{
    private \PDO $db;

    public function __construct()
    {
        $this->db = getDB();
    }

    /**
     * Listar clientes con filtros y paginación
     */
    public function listar(array $filters = []): array
    {
        try {
            $sql = "CALL sp_ListarClientes(
                        :filtro, :tipo_cliente, :estado, :limit, :offset
                    )";
            $stmt = $this->db->prepare($sql);
            $stmt->bindValue(':filtro', $filters['filtro'] ?? null, \PDO::PARAM_STR);
            $stmt->bindValue(':tipo_cliente', $filters['tipo_cliente'] ?? null, \PDO::PARAM_STR);
            $stmt->bindValue(':estado', $filters['estado'] ?? null, \PDO::PARAM_STR);
            $stmt->bindValue(':limit', (int)($filters['limit'] ?? 20), \PDO::PARAM_INT);
            $stmt->bindValue(':offset', (int)($filters['offset'] ?? 0), \PDO::PARAM_INT);
            $stmt->execute();

            $data = $stmt->fetchAll(\PDO::FETCH_ASSOC);

            $total = 0;
            if ($stmt->nextRowset()) {
                $row = $stmt->fetch(\PDO::FETCH_ASSOC);
                $total = (int)($row['total_registros'] ?? 0);
            }
            $stmt->closeCursor();

            return ['success' => true, 'data' => $data, 'total' => $total];

        } catch (\PDOException $e) {
            error_log("Cliente::listar — " . $e->getMessage());
            return ['success' => false, 'data' => [], 'total' => 0];
        }
    }

    /**
     * Obtener cliente por ID
     */
    public function getById(int $id): ?array
    {
        try {
            $stmt = $this->db->prepare("CALL sp_GetClienteById(:id)");
            $stmt->bindValue(':id', $id, \PDO::PARAM_INT);
            $stmt->execute();
            $result = $stmt->fetch(\PDO::FETCH_ASSOC);
            $stmt->closeCursor();
            return $result ?: null;
        } catch (\PDOException $e) {
            error_log("Cliente::getById — " . $e->getMessage());
            return null;
        }
    }

    /**
     * Buscar cliente por documento
     */
    public function buscarPorDocumento(string $tipoDocumento, string $numeroDocumento): ?array
    {
        try {
            $stmt = $this->db->prepare("CALL sp_BuscarClienteByDocumento(:tipo, :numero)");
            $stmt->bindValue(':tipo', $tipoDocumento, \PDO::PARAM_STR);
            $stmt->bindValue(':numero', $numeroDocumento, \PDO::PARAM_STR);
            $stmt->execute();
            $result = $stmt->fetch(\PDO::FETCH_ASSOC);
            $stmt->closeCursor();
            return $result ?: null;
        } catch (\PDOException $e) {
            error_log("Cliente::buscarPorDocumento — " . $e->getMessage());
            return null;
        }
    }

    /**
     * Crear nuevo cliente
     */
    public function crear(array $data): array
    {
        try {
            $sql = "CALL sp_InsertCliente(
                        :tipo_documento, :numero_documento, :nombre_completo,
                        :email, :telefono, :direccion, :fecha_nacimiento,
                        :genero, :tipo_cliente,
                        @p_id_cliente, @p_mensaje
                    )";
            $stmt = $this->db->prepare($sql);
            $stmt->bindValue(':tipo_documento', $data['tipo_documento'] ?? 'DNI', \PDO::PARAM_STR);
            $stmt->bindValue(':numero_documento', $data['numero_documento'], \PDO::PARAM_STR);
            $stmt->bindValue(':nombre_completo', $data['nombre_completo'], \PDO::PARAM_STR);
            $stmt->bindValue(':email', $data['email'] ?? null, \PDO::PARAM_STR);
            $stmt->bindValue(':telefono', $data['telefono'] ?? null, \PDO::PARAM_STR);
            $stmt->bindValue(':direccion', $data['direccion'] ?? null, \PDO::PARAM_STR);
            $stmt->bindValue(':fecha_nacimiento', $data['fecha_nacimiento'] ?? null, \PDO::PARAM_STR);
            $stmt->bindValue(':genero', $data['genero'] ?? null, \PDO::PARAM_STR);
            $stmt->bindValue(':tipo_cliente', $data['tipo_cliente'] ?? 'regular', \PDO::PARAM_STR);
            $stmt->execute();
            $stmt->closeCursor();

            $out = $this->db->query(
                "SELECT @p_id_cliente AS id_cliente, @p_mensaje AS mensaje"
            )->fetch(\PDO::FETCH_ASSOC);

            $idCliente = (int)($out['id_cliente'] ?? 0);
            $mensaje = $out['mensaje'] ?? 'Error desconocido';

            return [
                'success' => true,
                'id_cliente' => $idCliente,
                'mensaje' => $mensaje,
            ];

        } catch (\PDOException $e) {
            error_log("Cliente::crear — " . $e->getMessage());
            return ['success' => false, 'id_cliente' => 0, 'mensaje' => $this->parsearError($e)];
        }
    }

    /**
     * Actualizar cliente
     */
    public function actualizar(array $data): array
    {
        try {
            $sql = "CALL sp_UpdateCliente(
                        :id_cliente, :tipo_documento, :numero_documento,
                        :nombre_completo, :email, :telefono, :direccion,
                        :fecha_nacimiento, :genero, :tipo_cliente, :estado,
                        @p_actualizado, @p_mensaje
                    )";
            $stmt = $this->db->prepare($sql);
            $stmt->bindValue(':id_cliente', $data['id_cliente'], \PDO::PARAM_INT);
            $stmt->bindValue(':tipo_documento', $data['tipo_documento'] ?? 'DNI', \PDO::PARAM_STR);
            $stmt->bindValue(':numero_documento', $data['numero_documento'], \PDO::PARAM_STR);
            $stmt->bindValue(':nombre_completo', $data['nombre_completo'], \PDO::PARAM_STR);
            $stmt->bindValue(':email', $data['email'] ?? null, \PDO::PARAM_STR);
            $stmt->bindValue(':telefono', $data['telefono'] ?? null, \PDO::PARAM_STR);
            $stmt->bindValue(':direccion', $data['direccion'] ?? null, \PDO::PARAM_STR);
            $stmt->bindValue(':fecha_nacimiento', $data['fecha_nacimiento'] ?? null, \PDO::PARAM_STR);
            $stmt->bindValue(':genero', $data['genero'] ?? null, \PDO::PARAM_STR);
            $stmt->bindValue(':tipo_cliente', $data['tipo_cliente'] ?? 'regular', \PDO::PARAM_STR);
            $stmt->bindValue(':estado', $data['estado'] ?? 'activo', \PDO::PARAM_STR);
            $stmt->execute();
            $stmt->closeCursor();

            $out = $this->db->query(
                "SELECT @p_actualizado AS actualizado, @p_mensaje AS mensaje"
            )->fetch(\PDO::FETCH_ASSOC);

            return [
                'success' => (bool)($out['actualizado'] ?? false),
                'mensaje' => $out['mensaje'] ?? 'Error desconocido',
            ];

        } catch (\PDOException $e) {
            error_log("Cliente::actualizar — " . $e->getMessage());
            return ['success' => false, 'mensaje' => $this->parsearError($e)];
        }
    }

    /**
     * Eliminar (desactivar) cliente
     */
/**
 * Eliminar (desactivar) cliente
 */
public function eliminar(int $idCliente): array
{
    try {
        $sql = "CALL sp_DeleteCliente(:id_cliente, @p_eliminado, @p_mensaje)";
        $stmt = $this->db->prepare($sql);
        $stmt->bindValue(':id_cliente', $idCliente, PDO::PARAM_INT);
        $stmt->execute();
        $stmt->closeCursor();

        $out = $this->db->query(
            "SELECT @p_eliminado AS eliminado, @p_mensaje AS mensaje"
        )->fetch(PDO::FETCH_ASSOC);
        
        return [
            'success' => (bool) ($out['eliminado'] ?? false),
            'mensaje' => $out['mensaje'] ?? 'Error desconocido'
        ];
    } catch (PDOException $e) {
        error_log("Cliente::eliminar — " . $e->getMessage());
        return ['success' => false, 'mensaje' => 'Error en la base de datos: ' . $e->getMessage()];
    }
}

    /**
     * Activar cliente
     */
    public function activar(int $idCliente): array
    {
        try {
            $sql = "CALL sp_ActivateCliente(:id_cliente, @p_activado, @p_mensaje)";
            $stmt = $this->db->prepare($sql);
            $stmt->bindValue(':id_cliente', $idCliente, \PDO::PARAM_INT);
            $stmt->execute();
            $stmt->closeCursor();

            $out = $this->db->query(
                "SELECT @p_activado AS activado, @p_mensaje AS mensaje"
            )->fetch(\PDO::FETCH_ASSOC);

            return [
                'success' => (bool)($out['activado'] ?? false),
                'mensaje' => $out['mensaje'] ?? 'Error desconocido',
            ];

        } catch (\PDOException $e) {
            error_log("Cliente::activar — " . $e->getMessage());
            return ['success' => false, 'mensaje' => $this->parsearError($e)];
        }
    }

/**
 * Verifica si un documento ya existe en la base de datos
 * @param string $tipo Tipo de documento (DNI, RUC, CE, Pasaporte)
 * @param string $numero Número de documento
 * @param int|null $excludeId ID del cliente a excluir (para edición)
 * @return bool true si existe, false si no existe
 */
public function documentoExists(string $tipo, string $numero, ?int $excludeId = null): bool
{
    try {
        $sql = "SELECT COUNT(*) as total FROM clientes 
                WHERE tipo_documento = :tipo 
                AND numero_documento = :numero 
                AND estado != 'eliminado'";
        $params = [
            ':tipo' => $tipo,
            ':numero' => $numero
        ];
        
        if ($excludeId !== null && $excludeId > 0) {
            $sql .= " AND id_cliente != :exclude_id";
            $params[':exclude_id'] = $excludeId;
        }
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        
        return ($result['total'] ?? 0) > 0;
        
    } catch (PDOException $e) {
        error_log("Error en documentoExists: " . $e->getMessage());
        return false; // En caso de error, asumir que no existe
    }
}

    /**
     * Obtener clientes para select
     */
    public function getForSelect(): array
    {
        try {
            $stmt = $this->db->query("CALL sp_GetClientesForSelect()");
            $result = $stmt->fetchAll(\PDO::FETCH_ASSOC);
            $stmt->closeCursor();
            return $result;
        } catch (\PDOException $e) {
            error_log("Cliente::getForSelect — " . $e->getMessage());
            return [];
        }
    }

    /**
     * Parsear error de MySQL
     */
    private function parsearError(\PDOException $e): string
    {
        $msg = $e->getMessage();
        if (preg_match('/1644\s+(.+)$/i', $msg, $m)) {
            return trim($m[1]);
        }
        return 'Error interno del servidor';
    }
}