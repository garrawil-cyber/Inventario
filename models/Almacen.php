<?php
/**
 * ============================================================
 *  MODELO: Almacen.php
 *  Sistema : RopaVirtual – Módulo de Almacenes
 *  Arquitectura: MVC  |  Capa: Model
 *  Base de datos: ropavirtual_db
 * ============================================================
 *
 *  Stored Procedures utilizados:
 *   - sp_ListarAlmacenes
 *   - sp_GetAlmacenById
 *   - sp_InsertAlmacen
 *   - sp_UpdateAlmacen
 *   - sp_DeleteAlmacen
 *   - sp_GetAlmacenesForSelect
 *   - sp_GetEstadisticasAlmacen
 *   - sp_GetDashboardAlmacenes
 *
 *  Métodos adicionales:
 *   - getAll()     → Consulta directa con filtros y estadísticas
 *   - getAllSimple() → Versión simplificada para comboboxes
 * ============================================================
 */

require_once __DIR__ . '/../config/database.php';

class Almacen
{
    private \PDO $db;

    public function __construct()
    {
        $this->db = getDB();
    }

    // =========================================================================
    //  LISTAR almacenes con filtros y paginación (usa SP)
    //  SP: sp_ListarAlmacenes(p_filtro, p_tipo, p_estado, p_limit, p_offset)
    // =========================================================================
    public function listar(array $filters = []): array
    {
        try {
            $filtro = $filters['filtro'] ?? null;
            $tipo   = $filters['tipo']   ?? null;
            $estado = $filters['estado'] ?? null;
            $limit  = (int)($filters['limit']  ?? 20);
            $offset = (int)($filters['offset'] ?? 0);

            $sql = "CALL sp_ListarAlmacenes(:filtro, :tipo, :estado, :limit, :offset)";
            $stmt = $this->db->prepare($sql);
            $stmt->bindValue(':filtro', $filtro ?: null, PDO::PARAM_STR);
            $stmt->bindValue(':tipo',   $tipo   ?: null, PDO::PARAM_STR);
            $stmt->bindValue(':estado', $estado ?: null, PDO::PARAM_STR);
            $stmt->bindValue(':limit',  $limit,          PDO::PARAM_INT);
            $stmt->bindValue(':offset', $offset,         PDO::PARAM_INT);
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
            error_log("Almacen::listar — " . $e->getMessage());
            return ['success' => false, 'data' => [], 'total' => 0, 'mensaje' => 'Error al obtener los almacenes'];
        }
    }

    /**
 * Obtener almacenes para filtros y selectores (versión simplificada)
 */
public function getForFilters(): array
{
    try {
        $sql = "SELECT 
                    a.id_almacen,
                    a.nombre AS almacen_nombre,
                    a.codigo,
                    a.ciudad,
                    a.tipo,
                    a.estado
                FROM almacenes a
                WHERE a.estado = 'activo'
                ORDER BY a.nombre ASC";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
        
    } catch (\PDOException $e) {
        error_log("Almacen::getForFilters — " . $e->getMessage());
        return [];
    }
}

    // =========================================================================
//  OBTENER TODOS LOS ALMACENES (consulta directa con filtros y estadísticas)
//  Uso principal: reportes, dashboards, exportaciones
//  Retorna: array con estructura consistente
// =========================================================================
public function getAll(array $filters = []): array
{
    try {
        $sql = "SELECT 
                    a.id_almacen,
                    a.codigo,
                    a.nombre,
                    a.direccion,
                    a.ciudad,
                    a.capacidad_maxima,
                    a.responsable,
                    a.telefono,
                    a.tipo,
                    a.estado,
                    DATE_FORMAT(a.fecha_creacion, '%d/%m/%Y %H:%i:%s') AS fecha_creacion_format,
                    a.fecha_creacion,
                    -- Estadísticas agregadas correctamente (usando id_almacen directamente)
                    COALESCE((
                        SELECT COUNT(DISTINCT u.id_ubicacion)
                        FROM ubicaciones u
                        WHERE u.id_almacen = a.id_almacen
                    ), 0) AS total_ubicaciones,
                    COALESCE((
                        SELECT COUNT(DISTINCT p.id_producto)
                        FROM productos p
                        WHERE p.id_almacen = a.id_almacen AND p.estado != 'eliminado'
                    ), 0) AS total_productos,
                    COALESCE((
                        SELECT COUNT(DISTINCT p.id_producto)
                        FROM productos p
                        WHERE p.id_almacen = a.id_almacen AND p.estado = 'activo'
                    ), 0) AS productos_activos,
                    COALESCE((
                        SELECT COALESCE(SUM(p.stock_actual), 0)
                        FROM productos p
                        WHERE p.id_almacen = a.id_almacen AND p.estado = 'activo'
                    ), 0) AS stock_total,
                    COALESCE((
                        SELECT COALESCE(SUM(p.stock_actual * p.precio_compra), 0)
                        FROM productos p
                        WHERE p.id_almacen = a.id_almacen AND p.estado = 'activo'
                    ), 0) AS valor_costo,
                    COALESCE((
                        SELECT COALESCE(SUM(p.stock_actual * p.precio_venta), 0)
                        FROM productos p
                        WHERE p.id_almacen = a.id_almacen AND p.estado = 'activo'
                    ), 0) AS valor_venta,
                    COALESCE((
                        SELECT COALESCE(SUM(p.stock_actual * (p.precio_venta - p.precio_compra)), 0)
                        FROM productos p
                        WHERE p.id_almacen = a.id_almacen AND p.estado = 'activo'
                    ), 0) AS utilidad_potencial,
                    COALESCE((
                        SELECT COUNT(DISTINCT CASE WHEN u.estado = 'disponible' THEN u.id_ubicacion END)
                        FROM ubicaciones u
                        WHERE u.id_almacen = a.id_almacen
                    ), 0) AS ubicaciones_disponibles,
                    COALESCE((
                        SELECT COUNT(DISTINCT CASE WHEN u.estado = 'ocupado' THEN u.id_ubicacion END)
                        FROM ubicaciones u
                        WHERE u.id_almacen = a.id_almacen
                    ), 0) AS ubicaciones_ocupadas,
                    COALESCE((
                        SELECT COUNT(DISTINCT CASE WHEN u.estado = 'mantenimiento' THEN u.id_ubicacion END)
                        FROM ubicaciones u
                        WHERE u.id_almacen = a.id_almacen
                    ), 0) AS ubicaciones_mantenimiento,
                    -- Porcentaje de ocupación basado en capacidad máxima
                    CASE 
                        WHEN a.capacidad_maxima IS NOT NULL AND a.capacidad_maxima > 0 THEN
                            ROUND(
                                COALESCE((
                                    SELECT COALESCE(SUM(p.stock_actual), 0)
                                    FROM productos p
                                    WHERE p.id_almacen = a.id_almacen AND p.estado = 'activo'
                                ), 0) * 100.0 / a.capacidad_maxima, 2
                            )
                        ELSE NULL
                    END AS porcentaje_ocupacion
                FROM almacenes a
                WHERE 1=1";
            
            $params = [];
            
            // Filtro por estado
            if (!empty($filters['estado'])) {
                $sql .= " AND a.estado = :estado";
                $params[':estado'] = $filters['estado'];
            } else {
                // Por defecto, mostrar solo activos si no se especifica
                $sql .= " AND a.estado = 'activo'";
            }
            
            // Filtro por tipo
            if (!empty($filters['tipo'])) {
                $sql .= " AND a.tipo = :tipo";
                $params[':tipo'] = $filters['tipo'];
            }
            
            // Búsqueda por texto
            if (!empty($filters['buscar'])) {
                $sql .= " AND (a.nombre LIKE :buscar 
                              OR a.codigo LIKE :buscar 
                              OR a.ciudad LIKE :buscar 
                              OR a.responsable LIKE :buscar
                              OR a.direccion LIKE :buscar)";
                $params[':buscar'] = '%' . $filters['buscar'] . '%';
            }
            
            // Ordenamiento
            $ordenCampo = $filters['orden_campo'] ?? 'a.nombre';
            $ordenDireccion = $filters['orden_direccion'] ?? 'ASC';
            $ordenPermitidos = [
                'a.id_almacen', 'a.nombre', 'a.tipo', 'a.estado', 'a.ciudad', 
                'total_ubicaciones', 'total_productos', 'stock_total', 'valor_venta'
            ];
            $ordenCampo = in_array($ordenCampo, $ordenPermitidos) ? $ordenCampo : 'a.nombre';
            $ordenDireccion = strtoupper($ordenDireccion) === 'DESC' ? 'DESC' : 'ASC';
            $sql .= " ORDER BY {$ordenCampo} {$ordenDireccion}";
            
            // Paginación (opcional)
            $aplicarPaginacion = isset($filters['limit']) && isset($filters['offset']);
            if ($aplicarPaginacion) {
                $sql .= " LIMIT :limit OFFSET :offset";
                $params[':limit'] = (int)$filters['limit'];
                $params[':offset'] = (int)$filters['offset'];
            }
            
            $stmt = $this->db->prepare($sql);
            
            // Bind de parámetros con tipos específicos
            foreach ($params as $key => $value) {
                if ($key === ':limit' || $key === ':offset') {
                    $stmt->bindValue($key, $value, PDO::PARAM_INT);
                } else {
                    $stmt->bindValue($key, $value, PDO::PARAM_STR);
                }
            }
            
            $stmt->execute();
            $data = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            // Obtener total de registros (para paginación)
            $total = count($data);
            if ($aplicarPaginacion) {
                // Consulta para contar total sin paginación
                $countSql = "SELECT COUNT(*) as total FROM almacenes a WHERE 1=1";
                $countParams = [];
                
                if (!empty($filters['estado'])) {
                    $countSql .= " AND a.estado = :estado";
                    $countParams[':estado'] = $filters['estado'];
                } else {
                    $countSql .= " AND a.estado = 'activo'";
                }
                
                if (!empty($filters['tipo'])) {
                    $countSql .= " AND a.tipo = :tipo";
                    $countParams[':tipo'] = $filters['tipo'];
                }
                
                if (!empty($filters['buscar'])) {
                    $countSql .= " AND (a.nombre LIKE :buscar 
                                      OR a.codigo LIKE :buscar 
                                      OR a.ciudad LIKE :buscar 
                                      OR a.responsable LIKE :buscar)";
                    $countParams[':buscar'] = '%' . $filters['buscar'] . '%';
                }
                
                $countStmt = $this->db->prepare($countSql);
                foreach ($countParams as $key => $value) {
                    $countStmt->bindValue($key, $value, PDO::PARAM_STR);
                }
                $countStmt->execute();
                $total = (int)($countStmt->fetch(PDO::FETCH_ASSOC)['total'] ?? 0);
            }
            
            return [
                'success' => true,
                'data'    => $data,
                'total'   => $total,
                'mensaje' => count($data) . ' almacén(es) encontrado(s)'
            ];
            
        } catch (\PDOException $e) {
            error_log("Almacen::getAll — " . $e->getMessage());
            return [
                'success' => false,
                'data'    => [],
                'total'   => 0,
                'mensaje' => 'Error al obtener los almacenes: ' . $e->getMessage()
            ];
        }
    }

    // =========================================================================
    //  OBTENER TODOS LOS ALMACENES (versión simplificada)
    //  Uso: comboboxes, selects, reportes rápidos
    // =========================================================================
    public function getAllSimple(array $filters = []): array
    {
        try {
            $sql = "SELECT 
                        a.id_almacen,
                        a.codigo,
                        a.nombre,
                        a.ciudad,
                        a.tipo,
                        a.estado,
                        a.responsable,
                        COALESCE((
                            SELECT COUNT(DISTINCT u.id_ubicacion)
                            FROM ubicaciones u
                            WHERE u.id_almacen = a.id_almacen
                        ), 0) AS total_ubicaciones,
                        COALESCE((
                            SELECT COUNT(DISTINCT CASE WHEN u.estado = 'disponible' THEN u.id_ubicacion END)
                            FROM ubicaciones u
                            WHERE u.id_almacen = a.id_almacen
                        ), 0) AS ubicaciones_disponibles
                    FROM almacenes a
                    WHERE 1=1";
            
            $params = [];
            
            if (!empty($filters['estado'])) {
                $sql .= " AND a.estado = :estado";
                $params[':estado'] = $filters['estado'];
            } else {
                $sql .= " AND a.estado = 'activo'";
            }
            
            if (!empty($filters['tipo'])) {
                $sql .= " AND a.tipo = :tipo";
                $params[':tipo'] = $filters['tipo'];
            }
            
            if (!empty($filters['buscar'])) {
                $sql .= " AND (a.nombre LIKE :buscar 
                              OR a.codigo LIKE :buscar 
                              OR a.ciudad LIKE :buscar 
                              OR a.responsable LIKE :buscar)";
                $params[':buscar'] = '%' . $filters['buscar'] . '%';
            }
            
            $sql .= " ORDER BY a.nombre ASC";
            
            $stmt = $this->db->prepare($sql);
            foreach ($params as $key => $value) {
                $stmt->bindValue($key, $value, PDO::PARAM_STR);
            }
            $stmt->execute();
            $data = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            return [
                'success' => true,
                'data'    => $data,
                'total'   => count($data),
                'mensaje' => 'OK'
            ];
            
        } catch (\PDOException $e) {
            error_log("Almacen::getAllSimple — " . $e->getMessage());
            return [
                'success' => false,
                'data'    => [],
                'total'   => 0,
                'mensaje' => 'Error al obtener los almacenes'
            ];
        }
    }

    // =========================================================================
    //  OBTENER almacén por ID (con estadísticas y ubicaciones)
    //  SP: sp_GetAlmacenById(p_id_almacen)
    // =========================================================================
    public function getById(int $id): array
    {
        try {
            $stmt = $this->db->prepare("CALL sp_GetAlmacenById(:id)");
            $stmt->bindParam(':id', $id, PDO::PARAM_INT);
            $stmt->execute();

            $almacen = $stmt->fetch(PDO::FETCH_ASSOC);

            $ubicaciones = [];
            if ($stmt->nextRowset()) {
                $ubicaciones = $stmt->fetchAll(PDO::FETCH_ASSOC);
            }

            $stmt->closeCursor();

            if ($almacen) {
                return [
                    'success'     => true,
                    'almacen'     => $almacen,
                    'ubicaciones' => $ubicaciones
                ];
            }

            return ['success' => false, 'mensaje' => 'Almacén no encontrado'];

        } catch (\PDOException $e) {
            error_log("Almacen::getById — " . $e->getMessage());
            return ['success' => false, 'mensaje' => 'Error al obtener el almacén'];
        }
    }

    // =========================================================================
    //  CREAR nuevo almacén
    //  SP: sp_InsertAlmacen(..., OUT p_id_almacen, OUT p_mensaje)
    // =========================================================================
    public function crear(array $data): array
    {
        try {
            $sql = "CALL sp_InsertAlmacen(
                        :codigo, :nombre, :direccion, :ciudad, :capacidad_maxima,
                        :responsable, :telefono, :tipo, :estado,
                        @p_id_almacen, @p_mensaje
                    )";
            
            $stmt = $this->db->prepare($sql);
            $stmt->bindValue(':codigo',           $data['codigo'] ?? null,        PDO::PARAM_STR);
            $stmt->bindValue(':nombre',           $data['nombre'],                PDO::PARAM_STR);
            $stmt->bindValue(':direccion',        $data['direccion'] ?? null,     PDO::PARAM_STR);
            $stmt->bindValue(':ciudad',           $data['ciudad'] ?? null,        PDO::PARAM_STR);
            $stmt->bindValue(':capacidad_maxima', $data['capacidad_maxima'] ?? null, PDO::PARAM_INT);
            $stmt->bindValue(':responsable',      $data['responsable'],           PDO::PARAM_STR);
            $stmt->bindValue(':telefono',         $data['telefono'] ?? null,      PDO::PARAM_STR);
            $stmt->bindValue(':tipo',             $data['tipo'] ?? 'secundario',  PDO::PARAM_STR);
            $stmt->bindValue(':estado',           $data['estado'] ?? 'activo',    PDO::PARAM_STR);
            $stmt->execute();
            $stmt->closeCursor();

            $outStmt = $this->db->query("SELECT @p_id_almacen AS id_almacen, @p_mensaje AS mensaje");
            $out = $outStmt->fetch(PDO::FETCH_ASSOC);

            $idAlmacen = (int)($out['id_almacen'] ?? 0);
            $mensaje   = $out['mensaje'] ?? 'Error desconocido';

            return ['success' => $idAlmacen > 0, 'id_almacen' => $idAlmacen, 'mensaje' => $mensaje];

        } catch (\PDOException $e) {
            error_log("Almacen::crear — " . $e->getMessage());
            return ['success' => false, 'id_almacen' => 0, 'mensaje' => $this->parsearMensajePDO($e)];
        }
    }

    // =========================================================================
    //  ACTUALIZAR almacén
    //  SP: sp_UpdateAlmacen(..., OUT p_actualizado, OUT p_mensaje)
    // =========================================================================
    public function actualizar(array $data): array
    {
        try {
            $sql = "CALL sp_UpdateAlmacen(
                        :id_almacen, :codigo, :nombre, :direccion, :ciudad,
                        :capacidad_maxima, :responsable, :telefono, :tipo, :estado,
                        @p_actualizado, @p_mensaje
                    )";
            
            $stmt = $this->db->prepare($sql);
            $stmt->bindValue(':id_almacen',       $data['id_almacen'],            PDO::PARAM_INT);
            $stmt->bindValue(':codigo',           $data['codigo'],                PDO::PARAM_STR);
            $stmt->bindValue(':nombre',           $data['nombre'],                PDO::PARAM_STR);
            $stmt->bindValue(':direccion',        $data['direccion'] ?? null,     PDO::PARAM_STR);
            $stmt->bindValue(':ciudad',           $data['ciudad'] ?? null,        PDO::PARAM_STR);
            $stmt->bindValue(':capacidad_maxima', $data['capacidad_maxima'] ?? null, PDO::PARAM_INT);
            $stmt->bindValue(':responsable',      $data['responsable'],           PDO::PARAM_STR);
            $stmt->bindValue(':telefono',         $data['telefono'] ?? null,      PDO::PARAM_STR);
            $stmt->bindValue(':tipo',             $data['tipo'],                  PDO::PARAM_STR);
            $stmt->bindValue(':estado',           $data['estado'],                PDO::PARAM_STR);
            $stmt->execute();
            $stmt->closeCursor();

            $outStmt = $this->db->query("SELECT @p_actualizado AS actualizado, @p_mensaje AS mensaje");
            $out = $outStmt->fetch(PDO::FETCH_ASSOC);

            $actualizado = (bool)($out['actualizado'] ?? false);
            $mensaje     = $out['mensaje'] ?? 'Error desconocido';

            return ['success' => $actualizado, 'mensaje' => $mensaje];

        } catch (\PDOException $e) {
            error_log("Almacen::actualizar — " . $e->getMessage());
            return ['success' => false, 'mensaje' => $this->parsearMensajePDO($e)];
        }
    }

    // =========================================================================
    //  ELIMINAR (desactivar) almacén
    //  SP: sp_DeleteAlmacen(p_id_almacen, OUT p_eliminado, OUT p_mensaje)
    // =========================================================================
    public function eliminar(int $id): array
    {
        try {
            $sql = "CALL sp_DeleteAlmacen(:id, @p_eliminado, @p_mensaje)";
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
            error_log("Almacen::eliminar — " . $e->getMessage());
            return ['success' => false, 'mensaje' => $this->parsearMensajePDO($e)];
        }
    }

    // =========================================================================
    //  OBTENER para combobox (usa SP)
    //  SP: sp_GetAlmacenesForSelect(p_tipo)
    // =========================================================================
    public function getForSelect(?string $tipo = null): array
    {
        try {
            $sql = "CALL sp_GetAlmacenesForSelect(:tipo)";
            $stmt = $this->db->prepare($sql);
            $stmt->bindValue(':tipo', $tipo ?: null, PDO::PARAM_STR);
            $stmt->execute();
            $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
            $stmt->closeCursor();
            return $rows;

        } catch (\PDOException $e) {
            error_log("Almacen::getForSelect — " . $e->getMessage());
            return [];
        }
    }

    // =========================================================================
    //  OBTENER para combobox (versión directa sin SP, más rápida)
    // =========================================================================
    public function getForSelectDirect(?string $tipo = null, ?string $estado = 'activo'): array
    {
        try {
            $sql = "SELECT 
                        a.id_almacen AS value,
                        CONCAT(a.nombre, ' (', a.codigo, ')', 
                               IFNULL(CONCAT(' - ', a.ciudad), '')) AS text,
                        a.codigo,
                        a.nombre,
                        a.ciudad,
                        a.tipo,
                        a.estado,
                        COALESCE((
                            SELECT COUNT(DISTINCT u.id_ubicacion)
                            FROM ubicaciones u
                            WHERE u.id_almacen = a.id_almacen AND u.estado = 'disponible'
                        ), 0) AS ubicaciones_disponibles
                    FROM almacenes a
                    WHERE 1=1";
            
            $params = [];
            
            if ($estado !== null) {
                $sql .= " AND a.estado = :estado";
                $params[':estado'] = $estado;
            }
            
            if (!empty($tipo)) {
                $sql .= " AND a.tipo = :tipo";
                $params[':tipo'] = $tipo;
            }
            
            $sql .= " ORDER BY a.nombre ASC";
            
            $stmt = $this->db->prepare($sql);
            foreach ($params as $key => $value) {
                $stmt->bindValue($key, $value, PDO::PARAM_STR);
            }
            $stmt->execute();
            
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
            
        } catch (\PDOException $e) {
            error_log("Almacen::getForSelectDirect — " . $e->getMessage());
            return [];
        }
    }

    // =========================================================================
    //  ESTADÍSTICAS del almacén
    //  SP: sp_GetEstadisticasAlmacen(p_id_almacen)
    // =========================================================================
    public function getEstadisticas(int $id): array
    {
        try {
            $stmt = $this->db->prepare("CALL sp_GetEstadisticasAlmacen(:id)");
            $stmt->bindParam(':id', $id, PDO::PARAM_INT);
            $stmt->execute();

            $ubicacionesPorEstado = $stmt->fetchAll(PDO::FETCH_ASSOC);

            $topProductos = [];
            if ($stmt->nextRowset()) {
                $topProductos = $stmt->fetchAll(PDO::FETCH_ASSOC);
            }

            $distribucionCategorias = [];
            if ($stmt->nextRowset()) {
                $distribucionCategorias = $stmt->fetchAll(PDO::FETCH_ASSOC);
            }

            $stmt->closeCursor();

            return [
                'success' => true,
                'ubicaciones_por_estado' => $ubicacionesPorEstado,
                'top_productos' => $topProductos,
                'distribucion_categorias' => $distribucionCategorias
            ];

        } catch (\PDOException $e) {
            error_log("Almacen::getEstadisticas — " . $e->getMessage());
            return ['success' => false, 'mensaje' => 'Error al obtener estadísticas'];
        }
    }

    // =========================================================================
    //  DASHBOARD de almacenes
    //  SP: sp_GetDashboardAlmacenes()
    // =========================================================================
    public function getDashboardStats(): array
    {
        try {
            $stmt = $this->db->prepare("CALL sp_GetDashboardAlmacenes()");
            $stmt->execute();

            $resumenGeneral = $stmt->fetch(PDO::FETCH_ASSOC);

            $capacidadPorAlmacen = [];
            if ($stmt->nextRowset()) {
                $capacidadPorAlmacen = $stmt->fetchAll(PDO::FETCH_ASSOC);
            }

            $stmt->closeCursor();

            return [
                'success' => true,
                'resumen_general' => $resumenGeneral ?: [],
                'capacidad_por_almacen' => $capacidadPorAlmacen
            ];

        } catch (\PDOException $e) {
            error_log("Almacen::getDashboardStats — " . $e->getMessage());
            return ['success' => false, 'resumen_general' => [], 'capacidad_por_almacen' => []];
        }
    }

    // =========================================================================
    //  CONTAR almacenes por tipo
    // =========================================================================
    public function countByTipo(?string $estado = 'activo'): array
    {
        try {
            $sql = "SELECT 
                        a.tipo,
                        COUNT(*) AS total
                    FROM almacenes a
                    WHERE 1=1";
            
            $params = [];
            
            if ($estado !== null) {
                $sql .= " AND a.estado = :estado";
                $params[':estado'] = $estado;
            }
            
            $sql .= " GROUP BY a.tipo";
            
            $stmt = $this->db->prepare($sql);
            foreach ($params as $key => $value) {
                $stmt->bindValue($key, $value, PDO::PARAM_STR);
            }
            $stmt->execute();
            
            $resultados = $stmt->fetchAll(PDO::FETCH_ASSOC);
            $counts = [];
            foreach ($resultados as $row) {
                $counts[$row['tipo']] = (int)$row['total'];
            }
            
            return $counts;
            
        } catch (\PDOException $e) {
            error_log("Almacen::countByTipo — " . $e->getMessage());
            return [];
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