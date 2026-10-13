<?php
/**
 * Categoria.php — Modelo
 */
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/SessionMiddleware.php';

class Categoria
{
    private PDO $db;

    public function __construct()
    {
        $this->db = getDB();
    }

    public function getForSelect(bool $placeholder = false): array
    {
        $stmt = $this->db->query("CALL sp_GetCategoriasForSelect()");
        $categorias = $stmt->fetchAll(PDO::FETCH_ASSOC);
        $stmt->closeCursor();

        if ($placeholder) {
            array_unshift($categorias, ['value' => '', 'text' => '-- Seleccione categoría --']);
        }
        return $categorias;
    }

    public function getAll(bool $activas = true, string $filtro = '', bool $conProductos = false, int $limit = 100, int $offset = 0): array
    {
        $estado = $activas ? 'activo' : 'inactivo';
        $stmt = $this->db->prepare("CALL sp_ListarCategorias(:filtro, :estado, :con_productos, :limit, :offset)");
        $stmt->execute([
            ':filtro'        => $filtro ?: null,
            ':estado'        => $estado,
            ':con_productos' => (int) $conProductos,
            ':limit'         => $limit,
            ':offset'        => $offset,
        ]);

        $data = $stmt->fetchAll(PDO::FETCH_ASSOC);
        $total = 0;
        if ($stmt->nextRowset()) {
            $row = $stmt->fetch(PDO::FETCH_ASSOC);
            $total = (int) ($row['total_registros'] ?? 0);
        }
        $stmt->closeCursor();
        return ['data' => $data, 'total' => $total];
    }

    public function getById(int $id): ?array
    {
        $stmt = $this->db->prepare("CALL sp_GetCategoriaById(:id)");
        $stmt->execute([':id' => $id]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC) ?: null;
        $stmt->closeCursor();
        return $row;
    }
/**
 * Verifica si un nombre de categoría ya existe
 * @param string $nombre Nombre a verificar
 * @param int|null $excludeId ID a excluir (para edición)
 * @return bool true si existe, false si no existe
 */
public function nombreExists(string $nombre, ?int $excludeId = null): bool
{
    try {
        // Primero, intentar con consulta directa (más confiable que SP)
        $sql = "SELECT COUNT(*) as total FROM categorias WHERE nombre = :nombre AND estado != 'eliminado'";
        $params = [':nombre' => $nombre];
        
        if ($excludeId !== null && $excludeId > 0) {
            $sql .= " AND id_categoria != :exclude_id";
            $params[':exclude_id'] = $excludeId;
        }
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        
        return ($result['total'] ?? 0) > 0;
        
    } catch (PDOException $e) {
        // Log del error para depuración
        error_log("Error en nombreExists: " . $e->getMessage());
        return false; // En caso de error, asumir que no existe para no bloquear
    }
}

    // ✅ CREATE CORREGIDO
    public function create(array $data): array
    {
        try {
            $sql = "CALL sp_InsertCategoria(:nombre, :descripcion, :icono, @p_id, @p_msg)";
            $stmt = $this->db->prepare($sql);
            $stmt->execute([
                ':nombre'      => $data['nombre'],
                ':descripcion' => $data['descripcion'] ?: null,
                ':icono'       => $data['icono'] ?: null,
            ]);
            $stmt->closeCursor();

            $result = $this->db->query("SELECT @p_id AS id, @p_msg AS mensaje")->fetch(PDO::FETCH_ASSOC);
            
            $idCategoria = (int)($result['id'] ?? 0);
            $mensaje = $result['mensaje'] ?? '';

            if ($idCategoria <= 0) {
                return ['success' => false, 'mensaje' => $mensaje ?: 'Error al crear la categoría'];
            }

                return [
                'success' => true,
                'id_categoria' => $idCategoria,
                'mensaje' => $mensaje,
            ];


        } catch (PDOException $e) {
            return ['success' => false, 'mensaje' => 'Error en la base de datos: ' . $e->getMessage()];
        }
    }

    public function update(int $id, array $data): array
    {
        try {
            $stmt = $this->db->prepare("CALL sp_UpdateCategoria(:id, :nombre, :descripcion, :icono, :estado, @p_msg)");
            $stmt->execute([
                ':id'          => $id,
                ':nombre'      => $data['nombre'],
                ':descripcion' => $data['descripcion'] ?: null,
                ':icono'       => $data['icono'] ?: null,
                ':estado'      => $data['estado'] ?? 'activo',
            ]);
            $stmt->closeCursor();

            $result = $this->db->query("SELECT @p_msg AS mensaje")->fetch(PDO::FETCH_ASSOC);
            $success = str_contains($result['mensaje'] ?? '', 'exitosamente');
            return ['success' => $success, 'mensaje' => $result['mensaje']];
        } catch (PDOException $e) {
            return ['success' => false, 'mensaje' => 'Error en la base de datos: ' . $e->getMessage()];
        }
    }

    public function delete(int $id): array
    {
        try {
            $stmt = $this->db->prepare("CALL sp_DeleteCategoria(:id, @p_eliminado, @p_msg)");
            $stmt->execute([':id' => $id]);
            $stmt->closeCursor();

            $result = $this->db->query("SELECT @p_eliminado AS eliminado, @p_msg AS mensaje")->fetch(PDO::FETCH_ASSOC);
            return [
                'success' => (bool) ($result['eliminado'] ?? false),
                'mensaje' => $result['mensaje'] ?? ''
            ];
        } catch (PDOException $e) {
            return ['success' => false, 'mensaje' => 'Error en la base de datos: ' . $e->getMessage()];
        }
    }

    public function activate(int $id): array
    {
        try {
            $stmt = $this->db->prepare("CALL sp_ActivateCategoria(:id, @p_activado, @p_msg)");
            $stmt->execute([':id' => $id]);
            $stmt->closeCursor();

            $result = $this->db->query("SELECT @p_activado AS activado, @p_msg AS mensaje")->fetch(PDO::FETCH_ASSOC);
            return [
                'success' => (bool) ($result['activado'] ?? false),
                'mensaje' => $result['mensaje'] ?? ''
            ];
        } catch (PDOException $e) {
            return ['success' => false, 'mensaje' => 'Error en la base de datos: ' . $e->getMessage()];
        }
    }

    public function countProductos(int $idCategoria): int
    {
        try {
            $stmt = $this->db->prepare("CALL sp_CountProductosByCategoria(:id, @p_total)");
            $stmt->execute([':id' => $idCategoria]);
            $stmt->closeCursor();

            $result = $this->db->query("SELECT @p_total AS total")->fetch(PDO::FETCH_ASSOC);
            return (int) ($result['total'] ?? 0);
        } catch (PDOException $e) {
            return 0;
        }
    }

    public function getDashboardStats(): array
    {
        $stmt = $this->db->query("CALL sp_GetDashboardStats()");
        $keys = ['general', 'top_productos', 'top_valor', 'distribucion', 'stock', 'alertas'];
        $result = array_fill_keys($keys, []);
        $index = 0;

        do {
            $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
            if (isset($keys[$index])) {
                $result[$keys[$index]] = ($index === 0 && !empty($rows)) ? $rows[0] : $rows;
            }
            $index++;
        } while ($stmt->nextRowset());

        $stmt->closeCursor();
        return $result;
    }

    public function getCategoriaGraficos(int $idCategoria): array
    {
        $stmt = $this->db->prepare("CALL sp_GetCategoriaGraficos(:id)");
        $stmt->execute([':id' => $idCategoria]);

        $result['precios'] = $stmt->fetchAll(PDO::FETCH_ASSOC);
        $result['top_productos'] = [];
        if ($stmt->nextRowset()) {
            $result['top_productos'] = $stmt->fetchAll(PDO::FETCH_ASSOC);
        }

        $stmt->closeCursor();
        return $result;
    }

    public function getEstadisticasProductos(): array
    {
        $stmt = $this->db->query("CALL sp_GetEstadisticasProductos()");
        $general = $stmt->fetch(PDO::FETCH_ASSOC) ?: [];
        $top_categorias = [];
        if ($stmt->nextRowset()) {
            $top_categorias = $stmt->fetchAll(PDO::FETCH_ASSOC);
        }
        $stmt->closeCursor();
        return ['general' => $general, 'top_categorias' => $top_categorias];
    }
}
?>