<?php
/**
 * ============================================================
 *  MODELO: Ventas.php
 *  Sistema: RopaVirtual – Módulo de Ventas
 *  Arquitectura: MVC | Capa: Model
 *  Base de datos: ropavirtual_db
 * ============================================================
 */

require_once __DIR__ . '/../config/database.php';

class Ventas
{
    private \PDO $db;

    // Propiedades de la tabla ventas
    private int $id_venta = 0;
    private string $codigo = '';
    private ?int $id_cliente = null;
    private int $id_usuario = 0;
    private string $fecha_venta = '';
    private string $tipo_comprobante = 'boleta';
    private ?string $serie = null;
    private ?string $numero_comprobante = null;
    private float $subtotal = 0.00;
    private float $igv = 0.00;
    private float $descuento = 0.00;
    private float $total = 0.00;
    private string $forma_pago = 'efectivo';
    private string $estado_pago = 'pagado';
    private string $estado = 'completada';
    private ?string $observaciones = null;

    public function __construct()
    {
        $this->db = getDB();
    }

    public function getDB(): \PDO
    {
        return $this->db;
    }

    // ==================== GETTERS Y SETTERS ====================
    public function getIdVenta(): int { return $this->id_venta; }
    public function getCodigo(): string { return $this->codigo; }
    public function getIdCliente(): ?int { return $this->id_cliente; }
    public function getIdUsuario(): int { return $this->id_usuario; }
    public function getFechaVenta(): string { return $this->fecha_venta; }
    public function getTipoComprobante(): string { return $this->tipo_comprobante; }
    public function getSerie(): ?string { return $this->serie; }
    public function getNumeroComprobante(): ?string { return $this->numero_comprobante; }
    public function getSubtotal(): float { return $this->subtotal; }
    public function getIgv(): float { return $this->igv; }
    public function getDescuento(): float { return $this->descuento; }
    public function getTotal(): float { return $this->total; }
    public function getFormaPago(): string { return $this->forma_pago; }
    public function getEstadoPago(): string { return $this->estado_pago; }
    public function getEstado(): string { return $this->estado; }
    public function getObservaciones(): ?string { return $this->observaciones; }

    public function setIdVenta(int $v): void { $this->id_venta = $v; }
    public function setCodigo(string $v): void { $this->codigo = trim($v); }
    public function setIdCliente(?int $v): void { $this->id_cliente = $v; }
    public function setIdUsuario(int $v): void { $this->id_usuario = $v; }
    public function setFechaVenta(string $v): void { $this->fecha_venta = $v; }
    public function setTipoComprobante(string $v): void { $this->tipo_comprobante = $v; }
    public function setSerie(?string $v): void { $this->serie = $v; }
    public function setNumeroComprobante(?string $v): void { $this->numero_comprobante = $v; }
    public function setSubtotal(float $v): void { $this->subtotal = $v; }
    public function setIgv(float $v): void { $this->igv = $v; }
    public function setDescuento(float $v): void { $this->descuento = $v; }
    public function setTotal(float $v): void { $this->total = $v; }
    public function setFormaPago(string $v): void { $this->forma_pago = $v; }
    public function setEstadoPago(string $v): void { $this->estado_pago = $v; }
    public function setEstado(string $v): void { $this->estado = $v; }
    public function setObservaciones(?string $v): void { $this->observaciones = $v; }

    // ==================== MÉTODOS CORREGIDOS ====================

    /**
     * Buscar productos para venta por código, nombre o SKU
     * ✅ VERSIÓN CORREGIDA - SIN ERRORES DE PARÁMETROS
     */
    public function buscarProductosVenta(string $termino): array
    {
        try {
            $termino = trim($termino);
            
            if (strlen($termino) < 2) {
                return [];
            }

            // ✅ Usar CONCAT para evitar problemas con parámetros repetidos
            $sql = "SELECT 
                        p.id_producto as id,
                        p.codigo,
                        p.sku,
                        p.nombre,
                        p.descripcion,
                        p.stock_actual as stock,
                        p.precio_venta,
                        p.unidad_medida,
                        p.stock_maximo
                    FROM productos p
                    WHERE p.estado = 'activo'
                    AND p.stock_actual > 0
                    AND p.precio_venta > 0
                    AND (
                        p.codigo LIKE ? 
                        OR p.sku LIKE ? 
                        OR p.nombre LIKE ? 
                        OR p.descripcion LIKE ?
                    )
                    ORDER BY p.nombre ASC
                    LIMIT 20";
            
            $terminoBusqueda = "%{$termino}%";
            
            // ✅ Usar bindParam con índices en lugar de named parameters
            $stmt = $this->db->prepare($sql);
            $stmt->bindValue(1, $terminoBusqueda, PDO::PARAM_STR);
            $stmt->bindValue(2, $terminoBusqueda, PDO::PARAM_STR);
            $stmt->bindValue(3, $terminoBusqueda, PDO::PARAM_STR);
            $stmt->bindValue(4, $terminoBusqueda, PDO::PARAM_STR);
            $stmt->execute();
            
            $resultados = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            $productos = [];
            foreach ($resultados as $row) {
                $productos[] = [
                    'id' => (int)$row['id'],
                    'codigo' => $row['codigo'] ?? '',
                    'sku' => $row['sku'] ?? '',
                    'nombre' => $row['nombre'] ?? 'Producto sin nombre',
                    'descripcion' => $row['descripcion'] ?? '',
                    'stock' => (int)($row['stock'] ?? 0),
                    'precio_venta' => (float)($row['precio_venta'] ?? 0),
                    'unidad_medida' => $row['unidad_medida'] ?? 'unidad',
                    'stock_maximo' => (int)($row['stock_maximo'] ?? 0),
                ];
            }
            
            return $productos;
            
        } catch (PDOException $e) {
            error_log("Error en buscarProductosVenta: " . $e->getMessage());
            return [];
        }
    }

/**
 * ══════════════════════════════════════════════════════════════
 *  FIX: Ventas.php — getProductosPaginados() + getTotalProductosActivos()
 *
 *  BUG RAÍZ: PDO con emulate_prepares=false no permite reusar
 *  el mismo named placeholder (:search) más de una vez en la misma
 *  consulta. Las columnas 2ª y 3ª del LIKE recibían NULL → 0 filas.
 *
 *  FIX: usar placeholders distintos (:s1, :s2, :s3) o cambiar a
 *  posicionales (?). Se usa la variante posicional por compatibilidad.
 *
 *  SECUNDARIO: getTotalProductosActivos no filtraba stock_actual > 0
 *  (asimetría con getProductosPaginados → paginación incorrecta).
 * ══════════════════════════════════════════════════════════════
 */

/**
 * Reemplaza el método completo en Ventas.php
 */
public function getProductosPaginados(string $search = '', int $limit = 12, int $offset = 0): array
{
    try {
        $searchTerm = trim($search);
        $params     = [];

        $sql = "SELECT
                    p.id_producto AS id,
                    p.codigo,
                    p.sku,
                    p.nombre,
                    p.stock_actual   AS stock,
                    p.stock_minimo,
                    p.precio_venta,
                    p.unidad_medida,
                    m.nombre         AS marca
                FROM productos p
                LEFT JOIN marcas m ON p.id_marca = m.id_marca
                WHERE p.estado = 'activo'
                  AND p.stock_actual > 0";

        // ── BUG FIX: usar placeholders distintos para cada columna ──
        if ($searchTerm !== '') {
            $sql .= " AND (
                         p.codigo LIKE :s1
                      OR p.sku    LIKE :s2
                      OR p.nombre LIKE :s3
                     )";
            $like          = "%{$searchTerm}%";
            $params[':s1'] = $like;
            $params[':s2'] = $like;
            $params[':s3'] = $like;
        }

        $sql .= " ORDER BY p.nombre ASC LIMIT :limit OFFSET :offset";

        $stmt = $this->db->prepare($sql);

        foreach ($params as $key => $val) {
            $stmt->bindValue($key, $val, PDO::PARAM_STR);
        }
        $stmt->bindValue(':limit',  $limit,  PDO::PARAM_INT);
        $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
        $stmt->execute();

        return $stmt->fetchAll(PDO::FETCH_ASSOC);

    } catch (PDOException $e) {
        error_log("Ventas::getProductosPaginados — " . $e->getMessage());
        return [];
    }
}

/**
 * Reemplaza el método completo en Ventas.php
 */
public function getTotalProductosActivos(string $search = ''): int
{
    try {
        $searchTerm = trim($search);
        $params     = [];

        // ── Mismo WHERE que getProductosPaginados ──────────────────
        $sql = "SELECT COUNT(*) AS total
                FROM productos p
                WHERE p.estado = 'activo'
                  AND p.stock_actual > 0";   // ← faltaba en versión original

        if ($searchTerm !== '') {
            $sql .= " AND (
                         p.codigo LIKE :s1
                      OR p.sku    LIKE :s2
                      OR p.nombre LIKE :s3
                     )";
            $like          = "%{$searchTerm}%";
            $params[':s1'] = $like;
            $params[':s2'] = $like;
            $params[':s3'] = $like;
        }

        $stmt = $this->db->prepare($sql);
        foreach ($params as $key => $val) {
            $stmt->bindValue($key, $val, PDO::PARAM_STR);
        }
        $stmt->execute();

        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        return (int)($result['total'] ?? 0);

    } catch (PDOException $e) {
        error_log("Ventas::getTotalProductosActivos — " . $e->getMessage());
        return 0;
    }
}

    /**
     * Obtener producto por ID
     */
    public function getProductoById(int $id): ?array
    {
        try {
            $sql = "SELECT 
                        p.id_producto,
                        p.codigo,
                        p.nombre,
                        p.stock_actual,
                        p.precio_venta,
                        p.precio_mayorista,
                        p.talla,
                        c.nombre AS categoria_nombre
                    FROM productos p
                    LEFT JOIN categorias c ON p.id_categoria = c.id_categoria
                    WHERE p.id_producto = :id AND p.estado = 'activo'";
            
            $stmt = $this->db->prepare($sql);
            $stmt->execute([':id' => $id]);
            
            return $stmt->fetch(PDO::FETCH_ASSOC) ?: null;
            
        } catch (PDOException $e) {
            error_log("Ventas::getProductoById — " . $e->getMessage());
            return null;
        }
    }

    /**
     * Obtener el siguiente número correlativo para un tipo de comprobante
     */
    /*public function getSiguienteNumeroComprobante(string $tipo, string $serie): string
    {
        try {
            // Buscar el último número usado
            $sql = "SELECT MAX(CAST(numero_comprobante AS UNSIGNED)) as ultimo_numero
                    FROM ventas 
                    WHERE tipo_comprobante = :tipo 
                    AND serie = :serie
                    AND numero_comprobante IS NOT NULL
                    AND numero_comprobante REGEXP '^[0-9]+$'";
            
            $stmt = $this->db->prepare($sql);
            $stmt->execute([
                ':tipo' => $tipo,
                ':serie' => $serie
            ]);
            
            $resultado = $stmt->fetch(PDO::FETCH_ASSOC);
            $ultimoNumero = (int)($resultado['ultimo_numero'] ?? 0);
            $siguiente = $ultimoNumero + 1;
            
            return str_pad($siguiente, 8, '0', STR_PAD_LEFT);
            
        } catch (PDOException $e) {
            error_log("Error en getSiguienteNumeroComprobante: " . $e->getMessage());
            return date('Ymd') . str_pad(mt_rand(1, 999), 3, '0', STR_PAD_LEFT);
        }
    }*/

        public function getSiguienteNumeroComprobante(string $tipo, string $serie): string
{
    try {
        $sql = "SELECT MAX(CAST(numero_comprobante AS UNSIGNED)) as ultimo_numero
                FROM ventas 
                WHERE tipo_comprobante = :tipo 
                AND serie = :serie
                AND numero_comprobante IS NOT NULL
                AND numero_comprobante REGEXP '^[0-9]+$'";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute([
            ':tipo' => $tipo,
            ':serie' => $serie
        ]);
        
        $resultado = $stmt->fetch(PDO::FETCH_ASSOC);
        
        // Si la tabla está vacía o no hay resultados
        $ultimoNumero = ($resultado && isset($resultado['ultimo_numero'])) 
            ? (int)$resultado['ultimo_numero'] 
            : 0;
            
        $siguiente = $ultimoNumero + 1;
        
        // Asegurar mínimo 1
        if ($siguiente < 1) $siguiente = 1;
        
        return str_pad($siguiente, 8, '0', STR_PAD_LEFT);
        
    } catch (PDOException $e) {
        error_log("Error en getSiguienteNumeroComprobante: " . $e->getMessage());
        // En caso de error, devolver número por defecto
        return '00000001';
    }
}

    /**
     * Registrar nueva venta mediante sp_InsertVenta
     */
    public function registrarVenta(array $data, array $detalles): array
    {
        try {
            $detallesJson = json_encode($detalles, JSON_UNESCAPED_UNICODE);
            
            $sql = "CALL sp_InsertVenta(
                        :id_cliente, :id_usuario, :tipo_comprobante, :serie, :numero_comprobante,
                        :descuento, :forma_pago, :estado_pago, :observaciones, :detalles,
                        @p_id_venta, @p_codigo_venta, @p_mensaje
                    )";
            
            $stmt = $this->db->prepare($sql);
            $stmt->bindValue(':id_cliente', $data['id_cliente'] ?? null, PDO::PARAM_INT);
            $stmt->bindValue(':id_usuario', $data['id_usuario'], PDO::PARAM_INT);
            $stmt->bindValue(':tipo_comprobante', $data['tipo_comprobante'] ?? 'boleta', PDO::PARAM_STR);
            $stmt->bindValue(':serie', $data['serie'] ?? null, PDO::PARAM_STR);
            $stmt->bindValue(':numero_comprobante', $data['numero_comprobante'] ?? null, PDO::PARAM_STR);
            $stmt->bindValue(':descuento', $data['descuento'] ?? 0, PDO::PARAM_STR);
            $stmt->bindValue(':forma_pago', $data['forma_pago'] ?? 'efectivo', PDO::PARAM_STR);
            $stmt->bindValue(':estado_pago', $data['estado_pago'] ?? 'pagado', PDO::PARAM_STR);
            $stmt->bindValue(':observaciones', $data['observaciones'] ?? null, PDO::PARAM_STR);
            $stmt->bindValue(':detalles', $detallesJson, PDO::PARAM_STR);
            $stmt->execute();
            $stmt->closeCursor();
            
            $result = $this->db->query("SELECT @p_id_venta AS id_venta, @p_codigo_venta AS codigo_venta, @p_mensaje AS mensaje")->fetch(PDO::FETCH_ASSOC);
            
            $idVenta = (int)($result['id_venta'] ?? 0);
            $mensaje = $result['mensaje'] ?? '';
            $codigoVenta = $result['codigo_venta'] ?? '';
            
            if ($idVenta > 0) {
                return [
                    'success' => true,
                    'id_venta' => $idVenta,
                    'codigo_venta' => $codigoVenta,
                    'mensaje' => $mensaje ?: 'Venta registrada exitosamente'
                ];
            } else {
                return [
                    'success' => false,
                    'id_venta' => 0,
                    'codigo_venta' => '',
                    'mensaje' => $mensaje ?: 'Error al registrar la venta'
                ];
            }
            
        } catch (PDOException $e) {
            error_log("Error en registrarVenta: " . $e->getMessage());
            
            $mensaje = $e->getMessage();
            if (preg_match('/1644\s+(.+)$/i', $mensaje, $matches)) {
                $mensaje = trim($matches[1]);
            }
            
            return [
                'success' => false,
                'id_venta' => 0,
                'codigo_venta' => '',
                'mensaje' => $mensaje
            ];
        }
    }

    /**
     * Obtener una venta por ID con su detalle
     */
    public function getById(int $id): array
    {
        try {
            $sqlCabecera = "SELECT 
                                v.id_venta,
                                v.codigo,
                                v.fecha_venta,
                                v.tipo_comprobante,
                                v.serie,
                                v.numero_comprobante,
                                CONCAT(IFNULL(v.serie,''), '-', LPAD(IFNULL(v.numero_comprobante,''), 8, '0')) AS comprobante,
                                v.subtotal,
                                v.igv,
                                v.descuento,
                                v.total,
                                v.forma_pago,
                                v.estado_pago,
                                v.estado,
                                v.observaciones,
                                IFNULL(c.nombre_completo, 'Cliente General') AS cliente_nombre,
                                IFNULL(c.numero_documento, 'N/A') AS cliente_documento,
                                c.tipo_cliente,
                                u.nombre_completo AS vendedor_nombre
                            FROM ventas v
                            LEFT JOIN clientes c ON v.id_cliente = c.id_cliente
                            INNER JOIN usuarios u ON v.id_usuario = u.id_usuario
                            WHERE v.id_venta = :id";
            
            $stmt = $this->db->prepare($sqlCabecera);
            $stmt->execute([':id' => $id]);
            $cabecera = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$cabecera) {
                return ['success' => false, 'cabecera' => null, 'detalle' => []];
            }

            $sqlDetalle = "SELECT 
                                dv.id_detalle,
                                dv.id_producto,
                                p.codigo,
                                p.nombre,
                                p.talla,
                                p.color,
                                dv.cantidad,
                                dv.precio_unitario as precio,
                                dv.descuento,
                                (dv.cantidad * dv.precio_unitario) AS subtotal
                            FROM detalle_ventas dv
                            INNER JOIN productos p ON dv.id_producto = p.id_producto
                            WHERE dv.id_venta = :id";
            
            $stmt = $this->db->prepare($sqlDetalle);
            $stmt->execute([':id' => $id]);
            $detalle = $stmt->fetchAll(PDO::FETCH_ASSOC);

            return ['success' => true, 'cabecera' => $cabecera, 'detalle' => $detalle];

        } catch (PDOException $e) {
            error_log("Ventas::getById — " . $e->getMessage());
            return ['success' => false, 'cabecera' => null, 'detalle' => []];
        }
    }

    /**
     * Obtener lista de clientes para select
     */
    public function getForSelect(): array
    {
        try {
            return $this->db
                ->query("SELECT id_cliente, nombre_completo, numero_documento
                         FROM clientes
                         WHERE estado = 'activo'
                         ORDER BY nombre_completo ASC")
                ->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("Ventas::getForSelect — " . $e->getMessage());
            return [];
        }
    }

    /**
     * Listar ventas con filtros para la tabla
     */
    public function listarVentas(array $filters = []): array
    {
        try {
            $fechaDesde = $filters['fecha_desde'] ?? date('Y-m-01');
            $fechaHasta = $filters['fecha_hasta'] ?? date('Y-m-d');
            $estado = $filters['estado'] ?? null;
            $tipoPago = $filters['tipo_pago'] ?? null;
            $busqueda = $filters['busqueda'] ?? null;
            $limit = isset($filters['limit']) ? (int)$filters['limit'] : 20;
            $offset = isset($filters['offset']) ? (int)$filters['offset'] : 0;

            $where = [];
            $params = [];

            $where[] = "DATE(v.fecha_venta) BETWEEN :fecha_desde AND :fecha_hasta";
            $params[':fecha_desde'] = $fechaDesde;
            $params[':fecha_hasta'] = $fechaHasta;

            if ($estado && $estado !== '') {
                $where[] = "v.estado = :estado";
                $params[':estado'] = $estado;
            }

            if ($tipoPago && $tipoPago !== '') {
                $where[] = "v.forma_pago = :tipo_pago";
                $params[':tipo_pago'] = $tipoPago;
            }

            if ($busqueda && $busqueda !== '') {
                $where[] = "(v.codigo LIKE :busqueda OR c.nombre_completo LIKE :busqueda2 OR c.numero_documento LIKE :busqueda3 OR v.numero_comprobante LIKE :busqueda4)";
                $params[':busqueda'] = "%{$busqueda}%";
                $params[':busqueda2'] = "%{$busqueda}%";
                $params[':busqueda3'] = "%{$busqueda}%";
                $params[':busqueda4'] = "%{$busqueda}%";
            }

            $whereSQL = $where ? "WHERE " . implode(" AND ", $where) : "";

            $sqlCount = "SELECT COUNT(*) AS total
                         FROM ventas v
                         LEFT JOIN clientes c ON v.id_cliente = c.id_cliente
                         {$whereSQL}";
            $stmtCount = $this->db->prepare($sqlCount);
            foreach ($params as $key => $val) {
                $stmtCount->bindValue($key, $val);
            }
            $stmtCount->execute();
            $total = (int)$stmtCount->fetchColumn();

            $sql = "SELECT 
                        v.id_venta,
                        v.codigo,
                        v.fecha_venta,
                        v.tipo_comprobante,
                        v.serie,
                        v.numero_comprobante,
                        CONCAT(IFNULL(v.serie,''), '-', LPAD(IFNULL(v.numero_comprobante,''), 8, '0')) AS numero_comprobante,
                        v.subtotal,
                        v.igv,
                        v.descuento,
                        v.total,
                        v.forma_pago,
                        v.estado_pago,
                        v.estado,
                        IFNULL(c.nombre_completo, 'Cliente General') AS cliente_nombre,
                        IFNULL(c.numero_documento, 'N/A') AS cliente_documento,
                        u.nombre_completo AS usuario_nombre
                    FROM ventas v
                    LEFT JOIN clientes c ON v.id_cliente = c.id_cliente
                    INNER JOIN usuarios u ON v.id_usuario = u.id_usuario
                    {$whereSQL}
                    ORDER BY v.fecha_venta DESC, v.id_venta DESC
                    LIMIT :limit OFFSET :offset";

            $stmt = $this->db->prepare($sql);
            foreach ($params as $key => $val) {
                $stmt->bindValue($key, $val);
            }
            $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
            $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
            $stmt->execute();

            return [
                'success' => true,
                'data' => $stmt->fetchAll(PDO::FETCH_ASSOC),
                'total' => $total
            ];

        } catch (PDOException $e) {
            error_log("Ventas::listarVentas — " . $e->getMessage());
            return ['success' => false, 'data' => [], 'total' => 0, 'error' => $e->getMessage()];
        }
    }

    /**
     * Anular una venta
     */
    public function anularVenta(int $idVenta, int $idUsuario, string $motivo): array
    {
        try {
            $this->db->beginTransaction();

            $stmt = $this->db->prepare("SELECT estado, id_venta FROM ventas WHERE id_venta = :id_venta FOR UPDATE");
            $stmt->execute([':id_venta' => $idVenta]);
            $venta = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$venta) {
                throw new Exception('Venta no encontrada');
            }

            if ($venta['estado'] === 'anulada') {
                throw new Exception('La venta ya está anulada');
            }

            $stmt = $this->db->prepare("SELECT id_producto, cantidad FROM detalle_ventas WHERE id_venta = :id_venta");
            $stmt->execute([':id_venta' => $idVenta]);
            $detalles = $stmt->fetchAll(PDO::FETCH_ASSOC);

            foreach ($detalles as $detalle) {
                $stmtUpdate = $this->db->prepare("
                    UPDATE productos 
                    SET stock_actual = stock_actual + :cantidad
                    WHERE id_producto = :id_producto
                ");
                $stmtUpdate->execute([
                    ':cantidad' => $detalle['cantidad'],
                    ':id_producto' => $detalle['id_producto']
                ]);
            }

            $stmt = $this->db->prepare("
                UPDATE ventas 
                SET estado = 'anulada', 
                    estado_pago = 'anulado'
                WHERE id_venta = :id_venta
            ");
            $stmt->execute([':id_venta' => $idVenta]);

            $this->db->commit();
            return ['success' => true, 'mensaje' => 'Venta anulada exitosamente'];

        } catch (Exception $e) {
            $this->db->rollBack();
            error_log("Ventas::anularVenta — " . $e->getMessage());
            return ['success' => false, 'mensaje' => $e->getMessage()];
        }
    }

    /**
     * Obtener estadísticas del dashboard
     */
    public function getEstadisticasDashboard(array $filters = []): array
    {
        try {
            $fechaDesde = $filters['fecha_desde'] ?? date('Y-m-01');
            $fechaHasta = $filters['fecha_hasta'] ?? date('Y-m-d');

            $sql = "SELECT 
                        COUNT(*) AS total_ventas,
                        COALESCE(SUM(v.total), 0) AS ingresos_totales
                    FROM ventas v
                    WHERE DATE(v.fecha_venta) BETWEEN :fecha_desde AND :fecha_hasta
                    AND v.estado = 'completada'";
            
            $stmt = $this->db->prepare($sql);
            $stmt->execute([':fecha_desde' => $fechaDesde, ':fecha_hasta' => $fechaHasta]);
            $resultado = $stmt->fetch(PDO::FETCH_ASSOC);

            return [
                'total_ventas' => (int)($resultado['total_ventas'] ?? 0),
                'ingresos_totales' => (float)($resultado['ingresos_totales'] ?? 0),
                'top_productos' => []
            ];

        } catch (PDOException $e) {
            error_log("Ventas::getEstadisticasDashboard — " . $e->getMessage());
            return ['total_ventas' => 0, 'ingresos_totales' => 0, 'top_productos' => []];
        }
    }

    /**
     * Obtener métodos de pago disponibles
     */
    public function getMetodosPago(): array
    {
        return [
            ['valor' => 'efectivo', 'label' => 'Efectivo'],
            ['valor' => 'tarjeta', 'label' => 'Tarjeta'],
            ['valor' => 'transferencia', 'label' => 'Transferencia'],
            ['valor' => 'credito', 'label' => 'Crédito'],
            ['valor' => 'yape', 'label' => 'Yape'],
            ['valor' => 'plin', 'label' => 'Plin'],
        ];
    }

    /**
     * Obtener tipos de comprobante disponibles
     */
    public function getTiposComprobante(): array
    {
        return [
            ['valor' => 'boleta', 'label' => 'Boleta'],
            ['valor' => 'factura', 'label' => 'Factura'],
            ['valor' => 'ticket', 'label' => 'Ticket'],
            ['valor' => 'nota_venta', 'label' => 'Nota de Venta'],
        ];
    }

    /**
     * Obtener datos para exportar CSV
     */
    public function getDataForExport(array $filters = []): array
    {
        try {
            $fechaDesde = $filters['fecha_desde'] ?? date('Y-m-01');
            $fechaHasta = $filters['fecha_hasta'] ?? date('Y-m-d');

            $sql = "SELECT 
                        v.id_venta,
                        v.codigo,
                        DATE_FORMAT(v.fecha_venta, '%d/%m/%Y %H:%i') AS fecha_venta,
                        CONCAT(UPPER(v.tipo_comprobante), ' ', IFNULL(v.serie,''), '-', LPAD(IFNULL(v.numero_comprobante,''), 8, '0')) AS comprobante,
                        IFNULL(c.nombre_completo, 'Cliente General') AS cliente,
                        IFNULL(c.numero_documento, 'N/A') AS documento,
                        u.nombre_completo AS vendedor,
                        v.subtotal,
                        v.igv,
                        v.descuento,
                        v.total,
                        UPPER(v.forma_pago) AS forma_pago,
                        UPPER(v.estado_pago) AS estado_pago,
                        UPPER(v.estado) AS estado
                    FROM ventas v
                    LEFT JOIN clientes c ON v.id_cliente = c.id_cliente
                    INNER JOIN usuarios u ON v.id_usuario = u.id_usuario
                    WHERE DATE(v.fecha_venta) BETWEEN :fecha_desde AND :fecha_hasta
                    ORDER BY v.fecha_venta DESC";
            
            $stmt = $this->db->prepare($sql);
            $stmt->execute([':fecha_desde' => $fechaDesde, ':fecha_hasta' => $fechaHasta]);
            
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
            
        } catch (PDOException $e) {
            error_log("Ventas::getDataForExport — " . $e->getMessage());
            return [];
        }
    }
}