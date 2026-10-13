<?php
/**
 * Modelo: Marca.php
 * CRUD para la tabla marcas
 */

require_once __DIR__ . '/../config/database.php';

class Marca
{
    private \PDO $db;

    public function __construct()
    {
        $this->db = getDB();
    }

    /**
     * Listar marcas con filtros y paginación
     */
    public function listar(array $filters = []): array
    {
        try {
            $sql = "CALL sp_ListarMarcas(
                        :filtro, :estado, :limit, :offset
                    )";
            $stmt = $this->db->prepare($sql);
            $stmt->bindValue(':filtro', $filters['filtro'] ?? null, \PDO::PARAM_STR);
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
            error_log("Marca::listar — " . $e->getMessage());
            return ['success' => false, 'data' => [], 'total' => 0];
        }
    }

    /**
     * Obtener marca por ID
     */
    public function getById(int $id): ?array
    {
        try {
            $stmt = $this->db->prepare("CALL sp_GetMarcaById(:id)");
            $stmt->bindValue(':id', $id, \PDO::PARAM_INT);
            $stmt->execute();
            $result = $stmt->fetch(\PDO::FETCH_ASSOC);
            $stmt->closeCursor();
            return $result ?: null;
        } catch (\PDOException $e) {
            error_log("Marca::getById — " . $e->getMessage());
            return null;
        }
    }

    /**
     * Crear nueva marca
     */
    public function crear(array $data): array
    {
        try {
            $sql = "CALL sp_InsertMarca(
                        :nombre, :descripcion, :pais_origen, :sitio_web,
                        @p_id_marca, @p_mensaje
                    )";
            $stmt = $this->db->prepare($sql);
            $stmt->bindValue(':nombre', $data['nombre'], \PDO::PARAM_STR);
            $stmt->bindValue(':descripcion', $data['descripcion'] ?? null, \PDO::PARAM_STR);
            $stmt->bindValue(':pais_origen', $data['pais_origen'] ?? null, \PDO::PARAM_STR);
            $stmt->bindValue(':sitio_web', $data['sitio_web'] ?? null, \PDO::PARAM_STR);
            $stmt->execute();
            $stmt->closeCursor();

            $out = $this->db->query(
                "SELECT @p_id_marca AS id_marca, @p_mensaje AS mensaje"
            )->fetch(\PDO::FETCH_ASSOC);

            $idMarca = (int)($out['id_marca'] ?? 0);
            $mensaje = $out['mensaje'] ?? 'Error desconocido';

            return [
                'success' => $idMarca > 0,
                'id_marca' => $idMarca,
                'mensaje' => $mensaje,
            ];

        } catch (\PDOException $e) {
            error_log("Marca::crear — " . $e->getMessage());
            return ['success' => false, 'id_marca' => 0, 'mensaje' => $this->parsearError($e)];
        }
    }

    /**
     * Actualizar marca
     */
    public function actualizar(array $data): array
    {
        try {
            $sql = "CALL sp_UpdateMarca(
                        :id_marca, :nombre, :descripcion, :pais_origen, :sitio_web, :estado,
                        @p_actualizado, @p_mensaje
                    )";
            $stmt = $this->db->prepare($sql);
            $stmt->bindValue(':id_marca', $data['id_marca'], \PDO::PARAM_INT);
            $stmt->bindValue(':nombre', $data['nombre'], \PDO::PARAM_STR);
            $stmt->bindValue(':descripcion', $data['descripcion'] ?? null, \PDO::PARAM_STR);
            $stmt->bindValue(':pais_origen', $data['pais_origen'] ?? null, \PDO::PARAM_STR);
            $stmt->bindValue(':sitio_web', $data['sitio_web'] ?? null, \PDO::PARAM_STR);
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
            error_log("Marca::actualizar — " . $e->getMessage());
            return ['success' => false, 'mensaje' => $this->parsearError($e)];
        }
    }

    /**
     * Eliminar (desactivar) marca
     */
    public function eliminar(int $idMarca): array
    {
        try {
            $sql = "CALL sp_DeleteMarca(:id_marca, @p_eliminado, @p_mensaje)";
            $stmt = $this->db->prepare($sql);
            $stmt->bindValue(':id_marca', $idMarca, \PDO::PARAM_INT);
            $stmt->execute();
            $stmt->closeCursor();

            $out = $this->db->query(
                "SELECT @p_eliminado AS eliminado, @p_mensaje AS mensaje"
            )->fetch(\PDO::FETCH_ASSOC);

            return [
                'success' => (bool)($out['eliminado'] ?? false),
                'mensaje' => $out['mensaje'] ?? 'Error desconocido',
            ];

        } catch (\PDOException $e) {
            error_log("Marca::eliminar — " . $e->getMessage());
            return ['success' => false, 'mensaje' => $this->parsearError($e)];
        }
    }

    /**
     * Activar marca
     */
    public function activar(int $idMarca): array
    {
        try {
            $sql = "CALL sp_ActivateMarca(:id_marca, @p_activado, @p_mensaje)";
            $stmt = $this->db->prepare($sql);
            $stmt->bindValue(':id_marca', $idMarca, \PDO::PARAM_INT);
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
            error_log("Marca::activar — " . $e->getMessage());
            return ['success' => false, 'mensaje' => $this->parsearError($e)];
        }
    }

    /**
     * Obtener marcas para select (solo activas)
     */
    public function getForSelect(): array
    {
        try {
            $stmt = $this->db->query("CALL sp_GetMarcasForSelect()");
            $result = $stmt->fetchAll(\PDO::FETCH_ASSOC);
            $stmt->closeCursor();
            return $result;
        } catch (\PDOException $e) {
            error_log("Marca::getForSelect — " . $e->getMessage());
            return [];
        }
    }

    /**
     * Verificar si ya existe una marca con el nombre dado.
     * Usado por MarcaController::checkNombre()
     *
     * @param  string $nombre   Nombre a verificar (case-insensitive)
     * @param  int    $excluir  id_marca a ignorar; 0 = ninguno (al crear)
     * @return bool   true si ya existe, false si está disponible
     */
    public function existeNombre(string $nombre, int $excluir = 0): bool
    {
        try {
            if ($excluir > 0) {
                // Flujo EDITAR: ignorar la marca que se está editando
                $sql = "SELECT COUNT(*) FROM marcas
                         WHERE LOWER(nombre) = LOWER(:nombre)
                           AND id_marca <> :excluir
                           AND estado <> 'eliminado'";
                $stmt = $this->db->prepare($sql);
                $stmt->bindValue(':nombre',  $nombre,  \PDO::PARAM_STR);
                $stmt->bindValue(':excluir', $excluir, \PDO::PARAM_INT);
            } else {
                // Flujo CREAR: buscar en todos los registros no eliminados
                $sql = "SELECT COUNT(*) FROM marcas
                         WHERE LOWER(nombre) = LOWER(:nombre)
                           AND estado <> 'eliminado'";
                $stmt = $this->db->prepare($sql);
                $stmt->bindValue(':nombre', $nombre, \PDO::PARAM_STR);
            }

            $stmt->execute();
            return (int)$stmt->fetchColumn() > 0;

        } catch (\PDOException $e) {
            error_log("Marca::existeNombre — " . $e->getMessage());
            // Ante error de BD devolvemos false para no bloquear al usuario;
            // si duplicara, sp_InsertMarca lo rechazará de todas formas.
            return false;
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