<?php
/**
 * ============================================================
 *  MODELO: Compra.php
 *  Sistema : RopaVirtual – Módulo de Compras
 *  Arquitectura: MVC  |  Capa: Model
 *  Base de datos: ropavirtual_db
 * ============================================================
 *
 *  Stored Procedures utilizados:
 *   - sp_ListarCompras          (filtros, paginación)
 *   - sp_GetCompraById          (obtener compra por ID con detalle)
 *   - sp_InsertCompra           (crear nueva compra con JSON)
 *   - sp_UpdateCompra           (actualizar campos no críticos)
 *   - sp_AnularCompra           (anular compra y revertir stock)
 *   - sp_GetComprasForSelect    (compras para combobox)
 *   - sp_GetDashboardCompras    (estadísticas para dashboard)
 *
 * ============================================================
 */

require_once __DIR__ . '/../config/database.php';

class Compra
{
    private \PDO $db;

    public function __construct()
    {
        $this->db = getDB();
    }

    // =========================================================================
    //  LISTAR compras con filtros y paginación
    //  SP: sp_ListarCompras(p_filtro, p_proveedor_id, p_estado, p_estado_pago,
    //                       p_fecha_desde, p_fecha_hasta, p_limit, p_offset)
    // =========================================================================
    public function listar(array $filters = []): array
    {
        try {
            $filtro         = $filters['filtro']         ?? null;
            $proveedor_id   = $filters['proveedor_id']   ?? null;
            $estado         = $filters['estado']         ?? null;
            $estado_pago    = $filters['estado_pago']    ?? null;
            $fecha_desde    = $filters['fecha_desde']    ?? null;
            $fecha_hasta    = $filters['fecha_hasta']    ?? null;
            $limit          = (int)($filters['limit']    ?? 20);
            $offset         = (int)($filters['offset']   ?? 0);

            $sql = "CALL sp_ListarCompras(
                        :filtro, :proveedor_id, :estado, :estado_pago,
                        :fecha_desde, :fecha_hasta, :limit, :offset
                    )";
            
            $stmt = $this->db->prepare($sql);
            $stmt->bindValue(':filtro',        $filtro ?: null,       PDO::PARAM_STR);
            $stmt->bindValue(':proveedor_id',  $proveedor_id ?: null, PDO::PARAM_INT);
            $stmt->bindValue(':estado',        $estado ?: null,       PDO::PARAM_STR);
            $stmt->bindValue(':estado_pago',   $estado_pago ?: null,  PDO::PARAM_STR);
            $stmt->bindValue(':fecha_desde',   $fecha_desde ?: null,  PDO::PARAM_STR);
            $stmt->bindValue(':fecha_hasta',   $fecha_hasta ?: null,  PDO::PARAM_STR);
            $stmt->bindValue(':limit',         $limit,                PDO::PARAM_INT);
            $stmt->bindValue(':offset',        $offset,               PDO::PARAM_INT);
            $stmt->execute();

            // Primer result-set → filas de compras
            $data = $stmt->fetchAll(PDO::FETCH_ASSOC);

            // Segundo result-set → total_registros
            $total = 0;
            if ($stmt->nextRowset()) {
                $row = $stmt->fetch(PDO::FETCH_ASSOC);
                $total = (int)($row['total_registros'] ?? 0);
            }

            $stmt->closeCursor();

            return [
                'success' => true,
                'data'    => $data,
                'total'   => $total,
            ];

        } catch (\PDOException $e) {
            error_log("Compra::listar — " . $e->getMessage());
            return ['success' => false, 'data' => [], 'total' => 0, 'mensaje' => 'Error al obtener las compras'];
        }
    }

    // =========================================================================
    //  OBTENER compra por ID (con detalle)
    //  SP: sp_GetCompraById(p_id_compra)
    // =========================================================================
    public function getById(int $id): array
    {
        try {
            $stmt = $this->db->prepare("CALL sp_GetCompraById(:id)");
            $stmt->bindParam(':id', $id, PDO::PARAM_INT);
            $stmt->execute();

            // Primer result-set → cabecera de la compra
            $cabecera = $stmt->fetch(PDO::FETCH_ASSOC);

            // Segundo result-set → detalle de productos
            $detalle = [];
            if ($stmt->nextRowset()) {
                $detalle = $stmt->fetchAll(PDO::FETCH_ASSOC);
            }

            $stmt->closeCursor();

            if ($cabecera) {
                return [
                    'success'  => true,
                    'cabecera' => $cabecera,
                    'detalle'  => $detalle
                ];
            }

            return ['success' => false, 'mensaje' => 'Compra no encontrada'];

        } catch (\PDOException $e) {
            error_log("Compra::getById — " . $e->getMessage());
            return ['success' => false, 'mensaje' => 'Error al obtener la compra'];
        }
    }

    // =========================================================================
    //  CREAR nueva compra
    //  SP: sp_InsertCompra(..., OUT p_id_compra, OUT p_mensaje)
    //  p_detalles: array de productos con formato JSON
    // =========================================================================
    public function crear(array $data): array
    {
        try {
            // Convertir detalles a JSON
            $detallesJson = json_encode($data['detalles']);

            $sql = "CALL sp_InsertCompra(
                        :codigo, :proveedor_id, :id_usuario, :fecha,
                        :tipo_comprobante, :serie, :numero,
                        :subtotal, :igv, :descuento, :total,
                        :forma_pago, :estado_pago, :estado, :observaciones,
                        :detalles, @p_id_compra, @p_mensaje
                    )";
            
            $stmt = $this->db->prepare($sql);
            $stmt->bindValue(':codigo',            $data['codigo'] ?? null,           PDO::PARAM_STR);
            $stmt->bindValue(':proveedor_id',      $data['proveedor_id'],             PDO::PARAM_INT);
            $stmt->bindValue(':id_usuario',        $data['id_usuario'],               PDO::PARAM_INT);
            $stmt->bindValue(':fecha',             $data['fecha'] ?? date('Y-m-d'),   PDO::PARAM_STR);
            $stmt->bindValue(':tipo_comprobante',  $data['tipo_comprobante'],         PDO::PARAM_STR);
            $stmt->bindValue(':serie',             $data['serie'] ?? null,             PDO::PARAM_STR);
            $stmt->bindValue(':numero',            $data['numero'] ?? null,            PDO::PARAM_STR);
            $stmt->bindValue(':subtotal',          $data['subtotal'],                  PDO::PARAM_STR);
            $stmt->bindValue(':igv',               $data['igv'],                       PDO::PARAM_STR);
            $stmt->bindValue(':descuento',         $data['descuento'] ?? 0,            PDO::PARAM_STR);
            $stmt->bindValue(':total',             $data['total'],                     PDO::PARAM_STR);
            $stmt->bindValue(':forma_pago',        $data['forma_pago'],                PDO::PARAM_STR);
            $stmt->bindValue(':estado_pago',       $data['estado_pago'] ?? 'pendiente',PDO::PARAM_STR);
            $stmt->bindValue(':estado',            $data['estado'] ?? 'completada',    PDO::PARAM_STR);
            $stmt->bindValue(':observaciones',     $data['observaciones'] ?? null,     PDO::PARAM_STR);
            $stmt->bindValue(':detalles',          $detallesJson,                      PDO::PARAM_STR);
            $stmt->execute();
            $stmt->closeCursor();

            // Obtener valores de salida
            $outStmt = $this->db->query("SELECT @p_id_compra AS id_compra, @p_mensaje AS mensaje");
            $out = $outStmt->fetch(PDO::FETCH_ASSOC);

            $idCompra = (int)($out['id_compra'] ?? 0);
            $mensaje  = $out['mensaje'] ?? 'Error desconocido';

            return [
                'success'    => $idCompra > 0,
                'id_compra'  => $idCompra,
                'mensaje'    => $mensaje,
            ];

        } catch (\PDOException $e) {
            error_log("Compra::crear — " . $e->getMessage());
            return ['success' => false, 'id_compra' => 0, 'mensaje' => $this->parsearMensajePDO($e)];
        }
    }

    // =========================================================================
    //  ACTUALIZAR compra (solo campos no críticos)
    //  SP: sp_UpdateCompra(..., OUT p_actualizado, OUT p_mensaje)
    // =========================================================================
    public function actualizar(array $data): array
    {
        try {
            $sql = "CALL sp_UpdateCompra(
                        :id_compra, :tipo_comprobante, :serie, :numero,
                        :forma_pago, :estado_pago, :observaciones,
                        @p_actualizado, @p_mensaje
                    )";
            
            $stmt = $this->db->prepare($sql);
            $stmt->bindValue(':id_compra',          $data['id_compra'],               PDO::PARAM_INT);
            $stmt->bindValue(':tipo_comprobante',   $data['tipo_comprobante'] ?? null, PDO::PARAM_STR);
            $stmt->bindValue(':serie',              $data['serie'] ?? null,            PDO::PARAM_STR);
            $stmt->bindValue(':numero',             $data['numero'] ?? null,           PDO::PARAM_STR);
            $stmt->bindValue(':forma_pago',         $data['forma_pago'] ?? null,       PDO::PARAM_STR);
            $stmt->bindValue(':estado_pago',        $data['estado_pago'] ?? null,      PDO::PARAM_STR);
            $stmt->bindValue(':observaciones',      $data['observaciones'] ?? null,    PDO::PARAM_STR);
            $stmt->execute();
            $stmt->closeCursor();

            $outStmt = $this->db->query("SELECT @p_actualizado AS actualizado, @p_mensaje AS mensaje");
            $out = $outStmt->fetch(PDO::FETCH_ASSOC);

            $actualizado = (bool)($out['actualizado'] ?? false);
            $mensaje     = $out['mensaje'] ?? 'Error desconocido';

            return [
                'success'    => $actualizado,
                'mensaje'    => $mensaje,
            ];

        } catch (\PDOException $e) {
            error_log("Compra::actualizar — " . $e->getMessage());
            return ['success' => false, 'mensaje' => $this->parsearMensajePDO($e)];
        }
    }

    // =========================================================================
    //  ANULAR compra (revierte stock)
    //  SP: sp_AnularCompra(p_id_compra, p_id_usuario, p_motivo, OUT p_anulado, OUT p_mensaje)
    // =========================================================================
    public function anular(int $idCompra, int $idUsuario, string $motivo = ''): array
    {
        try {
            $sql = "CALL sp_AnularCompra(:id_compra, :id_usuario, :motivo, @p_anulado, @p_mensaje)";
            $stmt = $this->db->prepare($sql);
            $stmt->bindValue(':id_compra',  $idCompra,   PDO::PARAM_INT);
            $stmt->bindValue(':id_usuario', $idUsuario,  PDO::PARAM_INT);
            $stmt->bindValue(':motivo',     $motivo,     PDO::PARAM_STR);
            $stmt->execute();
            $stmt->closeCursor();

            $outStmt = $this->db->query("SELECT @p_anulado AS anulado, @p_mensaje AS mensaje");
            $out = $outStmt->fetch(PDO::FETCH_ASSOC);

            $anulado = (bool)($out['anulado'] ?? false);
            $mensaje = $out['mensaje'] ?? 'Error desconocido';

            return [
                'success' => $anulado,
                'mensaje' => $mensaje,
            ];

        } catch (\PDOException $e) {
            error_log("Compra::anular — " . $e->getMessage());
            return ['success' => false, 'mensaje' => $this->parsearMensajePDO($e)];
        }
    }

    // =========================================================================
    //  OBTENER compras para combobox
    //  SP: sp_GetComprasForSelect(p_estado)
    // =========================================================================
    public function getForSelect(?string $estado = null): array
    {
        try {
            $sql = "CALL sp_GetComprasForSelect(:estado)";
            $stmt = $this->db->prepare($sql);
            $stmt->bindValue(':estado', $estado ?: null, PDO::PARAM_STR);
            $stmt->execute();
            $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
            $stmt->closeCursor();
            return $rows;

        } catch (\PDOException $e) {
            error_log("Compra::getForSelect — " . $e->getMessage());
            return [];
        }
    }

    // =========================================================================
    //  ESTADÍSTICAS para dashboard
    //  SP: sp_GetDashboardCompras(p_periodo)
    // =========================================================================
    public function getDashboardStats(string $periodo = 'mes'): array
    {
        try {
            $sql = "CALL sp_GetDashboardCompras(:periodo)";
            $stmt = $this->db->prepare($sql);
            $stmt->bindValue(':periodo', $periodo, PDO::PARAM_STR);
            $stmt->execute();

            // Primer result-set → estadísticas principales
            $estadisticas = $stmt->fetch(PDO::FETCH_ASSOC);

            // Segundo result-set → top proveedores
            $topProveedores = [];
            if ($stmt->nextRowset()) {
                $topProveedores = $stmt->fetchAll(PDO::FETCH_ASSOC);
            }

            // Tercer result-set → compras por mes
            $comprasPorMes = [];
            if ($stmt->nextRowset()) {
                $comprasPorMes = $stmt->fetchAll(PDO::FETCH_ASSOC);
            }

            $stmt->closeCursor();

            return [
                'success'         => true,
                'estadisticas'    => $estadisticas ?: [],
                'topProveedores'  => $topProveedores,
                'comprasPorMes'   => $comprasPorMes,
            ];

        } catch (\PDOException $e) {
            error_log("Compra::getDashboardStats — " . $e->getMessage());
            return [
                'success'         => false,
                'estadisticas'    => [],
                'topProveedores'  => [],
                'comprasPorMes'   => [],
                'mensaje'         => 'Error al obtener estadísticas'
            ];
        }
    }

    // =========================================================================
    //  HELPERS PRIVADOS
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