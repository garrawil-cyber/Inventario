<?php
/**
 * ============================================================
 *  MODELO: InventarioFisico.php
 *  Sistema   : RopaVirtual – Módulo de Inventario Físico
 *  Capa      : Model  (MVC)
 *  Base datos: ropavirtual_db
 * ============================================================
 *
 *  Stored Procedures mapeados (11):
 *   sp_IF_CrearInventario     → crear()
 *   sp_IF_AgregarDetalle      → agregarDetalle()
 *   sp_IF_ActualizarDetalle   → actualizarDetalle()
 *   sp_IF_EliminarDetalle     → eliminarDetalle()
 *   sp_IF_IniciarConteo       → iniciarConteo()
 *   sp_IF_FinalizarInventario → finalizar()
 *   sp_IF_CancelarInventario  → cancelar()
 *   sp_IF_GetCabecera         → getCabecera()
 *   sp_IF_ListarInventarios   → listar()
 *   sp_IF_GetDetalle          → getDetalle()
 *   sp_IF_ResumenDiferencias  → resumenDiferencias()
 *
 *  Patrón OUT params:
 *    CALL sp_(..., @out1, @out2);   → execute() + closeCursor()
 *    SELECT @out1, @out2;           → query() + fetch()
 *
 *  SPs sin OUT params (solo SELECT):
 *    execute() + fetchAll/fetch + closeCursor()
 * ============================================================
 */

require_once __DIR__ . '/../config/database.php';

class InventarioFisico
{
    private \PDO $db;

    public function __construct()
    {
        $this->db = getDB();
    }

    // =========================================================================
    //  1. CREAR INVENTARIO
    //  SP: sp_IF_CrearInventario(id_almacen, tipo, fecha_inicio,
    //       responsable, observaciones, id_usuario,
    //       OUT p_id_inventario, OUT p_codigo, OUT p_mensaje)
    // =========================================================================
    public function crear(array $data): array
    {
        try {
            $sql = "CALL sp_IF_CrearInventario(
                        :id_almacen, :tipo, :fecha_inicio,
                        :responsable, :observaciones, :id_usuario,
                        @p_id_inventario, @p_codigo, @p_mensaje
                    )";
            $stmt = $this->db->prepare($sql);
            $stmt->bindValue(':id_almacen',    (int)$data['id_almacen'],            \PDO::PARAM_INT);
            $stmt->bindValue(':tipo',           $data['tipo']          ?? 'total',   \PDO::PARAM_STR);
            $stmt->bindValue(':fecha_inicio',   $data['fecha_inicio']  ?? date('Y-m-d'), \PDO::PARAM_STR);
            $stmt->bindValue(':responsable',    $data['responsable']   ?? null,      \PDO::PARAM_STR);
            $stmt->bindValue(':observaciones',  $data['observaciones'] ?? null,      \PDO::PARAM_STR);
            $stmt->bindValue(':id_usuario',     (int)$data['id_usuario'],            \PDO::PARAM_INT);
            $stmt->execute();
            $stmt->closeCursor();

            $out = $this->db->query(
                "SELECT @p_id_inventario AS id_inventario,
                        @p_codigo        AS codigo,
                        @p_mensaje       AS mensaje"
            )->fetch(\PDO::FETCH_ASSOC);

            $idInventario = (int)($out['id_inventario'] ?? 0);
            $codigo       = $out['codigo']  ?? '';
            $mensaje      = $out['mensaje'] ?? 'Error desconocido';

            return [
                'success'       => $idInventario > 0,
                'id_inventario' => $idInventario,
                'codigo'        => $codigo,
                'mensaje'       => $mensaje,
            ];

        } catch (\PDOException $e) {
            error_log("Inventario::crear — " . $e->getMessage());
            return [
                'success'       => false,
                'id_inventario' => 0,
                'codigo'        => '',
                'mensaje'       => $this->parsearError($e),
            ];
        }
    }

    // =========================================================================
    //  2. AGREGAR DETALLE (línea de producto)
    //  SP: sp_IF_AgregarDetalle(id_inventario, id_producto, stock_fisico,
    //       costo_unitario, observaciones, contado_por,
    //       OUT p_id_detalle, OUT p_mensaje)
    // =========================================================================
    public function agregarDetalle(array $data): array
    {
        try {
            $sql = "CALL sp_IF_AgregarDetalle(
                        :id_inventario, :id_producto, :stock_fisico,
                        :costo_unitario, :observaciones, :contado_por,
                        @p_id_detalle, @p_mensaje
                    )";
            $stmt = $this->db->prepare($sql);
            $stmt->bindValue(':id_inventario',  (int)$data['id_inventario'],        \PDO::PARAM_INT);
            $stmt->bindValue(':id_producto',    (int)$data['id_producto'],          \PDO::PARAM_INT);
            $stmt->bindValue(':stock_fisico',   (int)$data['stock_fisico'],         \PDO::PARAM_INT);
            $stmt->bindValue(':costo_unitario', $data['costo_unitario'] ?? null,    \PDO::PARAM_STR);
            $stmt->bindValue(':observaciones',  $data['observaciones']  ?? null,    \PDO::PARAM_STR);
            $stmt->bindValue(':contado_por',    $data['contado_por']    ?? null,    \PDO::PARAM_STR);
            $stmt->execute();
            $stmt->closeCursor();

            $out = $this->db->query(
                "SELECT @p_id_detalle AS id_detalle, @p_mensaje AS mensaje"
            )->fetch(\PDO::FETCH_ASSOC);

            $idDetalle = (int)($out['id_detalle'] ?? 0);
            $mensaje   = $out['mensaje'] ?? 'Error desconocido';

            return [
                'success'    => $idDetalle > 0,
                'id_detalle' => $idDetalle,
                'mensaje'    => $mensaje,
            ];

        } catch (\PDOException $e) {
            error_log("InventarioFisico::agregarDetalle — " . $e->getMessage());
            return ['success' => false, 'id_detalle' => 0, 'mensaje' => $this->parsearError($e)];
        }
    }

    // =========================================================================
    //  3. ACTUALIZAR DETALLE
    //  SP: sp_IF_ActualizarDetalle(id_detalle, stock_fisico, costo_unitario,
    //       observaciones, contado_por, verificado_por, OUT p_mensaje)
    // =========================================================================
    public function actualizarDetalle(array $data): array
    {
        try {
            $sql = "CALL sp_IF_ActualizarDetalle(
                        :id_detalle, :stock_fisico, :costo_unitario,
                        :observaciones, :contado_por, :verificado_por,
                        @p_mensaje
                    )";
            $stmt = $this->db->prepare($sql);
            $stmt->bindValue(':id_detalle',    (int)$data['id_detalle'],          \PDO::PARAM_INT);
            $stmt->bindValue(':stock_fisico',  (int)$data['stock_fisico'],        \PDO::PARAM_INT);
            $stmt->bindValue(':costo_unitario',$data['costo_unitario'] ?? null,   \PDO::PARAM_STR);
            $stmt->bindValue(':observaciones', $data['observaciones']  ?? null,   \PDO::PARAM_STR);
            $stmt->bindValue(':contado_por',   $data['contado_por']    ?? null,   \PDO::PARAM_STR);
            $stmt->bindValue(':verificado_por',$data['verificado_por'] ?? null,   \PDO::PARAM_STR);
            $stmt->execute();
            $stmt->closeCursor();

            $out     = $this->db->query("SELECT @p_mensaje AS mensaje")->fetch(\PDO::FETCH_ASSOC);
            $mensaje = $out['mensaje'] ?? 'Error desconocido';
            $success = str_contains($mensaje, 'correctamente');

            return ['success' => $success, 'mensaje' => $mensaje];

        } catch (\PDOException $e) {
            error_log("InventarioFisico::actualizarDetalle — " . $e->getMessage());
            return ['success' => false, 'mensaje' => $this->parsearError($e)];
        }
    }

    // =========================================================================
    //  4. ELIMINAR DETALLE
    //  SP: sp_IF_EliminarDetalle(id_detalle, OUT p_eliminado, OUT p_mensaje)
    // =========================================================================
    public function eliminarDetalle(int $idDetalle): array
    {
        try {
            $sql  = "CALL sp_IF_EliminarDetalle(:id_detalle, @p_eliminado, @p_mensaje)";
            $stmt = $this->db->prepare($sql);
            $stmt->bindValue(':id_detalle', $idDetalle, \PDO::PARAM_INT);
            $stmt->execute();
            $stmt->closeCursor();

            $out      = $this->db->query(
                "SELECT @p_eliminado AS eliminado, @p_mensaje AS mensaje"
            )->fetch(\PDO::FETCH_ASSOC);

            return [
                'success' => (bool)($out['eliminado'] ?? false),
                'mensaje' => $out['mensaje'] ?? 'Error desconocido',
            ];

        } catch (\PDOException $e) {
            error_log("InventarioFisico::eliminarDetalle — " . $e->getMessage());
            return ['success' => false, 'mensaje' => $this->parsearError($e)];
        }
    }

    // =========================================================================
    //  5. INICIAR CONTEO  planificado → en_proceso
    //  SP: sp_IF_IniciarConteo(id_inventario, id_usuario, OUT p_mensaje)
    // =========================================================================
    public function iniciarConteo(int $idInventario, int $idUsuario): array
    {
        try {
            $sql  = "CALL sp_IF_IniciarConteo(:id_inventario, :id_usuario, @p_mensaje)";
            $stmt = $this->db->prepare($sql);
            $stmt->bindValue(':id_inventario', $idInventario, \PDO::PARAM_INT);
            $stmt->bindValue(':id_usuario',    $idUsuario,    \PDO::PARAM_INT);
            $stmt->execute();
            $stmt->closeCursor();

            $out     = $this->db->query("SELECT @p_mensaje AS mensaje")->fetch(\PDO::FETCH_ASSOC);
            $mensaje = $out['mensaje'] ?? 'Error desconocido';
            $success = str_contains($mensaje, 'iniciado');

            return ['success' => $success, 'mensaje' => $mensaje];

        } catch (\PDOException $e) {
            error_log("InventarioFisico::iniciarConteo — " . $e->getMessage());
            return ['success' => false, 'mensaje' => $this->parsearError($e)];
        }
    }

    // =========================================================================
    //  6. FINALIZAR INVENTARIO  en_proceso → finalizado + ajuste stock
    //  SP: sp_IF_FinalizarInventario(id_inventario, id_usuario,
    //       aplicar_ajustes, OUT p_ajustados, OUT p_mensaje)
    // =========================================================================
   public function finalizar(int $idInventario, int $idUsuario, bool $aplicarAjustes = true): array
{
    try {
        error_log("=== FINALIZAR MODELO ===");
        error_log("idInventario: $idInventario, idUsuario: $idUsuario, aplicarAjustes: " . ($aplicarAjustes ? 'SI' : 'NO'));
        
        $sql = "CALL sp_IF_FinalizarInventario(
                    :id_inventario, :id_usuario, :aplicar_ajustes,
                    @p_ajustados, @p_mensaje
                )";
        $stmt = $this->db->prepare($sql);
        $stmt->bindValue(':id_inventario',   $idInventario,            \PDO::PARAM_INT);
        $stmt->bindValue(':id_usuario',      $idUsuario,               \PDO::PARAM_INT);
        $stmt->bindValue(':aplicar_ajustes', $aplicarAjustes ? 1 : 0, \PDO::PARAM_INT);
        
        error_log("SP ejecutado. Verificando resultados...");
        $stmt->execute();
        $stmt->closeCursor();

        // Esperar un momento para que MySQL procese los OUT params
        usleep(100000); // 0.1 segundos

        $out = $this->db->query(
            "SELECT @p_ajustados AS ajustados, @p_mensaje AS mensaje"
        )->fetch(\PDO::FETCH_ASSOC);
        
        error_log("Resultado OUT params: " . json_encode($out));
        
        $mensaje   = $out['mensaje']   ?? 'Error desconocido';
        $ajustados = (int)($out['ajustados'] ?? 0);
        $success   = str_contains($mensaje, 'finalizado') || str_contains($mensaje, 'ajust');
        
        error_log("success: " . ($success ? 'true' : 'false') . ", mensaje: $mensaje");
        
        return [
            'success'   => $success,
            'ajustados' => $ajustados,
            'mensaje'   => $mensaje,
        ];

    } catch (\PDOException $e) {
        error_log("ERROR PDO en finalizar: " . $e->getMessage());
        error_log("SQLSTATE: " . $e->getCode());
        return [
            'success'   => false, 
            'ajustados' => 0, 
            'mensaje'   => 'Error en la base de datos: ' . $e->getMessage()
        ];
    }
}
    // =========================================================================
    //  7. CANCELAR INVENTARIO
    //  SP: sp_IF_CancelarInventario(id_inventario, id_usuario, motivo,
    //       OUT p_cancelado, OUT p_mensaje)
    // =========================================================================
    public function cancelar(int $idInventario, int $idUsuario, string $motivo = ''): array
    {
        try {
            $sql  = "CALL sp_IF_CancelarInventario(
                        :id_inventario, :id_usuario, :motivo,
                        @p_cancelado, @p_mensaje
                    )";
            $stmt = $this->db->prepare($sql);
            $stmt->bindValue(':id_inventario', $idInventario,          \PDO::PARAM_INT);
            $stmt->bindValue(':id_usuario',    $idUsuario,             \PDO::PARAM_INT);
            $stmt->bindValue(':motivo',        $motivo ?: null,        \PDO::PARAM_STR);
            $stmt->execute();
            $stmt->closeCursor();

            $out = $this->db->query(
                "SELECT @p_cancelado AS cancelado, @p_mensaje AS mensaje"
            )->fetch(\PDO::FETCH_ASSOC);

            return [
                'success' => (bool)($out['cancelado'] ?? false),
                'mensaje' => $out['mensaje'] ?? 'Error desconocido',
            ];

        } catch (\PDOException $e) {
            error_log("InventarioFisico::cancelar — " . $e->getMessage());
            return ['success' => false, 'mensaje' => $this->parsearError($e)];
        }
    }

    // =========================================================================
    //  8. OBTENER CABECERA + STATS
    //  SP: sp_IF_GetCabecera(id_inventario)
    //  RS1 → cabecera   RS2 → estadísticas
    // =========================================================================
    public function getCabecera(int $idInventario): array
    {
        try {
            $stmt = $this->db->prepare("CALL sp_IF_GetCabecera(:id)");
            $stmt->bindValue(':id', $idInventario, \PDO::PARAM_INT);
            $stmt->execute();

            // RS1: cabecera
            $cabecera = $stmt->fetch(\PDO::FETCH_ASSOC);

            // RS2: stats del detalle
            $stats = [];
            if ($stmt->nextRowset()) {
                $stats = $stmt->fetch(\PDO::FETCH_ASSOC) ?: [];
            }
            $stmt->closeCursor();

            if (!$cabecera) {
                return ['success' => false, 'cabecera' => null, 'stats' => []];
            }

            return ['success' => true, 'cabecera' => $cabecera, 'stats' => $stats];

        } catch (\PDOException $e) {
            error_log("InventarioFisico::getCabecera — " . $e->getMessage());
            return ['success' => false, 'cabecera' => null, 'stats' => []];
        }
    }

    // =========================================================================
    //  9. LISTAR INVENTARIOS (paginado + filtros)
    //  SP: sp_IF_ListarInventarios(filtro, estado, tipo, id_almacen,
    //       fecha_desde, fecha_hasta, limit, offset)
    //  RS1 → filas   RS2 → total_registros
    // =========================================================================
    public function listar(array $filters = []): array
    {
        try {
            $sql = "CALL sp_IF_ListarInventarios(
                        :filtro, :estado, :tipo, :id_almacen,
                        :fecha_desde, :fecha_hasta, :limit, :offset
                    )";
            $stmt = $this->db->prepare($sql);
            $stmt->bindValue(':filtro',      $filters['filtro']      ?: null, \PDO::PARAM_STR);
            $stmt->bindValue(':estado',      $filters['estado']      ?: null, \PDO::PARAM_STR);
            $stmt->bindValue(':tipo',        $filters['tipo']        ?: null, \PDO::PARAM_STR);
            $stmt->bindValue(':id_almacen',  (int)($filters['id_almacen'] ?? 0) ?: null, \PDO::PARAM_INT);
            $stmt->bindValue(':fecha_desde', $filters['fecha_desde'] ?: null, \PDO::PARAM_STR);
            $stmt->bindValue(':fecha_hasta', $filters['fecha_hasta'] ?: null, \PDO::PARAM_STR);
            $stmt->bindValue(':limit',       (int)($filters['limit']  ?? 20), \PDO::PARAM_INT);
            $stmt->bindValue(':offset',      (int)($filters['offset'] ?? 0),  \PDO::PARAM_INT);
            $stmt->execute();

            // RS1: filas
            $data = $stmt->fetchAll(\PDO::FETCH_ASSOC);

            // RS2: total_registros (SQL_CALC_FOUND_ROWS)
            $total = 0;
            if ($stmt->nextRowset()) {
                $row   = $stmt->fetch(\PDO::FETCH_ASSOC);
                $total = (int)($row['total_registros'] ?? 0);
            }
            $stmt->closeCursor();

            return ['success' => true, 'data' => $data, 'total' => $total];

        } catch (\PDOException $e) {
            error_log("InventarioFisico::listar — " . $e->getMessage());
            return ['success' => false, 'data' => [], 'total' => 0];
        }
    }

    // =========================================================================
    //  10. OBTENER DETALLE DE LÍNEAS
    //  SP: sp_IF_GetDetalle(id_inventario)
    //  RS1 → array de líneas con producto, diferencia, valor
    // =========================================================================
    public function getDetalle(int $idInventario): array
    {
        try {
            $stmt = $this->db->prepare("CALL sp_IF_GetDetalle(:id)");
            $stmt->bindValue(':id', $idInventario, \PDO::PARAM_INT);
            $stmt->execute();
            $rows = $stmt->fetchAll(\PDO::FETCH_ASSOC);
            $stmt->closeCursor();

            return ['success' => true, 'data' => $rows];

        } catch (\PDOException $e) {
            error_log("InventarioFisico::getDetalle — " . $e->getMessage());
            return ['success' => false, 'data' => []];
        }
    }

    // =========================================================================
    //  11. RESUMEN DE DIFERENCIAS (para vista ver / reportes)
    //  SP: sp_IF_ResumenDiferencias(id_inventario)
    //  RS1 → totales globales   RS2 → top 10 productos
    // =========================================================================
    public function resumenDiferencias(int $idInventario): array
    {
        try {
            $stmt = $this->db->prepare("CALL sp_IF_ResumenDiferencias(:id)");
            $stmt->bindValue(':id', $idInventario, \PDO::PARAM_INT);
            $stmt->execute();

            // RS1: totales
            $totales = $stmt->fetch(\PDO::FETCH_ASSOC) ?: [];

            // RS2: top productos
            $topProductos = [];
            if ($stmt->nextRowset()) {
                $topProductos = $stmt->fetchAll(\PDO::FETCH_ASSOC);
            }
            $stmt->closeCursor();

            return [
                'success'      => true,
                'totales'      => $totales,
                'topProductos' => $topProductos,
            ];

        } catch (\PDOException $e) {
            error_log("InventarioFisico::resumenDiferencias — " . $e->getMessage());
            return ['success' => false, 'totales' => [], 'topProductos' => []];
        }
    }

    // =========================================================================
    //  AUXILIAR: Almacenes activos para el select del formulario crear
    // =========================================================================
    public function getAlmacenesActivos(): array
    {
        try {
            $stmt = $this->db->query(
                "SELECT id_almacen, nombre, codigo
                 FROM almacenes
                 WHERE estado = 'activo'
                 ORDER BY nombre ASC"
            );
            return $stmt->fetchAll(\PDO::FETCH_ASSOC);
        } catch (\PDOException $e) {
            error_log("InventarioFisico::getAlmacenesActivos — " . $e->getMessage());
            return [];
        }
    }

    // =========================================================================
    //  AUXILIAR: Productos activos para búsqueda en el formulario de detalle
    // =========================================================================
    public function buscarProductos(string $termino, int $limit = 15): array
    {
        try {
            $like = "%{$termino}%";
            $stmt = $this->db->prepare(
                "SELECT p.id_producto, p.codigo, p.sku, p.nombre,
                        p.stock_actual, p.precio_compra, p.unidad_medida,
                        c.nombre AS categoria
                 FROM productos p
                 LEFT JOIN categorias c ON p.id_categoria = c.id_categoria
                 WHERE p.estado = 'activo'
                   AND (p.nombre LIKE :b1 OR p.codigo LIKE :b2 OR p.sku LIKE :b3)
                 ORDER BY p.nombre ASC
                 LIMIT :limit"
            );
            $stmt->bindValue(':b1', $like);
            $stmt->bindValue(':b2', $like);
            $stmt->bindValue(':b3', $like);
            $stmt->bindValue(':limit', $limit, \PDO::PARAM_INT);
            $stmt->execute();
            return $stmt->fetchAll(\PDO::FETCH_ASSOC);
        } catch (\PDOException $e) {
            error_log("InventarioFisico::buscarProductos — " . $e->getMessage());
            return [];
        }
    }

    // =========================================================================
    //  HELPER PRIVADO — parsear mensajes de negocio desde PDOException
    //  MySQL SIGNAL SQLSTATE '45000' → código de error 1644
    // =========================================================================
    private function parsearError(\PDOException $e): string
    {
        $msg = $e->getMessage();
        if (preg_match('/1644\s+(.+)$/i', $msg, $m)) {
            return trim($m[1]);
        }
        return 'Error interno del servidor';
    }





    // =========================================================================
//  OBTENER PRODUCTOS POR ALMACÉN (para el formulario de conteo)
// =========================================================================
public function getProductosPorAlmacen(int $idAlmacen): array
{
    try {
        $stmt = $this->db->prepare("
            SELECT 
                p.id_producto,
                p.codigo,
                p.sku,
                p.nombre,
                p.stock_actual,
                p.precio_compra,
                p.precio_venta,
                c.nombre AS categoria
            FROM productos p
            LEFT JOIN categorias c ON p.id_categoria = c.id_categoria
            WHERE p.estado = 'activo'
            ORDER BY p.nombre ASC
        ");
        $stmt->execute();
        return $stmt->fetchAll(\PDO::FETCH_ASSOC);
    } catch (\PDOException $e) {
        error_log("InventarioFisico::getProductosPorAlmacen — " . $e->getMessage());
        return [];
    }
}

// =========================================================================
//  OBTENER DETALLE POR PRODUCTO (para saber si ya existe)
// =========================================================================
public function getDetalleByProducto(int $idInventario, int $idProducto): ?array
{
    try {
        $stmt = $this->db->prepare("
            SELECT id_detalle, stock_fisico, observaciones
            FROM inventario_fisico_detalle
            WHERE id_inventario = :id_inventario AND id_producto = :id_producto
        ");
        $stmt->bindValue(':id_inventario', $idInventario, \PDO::PARAM_INT);
        $stmt->bindValue(':id_producto', $idProducto, \PDO::PARAM_INT);
        $stmt->execute();
        $result = $stmt->fetch(\PDO::FETCH_ASSOC);
        return $result ?: null;
    } catch (\PDOException $e) {
        error_log("InventarioFisico::getDetalleByProducto — " . $e->getMessage());
        return null;
    }
}
}