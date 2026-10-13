<?php
/**
 * Kardex.php — Modelo del módulo Kardex (inventario valorizado)
 *
 * El kardex se alimenta automáticamente mediante el trigger
 * `trg_actualizar_kardex_after_movimiento` (costo promedio ponderado móvil),
 * por lo tanto este modelo es de SOLO LECTURA: no inserta ni actualiza kardex.
 */

require_once __DIR__ . '/../config/database.php';

class Kardex {
    private $db;

    public function __construct() {
        $this->db = getDB();
    }

    // ─────────────────────────────────────────────────────────────────────
    // Resumen general: todos los productos con su saldo valorizado actual
    // (última fila de kardex por producto, vía ROW_NUMBER)
    // ─────────────────────────────────────────────────────────────────────
    public function getResumenGeneral(array $filtros = []): array {
        $sql = "SELECT
                    p.id_producto, p.codigo, p.sku, p.nombre,
                    p.stock_actual, p.stock_minimo, p.estado_stock,
                    c.nombre AS categoria_nombre,
                    m.nombre AS marca_nombre,
                    COALESCE(k.saldo_cantidad, p.stock_actual, 0)      AS saldo_cantidad,
                    COALESCE(k.saldo_costo_unitario, p.precio_compra, 0) AS saldo_costo_unitario,
                    COALESCE(k.saldo_costo_total, 0)                  AS saldo_costo_total,
                    k.fecha_movimiento AS ultimo_movimiento
                FROM productos p
                LEFT JOIN categorias c ON p.id_categoria = c.id_categoria
                LEFT JOIN marcas     m ON p.id_marca     = m.id_marca
                LEFT JOIN (
                    SELECT id_producto, saldo_cantidad, saldo_costo_unitario,
                           saldo_costo_total, fecha_movimiento
                    FROM (
                        SELECT k.*,
                               ROW_NUMBER() OVER (
                                   PARTITION BY id_producto
                                   ORDER BY fecha_movimiento DESC, id_kardex DESC
                               ) AS rn
                        FROM kardex k
                    ) ranked
                    WHERE rn = 1
                ) k ON k.id_producto = p.id_producto
                WHERE p.estado = 'activo'";

        $params = [];

        if (!empty($filtros['q'])) {
            $sql .= " AND (p.nombre LIKE ? OR p.codigo LIKE ? OR p.sku LIKE ?)";
            $like = '%' . $filtros['q'] . '%';
            $params[] = $like; $params[] = $like; $params[] = $like;
        }

        if (!empty($filtros['id_categoria'])) {
            $sql .= " AND p.id_categoria = ?";
            $params[] = (int) $filtros['id_categoria'];
        }

        if (!empty($filtros['sin_movimiento']) && $filtros['sin_movimiento'] === '1') {
            $sql .= " AND k.id_producto IS NULL";
        }

        $orden = $filtros['orden'] ?? 'valor_desc';
        $sql .= match ($orden) {
            'valor_asc'   => " ORDER BY saldo_costo_total ASC",
            'nombre'      => " ORDER BY p.nombre ASC",
            'stock_bajo'  => " ORDER BY (p.stock_actual - p.stock_minimo) ASC",
            default       => " ORDER BY saldo_costo_total DESC",
        };

        try {
            $stmt = $this->db->prepare($sql);
            $stmt->execute($params);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("Kardex::getResumenGeneral — " . $e->getMessage());
            return [];
        }
    }

    // ─────────────────────────────────────────────────────────────────────
    // KPIs agregados para el dashboard general del módulo
    // ─────────────────────────────────────────────────────────────────────
    public function getEstadisticasGenerales(): array {
        $sql = "SELECT
                    COUNT(DISTINCT p.id_producto) AS total_productos,
                    COUNT(DISTINCT k.id_producto) AS productos_con_movimiento,
                    COALESCE(SUM(k.saldo_costo_total), 0) AS valor_total_inventario,
                    COALESCE(SUM(k.saldo_cantidad), 0)    AS unidades_totales
                FROM productos p
                LEFT JOIN (
                    SELECT id_producto, saldo_cantidad, saldo_costo_total
                    FROM (
                        SELECT k.*,
                               ROW_NUMBER() OVER (
                                   PARTITION BY id_producto
                                   ORDER BY fecha_movimiento DESC, id_kardex DESC
                               ) AS rn
                        FROM kardex k
                    ) ranked
                    WHERE rn = 1
                ) k ON k.id_producto = p.id_producto
                WHERE p.estado = 'activo'";

        try {
            $stmt = $this->db->query($sql);
            $row = $stmt->fetch(PDO::FETCH_ASSOC);
            $row['productos_sin_movimiento'] = max(0, (int)$row['total_productos'] - (int)$row['productos_con_movimiento']);
            return $row;
        } catch (PDOException $e) {
            error_log("Kardex::getEstadisticasGenerales — " . $e->getMessage());
            return [
                'total_productos' => 0, 'productos_con_movimiento' => 0,
                'valor_total_inventario' => 0, 'unidades_totales' => 0,
                'productos_sin_movimiento' => 0,
            ];
        }
    }

    // ─────────────────────────────────────────────────────────────────────
    // Ficha de producto (con categoría/marca) — cabecera de la vista detalle
    // ─────────────────────────────────────────────────────────────────────
    public function getProductoConDetalle(int $idProducto): ?array {
        $sql = "SELECT p.*, c.nombre AS categoria_nombre, m.nombre AS marca_nombre
                FROM productos p
                LEFT JOIN categorias c ON p.id_categoria = c.id_categoria
                LEFT JOIN marcas     m ON p.id_marca     = m.id_marca
                WHERE p.id_producto = ?";
        try {
            $stmt = $this->db->prepare($sql);
            $stmt->execute([$idProducto]);
            $row = $stmt->fetch(PDO::FETCH_ASSOC);
            return $row ?: null;
        } catch (PDOException $e) {
            error_log("Kardex::getProductoConDetalle — " . $e->getMessage());
            return null;
        }
    }

    // ─────────────────────────────────────────────────────────────────────
    // Última fila de kardex de un producto (saldo real vigente, sin filtro
    // de fechas) — se usa para los KPIs "a hoy" en la cabecera del detalle
    // ─────────────────────────────────────────────────────────────────────
    public function getSaldoActual(int $idProducto): ?array {
        $sql = "SELECT * FROM kardex
                WHERE id_producto = ?
                ORDER BY fecha_movimiento DESC, id_kardex DESC
                LIMIT 1";
        try {
            $stmt = $this->db->prepare($sql);
            $stmt->execute([$idProducto]);
            $row = $stmt->fetch(PDO::FETCH_ASSOC);
            return $row ?: null;
        } catch (PDOException $e) {
            error_log("Kardex::getSaldoActual — " . $e->getMessage());
            return null;
        }
    }

    // ─────────────────────────────────────────────────────────────────────
    // Kardex detallado de un producto en un rango de fechas
    // (usa el SP sp_obtener_kardex_producto ya existente en la BD)
    // ─────────────────────────────────────────────────────────────────────
    public function getKardexProducto(int $idProducto, string $fechaInicio, string $fechaFin, ?int $idAlmacen = null): array {
        try {
            $stmt = $this->db->prepare("CALL sp_obtener_kardex_producto(?, ?, ?)");
            $stmt->execute([$idProducto, $fechaInicio, $fechaFin]);
            $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
            $stmt->closeCursor();

            if ($idAlmacen) {
                $almacen = $this->getAlmacenById($idAlmacen);
                if ($almacen) {
                    $rows = array_values(array_filter(
                        $rows,
                        fn($r) => ($r['almacen_nombre'] ?? null) === $almacen['nombre']
                    ));
                }
            }

            return $rows;
        } catch (PDOException $e) {
            error_log("Kardex::getKardexProducto — " . $e->getMessage());
            return [];
        }
    }

    private function getAlmacenById(int $id): ?array {
        try {
            $stmt = $this->db->prepare("SELECT id_almacen, nombre FROM almacenes WHERE id_almacen = ?");
            $stmt->execute([$id]);
            $row = $stmt->fetch(PDO::FETCH_ASSOC);
            return $row ?: null;
        } catch (PDOException $e) {
            return null;
        }
    }

    // ─────────────────────────────────────────────────────────────────────
    // Movimientos de kardex de TODOS los productos en un almacén dado
    // (no tiene columna "saldo" propia, porque el saldo del kardex es
    // global por producto, no por almacén)
    // ─────────────────────────────────────────────────────────────────────
    public function getKardexPorAlmacen(int $idAlmacen, string $fechaInicio, string $fechaFin): array {
        $sql = "SELECT k.id_kardex, k.fecha_movimiento, k.tipo_movimiento,
                       k.entrada_cantidad, k.entrada_costo_unitario, k.entrada_costo_total,
                       k.salida_cantidad, k.salida_costo_unitario, k.salida_costo_total,
                       k.documento_referencia,
                       p.id_producto, p.nombre AS producto_nombre, p.codigo AS producto_codigo,
                       mi.documento_tipo, mi.documento_numero, mi.observaciones,
                       u.nombre_completo AS usuario_nombre
                FROM kardex k
                INNER JOIN movimientos_inventario mi ON k.id_movimiento = mi.id_movimiento
                INNER JOIN productos p ON k.id_producto = p.id_producto
                LEFT JOIN usuarios u ON mi.usuario_registro = u.id_usuario
                WHERE mi.id_almacen = ?
                    AND DATE(k.fecha_movimiento) BETWEEN ? AND ?
                ORDER BY k.fecha_movimiento DESC, k.id_kardex DESC";
        try {
            $stmt = $this->db->prepare($sql);
            $stmt->execute([$idAlmacen, $fechaInicio, $fechaFin]);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("Kardex::getKardexPorAlmacen — " . $e->getMessage());
            return [];
        }
    }

    // ─────────────────────────────────────────────────────────────────────
    // Autocomplete de productos (buscar_productos)
    // ─────────────────────────────────────────────────────────────────────
    public function buscarProductos(string $q, int $limit = 12): array {
        $sql = "SELECT id_producto, codigo, sku, nombre, stock_actual
                FROM productos
                WHERE estado = 'activo'
                    AND (nombre LIKE ? OR codigo LIKE ? OR sku LIKE ?)
                ORDER BY nombre ASC
                LIMIT ?";
        try {
            $like = '%' . $q . '%';
            $stmt = $this->db->prepare($sql);
            $stmt->bindValue(1, $like, PDO::PARAM_STR);
            $stmt->bindValue(2, $like, PDO::PARAM_STR);
            $stmt->bindValue(3, $like, PDO::PARAM_STR);
            $stmt->bindValue(4, $limit, PDO::PARAM_INT);
            $stmt->execute();
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("Kardex::buscarProductos — " . $e->getMessage());
            return [];
        }
    }
}