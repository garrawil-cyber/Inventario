<?php
/**
 * ============================================================
 *  MODELO: Ubicacion.php
 *  Sistema : RopaVirtual – Módulo de Ubicaciones
 *  Arquitectura: MVC  |  Capa: Model
 *  Base de datos: ropavirtual_db
 *  Relación: N ubicaciones → 1 almacén
 * ============================================================
 *
 *  Stored Procedures utilizados:
 *   - sp_ListarUbicaciones
 *   - sp_GetUbicacionById
 *   - sp_InsertUbicacion
 *   - sp_UpdateUbicacion
 *   - sp_DeleteUbicacion
 *   - sp_GetUbicacionesForSelect
 *   - sp_GetUbicacionesDisponibles
 *   - sp_ActualizarEstadoUbicacion
 *   - sp_GetUbicacionesPorAlmacen
 *   - sp_AsignarProductoAUbicacion
 *
 * ============================================================
 */

require_once __DIR__ . '/../config/database.php';

class Ubicacion
{
    private \PDO $db;

    public function __construct()
    {
        $this->db = getDB();
    }

    // =========================================================================
    //  LISTAR ubicaciones con filtros
    //  SP: sp_ListarUbicaciones(p_filtro, p_id_almacen, p_estado, p_limit, p_offset)
    // =========================================================================
    public function listar(array $filters = []): array
    {
        try {
            $filtro      = $filters['filtro'] ?? null;
            $id_almacen  = $filters['id_almacen'] ?? null;
            $estado      = $filters['estado'] ?? null;
            $limit       = (int)($filters['limit'] ?? 20);
            $offset      = (int)($filters['offset'] ?? 0);

            $sql = "CALL sp_ListarUbicaciones(:filtro, :id_almacen, :estado, :limit, :offset)";
            $stmt = $this->db->prepare($sql);
            $stmt->bindValue(':filtro',      $filtro ?: null, PDO::PARAM_STR);
            $stmt->bindValue(':id_almacen',  $id_almacen ?: null, PDO::PARAM_INT);
            $stmt->bindValue(':estado',      $estado ?: null, PDO::PARAM_STR);
            $stmt->bindValue(':limit',       $limit, PDO::PARAM_INT);
            $stmt->bindValue(':offset',      $offset, PDO::PARAM_INT);
            $stmt->execute();

            $data = $stmt->fetchAll(PDO::FETCH_ASSOC);

            $total = 0;
            if ($stmt->nextRowset()) {
                $row = $stmt->fetch(PDO::FETCH_ASSOC);
                $total = (int)($row['total_registros'] ?? 0);
            }

            $stmt->closeCursor();

            return ['success' => true, 'data' => $data, 'total' => $total];

        } catch (\PDOException $e) {
            error_log("Ubicacion::listar — " . $e->getMessage());
            return ['success' => false, 'data' => [], 'total' => 0, 'mensaje' => 'Error al obtener las ubicaciones'];
        }
    }

    // =========================================================================
    //  OBTENER ubicación por ID (con productos y movimientos)
    //  SP: sp_GetUbicacionById(p_id_ubicacion)
    // =========================================================================
    public function getById(int $id): array
    {
        try {
            $stmt = $this->db->prepare("CALL sp_GetUbicacionById(:id)");
            $stmt->bindParam(':id', $id, PDO::PARAM_INT);
            $stmt->execute();

            $ubicacion = $stmt->fetch(PDO::FETCH_ASSOC);

            $productos = [];
            if ($stmt->nextRowset()) {
                $productos = $stmt->fetchAll(PDO::FETCH_ASSOC);
            }

            $movimientos = [];
            if ($stmt->nextRowset()) {
                $movimientos = $stmt->fetchAll(PDO::FETCH_ASSOC);
            }

            $stmt->closeCursor();

            if ($ubicacion) {
                return [
                    'success'     => true,
                    'ubicacion'   => $ubicacion,
                    'productos'   => $productos,
                    'movimientos' => $movimientos
                ];
            }

            return ['success' => false, 'mensaje' => 'Ubicación no encontrada'];

        } catch (\PDOException $e) {
            error_log("Ubicacion::getById — " . $e->getMessage());
            return ['success' => false, 'mensaje' => 'Error al obtener la ubicación'];
        }
    }

    // =========================================================================
    //  CREAR nueva ubicación
    //  SP: sp_InsertUbicacion(..., OUT p_id_ubicacion, OUT p_mensaje)
    // =========================================================================
    public function crear(array $data): array
    {
        try {
            $sql = "CALL sp_InsertUbicacion(
                        :id_almacen, :codigo, :pasillo, :nivel, :posicion,
                        :capacidad, :descripcion, :estado,
                        @p_id_ubicacion, @p_mensaje
                    )";
            
            $stmt = $this->db->prepare($sql);
            $stmt->bindValue(':id_almacen',   $data['id_almacen'],              PDO::PARAM_INT);
            $stmt->bindValue(':codigo',       $data['codigo'],                  PDO::PARAM_STR);
            $stmt->bindValue(':pasillo',      $data['pasillo'] ?? null,         PDO::PARAM_STR);
            $stmt->bindValue(':nivel',        $data['nivel'] ?? null,           PDO::PARAM_STR);
            $stmt->bindValue(':posicion',     $data['posicion'] ?? null,        PDO::PARAM_STR);
            $stmt->bindValue(':capacidad',    $data['capacidad'] ?? null,       PDO::PARAM_INT);
            $stmt->bindValue(':descripcion',  $data['descripcion'] ?? null,     PDO::PARAM_STR);
            $stmt->bindValue(':estado',       $data['estado'] ?? 'disponible',  PDO::PARAM_STR);
            $stmt->execute();
            $stmt->closeCursor();

            $outStmt = $this->db->query("SELECT @p_id_ubicacion AS id_ubicacion, @p_mensaje AS mensaje");
            $out = $outStmt->fetch(PDO::FETCH_ASSOC);

            $idUbicacion = (int)($out['id_ubicacion'] ?? 0);
            $mensaje     = $out['mensaje'] ?? 'Error desconocido';

            return ['success' => $idUbicacion > 0, 'id_ubicacion' => $idUbicacion, 'mensaje' => $mensaje];

        } catch (\PDOException $e) {
            error_log("Ubicacion::crear — " . $e->getMessage());
            return ['success' => false, 'id_ubicacion' => 0, 'mensaje' => $this->parsearMensajePDO($e)];
        }
    }

    // =========================================================================
    //  ACTUALIZAR ubicación
    //  SP: sp_UpdateUbicacion(..., OUT p_actualizado, OUT p_mensaje)
    // =========================================================================
    public function actualizar(array $data): array
    {
        try {
            $sql = "CALL sp_UpdateUbicacion(
                        :id_ubicacion, :codigo, :pasillo, :nivel, :posicion,
                        :capacidad, :descripcion, :estado,
                        @p_actualizado, @p_mensaje
                    )";
            
            $stmt = $this->db->prepare($sql);
            $stmt->bindValue(':id_ubicacion', $data['id_ubicacion'],            PDO::PARAM_INT);
            $stmt->bindValue(':codigo',       $data['codigo'],                  PDO::PARAM_STR);
            $stmt->bindValue(':pasillo',      $data['pasillo'] ?? null,         PDO::PARAM_STR);
            $stmt->bindValue(':nivel',        $data['nivel'] ?? null,           PDO::PARAM_STR);
            $stmt->bindValue(':posicion',     $data['posicion'] ?? null,        PDO::PARAM_STR);
            $stmt->bindValue(':capacidad',    $data['capacidad'] ?? null,       PDO::PARAM_INT);
            $stmt->bindValue(':descripcion',  $data['descripcion'] ?? null,     PDO::PARAM_STR);
            $stmt->bindValue(':estado',       $data['estado'],                  PDO::PARAM_STR);
            $stmt->execute();
            $stmt->closeCursor();

            $outStmt = $this->db->query("SELECT @p_actualizado AS actualizado, @p_mensaje AS mensaje");
            $out = $outStmt->fetch(PDO::FETCH_ASSOC);

            $actualizado = (bool)($out['actualizado'] ?? false);
            $mensaje     = $out['mensaje'] ?? 'Error desconocido';

            return ['success' => $actualizado, 'mensaje' => $mensaje];

        } catch (\PDOException $e) {
            error_log("Ubicacion::actualizar — " . $e->getMessage());
            return ['success' => false, 'mensaje' => $this->parsearMensajePDO($e)];
        }
    }

    // =========================================================================
    //  ELIMINAR ubicación (solo si está vacía)
    //  SP: sp_DeleteUbicacion(p_id_ubicacion, OUT p_eliminado, OUT p_mensaje)
    // =========================================================================
    public function eliminar(int $id): array
    {
        try {
            $sql = "CALL sp_DeleteUbicacion(:id, @p_eliminado, @p_mensaje)";
            $stmt = $this->db->prepare($sql);
            $stmt->bindValue(':id', $id, PDO::PARAM_INT);
            $stmt->execute();
            $stmt->closeCursor();

            $outStmt = $this->db->query("SELECT @p_eliminado AS eliminado, @p_mensaje AS mensaje");
            $out = $outStmt->fetch(PDO::FETCH_ASSOC);

            $eliminado = (bool)($out['eliminado'] ?? false);
            $mensaje   = $out['mensaje'] ?? 'Error desconocido';

            return ['success' => $eliminado, 'mensaje' => $mensaje];

        } catch (\PDOException $e) {
            error_log("Ubicacion::eliminar — " . $e->getMessage());
            return ['success' => false, 'mensaje' => $this->parsearMensajePDO($e)];
        }
    }

    // =========================================================================
    //  OBTENER para combobox
    //  SP: sp_GetUbicacionesForSelect(p_id_almacen, p_estado)
    // =========================================================================
    public function getForSelect(?int $idAlmacen = null, ?string $estado = null): array
    {
        try {
            $sql = "CALL sp_GetUbicacionesForSelect(:id_almacen, :estado)";
            $stmt = $this->db->prepare($sql);
            $stmt->bindValue(':id_almacen', $idAlmacen ?: null, PDO::PARAM_INT);
            $stmt->bindValue(':estado',     $estado ?: null, PDO::PARAM_STR);
            $stmt->execute();
            $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
            $stmt->closeCursor();
            return $rows;

        } catch (\PDOException $e) {
            error_log("Ubicacion::getForSelect — " . $e->getMessage());
            return [];
        }
    }

    // =========================================================================
    //  UBICACIONES DISPONIBLES (con espacio)
    //  SP: sp_GetUbicacionesDisponibles(p_id_almacen, p_capacidad_minima)
    // =========================================================================
    public function getDisponibles(int $idAlmacen, ?int $capacidadMinima = null): array
    {
        try {
            $sql = "CALL sp_GetUbicacionesDisponibles(:id_almacen, :capacidad_minima)";
            $stmt = $this->db->prepare($sql);
            $stmt->bindValue(':id_almacen', $idAlmacen, PDO::PARAM_INT);
            $stmt->bindValue(':capacidad_minima', $capacidadMinima ?: null, PDO::PARAM_INT);
            $stmt->execute();
            $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
            $stmt->closeCursor();
            return $rows;

        } catch (\PDOException $e) {
            error_log("Ubicacion::getDisponibles — " . $e->getMessage());
            return [];
        }
    }

    // =========================================================================
    //  ACTUALIZAR ESTADO automáticamente según ocupación
    //  SP: sp_ActualizarEstadoUbicacion(p_id_ubicacion, OUT p_estado, OUT p_mensaje)
    // =========================================================================
    public function actualizarEstado(int $id): array
    {
        try {
            $sql = "CALL sp_ActualizarEstadoUbicacion(:id, @p_estado, @p_mensaje)";
            $stmt = $this->db->prepare($sql);
            $stmt->bindValue(':id', $id, PDO::PARAM_INT);
            $stmt->execute();
            $stmt->closeCursor();

            $outStmt = $this->db->query("SELECT @p_estado AS estado, @p_mensaje AS mensaje");
            $out = $outStmt->fetch(PDO::FETCH_ASSOC);

            return [
                'success' => true,
                'estado'  => $out['estado'] ?? null,
                'mensaje' => $out['mensaje'] ?? 'Estado actualizado'
            ];

        } catch (\PDOException $e) {
            error_log("Ubicacion::actualizarEstado — " . $e->getMessage());
            return ['success' => false, 'mensaje' => $this->parsearMensajePDO($e)];
        }
    }

    // =========================================================================
    //  RESUMEN por almacén
    //  SP: sp_GetUbicacionesPorAlmacen(p_id_almacen)
    // =========================================================================
    public function getResumenPorAlmacen(?int $idAlmacen = null): array
    {
        try {
            $sql = "CALL sp_GetUbicacionesPorAlmacen(:id_almacen)";
            $stmt = $this->db->prepare($sql);
            $stmt->bindValue(':id_almacen', $idAlmacen ?: null, PDO::PARAM_INT);
            $stmt->execute();
            $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
            $stmt->closeCursor();
            return $rows;

        } catch (\PDOException $e) {
            error_log("Ubicacion::getResumenPorAlmacen — " . $e->getMessage());
            return [];
        }
    }

    // =========================================================================
    //  ASIGNAR PRODUCTO a ubicación
    //  SP: sp_AsignarProductoAUbicacion(p_id_producto, p_id_ubicacion, p_usuario_id, OUT p_asignado, OUT p_mensaje)
    // =========================================================================
    public function asignarProducto(int $idProducto, int $idUbicacion, int $idUsuario): array
    {
        try {
            $sql = "CALL sp_AsignarProductoAUbicacion(:id_producto, :id_ubicacion, :id_usuario, @p_asignado, @p_mensaje)";
            $stmt = $this->db->prepare($sql);
            $stmt->bindValue(':id_producto', $idProducto, PDO::PARAM_INT);
            $stmt->bindValue(':id_ubicacion', $idUbicacion, PDO::PARAM_INT);
            $stmt->bindValue(':id_usuario', $idUsuario, PDO::PARAM_INT);
            $stmt->execute();
            $stmt->closeCursor();

            $outStmt = $this->db->query("SELECT @p_asignado AS asignado, @p_mensaje AS mensaje");
            $out = $outStmt->fetch(PDO::FETCH_ASSOC);

            $asignado = (bool)($out['asignado'] ?? false);
            $mensaje  = $out['mensaje'] ?? 'Error desconocido';

            return ['success' => $asignado, 'mensaje' => $mensaje];

        } catch (\PDOException $e) {
            error_log("Ubicacion::asignarProducto — " . $e->getMessage());
            return ['success' => false, 'mensaje' => $this->parsearMensajePDO($e)];
        }
    }

    // =========================================================================
    //  HELPERS
    // =========================================================================
    private function parsearMensajePDO(\PDOException $e): string
    {
        $msg = $e->getMessage();
        if (preg_match('/1644\s+(.+)$/i', $msg, $m)) {
            return trim($m[1]);
        }
        return 'Error interno del servidor';
    }
}